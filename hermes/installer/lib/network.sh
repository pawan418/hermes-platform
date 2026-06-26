#!/usr/bin/env bash

# Hermes AI Platform - Network Utilities
# Targets: Ubuntu 24.04 LTS (x86_64)

# Network port check
check_port_open() {
    local port=$1
    if lsof -Pi :"$port" -sTCP:LISTEN -t &>/dev/null || ss -lntu | grep -q ":$port "; then
        return 1 # Port is occupied
    fi
    return 0 # Port is free
}

# Verify and create Docker networks dynamically
ensure_docker_network() {
    local network_name=$1
    if ! docker network inspect "$network_name" &>/dev/null; then
        docker network create "$network_name" >/dev/null
    fi
}

# Verify network routing to critical developer and provider servers
verify_network_connectivity() {
    local docker_ok=0
    local git_ok=0
    local openai_ok=0
    
    curl -s --connect-timeout 4 https://download.docker.com >/dev/null && docker_ok=1 || true
    curl -s --connect-timeout 4 https://github.com >/dev/null && git_ok=1 || true
    curl -s --connect-timeout 4 https://api.openai.com >/dev/null && openai_ok=1 || true
    
    if [ $docker_ok -eq 0 ] && [ $git_ok -eq 0 ] && [ $openai_ok -eq 0 ]; then
        return 1 # All failed
    fi
    return 0 # At least one succeeded
}
