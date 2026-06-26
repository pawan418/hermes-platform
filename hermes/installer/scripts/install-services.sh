#!/usr/bin/env bash

# Hermes AI Platform - Services Deployment and Setup Module
# Targets: Ubuntu 24.04 LTS (x86_64)

set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
INSTALLER_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

# Source libraries
source "${INSTALLER_DIR}/lib/logging.sh"
source "${INSTALLER_DIR}/lib/network.sh"

LOG_FILE="logs/install.log"
DOCKER_LOG="logs/docker.log"

setup_filesystem_permissions() {
    log_info "Verifying and configuring filesystem permissions..." "$LOG_FILE"
    
    local dirs=(
        storage
        storage/framework/cache
        storage/framework/sessions
        storage/framework/views
        storage/logs
        bootstrap/cache
        uploads
        knowledge
        logs
    )
    
    for dir in "${dirs[@]}"; do
        if [ ! -d "$dir" ]; then
            mkdir -p "$dir"
            log_info "Created directory: $dir" "$LOG_FILE"
        fi
        chmod -R 775 "$dir" >> "$LOG_FILE" 2>&1 || true
        if getent group www-data &>/dev/null; then
            sudo chown -R :www-data "$dir" >> "$LOG_FILE" 2>&1 || true
        fi
    done
    
    log_info "Filesystem permissions secured successfully." "$LOG_FILE"
}

ensure_networks_and_volumes() {
    log_info "Validating container networking and volumes..." "$LOG_FILE"
    ensure_docker_network "hermes-network"
    log_info "Docker networks verified." "$LOG_FILE"
}

build_and_start_containers() {
    log_info "Checking syntax of docker-compose.yml..." "$LOG_FILE"
    docker compose config >/dev/null 2>> "$DOCKER_LOG"
    
    log_info "Pulling official container dependencies..." "$LOG_FILE"
    docker compose pull >> "$DOCKER_LOG" 2>&1
    
    log_info "Building custom application images (PHP runtime)..." "$LOG_FILE"
    docker compose build --pull >> "$DOCKER_LOG" 2>&1
    
    log_info "Starting all application services..." "$LOG_FILE"
    docker compose up -d >> "$DOCKER_LOG" 2>&1
    
    log_info "All containers initialized." "$LOG_FILE"
}

configure_php_dependencies() {
    log_info "Checking PHP dependencies installation state..." "$LOG_FILE"
    
    if [ ! -d "vendor" ]; then
        log_info "Vendor directory not found. Executing fresh composer install..." "$LOG_FILE"
        docker compose exec -T --user root app composer install --no-interaction --optimize-autoloader >> "$DOCKER_LOG" 2>&1
        log_info "Composer packages installed." "$LOG_FILE"
    else
        log_info "Vendor directory exists. Running light dependency optimization..." "$LOG_FILE"
        docker compose exec -T --user root app composer dump-autoload --no-interaction --optimize >> "$DOCKER_LOG" 2>&1
        docker compose exec -T --user www-data app php artisan optimize --no-interaction >> "$DOCKER_LOG" 2>&1
        log_info "Composer autoloader optimized." "$LOG_FILE"
    fi
}

run_deployment() {
    setup_filesystem_permissions
    ensure_networks_and_volumes
    build_and_start_containers
    configure_php_dependencies
}

run_deployment
