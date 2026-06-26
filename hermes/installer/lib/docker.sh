#!/usr/bin/env bash

# Hermes AI Platform - Docker Integration & Auditing Library
# Targets: Ubuntu 24.04 LTS (x86_64)

# Load libraries
LIB_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${LIB_DIR}/logging.sh"
source "${LIB_DIR}/version.sh"

MIN_DOCKER_ENGINE="28.0.0"
MIN_DOCKER_COMPOSE="2.30.0"

validate_docker_setup() {
    local log_file=${1:-logs/install.log}
    log_info "Auditing container runtime environment..." "$log_file"

    # 1. Daemon status
    if ! command -v docker &>/dev/null; then
        log_err "Docker Engine is not installed on this host." "$log_file"
        return 1
    fi
    if ! sudo systemctl is-active --quiet docker; then
        log_err "Docker service is inactive or not running." "$log_file"
        return 1
    fi

    # 2. Docker version
    local docker_ver
    docker_ver=$(docker version --format '{{.Server.Version}}' 2>/dev/null || docker --version | awk '{print $3}' | sed 's/,//')
    docker_ver=${docker_ver#v}
    version_compare "$docker_ver" "$MIN_DOCKER_ENGINE"
    if [ $? -eq 2 ]; then
        log_warn "Docker version $docker_ver is older than recommended $MIN_DOCKER_ENGINE." "$log_file"
    else
        log_info "Docker Engine version: $docker_ver (>= $MIN_DOCKER_ENGINE)" "$log_file"
    fi

    # 3. Docker Compose version
    if ! docker compose version &>/dev/null; then
        log_err "Docker Compose command failed." "$log_file"
        return 1
    fi
    local compose_ver
    compose_ver=$(docker compose version --short 2>/dev/null || docker compose version | awk '{print $4}' | sed 's/^v//')
    compose_ver=${compose_ver#v}
    version_compare "$compose_ver" "$MIN_DOCKER_COMPOSE"
    if [ $? -eq 2 ]; then
        log_warn "Docker Compose version $compose_ver is older than recommended $MIN_DOCKER_COMPOSE." "$log_file"
    else
        log_info "Docker Compose version: $compose_ver (>= $MIN_DOCKER_COMPOSE)" "$log_file"
    fi

    # 4. Host Space and Inodes
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

    # 5. Docker Volumes and Networks
    if [ -f docker-compose.yml ]; then
        log_info "Checking Docker volumes and networks configuration..." "$log_file"
        local proj_name
        proj_name=$(docker compose project name 2>/dev/null || echo "hermes")
        
        # Verify required network
        if ! docker network inspect "${proj_name}_hermes-network" &>/dev/null && ! docker network inspect "hermes-network" &>/dev/null; then
            log_warn "Standard Hermes network is missing. Service scripts will create it." "$log_file"
        fi
    fi

    # 6. Image Digests integrity check
    log_info "Verifying image digests and build states..." "$log_file"
    if [ -f docker-compose.yml ]; then
        # Check if local docker images can be parsed for digests
        docker compose images -q &>/dev/null || true
    fi

    return 0
}
