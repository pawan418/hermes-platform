#!/usr/bin/env bash

# Hermes AI Platform - Backup Manager Sub-CLI
# Targets: Ubuntu 24.04 LTS (x86_64)

set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
INSTALLER_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

# Source libraries
source "${INSTALLER_DIR}/lib/logging.sh"

list_backups() {
    echo -e "${BLUE}=== Available Backup Archives ===${NC}"
    if [ -d backups ]; then
        local files=(backups/hermes_backup_*.tar.gz)
        if [ -e "${files[0]}" ]; then
            for f in "${files[@]}"; do
                local size=$(du -sh "$f" | cut -f1)
                local date=$(date -r "$f" '+%Y-%m-%d %H:%M:%S')
                echo -e "  - $(basename "$f") (${GREEN}$size${NC}) - Created: $date"
            done
        else
            echo "  No backup archives located in backups/ folder."
        fi
    else
        echo "  No backups directory exists."
    fi
}

verify_backup() {
    local file_path=$1
    if [ -z "$file_path" ] || [ ! -f "$file_path" ]; then
        log_err "Target backup file is required and must exist."
        exit 1
    fi
    echo -e "Verifying structural archive contents for: ${YELLOW}$file_path${NC}"
    if tar -tzf "$file_path" >/dev/null; then
        log_info "Archive verification passed. Gzip structure is valid."
    else
        log_err "Archive verification failed. Package is corrupted."
        exit 1
    fi
}

delete_backup() {
    local file_path=$1
    if [ -z "$file_path" ]; then
        log_err "Filename to delete is required."
        exit 1
    fi
    # Ensure it's inside backups directory for safety
    local base_name=$(basename "$file_path")
    local full_path="backups/$base_name"
    
    if [ -f "$full_path" ]; then
        rm -f "$full_path"
        log_info "Deleted backup archive: $base_name"
    else
        log_err "Backup file not found at: $full_path"
        exit 1
    fi
}

show_schedule() {
    echo -e "${BLUE}=== Backup Automation Cron Schedule ===${NC}"
    if command -v crontab &>/dev/null; then
        local schedule=$(sudo crontab -l 2>/dev/null | grep "hermes backup" || echo "")
        if [ -n "$schedule" ]; then
            echo -e "  Active automation cron: ${GREEN}$schedule${NC}"
        else
            echo -e "  No active automation cron found. Configure one via:"
            echo -e "  ${YELLOW}hermes backup schedule --setup${NC}"
        fi
    else
        echo -e "  Crontab CLI is not installed on this system."
    fi
}

setup_schedule() {
    if ! command -v crontab &>/dev/null; then
        log_err "crontab utility not available."
        exit 1
    fi
    
    local cron_entry="0 2 * * * /usr/local/bin/hermes backup >> /var/www/hermes/logs/backup.log 2>&1"
    
    # Check if already exists
    if sudo crontab -l 2>/dev/null | grep -q "hermes backup"; then
        log_info "Cron backup schedule is already configured."
    else
        (sudo crontab -l 2>/dev/null; echo "$cron_entry") | sudo crontab - || true
        log_info "Configured cron job: backup automatically triggered nightly at 02:00 AM."
    fi
}

COMMAND=${1:-""}
if [ -n "$COMMAND" ]; then
    shift
fi

case "$COMMAND" in
    create)
        bash "${SCRIPT_DIR}/backup.sh"
        ;;
    list)
        list_backups
        ;;
    restore)
        if [ -z "${1:-}" ]; then
            log_err "Backup file path required."
            exit 1
        fi
        bash "${SCRIPT_DIR}/restore.sh" "$1"
        ;;
    verify)
        verify_backup "${1:-}"
        ;;
    delete)
        delete_backup "${1:-}"
        ;;
    schedule)
        if [ "${1:-}" = "--setup" ]; then
            setup_schedule
        else
            show_schedule
        fi
        ;;
    *)
        echo "Usage: hermes backup [create|list|restore <file>|verify <file>|delete <file>|schedule [--setup]]"
        exit 1
        ;;
esac
