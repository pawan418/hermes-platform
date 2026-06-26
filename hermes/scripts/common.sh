#!/usr/bin/env bash

# Hermes AI Platform - Shared DevOps Library
# Targets: Ubuntu 24.04 LTS (x86_64)

# Color variables
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Create logs directory
mkdir -p logs

# Logging utility
log_message() {
    local type=$1
    local msg=$2
    local log_file=${3:-logs/install.log}
    local timestamp
    timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    
    # Ensure logs folder has secure permissions
    chmod 700 logs &>/dev/null || true
    
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

# Version comparison tool
# Returns: 0 (v1 == v2), 1 (v1 > v2), 2 (v1 < v2)
version_compare() {
    if [[ "$1" == "$2" ]]; then
        return 0
    fi
    
    # Strip any leading 'v' characters
    local clean_v1=${1#v}
    local clean_v2=${2#v}

    local IFS=.
    local i ver1=($clean_v1) ver2=($clean_v2)
    
    # Fill empty fields in ver1 with 0
    for ((i=${#ver1[@]}; i<${#ver2[@]}; i++)); do
        ver1[i]=0
    done
    
    for ((i=0; i<${#ver1[@]}; i++)); do
        if [[ -z ${ver2[i]} ]]; then
            ver2[i]=0
        fi
        
        # Strip non-numeric suffixes (e.g. 28.0.0-beta -> 28)
        local num1=$(echo "${ver1[i]}" | grep -oE '^[0-9]+' || echo "0")
        local num2=$(echo "${ver2[i]}" | grep -oE '^[0-9]+' || echo "0")

        if ((10#$num1 > 10#$num2)); then
            return 1
        fi
        if ((10#$num1 < 10#$num2)); then
            return 2
        fi
    done
    return 0
}

# Graphical Progress Bar
show_progress() {
    local step=$1
    local total=$2
    local message=$3
    local percent=$(( (step * 100) / total ))
    local completed=$(( percent / 5 ))
    local remaining=$(( 20 - completed ))
    
    local bar=""
    for ((i=0; i<completed; i++)); do bar="${bar}█"; done
    for ((i=0; i<remaining; i++)); do bar="${bar}░"; done
    
    echo -e "${BLUE}Step ${step}/${total} [${bar}] ${percent}% - ${message}${NC}"
}

# Terminal Spinner for background tasks
show_spinner() {
    local pid=$1
    local message=$2
    local delay=0.1
    local spinstr='|/-\'
    
    echo -n -e "${YELLOW}$message   ${NC}"
    while ps -p "$pid" &>/dev/null; do
        local temp=${spinstr#?}
        printf "%c" "$spinstr"
        spinstr=$temp${spinstr%"$temp"}
        sleep $delay
        printf "\b"
    done
    printf " \b\n"
}

# Cryptographically secure random password generator
# Excludes characters that break sed commands (/, \, &)
generate_secure_password() {
    local length=${1:-16}
    openssl rand -base64 48 | tr -dc 'A-Za-z0-9!@#%^*' | head -c "$length"
}

# Password complexity validation
# Requirements: >=8 chars, uppercase, lowercase, number, symbol
validate_password_strength() {
    local pass=$1
    if [ ${#pass} -lt 8 ]; then
        return 1
    fi
    if ! [[ "$pass" =~ [A-Z] ]]; then
        return 2
    fi
    if ! [[ "$pass" =~ [a-z] ]]; then
        return 3
    fi
    if ! [[ "$pass" =~ [0-9] ]]; then
        return 4
    fi
    if ! [[ "$pass" =~ [!@#%^\*] ]]; then
        return 5
    fi
    return 0
}

# Network port check
check_port_open() {
    local port=$1
    if lsof -Pi :"$port" -sTCP:LISTEN -t &>/dev/null || ss -lntu | grep -q ":$port "; then
        return 1 # Port is already in use
    fi
    return 0 # Port is available
}

# Verify and create Docker networks dynamically
ensure_docker_network() {
    local network_name=$1
    if ! docker network inspect "$network_name" &>/dev/null; then
        docker network create "$network_name" >/dev/null
        log_info "Created missing Docker network: $network_name"
    fi
}
