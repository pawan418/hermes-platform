#!/usr/bin/env bash

# Hermes AI Platform - Docker Integration & Auditing Library
# Targets: Ubuntu 24.04 LTS (x86_64)

# Load libraries
LIB_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${LIB_DIR}/logging.sh"
source "${LIB_DIR}/version.sh"

MIN_DOCKER_ENGINE="28.0.0"
MIN_DOCKER_COMPOSE="2.30.0"

install_upgrade_docker() {
    local log_file=$1
    log_info "Installing/upgrading Docker Engine & Compose plugin..." "$log_file"
    
    # 1. Prerequisites
    sudo apt-get update -y >> "$log_file" 2>&1
    sudo apt-get install -y ca-certificates curl gnupg >> "$log_file" 2>&1
    
    # 2. Add Docker's official GPG key
    sudo install -m 0755 -d /etc/apt/keyrings >> "$log_file" 2>&1
    sudo rm -f /etc/apt/keyrings/docker.gpg
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg >> "$log_file" 2>&1
    sudo chmod a+r /etc/apt/keyrings/docker.gpg >> "$log_file" 2>&1

    # 3. Setup repository
    local codename
    codename=$(. /etc/os-release && echo "$VERSION_CODENAME")
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $codename stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

    # 4. Install packages
    sudo apt-get update -y >> "$log_file" 2>&1
    sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin >> "$log_file" 2>&1

    # 5. Enable and start service
    sudo systemctl enable docker >> "$log_file" 2>&1
    sudo systemctl start docker >> "$log_file" 2>&1
    
    log_info "Docker Engine and Compose plugin installed/upgraded successfully." "$log_file"
}

validate_docker_setup() {
    local log_file=${1:-logs/install.log}
    log_info "Auditing container runtime environment..." "$log_file"

    local needs_install=0
    
    # 1. Check Docker command existence
    if ! command -v docker &>/dev/null; then
        log_warn "Docker Engine is not installed." "$log_file"
        needs_install=1
    else
        local docker_ver
        docker_ver=$(docker version --format '{{.Server.Version}}' 2>/dev/null || docker --version | awk '{print $3}' | sed 's/,//')
        docker_ver=${docker_ver#v}
        version_compare "$docker_ver" "$MIN_DOCKER_ENGINE"
        if [ $? -eq 2 ]; then
            log_warn "Docker Engine version $docker_ver is older than recommended $MIN_DOCKER_ENGINE." "$log_file"
            needs_install=1
        fi
    fi

    # 2. Check Compose command existence
    if ! docker compose version &>/dev/null; then
        log_warn "Docker Compose plugin is not active." "$log_file"
        needs_install=1
    else
        local compose_ver
        compose_ver=$(docker compose version --short 2>/dev/null || docker compose version | awk '{print $4}' | sed 's/^v//')
        compose_ver=${compose_ver#v}
        version_compare "$compose_ver" "$MIN_DOCKER_COMPOSE"
        if [ $? -eq 2 ]; then
            log_warn "Docker Compose version $compose_ver is older than recommended $MIN_DOCKER_COMPOSE." "$log_file"
            needs_install=1
        fi
    fi

    # 3. Interactive Installation Trigger
    if [ $needs_install -eq 1 ]; then
        echo -e "${YELLOW}System requires a Docker Engine/Compose installation or upgrade.${NC}"
        read -p "Would you like to automatically install/upgrade Docker and Compose now? (y/n) [y]: " choice
        choice=${choice:-y}
        
        if [[ "$choice" =~ ^[Yy]$ ]]; then
            install_upgrade_docker "$log_file"
        else
            log_err "Docker requirements validation failed. Cannot continue without Docker Engine (>= $MIN_DOCKER_ENGINE) and Compose (>= $MIN_DOCKER_COMPOSE)." "$log_file"
            return 1
        fi
    fi

    # 4. Check Daemon Active Status
    if ! sudo systemctl is-active --quiet docker; then
        log_info "Attempting to activate Docker daemon..." "$log_file"
        sudo systemctl enable docker >> "$log_file" 2>&1
        sudo systemctl start docker >> "$log_file" 2>&1
    fi

    # 5. Host Space and Inodes Checks
    local free_space
    free_space=$(df -BG / | awk 'NR==2 {print $4}' | sed 's/G//')
    if [ "$free_space" -lt 40 ]; then
        log_err "Critically low disk space: $free_space GB (Minimum: 40 GB)" "$log_file"
        return 1
    fi
    log_info "Available Root Partition Disk: $free_space GB" "$log_file"

    local inode_used
    inode_used=$(df -i / | awk 'NR==2 {print $5}' | sed 's/%//')
    if [ "$inode_used" -gt 95 ]; then
        log_err "Critically high disk inode usage: $inode_used% (Maximum: 95%)" "$log_file"
        return 1
    fi
    log_info "Inode Availability: $((100 - inode_used))% free" "$log_file"

    # 6. Docker Volumes and Networks Checks
    if [ -f docker-compose.yml ]; then
        log_info "Checking Docker volumes and networks configuration..." "$log_file"
        local proj_name
        proj_name=$(docker compose config 2>/dev/null | grep -E "^name:" | head -n1 | awk '{print $2}' | tr -d '\r\n"' || echo "hermes")
        if ! docker network inspect "${proj_name}_hermes-network" &>/dev/null && ! docker network inspect "hermes-network" &>/dev/null; then
            log_warn "Standard Hermes network is missing. Service scripts will create it." "$log_file"
        fi
    fi

    return 0
}
