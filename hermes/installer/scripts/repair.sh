#!/usr/bin/env bash

# Hermes AI Platform - Platform Repair Utility
# Targets: Ubuntu 24.04 LTS (x86_64)

set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
INSTALLER_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

# Source libraries
source "${INSTALLER_DIR}/lib/logging.sh"
source "${INSTALLER_DIR}/lib/backup.sh"

LOG_FILE="logs/install.log"

echo -e "${BLUE}====================================================${NC}"
echo -e "${BLUE}         Hermes System Repair Utility               ${NC}"
echo -e "${BLUE}====================================================${NC}"

# 1. Take automated backup before repairing
log_info "Creating automatic pre-repair backup..." "$LOG_FILE"
if bash "${SCRIPT_DIR}/backup.sh" >> "$LOG_FILE" 2>&1; then
    PRE_REPAIR_BACKUP=$(ls -t backups/hermes_backup_*.tar.gz 2>/dev/null | head -n 1 || echo "")
    if [ -n "$PRE_REPAIR_BACKUP" ]; then
        log_info "Pre-repair backup captured: $PRE_REPAIR_BACKUP" "$LOG_FILE"
    fi
else
    log_warn "Pre-repair backup failed. Proceeding with caution..." "$LOG_FILE"
fi

# Trap to restore backup if repair fails critical operations
rollback_on_repair_failure() {
    local exit_code=$?
    if [ $exit_code -ne 0 ]; then
        log_err "Repair execution failed! Reverting platform to pre-repair state..." "$LOG_FILE"
        if [ -n "${PRE_REPAIR_BACKUP:-}" ] && [ -f "$PRE_REPAIR_BACKUP" ]; then
            bash "${SCRIPT_DIR}/restore.sh" "$PRE_REPAIR_BACKUP" --force || true
        fi
    fi
}
trap rollback_on_repair_failure EXIT

# 2. Reset permissions
log_info "Resetting filesystem directory and file permissions..." "$LOG_FILE"
local dirs=(storage bootstrap/cache uploads knowledge logs)
for dir in "${dirs[@]}"; do
    if [ ! -d "$dir" ]; then
        mkdir -p "$dir"
    fi
    chmod -R 775 "$dir" >> "$LOG_FILE" 2>&1 || true
    if getent group www-data &>/dev/null; then
        sudo chown -R :www-data "$dir" >> "$LOG_FILE" 2>&1 || true
    fi
done

if [ -f .env ]; then
    chmod 600 .env
fi
log_info "Permissions verified." "$LOG_FILE"

# 3. Reload containers
log_info "Rebuilding and starting container infrastructure..." "$LOG_FILE"
docker compose up -d >> "$LOG_FILE" 2>&1
log_info "Containers started." "$LOG_FILE"

# 4. Laravel optimizations
log_info "Rebuilding and optimizing Laravel caching configurations..." "$LOG_FILE"
docker compose exec -T --user www-data app php artisan optimize:clear >> "$LOG_FILE" 2>&1 || true
docker compose exec -T --user www-data app php artisan optimize >> "$LOG_FILE" 2>&1 || true
docker compose exec -T --user www-data app php artisan config:cache >> "$LOG_FILE" 2>&1 || true
docker compose exec -T --user www-data app php artisan route:cache >> "$LOG_FILE" 2>&1 || true
docker compose exec -T --user www-data app php artisan view:cache >> "$LOG_FILE" 2>&1 || true
docker compose exec -T --user www-data app php artisan queue:restart >> "$LOG_FILE" 2>&1 || true

# 5. Run migrations
log_info "Verifying database migrations..." "$LOG_FILE"
docker compose exec -T --user www-data app php artisan migrate --force >> "$LOG_FILE" 2>&1
log_info "Migrations completed." "$LOG_FILE"

# Remove trap on successful run
trap - EXIT

# 6. Execute diagnostics
echo -e "\n${YELLOW}Running diagnostics check post-repair...${NC}"
if bash "${SCRIPT_DIR}/doctor.sh"; then
    echo -e "\n${GREEN}====================================================${NC}"
    echo -e "${GREEN}      Repair Actions Completed Successfully!        ${NC}"
    echo -e "${GREEN}====================================================${NC}"
else
    echo -e "\n${YELLOW}Repair finished, but system reported diagnostics warnings.${NC}"
fi
