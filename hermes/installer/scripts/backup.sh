#!/usr/bin/env bash

# Hermes AI Platform - Enterprise Backup Utility
# Targets: Ubuntu 24.04 LTS (x86_64)

set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
INSTALLER_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

# Source libraries
source "${INSTALLER_DIR}/lib/logging.sh"
source "${INSTALLER_DIR}/lib/backup.sh"

LOG_FILE="logs/backup.log"
touch "$LOG_FILE"
chmod 600 "$LOG_FILE"

echo -e "${BLUE}====================================================${NC}"
echo -e "${BLUE}         Hermes System Backup Utility               ${NC}"
echo -e "${BLUE}====================================================${NC}"

# Ensure we have credentials
if [ ! -f .env ]; then
    log_err "Error: Environment configurations file .env not found."
    exit 1
fi

DB_USER=$(grep "^DB_USERNAME=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "hermes")
DB_NAME=$(grep "^DB_DATABASE=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "hermes")
DB_PASS=$(grep "^DB_PASSWORD=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "")

mkdir -p backups

TIMESTAMP=$(date '+%Y%m%d_%H%M%S')
BACKUP_NAME="hermes_backup_${TIMESTAMP}"
TEMP_DIR="backups/tmp_${TIMESTAMP}"
mkdir -p "${TEMP_DIR}"

cleanup_backup() {
    rm -rf "${TEMP_DIR}"
}
trap cleanup_backup EXIT

PROJECT_NAME=$(get_compose_project_name)
log_info "Active project resolved: $PROJECT_NAME" "$LOG_FILE"

# 1. DB Dump
echo -e "${YELLOW}[1/8] Dumping PostgreSQL database...${NC}"
if ! execute_pg_dump "$DB_USER" "$DB_NAME" "$DB_PASS" "${TEMP_DIR}/database.sql" "$LOG_FILE"; then
    log_err "Database dump failed. Backup aborted." "$LOG_FILE"
    exit 1
fi
log_info "Database dump finished." "$LOG_FILE"

# 2. Redis volume
echo -e "${YELLOW}[2/8] Archiving Redis cache storage volume...${NC}"
archive_docker_volume "$PROJECT_NAME" "hermes-redis" "${TEMP_DIR}/redis_data.tar.gz" "$LOG_FILE"

# 3. MinIO volume
echo -e "${YELLOW}[3/8] Archiving MinIO Object Storage volume...${NC}"
archive_docker_volume "$PROJECT_NAME" "hermes-minio" "${TEMP_DIR}/minio_data.tar.gz" "$LOG_FILE"

# 4. Qdrant volume
echo -e "${YELLOW}[4/8] Archiving Qdrant Vector database volume...${NC}"
archive_docker_volume "$PROJECT_NAME" "hermes-qdrant" "${TEMP_DIR}/qdrant_data.tar.gz" "$LOG_FILE"

# 5. n8n volume
echo -e "${YELLOW}[5/8] Archiving n8n environment config volume...${NC}"
archive_docker_volume "$PROJECT_NAME" "hermes-n8n" "${TEMP_DIR}/n8n_data.tar.gz" "$LOG_FILE"

# 6. Uploads
echo -e "${YELLOW}[6/8] Archiving host uploads directory...${NC}"
archive_host_directory "uploads" "${TEMP_DIR}/uploads_data.tar.gz" "$LOG_FILE"

# 7. Knowledge
echo -e "${YELLOW}[7/8] Archiving host knowledge directory...${NC}"
archive_host_directory "knowledge" "${TEMP_DIR}/knowledge_data.tar.gz" "$LOG_FILE"

# 8. .env
echo -e "${YELLOW}[8/8] Packaging credentials...${NC}"
cp .env "${TEMP_DIR}/.env"

# Compress package
echo -e "\n${YELLOW}Packaging and compressing final backup archive...${NC}"
if tar -czf "backups/${BACKUP_NAME}.tar.gz" -C "${TEMP_DIR}" . >> "$LOG_FILE" 2>&1; then
    FILE_SIZE=$(du -sh "backups/${BACKUP_NAME}.tar.gz" | cut -f1)
    
    echo -e "${GREEN}====================================================${NC}"
    echo -e "${GREEN}      Backup Created Successfully!                  ${NC}"
    echo -e "${BLUE}====================================================${NC}"
    echo -e "Archive File:      ${GREEN}backups/${BACKUP_NAME}.tar.gz${NC}"
    echo -e "Archive Size:      ${GREEN}${FILE_SIZE}${NC}"
    echo -e "Restore Command:   ${YELLOW}hermes restore backups/${BACKUP_NAME}.tar.gz${NC}"
    echo -e "${BLUE}====================================================${NC}"
else
    log_err "Failed to compress archive." "$LOG_FILE"
    exit 1
fi
