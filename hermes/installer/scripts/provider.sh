#!/usr/bin/env bash

# Hermes AI Platform - AI Provider Management Sub-CLI
# Targets: Ubuntu 24.04 LTS (x86_64)

set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
INSTALLER_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

# Source libraries
source "${INSTALLER_DIR}/lib/logging.sh"

get_env_val() {
    local key=$1
    local val
    val=$(grep -E "^${key}=" .env | head -n1 | cut -d'=' -f2- | tr -d '\r\n' | sed 's/^"//;s/"$//' || echo "")
    echo "$val"
}

update_env_key() {
    local key=$1
    local val=$2
    sed -i "s|^${key}=.*|${key}=\"${val}\"|" .env
}

list_providers() {
    local active_llm=$(get_env_val "DEFAULT_LLM_PROVIDER")
    local active_emb=$(get_env_val "DEFAULT_EMBEDDING_PROVIDER")
    
    echo -e "${BLUE}=== Supported AI Providers ===${NC}"
    local providers=(openai gemini anthropic ollama)
    for p in "${providers[@]}"; do
        if [ "$p" = "$active_llm" ]; then
            echo -e "  - ${p^^} (API): ${GREEN}active LLM provider${NC}"
        elif [ "$p" = "$active_emb" ]; then
            echo -e "  - ${p^^} (API): ${GREEN}active embedding provider${NC}"
        else
            echo -e "  - ${p^^} (API): supported"
        fi
    done
}

switch_provider() {
    local target=$1
    if [ -z "$target" ]; then
        log_err "Target provider required."
        exit 1
    fi
    target=$(echo "$target" | tr '[:upper:]' '[:lower:]')
    
    if [ "$target" != "openai" ] && [ "$target" != "gemini" ] && [ "$target" != "anthropic" ] && [ "$target" != "ollama" ]; then
        log_err "Unsupported provider '$target'."
        exit 1
    fi
    
    update_env_key "DEFAULT_LLM_PROVIDER" "$target"
    if [ "$target" = "openai" ] || [ "$target" = "ollama" ]; then
        update_env_key "DEFAULT_EMBEDDING_PROVIDER" "$target"
    fi
    
    log_info "Switched default LLM driver to '$target'."
    
    # Reload configs
    docker compose exec -T --user www-data app php artisan optimize:clear &>/dev/null || true
}

COMMAND=${1:-""}
if [ -n "$COMMAND" ]; then
    shift
fi

case "$COMMAND" in
    list)
        list_providers
        ;;
    switch)
        if [ -z "${1:-}" ]; then log_err "Target provider name required."; exit 1; fi
        switch_provider "$1"
        ;;
    test)
        bash "${SCRIPT_DIR}/ai.sh" test
        ;;
    benchmark)
        bash "${SCRIPT_DIR}/ai.sh" benchmark
        ;;
    *)
        echo "Usage: hermes provider [list|switch <provider>|test|benchmark]"
        exit 1
        ;;
esac
