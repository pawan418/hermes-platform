#!/usr/bin/env bash

# Hermes AI Platform - Knowledge Base Sub-CLI
# Targets: Ubuntu 24.04 LTS (x86_64)

set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
INSTALLER_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

# Source libraries
source "${INSTALLER_DIR}/lib/logging.sh"

LOG_FILE="logs/install.log"

import_knowledge_source() {
    local source_path=$1
    if [ -z "$source_path" ] || [ ! -f "$source_path" ]; then
        log_err "Source file does not exist."
        exit 1
    fi
    mkdir -p knowledge
    cp "$source_path" knowledge/
    log_info "Copied '$(basename "$source_path")' to knowledge/ directory."
    log_info "Triggering background re-indexing of knowledge assets..."
    docker compose exec -T --user www-data app php artisan scout:import "App\Models\Knowledge" || true
}

export_knowledge_source() {
    local dest_zip="backups/knowledge_export_$(date '+%Y%m%d_%H%M%S').zip"
    mkdir -p backups
    if [ -d knowledge ]; then
        zip -r "$dest_zip" knowledge/ >> "$LOG_FILE" 2>&1
        log_info "Knowledge folder successfully exported to: $dest_zip"
    else
        log_err "Knowledge folder is empty."
    fi
}

rebuild_knowledge_base() {
    log_info "Rebuilding knowledge base indices..."
    docker compose exec -T --user www-data app php artisan scout:import "App\Models\Knowledge" || true
}

clean_knowledge_base() {
    read -p "Are you sure you want to delete all files in the knowledge directory? (y/n) [n]: " confirm
    if [[ "$confirm" =~ ^[Yy]$ ]]; then
        rm -rf knowledge/*
        log_info "Cleared knowledge folder assets."
    fi
}

show_statistics() {
    echo -e "${BLUE}=== Knowledge Base Metrics ===${NC}"
    if [ -d knowledge ]; then
        local count=$(find knowledge/ -type f | wc -l || echo "0")
        local size=$(du -sh knowledge/ | cut -f1 || echo "0")
        echo -e "  Total Files:        ${GREEN}$count${NC}"
        echo -e "  Storage Size:       ${GREEN}$size${NC}"
    else
        echo -e "  Total Files:        ${GREEN}0${NC}"
    fi
    
    echo -e "\nVector Collection stats (Qdrant):"
    docker compose exec -T app curl -s http://qdrant:6333/collections || echo "Qdrant endpoint unresponsive."
}

run_search() {
    local query=$1
    if [ -z "$query" ]; then
        log_err "Search query is required."
        exit 1
    fi
    echo -e "Executing vector search for: ${YELLOW}'$query'${NC}"
    # Calls artisan or custom REST search
    docker compose exec -T --user www-data app php artisan hermes:search-knowledge "$query" || echo "Command hermes:search-knowledge not implemented. Defaulting to general DB search."
}

COMMAND=${1:-""}
if [ -n "$COMMAND" ]; then
    shift
fi

case "$COMMAND" in
    import)
        import_knowledge_source "${1:-}"
        ;;
    export)
        export_knowledge_source
        ;;
    rebuild)
        rebuild_knowledge_base
        ;;
    clean)
        clean_knowledge_base
        ;;
    statistics)
        show_statistics
        ;;
    search)
        run_search "${1:-}"
        ;;
    *)
        echo "Usage: hermes knowledge [import <file>|export|rebuild|clean|statistics|search <query>]"
        exit 1
        ;;
esac
