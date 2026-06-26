#!/usr/bin/env bash

# Hermes AI Platform - Environment Configuration Compiler
# Targets: Ubuntu 24.04 LTS (x86_64)

set -o pipefail
set -o errexit

# Load shared library
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/common.sh"

WIZARD_DATA=".wizard_env"
LOG_FILE="logs/install.log"

if [ ! -f "$WIZARD_DATA" ]; then
    log_err "Configuration wizard data missing. Run wizard first." "$LOG_FILE"
    exit 1
fi

# Source wizard configurations
source "$WIZARD_DATA"

# Helper to read existing .env values if they exist
get_env_val() {
    local key=$1
    local default=$2
    if [ -f .env ]; then
        local val
        val=$(grep -E "^${key}=" .env | head -n1 | cut -d'=' -f2- | tr -d '\r\n' | sed 's/^"//;s/"$//' || true)
        if [ -n "$val" ]; then
            echo "$val"
            return
        fi
    fi
    echo "$default"
}

compile_env() {
    log_info "Compiling application environment configurations..." "$LOG_FILE"
    
    # Preserve or generate credentials
    local app_key
    app_key=$(get_env_val "APP_KEY" "")
    
    local db_pass
    db_pass=$(get_env_val "DB_PASSWORD" "")
    if [ -z "$db_pass" ]; then db_pass=$(generate_secure_password 24); fi
    
    local redis_pass
    redis_pass=$(get_env_val "REDIS_PASSWORD" "")
    if [ -z "$redis_pass" ]; then redis_pass=$(generate_secure_password 24); fi
    
    local minio_pass
    minio_pass=$(get_env_val "AWS_SECRET_ACCESS_KEY" "")
    if [ -z "$minio_pass" ]; then minio_pass=$(generate_secure_password 24); fi
    
    local n8n_key
    n8n_key=$(get_env_val "N8N_ENCRYPTION_KEY" "")
    if [ -z "$n8n_key" ]; then n8n_key=$(generate_secure_password 32); fi

    # OpenAI Key resolution
    local final_openai_key="$OPENAI_API_KEY"
    if [ -z "$final_openai_key" ]; then
        final_openai_key=$(get_env_val "OPENAI_API_KEY" "")
    fi

    # SMTP credentials resolution
    local final_mail_host
    local final_mail_port
    local final_mail_username
    local final_mail_password
    local final_mail_encryption
    
    if [ "$SMTP_CONFIGURED" = "true" ]; then
        final_mail_host="$SMTP_HOST"
        final_mail_port="$SMTP_PORT"
        final_mail_username="$SMTP_USER"
        final_mail_password="$SMTP_PASS"
        final_mail_encryption="$SMTP_ENC"
    else
        final_mail_host=$(get_env_val "MAIL_HOST" "smtp.mailgun.org")
        final_mail_port=$(get_env_val "MAIL_PORT" "587")
        final_mail_username=$(get_env_val "MAIL_USERNAME" "postmaster@yourdomain.com")
        final_mail_password=$(get_env_val "MAIL_PASSWORD" "")
        final_mail_encryption=$(get_env_val "MAIL_ENCRYPTION" "tls")
    fi

    # Preset profiles configurations
    local scout_driver="database"
    local log_level="info"
    
    if [ "$APP_PROFILE" = "development" ]; then
        log_level="debug"
    elif [ "$APP_PROFILE" = "enterprise" ]; then
        scout_driver="database" # default scout driver
    fi

    # Backup existing .env if present
    if [ -f .env ]; then
        cp .env .env.bak
        log_info "Created backup of existing configuration at .env.bak" "$LOG_FILE"
    fi

    # Write configurations
    cat <<EOF > .env
# Hermes AI Platform Environment Configuration
APP_NAME=Hermes
APP_ENV=${APP_ENV}
APP_KEY=${app_key}
APP_DEBUG=${APP_DEBUG}
APP_TIMEZONE=${SYSTEM_TIMEZONE}
APP_URL=http://localhost:8080

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=${log_level}

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=hermes
DB_USERNAME=hermes
DB_PASSWORD="${db_pass}"

SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false

CACHE_STORE=redis
CACHE_PREFIX=hermes_cache_

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD="${redis_pass}"
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

QUEUE_CONNECTION=redis

# MinIO Object Storage
AWS_ACCESS_KEY_ID=hermes_admin
AWS_SECRET_ACCESS_KEY="${minio_pass}"
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=hermes-storage
AWS_ENDPOINT=http://minio:9000
AWS_USE_PATH_STYLE_ENDPOINT=true

# Qdrant Vector DB
QDRANT_HOST=qdrant
QDRANT_PORT=6333
QDRANT_API_KEY=

# Pluggable AI Keys
DEFAULT_LLM_PROVIDER=openai
DEFAULT_EMBEDDING_PROVIDER=openai
OPENAI_API_KEY="${final_openai_key}"
ANTHROPIC_API_KEY=
GEMINI_API_KEY=
OLLAMA_HOST=http://host.docker.internal:11434

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST="${final_mail_host}"
MAIL_PORT="${final_mail_port}"
MAIL_USERNAME="${final_mail_username}"
MAIL_PASSWORD="${final_mail_password}"
MAIL_ENCRYPTION="${final_mail_encryption}"
MAIL_FROM_ADDRESS="admin@lspl.xyz"
MAIL_FROM_NAME="\${APP_NAME}"

# n8n Workflow Configuration
N8N_ENCRYPTION_KEY="${n8n_key}"
N8N_URL=http://localhost:5678

SCOUT_DRIVER=${scout_driver}
EOF

    chmod 600 .env
    log_info "Configured .env file successfully with permissions 0600." "$LOG_FILE"
}

if [ "${BASH_SOURCE[0]}" = "$0" ]; then
    compile_env
fi
