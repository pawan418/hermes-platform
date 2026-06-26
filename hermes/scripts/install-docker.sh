#!/usr/bin/env bash

# Hermes AI Platform - Docker Installation and Version Validation Module
# Targets: Ubuntu 24.04 LTS (x86_64)

set -o pipefail
set -o errexit

# Load shared library
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/common.sh"

LOG_FILE="logs/docker.log"
touch "$LOG_FILE"
chmod 600 "$LOG_FILE"

MIN_DOCKER="28.0.0"
MIN_COMPOSE="2.30.0"

check_docker_status() {
    log_info "Verifying container runtime requirements..." "$LOG_FILE"
    
    local install_or_upgrade=0
    local docker_installed=0
    local compose_installed=0
    
    # Check Docker Engine version
    if command -v docker &>/dev/null; then
        docker_installed=1
        local current_docker
        current_docker=$(docker version --format '{{.Server.Version}}' 2>/dev/null || docker --version | awk '{print $3}' | sed 's/,//')
        
        # Strip v prefix if any
        current_docker=${current_docker#v}
        
        version_compare "$current_docker" "$MIN_DOCKER"
        local docker_cmp=$?
        
        if [ $docker_cmp -eq 2 ]; then
            log_warn "Docker Engine version $current_docker is below recommended minimum $MIN_DOCKER." "$LOG_FILE"
            install_or_upgrade=1
        else
            log_info "Docker Engine version $current_docker verified (>= $MIN_DOCKER)." "$LOG_FILE"
        fi
    else
        log_warn "Docker Engine is not installed." "$LOG_FILE"
        install_or_upgrade=1
    fi

    # Check Docker Compose version
    if docker compose version &>/dev/null; then
        compose_installed=1
        local current_compose
        current_compose=$(docker compose version --short 2>/dev/null || docker compose version | awk '{print $4}' | sed 's/^v//')
        current_compose=${current_compose#v}
        
        version_compare "$current_compose" "$MIN_COMPOSE"
        local compose_cmp=$?
        
        if [ $compose_cmp -eq 2 ]; then
            log_warn "Docker Compose version $current_compose is below recommended minimum $MIN_COMPOSE." "$LOG_FILE"
            install_or_upgrade=1
        else
            log_info "Docker Compose version $current_compose verified (>= $MIN_COMPOSE)." "$LOG_FILE"
        fi
    else
        log_warn "Docker Compose is not installed." "$LOG_FILE"
        install_or_upgrade=1
    fi

    if [ $install_or_upgrade -eq 1 ]; then
        echo -e "${YELLOW}System requires a Docker Engine/Compose installation or upgrade.${NC}"
        read -p "Would you like to automatically install/upgrade Docker and Compose now? (y/n) [y]: " choice
        choice=${choice:-y}
        
        if [[ "$choice" =~ ^[Yy]$ ]]; then
            install_upgrade_docker
        else
            log_err "Docker verification failed. Manual upgrade required to meet requirements (Docker >= $MIN_DOCKER, Compose >= $MIN_COMPOSE)." "$LOG_FILE"
            return 1
        fi
    fi

    # Final service check
    if ! sudo systemctl is-active --quiet docker; then
        log_warn "Docker service is not active. Attempting to start..." "$LOG_FILE"
        sudo systemctl enable docker >> "$LOG_FILE" 2>&1
        sudo systemctl start docker >> "$LOG_FILE" 2>&1
    fi

    if ! docker compose version &>/dev/null; then
        log_err "Docker Compose command remains unresponsive." "$LOG_FILE"
        return 1
    fi

    log_info "Container runtime checks completed successfully." "$LOG_FILE"
    return 0
}

install_upgrade_docker() {
    log_info "Beginning Docker Engine & Compose setup/upgrade..." "$LOG_FILE"
    
    # 1. Update and setup GPG keys
    sudo apt-get update -y >> "$LOG_FILE" 2>&1
    sudo apt-get install -y ca-certificates curl gnupg >> "$LOG_FILE" 2>&1
    
    sudo install -m 0755 -d /etc/apt/keyrings >> "$LOG_FILE" 2>&1
    
    # Clean up old keys if any
    sudo rm -f /etc/apt/keyrings/docker.gpg
    
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg >> "$LOG_FILE" 2>&1
    sudo chmod a+r /etc/apt/keyrings/docker.gpg >> "$LOG_FILE" 2>&1

    # 2. Add repo configuration
    local codename
    codename=$(. /etc/os-release && echo "$VERSION_CODENAME")
    
    echo \
      "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
      $codename stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

    # 3. Install packages
    sudo apt-get update -y >> "$LOG_FILE" 2>&1
    
    log_info "Installing Docker Engine and Compose packages..." "$LOG_FILE"
    sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin >> "$LOG_FILE" 2>&1
    
    # 4. Service enablement
    sudo systemctl enable docker >> "$LOG_FILE" 2>&1
    sudo systemctl start docker >> "$LOG_FILE" 2>&1
    
    log_info "Docker Engine and Compose setup completed." "$LOG_FILE"
}

# Run module check if invoked directly
if [ "${BASH_SOURCE[0]}" = "$0" ]; then
    check_docker_status
fi
