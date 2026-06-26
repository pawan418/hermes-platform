#!/usr/bin/env bash

# Hermes AI Platform - Pre-flight System Validation Module
# Targets: Ubuntu 24.04 LTS (x86_64)

# Load shared library
source "$(dirname "$0")/common.sh"

check_system_specs() {
    log_info "Initiating system pre-flight checks..." "logs/install.log"
    
    # 1. Check OS version
    if [ -f /etc/os-release ]; then
        local os_name=$(grep -oP 'NAME="\K[^"]+' /etc/os-release)
        local os_version=$(grep -oP 'VERSION_ID="\K[^"]+' /etc/os-release)
        if [[ "$os_name" != *"Ubuntu"* ]] || [ "$os_version" != "24.04" ]; then
            log_warn "Target OS is $os_name $os_version. Ubuntu 24.04 LTS is recommended."
        else
            log_info "Ubuntu 24.04 detected" "logs/install.log"
        fi
    else
        log_err "Unable to read /etc/os-release. System OS check failed."
        return 1
    fi

    # 2. Check Architecture
    local arch=$(uname -m)
    if [ "$arch" != "x86_64" ]; then
        log_err "Unsupported CPU architecture: $arch. Hermes requires x86_64 architecture."
        return 1
    fi

    # 3. Check CPU Cores (minimum 4)
    local cpu_cores=$(nproc)
    if [ "$cpu_cores" -lt 4 ]; then
        log_err "Hermes requires a minimum of 4 CPU cores (Detected: $cpu_cores)."
        return 1
    else
        log_info "CPU: $cpu_cores cores" "logs/install.log"
    fi

    # 4. Check RAM capacity (minimum 8 GB)
    local total_ram_kb=$(awk '/MemTotal/ {print $2}' /proc/meminfo)
    local total_ram_gb=$((total_ram_kb / 1024 / 1024))
    if [ "$total_ram_gb" -lt 8 ]; then
        log_err "Hermes requires a minimum of 8 GB RAM (Detected: $total_ram_gb GB)."
        return 1
    else
        log_info "RAM: $total_ram_gb GB" "logs/install.log"
    fi

    # 5. Check free disk space (minimum 80 GB)
    local free_disk_gb=$(df -BG / | awk 'NR==2 {print $4}' | sed 's/G//')
    if [ "$free_disk_gb" -lt 80 ]; then
        log_err "Hermes requires a minimum of 80 GB available SSD space (Detected: $free_disk_gb GB free)."
        return 1
    else
        log_info "Disk: $free_disk_gb GB free space" "logs/install.log"
    fi

    # 6. Check Inodes Availability (must not exceed 95% usage)
    local inode_used_pct=$(df -i / | awk 'NR==2 {print $5}' | sed 's/%//')
    if [ "$inode_used_pct" -gt 95 ]; then
        log_err "Critically low disk inodes. Inode usage on root is: $inode_used_pct%."
        return 1
    else
        log_info "Disk Inodes: $((100 - inode_used_pct))% available" "logs/install.log"
    fi

    # 7. Check Network connectivity to required developer repositories
    local docker_ok=0
    local git_ok=0
    local openai_ok=0
    
    curl -s --connect-timeout 4 https://download.docker.com >/dev/null && docker_ok=1 || true
    curl -s --connect-timeout 4 https://github.com >/dev/null && git_ok=1 || true
    curl -s --connect-timeout 4 https://api.openai.com >/dev/null && openai_ok=1 || true
    
    if [ $docker_ok -eq 0 ] && [ $git_ok -eq 0 ] && [ $openai_ok -eq 0 ]; then
        log_err "Internet check failed. Unable to establish connections to download.docker.com, github.com, or api.openai.com."
        return 1
    else
        log_info "Network: Connected to external repositories" "logs/install.log"
    fi

    log_info "All pre-flight system checks passed successfully." "logs/install.log"
    return 0
}

# Run execution if called directly
if [ "${BASH_SOURCE[0]}" = "$0" ]; then
    check_system_specs
fi
