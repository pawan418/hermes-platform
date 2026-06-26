#!/usr/bin/env bash

# Hermes AI Platform - self-updater tool
# Targets: Ubuntu 24.04 LTS (x86_64)

set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
INSTALLER_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

# Source libraries
source "${INSTALLER_DIR}/lib/logging.sh"

LOG_FILE="logs/upgrade.log"

self_update_cli() {
    log_info "Initiating Hermes CLI self-update..." "$LOG_FILE"
    
    if [ -d .git ] && command -v git &>/dev/null; then
        local active_branch
        active_branch=$(git rev-parse --abbrev-ref HEAD || echo "main")
        
        log_info "Checking updates for branch: $active_branch..." "$LOG_FILE"
        git fetch origin >> "$LOG_FILE" 2>&1
        
        # Checkout only installer files and root scripts to avoid overwriting workspace configurations
        git checkout "origin/$active_branch" -- installer/ install.sh doctor.sh backup.sh restore.sh upgrade.sh update-env.sh >> "$LOG_FILE" 2>&1
        
        local new_ver
        new_ver=$(cat installer/VERSION 2>/dev/null || echo "1.0.0")
        log_info "Hermes CLI updated successfully to version $new_ver." "$LOG_FILE"
    else
        log_warn "Not running within a Git repository. Self-update aborted." "$LOG_FILE"
    fi
}

self_update_cli
