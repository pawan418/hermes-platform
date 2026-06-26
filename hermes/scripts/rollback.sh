#!/usr/bin/env bash

# Hermes AI Platform - Rollback and Failure Recovery Module
# Targets: Ubuntu 24.04 LTS (x86_64)

set -o pipefail

# Load shared library
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/common.sh"

LOG_FILE="logs/rollback.log"
touch "$LOG_FILE"
chmod 600 "$LOG_FILE"

BACKUP_FILE=".pre_migration_backup.sql"

backup_db_before_migration() {
    log_info "Creating database snapshot before running schema migrations..." "$LOG_FILE"
    
    if [ ! -f .env ]; then
        log_warn "Environment configuration .env is missing. Database backup skipped." "$LOG_FILE"
        return 0
    fi
    
    local db_user
    db_user=$(grep "^DB_USERNAME=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "hermes")
    local db_name
    db_name=$(grep "^DB_DATABASE=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "hermes")
    local db_pass
    db_pass=$(grep "^DB_PASSWORD=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "")
    
    # Verify db container is running
    local cid
    cid=$(docker compose ps -q db 2>/dev/null | head -n1 || true)
    if [ -z "$cid" ]; then
        log_warn "Database service is not running. Snapshot skipped." "$LOG_FILE"
        return 0
    fi
    
    # Run pg_dump and capture back to host
    rm -f "$BACKUP_FILE"
    if docker compose exec -T -e PGPASSWORD="$db_pass" db pg_dump -U "$db_user" -d "$db_name" --clean --if-exists > "$BACKUP_FILE" 2>> "$LOG_FILE"; then
        log_info "Database snapshot successfully created at $BACKUP_FILE." "$LOG_FILE"
        return 0
    else
        log_warn "Failed to create database snapshot. Proceeding with caution." "$LOG_FILE"
        rm -f "$BACKUP_FILE"
        return 0
    fi
}

restore_db_on_failure() {
    log_warn "Migration execution encountered a failure. Initiating rollback..." "$LOG_FILE"
    
    if [ ! -f "$BACKUP_FILE" ]; then
        log_err "Pre-migration backup file $BACKUP_FILE was not found. Cannot rollback automatically." "$LOG_FILE"
        return 1
    fi
    
    local db_user
    db_user=$(grep "^DB_USERNAME=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "hermes")
    local db_name
    db_name=$(grep "^DB_DATABASE=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "hermes")
    local db_pass
    db_pass=$(grep "^DB_PASSWORD=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "")
    
    # Reset database schema and restore from backup file
    log_info "Dropping and recreating schema 'public' to clear partial migration state..." "$LOG_FILE"
    docker compose exec -T -e PGPASSWORD="$db_pass" db psql -U "$db_user" -d "$db_name" -c "DROP SCHEMA public CASCADE; CREATE SCHEMA public; ALTER SCHEMA public OWNER TO $db_user;" >> "$LOG_FILE" 2>&1
    
    log_info "Restoring database snapshot from $BACKUP_FILE..." "$LOG_FILE"
    if docker compose exec -T -e PGPASSWORD="$db_pass" db psql -U "$db_user" -d "$db_name" -f - < "$BACKUP_FILE" >> "$LOG_FILE" 2>&1; then
        log_info "Database successfully rolled back to pre-migration snapshot state." "$LOG_FILE"
        rm -f "$BACKUP_FILE"
        return 0
    else
        log_err "Failed to restore database from backup snapshot. Manual intervention required." "$LOG_FILE"
        return 1
    fi
}

cleanup_pre_migration_backup() {
    if [ -f "$BACKUP_FILE" ]; then
        rm -f "$BACKUP_FILE"
        log_info "Cleaned up temporary database backup snapshot." "$LOG_FILE"
    fi
}

# Allow direct script calls for testing rollback
if [ "${BASH_SOURCE[0]}" = "$0" ]; then
    if [ "$1" = "backup" ]; then
        backup_db_before_migration
    elif [ "$1" = "restore" ]; then
        restore_db_on_failure
    fi
fi
