#!/usr/bin/env bash

# Hermes AI Platform - Health Checking Module
# Targets: Ubuntu 24.04 LTS (x86_64)

set -o pipefail
set -o errexit

# Load shared library
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/common.sh"

LOG_FILE="logs/health.log"
touch "$LOG_FILE"
chmod 600 "$LOG_FILE"

check_compose_service_health() {
    local service=$1
    local max_tries=${2:-30}
    local try=1
    
    echo -n "Waiting for service '$service' to start up..."
    while [ $try -le $max_tries ]; do
        # Resolve container ID dynamically from Docker Compose service name
        local cid
        cid=$(docker compose ps -q "$service" 2>/dev/null | head -n1 || true)
        
        if [ -n "$cid" ]; then
            local health_status
            health_status=$(docker inspect --format='{{json .State.Health.Status}}' "$cid" 2>/dev/null || echo "\"unknown\"")
            local running
            running=$(docker inspect --format='{{.State.Running}}' "$cid" 2>/dev/null || echo "false")
            
            if [ "$health_status" = "\"healthy\"" ] || ([ "$running" = "true" ] && [ "$health_status" = "\"unknown\"" ]); then
                echo -e " ${GREEN}[OK]${NC}"
                log_message "INFO" "Service '$service' (CID: $cid) is healthy." "$LOG_FILE"
                return 0
            fi
            
            if [ "$health_status" = "\"unhealthy\"" ]; then
                echo -e " ${RED}[UNHEALTHY]${NC}"
                log_message "ERROR" "Service '$service' (CID: $cid) is unhealthy." "$LOG_FILE"
                return 1
            fi
        fi
        
        echo -n "."
        sleep 2
        try=$((try+1))
    done
    
    echo -e " ${RED}[TIMEOUT]${NC}"
    log_message "ERROR" "Timeout waiting for service '$service' to start." "$LOG_FILE"
    return 1
}

verify_database_connection() {
    log_info "Verifying database query execution readiness..." "$LOG_FILE"
    
    local db_user
    db_user=$(grep "^DB_USERNAME=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "hermes")
    local db_name
    db_name=$(grep "^DB_DATABASE=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "hermes")
    local db_pass
    db_pass=$(grep "^DB_PASSWORD=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "hermes_secure_pass")

    local tries=0
    local max_tries=10
    
    while [ $tries -lt $max_tries ]; do
        # Execute real query SELECT 1 inside postgres container
        if docker compose exec -T -e PGPASSWORD="$db_pass" db psql -U "$db_user" -d "$db_name" -c "SELECT 1;" &>/dev/null; then
            log_info "Database query check passed (SELECT 1 succeeded)." "$LOG_FILE"
            return 0
        fi
        log_warn "Database is starting up. Retrying query check..." "$LOG_FILE"
        sleep 2
        tries=$((tries+1))
    done

    log_err "Database failed to process queries (SELECT 1 failed)." "$LOG_FILE"
    return 1
}

verify_redis_connection() {
    log_info "Verifying Redis cache connectivity..." "$LOG_FILE"
    
    local redis_pass
    redis_pass=$(grep "^REDIS_PASSWORD=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "")
    
    local ping_resp
    if [ -n "$redis_pass" ]; then
        ping_resp=$(docker compose exec -T redis redis-cli -a "$redis_pass" ping 2>/dev/null | tr -d '\r\n' || true)
    else
        ping_resp=$(docker compose exec -T redis redis-cli ping 2>/dev/null | tr -d '\r\n' || true)
    fi
    
    if [ "$ping_resp" = "PONG" ]; then
        log_info "Redis connectivity verified successfully." "$LOG_FILE"
        return 0
    else
        log_err "Redis connection failed. Response: $ping_resp" "$LOG_FILE"
        return 1
    fi
}

verify_external_components() {
    log_info "Verifying Vector DB, MinIO, and n8n REST endpoints..." "$LOG_FILE"
    
    # Qdrant Check
    local qdrant_code
    qdrant_code=$(docker compose exec -T app curl -s -o /dev/null -w "%{http_code}" http://qdrant:6333/collections || echo "000")
    if [ "$qdrant_code" = "200" ]; then
        log_info "Qdrant Vector DB resolved successfully (HTTP 200)." "$LOG_FILE"
    else
        log_err "Qdrant Vector DB API check failed (HTTP $qdrant_code)." "$LOG_FILE"
        return 1
    fi

    # MinIO Check
    local minio_code
    minio_code=$(docker compose exec -T app curl -s -o /dev/null -w "%{http_code}" http://minio:9000/minio/health/live || echo "000")
    if [ "$minio_code" = "200" ]; then
        log_info "MinIO storage health check resolved successfully (HTTP 200)." "$LOG_FILE"
    else
        log_err "MinIO storage health check failed (HTTP $minio_code)." "$LOG_FILE"
        return 1
    fi

    # n8n Check
    local n8n_code
    n8n_code=$(docker compose exec -T app curl -s -o /dev/null -w "%{http_code}" http://n8n:5678/healthz || echo "000")
    if [ "$n8n_code" = "200" ]; then
        log_info "n8n workflow service healthy (HTTP 200)." "$LOG_FILE"
    else
        log_err "n8n workflow check failed (HTTP $n8n_code)." "$LOG_FILE"
        return 1
    fi
    
    return 0
}

verify_smtp_dispatch() {
    # Check if SMTP configuration is active
    if [ -f .wizard_env ]; then
        source .wizard_env
    fi
    
    local smtp_configured=$(grep "^MAIL_PASSWORD=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "")
    if [ -n "$smtp_configured" ] || [ "$SMTP_CONFIGURED" = "true" ]; then
        log_info "Initiating SMTP outbound verification..." "$LOG_FILE"
        
        while true; do
            # Send test email inside application container
            local receiver
            receiver=$(grep "^ADMIN_EMAIL=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "admin@lspl.xyz")
            
            if docker compose exec -T --user www-data app php artisan hermes:test-smtp --to="$receiver" >> "$LOG_FILE" 2>&1; then
                log_info "SMTP validation mail dispatched successfully." "$LOG_FILE"
                break
            else
                log_warn "SMTP verification failed. Please review settings." "$LOG_FILE"
                echo "1) Retry SMTP validation"
                echo "2) Proceed anyway (SMTP unverified)"
                echo "3) Disable SMTP settings for now"
                read -p "Select option [1]: " smtp_opt
                smtp_opt=${smtp_opt:-1}
                
                if [ "$smtp_opt" = "2" ]; then
                    break
                elif [ "$smtp_opt" = "3" ]; then
                    # Clear SMTP settings in .env
                    sed -i 's/^MAIL_PASSWORD=.*/MAIL_PASSWORD=""/' .env
                    sed -i 's/^MAIL_USERNAME=.*/MAIL_USERNAME=""/' .env
                    log_warn "SMTP credentials disabled in .env configuration." "$LOG_FILE"
                    break
                else
                    # Allow user to update values and retest
                    read -p "Enter SMTP Host: " host_val
                    read -p "Enter SMTP Port: " port_val
                    read -p "Enter SMTP Username: " user_val
                    read -s -p "Enter SMTP Password: " pass_val
                    echo ""
                    
                    sed -i "s/^MAIL_HOST=.*/MAIL_HOST=\"$host_val\"/" .env
                    sed -i "s/^MAIL_PORT=.*/MAIL_PORT=\"$port_val\"/" .env
                    sed -i "s/^MAIL_USERNAME=.*/MAIL_USERNAME=\"$user_val\"/" .env
                    sed -i "s/^MAIL_PASSWORD=.*/MAIL_PASSWORD=\"$pass_val\"/" .env
                    
                    # Restart services to pick up config updates
                    log_info "Restarting Laravel application to apply new configurations..." "$LOG_FILE"
                    docker compose restart app queue scheduler >> "$LOG_FILE" 2>&1
                fi
            fi
        done
    fi
}

run_health_checks() {
    log_info "Initiating services health checks..." "$LOG_FILE"
    
    # 1. Container health checks
    local services=(db redis qdrant minio n8n app web queue scheduler)
    for service in "${services[@]}"; do
        check_compose_service_health "$service"
    done
    
    # 2. Database validation query
    verify_database_connection
    
    # 3. Redis connection
    verify_redis_connection
    
    # 4. External endpoints
    verify_external_components
    
    # 5. SMTP validation dispatch
    verify_smtp_dispatch
    
    log_info "All service health validation completed." "$LOG_FILE"
}

if [ "${BASH_SOURCE[0]}" = "$0" ]; then
    run_health_checks
fi
