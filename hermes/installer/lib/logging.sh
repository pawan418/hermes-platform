#!/usr/bin/env bash

# Hermes AI Platform - CENTRALIZED DEVOP LOGGING & EXCEPTION LIBRARY
# Targets: Ubuntu 24.04 LTS (x86_64)

# Color variables for terminal styling
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Ensure logs directory exists
mkdir -p logs
chmod 700 logs &>/dev/null || true

log_message() {
    local type=$1
    local msg=$2
    local log_file=${3:-logs/install.log}
    local timestamp
    timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    
    echo "[$type] $timestamp - $msg" >> "$log_file"
    if [ "$type" = "ERROR" ]; then
        echo "[$type] $timestamp - $msg" >> logs/error.log
    fi
}

log_info() {
    echo -e "${GREEN}✓ $1${NC}"
    log_message "INFO" "$1" "$2"
}

log_warn() {
    echo -e "${YELLOW}! $1${NC}"
    log_message "WARN" "$1" "$2"
}

log_err() {
    echo -e "${RED}✗ $1${NC}" >&2
    log_message "ERROR" "$1" "$2"
}

# Centralized exception handler
error_handler() {
    local exit_code=$?
    local line_no=$1
    local command=$2
    local script_name
    script_name=$(basename "${BASH_SOURCE[1]}" 2>/dev/null || echo "unknown")
    
    # Avoid printing on clean exit codes
    if [ "$exit_code" -eq 0 ]; then
        return
    fi

    echo -e "\n${RED}✗ CRITICAL EXCEPTION TRIGGERED${NC}" >&2
    echo -e "${RED}====================================================${NC}" >&2
    echo -e "Script Name:     ${YELLOW}${script_name}${NC}" >&2
    echo -e "Failed Command:  ${RED}${command}${NC}" >&2
    echo -e "Line Number:     ${YELLOW}${line_no}${NC}" >&2
    echo -e "Exit Code:       ${RED}${exit_code}${NC}" >&2
    echo -e "Relevant Log:    ${YELLOW}logs/error.log${NC}" >&2
    echo -e "${RED}====================================================${NC}" >&2
    
    # Save parameters to error log
    echo "[CRITICAL] $(date '+%Y-%m-%d %H:%M:%S') - Script: $script_name, Line: $line_no, Command: $command, ExitCode: $exit_code" >> logs/error.log
    
    # Suggest appropriate actions
    echo -e "${YELLOW}Suggested Action Plan:${NC}" >&2
    if [[ "$command" =~ "docker" ]]; then
        echo -e "  Verify that the Docker daemon is active: 'sudo systemctl status docker'" >&2
    elif [[ "$command" =~ "composer" ]]; then
        echo -e "  Ensure internet connectivity is functional and DNS server resolves package links." >&2
    elif [[ "$command" =~ "migrate" ]]; then
        echo -e "  Database migrations failed. Review database server container or restore backup." >&2
    else
        echo -e "  Review diagnostic logs under logs/ directory. Run 'hermes doctor' to isolate." >&2
    fi
    echo -e "${RED}====================================================${NC}" >&2
}

# Register centralized error trap
trap 'error_handler ${LINENO} "$BASH_COMMAND"' ERR
