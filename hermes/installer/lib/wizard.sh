#!/usr/bin/env bash

# Hermes AI Platform - Interactive Wizard Utilities Library
# Targets: Ubuntu 24.04 LTS (x86_64)

LIB_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${LIB_DIR}/logging.sh"
source "${LIB_DIR}/security.sh"

prompt_string() {
    local text=$1
    local default=$2
    local var_name=$3
    local is_secret=$4
    local val

    if [ "$is_secret" = "true" ]; then
        local masked="Empty"
        if [ -n "$default" ]; then
            if [ ${#default} -le 8 ]; then
                masked="********"
            else
                masked="${default:0:4}****************${default: -4}"
            fi
        fi
        read -s -p "$text [$masked]: " val
        echo ""
        if [ -z "$val" ]; then
            val=$default
        fi
    else
        read -p "$text [$default]: " val
        if [ -z "$val" ]; then
            val=$default
        fi
    fi
    eval "$var_name=\$val"
}

validate_openai_auth() {
    local key=$1
    if [ -z "$key" ]; then
        return 1
    fi
    local http_code
    http_code=$(curl -s -o /dev/null -w "%{http_code}" \
        -H "Authorization: Bearer $key" \
        https://api.openai.com/v1/models || echo "000")
    if [ "$http_code" = "200" ]; then
        return 0
    else
        return 1
    fi
}

validate_google_credentials() {
    local client_id=$1
    local client_secret=$2
    
    if [ -z "$client_id" ] || [ -z "$client_secret" ]; then
        return 1
    fi
    
    # Structural check on Google Client ID
    if [[ ! "$client_id" =~ \.apps\.googleusercontent\.com$ ]]; then
        return 2 # Invalid structure
    fi
    
    # Check if Google Accounts endpoints resolve
    if curl -s --connect-timeout 3 "https://accounts.google.com/.well-known/openid-configuration" >/dev/null; then
        return 0
    else
        return 3 # DNS/Network failure to Google auth
    fi
}

validate_whatsapp_credentials() {
    local wa_token=$1
    local wa_phone_id=$2
    
    if [ -z "$wa_token" ] || [ -z "$wa_phone_id" ]; then
        return 1
    fi
    
    # Meta access tokens are typically long strings beginning with EA
    if [[ ! "$wa_token" =~ ^EA[A-Za-z0-9]+ ]]; then
        return 2 # Structural warning
    fi
    
    return 0
}
