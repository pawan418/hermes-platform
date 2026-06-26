#!/usr/bin/env bash

# Hermes AI Platform - Security Utilities
# Targets: Ubuntu 24.04 LTS (x86_64)

# Cryptographically secure random password generator (excluding characters that conflict with regex/sed)
generate_secure_password() {
    local length=${1:-16}
    openssl rand -base64 48 | tr -dc 'A-Za-z0-9!@#%^*' | head -c "$length"
}

# Password complexity validation
# Returns: 0 (strong), 1 (short), 2 (no upper), 3 (no lower), 4 (no digit), 5 (no symbol)
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
