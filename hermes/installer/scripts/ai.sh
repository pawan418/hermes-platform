#!/usr/bin/env bash

# Hermes AI Platform - AI Namespace Administration Tool
# Targets: Ubuntu 24.04 LTS (x86_64)

set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
INSTALLER_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

# Source libraries
source "${INSTALLER_DIR}/lib/logging.sh"

LOG_FILE="logs/install.log"

if [ ! -f .env ]; then
    log_err "Error: .env file missing."
    exit 1
fi

get_env_val() {
    local key=$1
    local val
    val=$(grep -E "^${key}=" .env | head -n1 | cut -d'=' -f2- | tr -d '\r\n' | sed 's/^"//;s/"$//' || echo "")
    echo "$val"
}

LLM_PROVIDER=$(get_env_val "DEFAULT_LLM_PROVIDER")
OPENAI_KEY=$(get_env_val "OPENAI_API_KEY")

test_provider() {
    echo -e "Testing active LLM Provider: ${GREEN}${LLM_PROVIDER}${NC}"
    if [ "$LLM_PROVIDER" = "openai" ]; then
        if [ -z "$OPENAI_KEY" ]; then
            log_err "OpenAI API Key is empty."
            exit 1
        fi
        local code
        code=$(curl -s -o /dev/null -w "%{http_code}" \
            -H "Authorization: Bearer $OPENAI_KEY" \
            https://api.openai.com/v1/models || echo "000")
        if [ "$code" = "200" ]; then
            log_info "OpenAI connectivity check passed (HTTP 200)."
        else
            log_err "OpenAI API returned error code: $code"
            exit 1
        fi
    else
        log_warn "Test for provider '$LLM_PROVIDER' not implemented. Connectivity test skipped."
    fi
}

list_models() {
    echo -e "Listing models for: ${GREEN}${LLM_PROVIDER}${NC}"
    if [ "$LLM_PROVIDER" = "openai" ]; then
        if [ -z "$OPENAI_KEY" ]; then
            log_err "OpenAI API Key is empty."
            exit 1
        fi
        curl -s -H "Authorization: Bearer $OPENAI_KEY" https://api.openai.com/v1/models | \
            grep -oP '"id":\s*"\K[^"]+' | head -n 15 || echo "No models resolved."
    else
        echo "Local model directory not resolved. Using Ollama fallback tags:"
        curl -s http://localhost:11434/api/tags 2>/dev/null || echo "Ollama offline."
    fi
}

generate_embedding() {
    local text="${1:-Hermes AI Platform test text}"
    echo -e "Generating test embedding vector for: ${YELLOW}'$text'${NC}"
    if [ "$LLM_PROVIDER" = "openai" ]; then
        if [ -z "$OPENAI_KEY" ]; then
            log_err "OpenAI API Key is empty."
            exit 1
        fi
        
        local resp
        resp=$(curl -s -X POST https://api.openai.com/v1/embeddings \
            -H "Content-Type: application/json" \
            -H "Authorization: Bearer $OPENAI_KEY" \
            -d "{\"input\": \"$text\", \"model\": \"text-embedding-3-small\"}" || echo "")
            
        if echo "$resp" | grep -q "embedding"; then
            local dims
            dims=$(echo "$resp" | grep -o '"embedding":\s*\[[^]]*\]' | grep -o ',' | wc -l || echo "0")
            dims=$((dims+1))
            log_info "Embedding created successfully (Dimensions: $dims)."
        else
            log_err "Failed to generate embedding: $resp"
            exit 1
        fi
    else
        log_warn "Embedding generation not configured for $LLM_PROVIDER."
    fi
}

start_chat_console() {
    if [ "$LLM_PROVIDER" != "openai" ]; then
        log_err "Chat console currently only supports OpenAI driver."
        exit 1
    fi
    if [ -z "$OPENAI_KEY" ]; then
        log_err "OpenAI API Key not configured."
        exit 1
    fi

    echo -e "${BLUE}====================================================${NC}"
    echo -e "${BLUE}        Hermes AI Platform Terminal Chat Console     ${NC}"
    echo -e "${BLUE}====================================================${NC}"
    echo -e "Type 'exit' or 'quit' to terminate chat."
    
    while true; do
        read -p "You > " prompt_input
        if [ "$prompt_input" = "exit" ] || [ "$prompt_input" = "quit" ] || [ -z "$prompt_input" ]; then
            break
        fi
        
        echo -n -e "AI  > ${YELLOW}Thinking...${NC}"
        
        # Escape double quotes for json
        local escaped_prompt
        escaped_prompt=$(echo "$prompt_input" | sed 's/"/\\"/g')
        
        local response
        response=$(curl -s -X POST https://api.openai.com/v1/chat/completions \
            -H "Content-Type: application/json" \
            -H "Authorization: Bearer $OPENAI_KEY" \
            -d "{\"model\": \"gpt-4o-mini\", \"messages\": [{\"role\": \"user\", \"content\": \"$escaped_prompt\"}]}" || echo "")
            
        # Delete "Thinking..."
        printf "\rAI  > "
        
        local reply
        reply=$(echo "$response" | grep -oP '"content":\s*"\K[^"]+' | head -n1 || echo "Error: Unable to fetch reply.")
        
        # Unescape newlines
        echo -e "${GREEN}$(echo -e "$reply" | sed 's/\\n/\n/g')${NC}\n"
    done
}

run_prompt_test() {
    local prompt="${1:-Hello, who are you?}"
    echo -e "Sending prompt test: ${YELLOW}'$prompt'${NC}"
    if [ "$LLM_PROVIDER" = "openai" ] && [ -n "$OPENAI_KEY" ]; then
        local start_time
        start_time=$(date +%s.%N)
        local resp
        resp=$(curl -s -X POST https://api.openai.com/v1/chat/completions \
            -H "Content-Type: application/json" \
            -H "Authorization: Bearer $OPENAI_KEY" \
            -d "{\"model\": \"gpt-4o-mini\", \"messages\": [{\"role\": \"user\", \"content\": \"$prompt\"}]}" || echo "")
        local end_time
        end_time=$(date +%s.%N)
        
        # Calculate diff using awk
        local diff
        diff=$(awk -v start="$start_time" -v end="$end_time" 'BEGIN {print end - start}')
        
        local reply
        reply=$(echo "$resp" | grep -oP '"content":\s*"\K[^"]+' | head -n1 || echo "Error")
        echo -e "Response Received: ${GREEN}$reply${NC}"
        echo -e "Execution Latency: ${GREEN}${diff} seconds${NC}"
    else
        log_err "AI provider credentials unverified."
    fi
}

benchmark_provider() {
    echo -e "Benchmarking OpenAI completions speed..."
    run_prompt_test "Write a 50 word description of enterprise AI platforms."
}

index_documents() {
    log_info "Calling Laravel Artisan Scout to index model collections..."
    docker compose exec -T --user www-data app php artisan scout:import "App\Models\User" || true
    docker compose exec -T --user www-data app php artisan scout:import "App\Models\Tenant" || true
}

COMMAND=${1:-""}
if [ -n "$COMMAND" ]; then
    shift
fi

case "$COMMAND" in
    test)
        test_provider
        ;;
    chat)
        start_chat_console
        ;;
    models)
        list_models
        ;;
    providers)
        echo -e "Default LLM Provider:       ${GREEN}$(get_env_val "DEFAULT_LLM_PROVIDER")${NC}"
        echo -e "Default Embedding Provider: ${GREEN}$(get_env_val "DEFAULT_EMBEDDING_PROVIDER")${NC}"
        ;;
    embeddings)
        generate_embedding "${1:-}"
        ;;
    index|reindex)
        index_documents
        ;;
    documents)
        echo "Index stats:"
        docker compose exec -T app php artisan scout:status || echo "Laravel scout status not implemented."
        ;;
    prompt-test)
        run_prompt_test "${1:-}"
        ;;
    benchmark)
        benchmark_provider
        ;;
    *)
        echo "Usage: hermes ai [test|chat|models|providers|embeddings|index|reindex|documents|prompt-test|benchmark]"
        exit 1
        ;;
esac
