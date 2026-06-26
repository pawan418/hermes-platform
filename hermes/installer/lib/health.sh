#!/usr/bin/env bash

# Hermes AI Platform - Deep Health Auditing Library
# Targets: Ubuntu 24.04 LTS (x86_64)

LIB_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${LIB_DIR}/logging.sh"

audit_health_status() {
    local label=$1
    local status=$2
    local details=$3
    
    if [ "$status" = "ok" ]; then
        echo -e "${GREEN}✓ [OK]      ${label}${NC} ${details}"
        return 0
    elif [ "$status" = "warn" ]; then
        echo -e "${YELLOW}! [WARN]    ${label}${NC} ${details}"
        return 1
    else
        echo -e "${RED}✗ [FAIL]    ${label}${NC} ${details}"
        return 2
    fi
}

run_deep_diagnostics() {
    local log_file="logs/health.log"
    touch "$log_file"
    chmod 600 "$log_file"
    
    log_info "Initiating enterprise-grade deep health diagnostics..." "$log_file"
    
    local errors_found=0
    
    # 1. Daemon and Compose CLI
    if sudo systemctl is-active --quiet docker; then
        audit_health_status "Docker Daemon" "ok" "Daemon is running"
    else
        audit_health_status "Docker Daemon" "fail" "Daemon is stopped"
        errors_found=$((errors_found+1))
    fi
    
    if docker compose version &>/dev/null; then
        audit_health_status "Docker Compose CLI" "ok" "Compose validated"
    else
        audit_health_status "Docker Compose CLI" "fail" "Compose command failed"
        errors_found=$((errors_found+1))
    fi
    
    # Check if .env is missing
    if [ ! -f .env ]; then
        audit_health_status "Environment Config" "fail" ".env file is missing"
        return 1
    fi
    
    # Read DB and Cache credentials
    local db_user
    db_user=$(grep "^DB_USERNAME=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "hermes")
    local db_name
    db_name=$(grep "^DB_DATABASE=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "hermes")
    local db_pass
    db_pass=$(grep "^DB_PASSWORD=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "")
    local redis_pass
    redis_pass=$(grep "^REDIS_PASSWORD=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "")
    local openai_key
    openai_key=$(grep "^OPENAI_API_KEY=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "")
    
    # Verify containers are created
    local db_cid
    db_cid=$(docker compose ps -q db 2>/dev/null | head -n1 || true)
    if [ -n "$db_cid" ]; then
        # 2. PostgreSQL Connection & SELECT 1
        if docker compose exec -T db pg_isready -U "$db_user" &>/dev/null; then
            if docker compose exec -T -e PGPASSWORD="$db_pass" db psql -U "$db_user" -d "$db_name" -c "SELECT 1;" &>/dev/null; then
                audit_health_status "PostgreSQL (SELECT 1)" "ok" "Database accepts queries"
            else
                audit_health_status "PostgreSQL (SELECT 1)" "fail" "Connection accepted but query failed"
                errors_found=$((errors_found+1))
            fi
        else
            audit_health_status "PostgreSQL Connection" "fail" "Connection refused"
            errors_found=$((errors_found+1))
        fi
    else
        audit_health_status "PostgreSQL DB Service" "fail" "DB container not created"
        errors_found=$((errors_found+1))
    fi

    # 3. Redis PING
    local redis_cid
    redis_cid=$(docker compose ps -q redis 2>/dev/null | head -n1 || true)
    if [ -n "$redis_cid" ]; then
        local ping_resp
        if [ -n "$redis_pass" ]; then
            ping_resp=$(docker compose exec -T redis redis-cli -a "$redis_pass" ping 2>/dev/null | tr -d '\r\n' || true)
        else
            ping_resp=$(docker compose exec -T redis redis-cli ping 2>/dev/null | tr -d '\r\n' || true)
        fi
        
        if [ "$ping_resp" = "PONG" ]; then
            audit_health_status "Redis Cache Store" "ok" "PING responded PONG"
        else
            audit_health_status "Redis Cache Store" "fail" "PING failed ($ping_resp)"
            errors_found=$((errors_found+1))
        fi
    else
        audit_health_status "Redis Cache Service" "fail" "Redis container not created"
        errors_found=$((errors_found+1))
    fi

    # 4. Qdrant REST Collections
    local qdrant_code
    qdrant_code=$(docker compose exec -T app curl -s -o /dev/null -w "%{http_code}" http://qdrant:6333/collections || echo "000")
    if [ "$qdrant_code" = "200" ]; then
        audit_health_status "Qdrant Vector DB" "ok" "REST API resolved HTTP 200"
    else
        audit_health_status "Qdrant Vector DB" "fail" "Endpoint unresponsive (HTTP $qdrant_code)"
        errors_found=$((errors_found+1))
    fi

    # 5. MinIO Object Storage
    local minio_code
    minio_code=$(docker compose exec -T app curl -s -o /dev/null -w "%{http_code}" http://minio:9000/minio/health/live || echo "000")
    if [ "$minio_code" = "200" ]; then
        audit_health_status "MinIO S3 Storage" "ok" "Live check resolved HTTP 200"
    else
        audit_health_status "MinIO S3 Storage" "fail" "Endpoint check failed (HTTP $minio_code)"
        errors_found=$((errors_found+1))
    fi

    # 6. Laravel health endpoint (/api/health)
    local app_code
    app_code=$(docker compose exec -T app curl -s -o /dev/null -w "%{http_code}" http://web/api/health || echo "000")
    if [ "$app_code" = "200" ]; then
        audit_health_status "Laravel App Routing" "ok" "HTTP health check endpoint is live"
    else
        audit_health_status "Laravel App Routing" "fail" "Routing check failed (HTTP $app_code)"
        errors_found=$((errors_found+1))
    fi

    # 7. Queue worker checks
    local queue_active
    queue_active=$(docker compose exec -T queue ps aux | grep -v grep | grep -c "queue:work" || echo "0")
    if [ "$queue_active" -gt 0 ]; then
        audit_health_status "Queue Processor" "ok" "Active workers: $queue_active"
    else
        audit_health_status "Queue Processor" "fail" "No active workers detected"
        errors_found=$((errors_found+1))
    fi

    # 8. Scheduler checks
    local scheduler_active
    scheduler_active=$(docker compose exec -T scheduler ps aux | grep -v grep | grep -c "schedule:work" || echo "0")
    if [ "$scheduler_active" -gt 0 ]; then
        audit_health_status "Cron Scheduler" "ok" "Scheduler daemon running"
    else
        audit_health_status "Cron Scheduler" "fail" "Scheduler daemon stopped"
        errors_found=$((errors_found+1))
    fi

    # 9. Filesystem Write checks
    local storage_ok
    storage_ok=$(docker compose exec -T --user www-data app test -w storage && echo "ok" || echo "fail")
    local cache_ok
    cache_ok=$(docker compose exec -T --user www-data app test -w bootstrap/cache && echo "ok" || echo "fail")
    
    if [ "$storage_ok" = "ok" ] && [ "$cache_ok" = "ok" ]; then
        audit_health_status "App Folder Permissions" "ok" "storage/ & cache/ are writable"
    else
        audit_health_status "App Folder Permissions" "fail" "Write check rejected on storage/ or cache/"
        errors_found=$((errors_found+1))
    fi

    # 10. OpenAI connectivity & Embeddings
    if [ -n "$openai_key" ]; then
        local openai_code
        openai_code=$(curl -s -o /dev/null -w "%{http_code}" \
            -H "Authorization: Bearer $openai_key" \
            https://api.openai.com/v1/models || echo "000")
        if [ "$openai_code" = "200" ]; then
            audit_health_status "OpenAI API Reachability" "ok" "API responded HTTP 200 (models active)"
        else
            audit_health_status "OpenAI API Reachability" "warn" "Key rejected by provider (HTTP $openai_code)"
        fi
    fi

    # 11. SMTP Connection dispatch
    local mail_pass
    mail_pass=$(grep "^MAIL_PASSWORD=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "")
    if [ -n "$mail_pass" ]; then
        local mail_to
        mail_to=$(grep "^MAIL_FROM_ADDRESS=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "admin@lspl.xyz")
        if docker compose exec -T --user www-data app php artisan hermes:test-smtp --to="$mail_to" &>/dev/null; then
            audit_health_status "SMTP Outbound Connection" "ok" "Diagnostic email dispatched successfully"
        else
            audit_health_status "SMTP Outbound Connection" "warn" "Email dispatch failed. Verify SMTP username/password."
        fi
    fi

    log_info "Deep health diagnostics completed. Total critical issues: $errors_found" "$log_file"
    return $errors_found
}
