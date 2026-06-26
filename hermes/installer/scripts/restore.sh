#!/usr/bin/env bash

# Hermes AI Platform - Platform Restore Utility
# Targets: Ubuntu 24.04 LTS (x86_64)

set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
INSTALLER_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

# Source libraries
source "${INSTALLER_DIR}/lib/logging.sh"
source "${INSTALLER_DIR}/lib/backup.sh"

LOG_FILE="logs/restore.log"
touch "$LOG_FILE"
chmod 600 "$LOG_FILE"

echo -e "${BLUE}====================================================${NC}"
echo -e "${BLUE}         Hermes System Restore Utility              ${NC}"
echo -e "${BLUE}====================================================${NC}"

if [ -z "${1:-}" ]; then
    log_err "Error: Backup archive path not specified."
    echo -e "Usage: ${YELLOW}hermes restore backups/hermes_backup_timestamp.tar.gz${NC}"
    exit 1
fi

BACKUP_FILE=$1

if [ ! -f "$BACKUP_FILE" ]; then
    log_err "Error: Backup file not found at: ${BACKUP_FILE}"
    exit 1
fi

echo -e "${YELLOW}Verifying backup archive integrity...${NC}"
TIMESTAMP=$(date '+%Y%m%d_%H%M%S')
TEMP_DIR="backups/restore_tmp_${TIMESTAMP}"
mkdir -p "${TEMP_DIR}"

cleanup_restore() {
    rm -rf "${TEMP_DIR}"
}
trap cleanup_restore EXIT

# Unpack the archive to temp directory
tar -xzf "$BACKUP_FILE" -C "$TEMP_DIR"

# Verify all required resources exist inside the package
REQUIRED_FILES=(
    "database.sql"
    "redis_data.tar.gz"
    "minio_data.tar.gz"
    "qdrant_data.tar.gz"
    "n8n_data.tar.gz"
    "uploads_data.tar.gz"
    "knowledge_data.tar.gz"
    ".env"
)

for file in "${REQUIRED_FILES[@]}"; do
    if [ ! -f "${TEMP_DIR}/${file}" ]; then
        log_err "Error: Invalid backup structure. Missing file: ${file}"
        exit 1
    fi
done
log_info "Backup archive integrity verified." "$LOG_FILE"

# Confirm action
FORCE_RESTORE=0
if [ "${2:-}" = "--force" ] || [ "${2:-}" = "-y" ]; then
    FORCE_RESTORE=1
fi

if [ $FORCE_RESTORE -ne 1 ]; then
    echo -e "${RED}WARNING: This operation will completely overwrite the active database and volumes.${NC}"
    read -p "Are you sure you want to proceed? (y/n) [n]: " CONFIRM
    if [[ ! "$CONFIRM" =~ ^[Yy]$ ]]; then
        echo -e "${YELLOW}Restore operation aborted by user.${NC}"
        exit 0
    fi
fi

PROJECT_NAME=$(get_compose_project_name)
log_info "Active project: $PROJECT_NAME" "$LOG_FILE"

# Load database credentials from backup .env
DB_USER=$(grep "^DB_USERNAME=" "${TEMP_DIR}/.env" | cut -d'=' -f2- | tr -d '\r\n"' || echo "hermes")
DB_NAME=$(grep "^DB_DATABASE=" "${TEMP_DIR}/.env" | cut -d'=' -f2- | tr -d '\r\n"' || echo "hermes")
DB_PASS=$(grep "^DB_PASSWORD=" "${TEMP_DIR}/.env" | cut -d'=' -f2- | tr -d '\r\n"' || echo "")

# 1. Shut down services
echo -e "\n${YELLOW}[1/7] Pausing all running services...${NC}"
docker compose down >> "$LOG_FILE" 2>&1 || true
log_info "Services paused." "$LOG_FILE"

# 2. Volumes
echo -e "${YELLOW}[2/7] Restoring Redis volume...${NC}"
extract_docker_volume "$PROJECT_NAME" "hermes-redis" "${TEMP_DIR}/redis_data.tar.gz" "$LOG_FILE"

echo -e "${YELLOW}[3/7] Restoring MinIO storage volume...${NC}"
extract_docker_volume "$PROJECT_NAME" "hermes-minio" "${TEMP_DIR}/minio_data.tar.gz" "$LOG_FILE"

echo -e "${YELLOW}[4/7] Restoring Qdrant Vector database volume...${NC}"
extract_docker_volume "$PROJECT_NAME" "hermes-qdrant" "${TEMP_DIR}/qdrant_data.tar.gz" "$LOG_FILE"

echo -e "${YELLOW}[5/7] Restoring n8n config volume...${NC}"
extract_docker_volume "$PROJECT_NAME" "hermes-n8n" "${TEMP_DIR}/n8n_data.tar.gz" "$LOG_FILE"

# 3. Host directories
echo -e "${YELLOW}[6/7] Restoring host uploads directory...${NC}"
extract_host_directory "uploads" "${TEMP_DIR}/uploads_data.tar.gz" "$LOG_FILE"

echo -e "${YELLOW}[7/7] Restoring host knowledge directory...${NC}"
extract_host_directory "knowledge" "${TEMP_DIR}/knowledge_data.tar.gz" "$LOG_FILE"

# Copy configurations
cp "${TEMP_DIR}/.env" .env
chmod 644 .env
log_info "Environment .env file restored." "$LOG_FILE"

# 4. Start database container and wait for queries
echo -e "\n${YELLOW}Spinning up database to reload schemas...${NC}"
docker compose up -d db >> "$LOG_FILE" 2>&1

echo -n "Waiting for database service to be ready"
until docker compose exec -T db pg_isready -U "$DB_USER" -d "$DB_NAME" &>/dev/null; do
    echo -n "."
    sleep 2
done
echo -e " ${GREEN}[OK]${NC}"

# Recreate DB schema
log_info "Dropping and recreating database schema..." "$LOG_FILE"
docker compose exec -T -e PGPASSWORD="$DB_PASS" db psql -U "$DB_USER" -d postgres -c "DROP DATABASE IF EXISTS $DB_NAME;" >> "$LOG_FILE" 2>&1
docker compose exec -T -e PGPASSWORD="$DB_PASS" db psql -U "$DB_USER" -d postgres -c "CREATE DATABASE $DB_NAME;" >> "$LOG_FILE" 2>&1

# Restore dump
log_info "Loading database dump..." "$LOG_FILE"
if execute_pg_restore "$DB_USER" "$DB_NAME" "$DB_PASS" "${TEMP_DIR}/database.sql" "$LOG_FILE"; then
    log_info "Database restored successfully." "$LOG_FILE"
else
    log_err "Database restoration failed." "$LOG_FILE"
    exit 1
fi

# 5. Start remaining containers
echo -e "\n${YELLOW}Starting all containerized services...${NC}"
docker compose up -d >> "$LOG_FILE" 2>&1
docker compose exec -T --user www-data app php artisan queue:restart >> "$LOG_FILE" 2>&1 || true

# 6. Verify health using doctor
echo -e "\n${YELLOW}Running diagnostics tests...${NC}"
sleep 3
if bash "${SCRIPT_DIR}/doctor.sh"; then
    echo -e "\n${GREEN}====================================================${NC}"
    echo -e "${GREEN}      Hermes Platform Restored Successfully!        ${NC}"
    echo -e "${GREEN}====================================================${NC}"
else
    echo -e "\n${YELLOW}Restore finished, but system reported diagnostic warnings.${NC}"
fi
