#!/usr/bin/env bash

# Hermes AI Platform - Rich Status Dashboard
# Targets: Ubuntu 24.04 LTS (x86_64)

set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
INSTALLER_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

# Source libraries
source "${INSTALLER_DIR}/lib/logging.sh"
source "${INSTALLER_DIR}/lib/backup.sh"

LOG_FILE="logs/health.log"

# System Metrics
get_cpu_usage() {
    top -bn1 | grep "Cpu(s)" | awk '{print $2 + $4}' || echo "0"
}

get_ram_usage() {
    free -m | awk 'NR==2{printf "%.1f", $3*100/$2}' || echo "0"
}

get_disk_usage() {
    df -h / | awk 'NR==2 {print $5}' | sed 's/%//' || echo "0"
}

get_uptime() {
    uptime -p | sed 's/up //' || echo "unknown"
}

# Service Probes
check_container_state() {
    local service=$1
    local cid
    cid=$(docker compose ps -q "$service" 2>/dev/null | head -n1 || true)
    if [ -n "$cid" ]; then
        docker inspect -f '{{.State.Status}}' "$cid" 2>/dev/null || echo "offline"
    else
        echo "offline"
    fi
}

check_container_health() {
    local service=$1
    local cid
    cid=$(docker compose ps -q "$service" 2>/dev/null | head -n1 || true)
    if [ -n "$cid" ]; then
        docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}none{{end}}' "$cid" 2>/dev/null || echo "none"
    else
        echo "offline"
    fi
}

print_metric() {
    local label=$1
    local value=$2
    local threshold_warn=$3
    local threshold_err=$4
    local suffix=$5
    
    local val_num
    val_num=$(echo "$value" | grep -oE '^[0-9]+(\.[0-9]+)?' || echo "0")
    
    # Simple float comparison using awk
    local status
    status=$(awk -v val="$val_num" -v warn="$threshold_warn" -v err="$threshold_err" '
        BEGIN {
            if (val >= err) print "err";
            else if (val >= warn) print "warn";
            else print "ok";
        }
    ')

    if [ "$status" = "err" ]; then
        echo -e "  $label: ${RED}${value}${suffix}${NC}"
    elif [ "$status" = "warn" ]; then
        echo -e "  $label: ${YELLOW}${value}${suffix}${NC}"
    else
        echo -e "  $label: ${GREEN}${value}${suffix}${NC}"
    fi
}

render_dashboard() {
    echo -e "${BLUE}====================================================${NC}"
    echo -e "${BLUE}          Hermes Platform Status Dashboard          ${NC}"
    echo -e "${BLUE}====================================================${NC}"
    
    local uptime_val=$(get_uptime)
    echo -e "Platform Uptime:   ${GREEN}${uptime_val}${NC}"
    echo -e "Docker Version:    ${GREEN}$(docker version --format '{{.Server.Version}}' 2>/dev/null || echo "Unknown")${NC}"
    
    echo -e "\n${BLUE}Host Resources:${NC}"
    print_metric "CPU Usage" "$(get_cpu_usage)" "70" "90" "%"
    print_metric "RAM Usage" "$(get_ram_usage)" "80" "95" "%"
    print_metric "Disk Usage" "$(get_disk_usage)" "80" "95" "%"
    
    echo -e "\n${BLUE}Running Services & Container Health:${NC}"
    local services=(db redis qdrant minio n8n app web queue scheduler)
    local healthy_count=0
    local total_score=100
    
    for s in "${services[@]}"; do
        local state=$(check_container_state "$s")
        local health=$(check_container_health "$s")
        
        if [ "$state" = "running" ]; then
            if [ "$health" = "healthy" ] || [ "$health" = "none" ]; then
                echo -e "  - Service '${s}': ${GREEN}✓ Running${NC} (Health: ${GREEN}${health}${NC})"
                healthy_count=$((healthy_count+1))
            else
                echo -e "  - Service '${s}': ${YELLOW}! Running${NC} (Health: ${RED}${health}${NC})"
                total_score=$((total_score-8))
            fi
        else
            echo -e "  - Service '${s}': ${RED}✗ Stopped${NC}"
            total_score=$((total_score-12))
        fi
    done
    
    # Internal Probes if environment exists
    echo -e "\n${BLUE}Core Integration Probes:${NC}"
    if [ -f .env ]; then
        local db_user=$(grep "^DB_USERNAME=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "hermes")
        local db_name=$(grep "^DB_DATABASE=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "hermes")
        local db_pass=$(grep "^DB_PASSWORD=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "")
        local redis_pass=$(grep "^REDIS_PASSWORD=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "")
        local openai_key=$(grep "^OPENAI_API_KEY=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "")
        
        # PostgreSQL SELECT 1 check
        if docker compose exec -T -e PGPASSWORD="$db_pass" db psql -U "$db_user" -d "$db_name" -c "SELECT 1;" &>/dev/null; then
            echo -e "  - PostgreSQL Connection: ${GREEN}✓ SELECT 1 Success${NC}"
            healthy_count=$((healthy_count+1))
        else
            echo -e "  - PostgreSQL Connection: ${RED}✗ SELECT 1 Failed${NC}"
            total_score=$((total_score-10))
        fi

        # Redis Ping check
        local ping_resp
        if [ -n "$redis_pass" ]; then
            ping_resp=$(docker compose exec -T redis redis-cli -a "$redis_pass" ping 2>/dev/null | tr -d '\r\n' || true)
        else
            ping_resp=$(docker compose exec -T redis redis-cli ping 2>/dev/null | tr -d '\r\n' || true)
        fi
        if [ "$ping_resp" = "PONG" ]; then
            echo -e "  - Redis Cache Connection: ${GREEN}✓ PING PONG Success${NC}"
            healthy_count=$((healthy_count+1))
        else
            echo -e "  - Redis Cache Connection: ${RED}✗ PING Failed${NC}"
            total_score=$((total_score-10))
        fi

        # OpenAI verify check
        if [ -n "$openai_key" ]; then
            local openai_code
            openai_code=$(curl -s -o /dev/null -w "%{http_code}" \
                -H "Authorization: Bearer $openai_key" \
                https://api.openai.com/v1/models || echo "000")
            if [ "$openai_code" = "200" ]; then
                echo -e "  - OpenAI API Connection: ${GREEN}✓ API Active (200)${NC}"
            else
                echo -e "  - OpenAI API Connection: ${YELLOW}! Key Auth Failed ($openai_code)${NC}"
                total_score=$((total_score-5))
            fi
        else
            echo -e "  - OpenAI API Connection: ${YELLOW}! Config Empty${NC}"
        fi
    else
        echo -e "  ${RED}Environment file .env not found.${NC}"
        total_score=0
    fi
    
    # Cap score
    if [ $total_score -lt 0 ]; then total_score=0; fi
    
    echo -e "\n${BLUE}Overall System Health Score:${NC}"
    if [ $total_score -ge 90 ]; then
        echo -e "  Score: [ ${GREEN}${total_score}/100${NC} ] - ${GREEN}System is completely stable.${NC}"
    elif [ $total_score -ge 70 ]; then
        echo -e "  Score: [ ${YELLOW}${total_score}/100${NC} ] - ${YELLOW}System operates with warnings.${NC}"
    else
        echo -e "  Score: [ ${RED}${total_score}/100${NC} ] - ${RED}Critical issues detected. Run repair.${NC}"
    fi
    echo -e "${BLUE}====================================================${NC}"
}

render_dashboard
