#!/usr/bin/env bash

# Hermes AI Platform - Platform Upgrade Engine
# Targets: Ubuntu 24.04 LTS (x86_64)

set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
INSTALLER_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

# Source libraries
source "${INSTALLER_DIR}/lib/logging.sh"
source "${INSTALLER_DIR}/lib/backup.sh"

LOG_FILE="logs/upgrade.log"
touch "$LOG_FILE"
chmod 600 "$LOG_FILE"

echo -e "${BLUE}====================================================${NC}"
echo -e "${BLUE}         Hermes Platform Upgrade Engine             ${NC}"
echo -e "${BLUE}====================================================${NC}"

# 1. System checks
log_info "Verifying hardware specifications..." "$LOG_FILE"
if ! bash "${SCRIPT_DIR}/doctor.sh" >> "$LOG_FILE" 2>&1; then
    log_err "Pre-upgrade system check failed. Aborting." "$LOG_FILE"
    exit 1
fi

# 2. Capture pre-upgrade backup
log_info "Creating pre-upgrade backup archive..." "$LOG_FILE"
if bash "${SCRIPT_DIR}/backup.sh"; then
    PRE_UPGRADE_BACKUP=$(ls -t backups/hermes_backup_*.tar.gz 2>/dev/null | head -n 1 || echo "")
    if [ -z "$PRE_UPGRADE_BACKUP" ]; then
        log_err "Pre-upgrade backup file not located. Aborting." "$LOG_FILE"
        exit 1
    fi
else
    log_err "Pre-upgrade backup failed. Aborting upgrade to protect data." "$LOG_FILE"
    exit 1
fi

# Trap to restore full platform if upgrade fails
rollback_on_upgrade_failure() {
    local exit_code=$?
    if [ $exit_code -ne 0 ]; then
        log_err "Upgrade execution failed! Initiating full automated rollback..." "$LOG_FILE"
        if [ -n "${PRE_UPGRADE_BACKUP:-}" ] && [ -f "$PRE_UPGRADE_BACKUP" ]; then
            bash "${SCRIPT_DIR}/restore.sh" "$PRE_UPGRADE_BACKUP" --force || true
            log_info "Platform rolled back successfully." "$LOG_FILE"
        else
            log_err "No pre-upgrade backup found to restore." "$LOG_FILE"
        fi
    fi
}
trap rollback_on_upgrade_failure EXIT

# 3. Pull Git changes
log_info "Fetching latest git revisions..." "$LOG_FILE"
if [ -d .git ] && command -v git &>/dev/null; then
    BRANCH=$(git rev-parse --abbrev-ref HEAD || echo "main")
    git fetch origin >> "$LOG_FILE" 2>&1
    git reset --hard "origin/${BRANCH}" >> "$LOG_FILE" 2>&1
    log_info "Git pulled and updated to branch $BRANCH." "$LOG_FILE"
else
    log_warn "Not running inside a Git repository. Source pull skipped." "$LOG_FILE"
fi

# 4. Pull container base images
log_info "Pulling updated Docker container base images..." "$LOG_FILE"
docker compose pull >> "$LOG_FILE" 2>&1

# 5. Compile custom containers
log_info "Building custom application images (PHP container)..." "$LOG_FILE"
docker compose build --pull >> "$LOG_FILE" 2>&1
docker compose up -d >> "$LOG_FILE" 2>&1

# 6. PHP Dependencies check (smart composer)
log_info "Verifying dependencies installation..." "$LOG_FILE"
if [ ! -d "vendor" ]; then
    log_info "Vendor folder missing. Installing Composer packages..." "$LOG_FILE"
    docker compose exec -T --user www-data app composer install --no-interaction --optimize-autoloader >> "$LOG_FILE" 2>&1
else
    log_info "Vendor folder exists. Executing autoloader optimizations..." "$LOG_FILE"
    docker compose exec -T --user www-data app composer dump-autoload --no-interaction --optimize >> "$LOG_FILE" 2>&1
    docker compose exec -T --user www-data app php artisan optimize --no-interaction >> "$LOG_FILE" 2>&1
fi

# 7. Database migrations
log_info "Snapshotting database schema pre-migrations..." "$LOG_FILE"
DB_USER=$(grep "^DB_USERNAME=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "hermes")
DB_NAME=$(grep "^DB_DATABASE=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "hermes")
DB_PASS=$(grep "^DB_PASSWORD=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "")

pre_mig_backup=".pre_upgrade_migration_backup.sql"
rm -f "$pre_mig_backup"
execute_pg_dump "$DB_USER" "$DB_NAME" "$DB_PASS" "$pre_mig_backup" "$LOG_FILE" || true

log_info "Executing migrations..." "$LOG_FILE"
if ! docker compose exec -T --user www-data app php artisan migrate --force >> "$LOG_FILE" 2>&1; then
    log_err "Database migration failed. Restoring pre-migration snapshot..." "$LOG_FILE"
    if [ -f "$pre_mig_backup" ]; then
        execute_pg_restore "$DB_USER" "$DB_NAME" "$DB_PASS" "$pre_mig_backup" "$LOG_FILE" || true
        rm -f "$pre_mig_backup"
    fi
    exit 1
fi
rm -f "$pre_mig_backup"

# 8. Optimized caches matching profile
log_info "Rebuilding Laravel caching files..." "$LOG_FILE"
local app_profile
app_profile=$(grep "^APP_PROFILE=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "production")
if [ "$app_profile" = "development" ]; then
    docker compose exec -T --user www-data app php artisan optimize:clear >> "$LOG_FILE" 2>&1 || true
else
    docker compose exec -T --user www-data app php artisan optimize >> "$LOG_FILE" 2>&1
    docker compose exec -T --user www-data app php artisan config:cache >> "$LOG_FILE" 2>&1
    docker compose exec -T --user www-data app php artisan route:cache >> "$LOG_FILE" 2>&1
    docker compose exec -T --user www-data app php artisan view:cache >> "$LOG_FILE" 2>&1
fi
docker compose exec -T --user www-data app php artisan queue:restart >> "$LOG_FILE" 2>&1

# 9. Health checks
log_info "Verifying health status post-upgrade..." "$LOG_FILE"
sleep 3
if ! bash "${SCRIPT_DIR}/doctor.sh"; then
    log_err "Diagnostics check failed post-upgrade." "$LOG_FILE"
    exit 1 # Triggers rollback trap
fi

# Remove EXIT trap on successful run
trap - EXIT

echo -e "\n${BLUE}====================================================${NC}"
echo -e "${GREEN}      Hermes Platform Upgraded Successfully!        ${NC}"
echo -e "${BLUE}====================================================${NC}"
echo -e "Web Application URL:    ${GREEN}$(grep "^APP_URL=" .env | cut -d'=' -f2- | tr -d '\r\n"')\033[0m"
echo -e "Logs Location:          ${GREEN}logs/upgrade.log${NC}"
echo -e "${BLUE}====================================================${NC}"
