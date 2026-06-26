#!/usr/bin/env bash

# Hermes AI Platform - Modular Extension CLI
# Targets: Ubuntu 24.04 LTS (x86_64)

set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
INSTALLER_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

# Source libraries
source "${INSTALLER_DIR}/lib/logging.sh"

MODULES_REGISTRY="config/modules.json"

ensure_registry_exists() {
    if [ ! -f "$MODULES_REGISTRY" ]; then
        mkdir -p config
        cat <<EOF > "$MODULES_REGISTRY"
{
  "voice": "enabled",
  "whatsapp": "enabled",
  "knowledge": "enabled",
  "crm": "disabled",
  "sales": "disabled",
  "academy": "disabled",
  "hr": "disabled",
  "finance": "disabled",
  "support": "disabled",
  "analytics": "disabled"
}
EOF
    fi
}

list_modules() {
    ensure_registry_exists
    echo -e "${BLUE}=== Hermes Platform Extensions Registry ===${NC}"
    
    # Read and print from JSON cleanly in bash (using jq or grep/awk)
    if command -v jq &>/dev/null; then
        jq -r 'to_entries[] | "  - \(.key | ascii_upcase): \(.value)"' "$MODULES_REGISTRY" | while read -r line; do
            if [[ "$line" =~ "enabled" ]]; then
                echo -e "${line/enabled/${GREEN}enabled${NC}}"
            else
                echo -e "${line/disabled/${YELLOW}disabled${NC}}"
            fi
        done
    else
        # Fallback without jq
        grep -oP '"\K[^"]+":\s*"\K[^"]+' "$MODULES_REGISTRY" | while read -r key; do
            read -r val
            if [ "$val" = "enabled" ]; then
                echo -e "  - ${key^^}: ${GREEN}enabled${NC}"
            else
                echo -e "  - ${key^^}: ${YELLOW}disabled${NC}"
            fi
        done 2>/dev/null || echo "  Unable to parse registry. Install 'jq' for formatted display."
    fi
}

toggle_module() {
    local mod=$1
    local action=$2 # "enabled" or "disabled"
    ensure_registry_exists
    
    # Lowercase module name
    mod=$(echo "$mod" | tr '[:upper:]' '[:lower:]')
    
    if ! grep -q "\"$mod\":" "$MODULES_REGISTRY"; then
        log_err "Module '$mod' is not registered in the extension suite."
        exit 1
    fi
    
    if command -v jq &>/dev/null; then
        local temp=$(mktemp)
        jq --arg k "$mod" --arg v "$action" '.[$k] = $v' "$MODULES_REGISTRY" > "$temp"
        mv "$temp" "$MODULES_REGISTRY"
    else
        # Simple sed replacement
        sed -i "s/\"$mod\":.*/\"$mod\": \"$action\",/" "$MODULES_REGISTRY"
    fi
    
    log_info "Module '$mod' has been successfully ${action}."
    
    # Run artisan optimize clear if enabled
    docker compose exec -T --user www-data app php artisan optimize:clear &>/dev/null || true
}

install_module() {
    local mod=$1
    log_info "Installing module package '$mod'..."
    toggle_module "$mod" "enabled"
    log_info "Running migrations for '$mod'..."
    docker compose exec -T --user www-data app php artisan migrate --force || true
}

remove_module() {
    local mod=$1
    log_info "Uninstalling and disabling module package '$mod'..."
    toggle_module "$mod" "disabled"
}

COMMAND=${1:-""}
if [ -n "$COMMAND" ]; then
    shift
fi

case "$COMMAND" in
    list)
        list_modules
        ;;
    enable)
        if [ -z "${1:-}" ]; then log_err "Module name required."; exit 1; fi
        toggle_module "$1" "enabled"
        ;;
    disable)
        if [ -z "${1:-}" ]; then log_err "Module name required."; exit 1; fi
        toggle_module "$1" "disabled"
        ;;
    install)
        if [ -z "${1:-}" ]; then log_err "Module name required."; exit 1; fi
        install_module "$1"
        ;;
    remove)
        if [ -z "${1:-}" ]; then log_err "Module name required."; exit 1; fi
        remove_module "$1"
        ;;
    update)
        if [ -z "${1:-}" ]; then log_err "Module name required."; exit 1; fi
        log_info "Module update initiated for '$1'."
        ;;
    *)
        echo "Usage: hermes module [list|enable <mod>|disable <mod>|install <mod>|remove <mod>|update <mod>]"
        exit 1
        ;;
esac
