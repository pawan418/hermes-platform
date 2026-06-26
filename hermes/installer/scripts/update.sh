#!/usr/bin/env bash

# Hermes AI Platform - Interactive Configurations Tool
# Targets: Ubuntu 24.04 LTS (x86_64)

set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
INSTALLER_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

# Source libraries
source "${INSTALLER_DIR}/lib/logging.sh"
source "${INSTALLER_DIR}/lib/wizard.sh"
source "${INSTALLER_DIR}/lib/security.sh"

LOG_FILE="logs/install.log"

if [ ! -f .env ]; then
    log_err "Error: Environment configurations file .env not found."
    exit 1
fi

get_env_val() {
    local key=$1
    local val
    val=$(grep -E "^${key}=" .env | head -n1 | cut -d'=' -f2- | tr -d '\r\n' | sed 's/^"//;s/"$//' || echo "")
    echo "$val"
}

update_env_key() {
    local key=$1
    local val=$2
    local escaped_val
    escaped_val=$(echo "$val" | sed 's/[&/\]/\\&/g')

    if grep -q "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=\"${escaped_val}\"|" .env
    else
        echo "${key}=\"${val}\"" >> .env
    fi
}

while true; do
    echo -e "\n${BLUE}====================================================${NC}"
    echo -e "${BLUE}        Hermes Interactive Configuration Manager     ${NC}"
    echo -e "${BLUE}====================================================${NC}"
    echo -e "1) Configure AI Providers (OpenAI, Gemini, Anthropic, Ollama)"
    echo -e "2) Configure WhatsApp API Channel"
    echo -e "3) Configure Twilio Voice Channel"
    echo -e "4) Configure SMTP Mail Settings"
    echo -e "5) Apply Configurations (Restart and Optimize Services)"
    echo -e "6) Exit"
    echo -e "${BLUE}====================================================${NC}"
    read -p "Select option (1-6): " OPTION

    case $OPTION in
        1)
            echo -e "\n${YELLOW}--- AI Providers Settings ---${NC}"
            CUR_PROVIDER=$(get_env_val "DEFAULT_LLM_PROVIDER")
            CUR_EMBEDDING=$(get_env_val "DEFAULT_EMBEDDING_PROVIDER")
            CUR_OPENAI=$(get_env_val "OPENAI_API_KEY")
            CUR_GEMINI=$(get_env_val "GEMINI_API_KEY")
            CUR_ANTHROPIC=$(get_env_val "ANTHROPIC_API_KEY")
            CUR_OLLAMA=$(get_env_val "OLLAMA_HOST")

            prompt_string "Default LLM Provider (openai/gemini/anthropic/ollama)" "$CUR_PROVIDER" NEW_PROVIDER "false"
            prompt_string "Default Embedding Provider (openai/ollama)" "$CUR_EMBEDDING" NEW_EMBEDDING "false"
            prompt_string "OpenAI API Key" "$CUR_OPENAI" NEW_OPENAI "true"
            prompt_string "Gemini API Key" "$CUR_GEMINI" NEW_GEMINI "true"
            prompt_string "Anthropic API Key" "$CUR_ANTHROPIC" NEW_ANTHROPIC "true"
            prompt_string "Ollama Endpoint" "$CUR_OAMA" NEW_OLLAMA "false"

            if [ "$NEW_OPENAI" != "$CUR_OPENAI" ] && [ -n "$NEW_OPENAI" ]; then
                log_info "Verifying OpenAI API Key..." "$LOG_FILE"
                if validate_openai_auth "$NEW_OPENAI"; then
                    log_info "OpenAI Key verified successfully." "$LOG_FILE"
                else
                    log_warn "OpenAI API key verification failed." "$LOG_FILE"
                    read -p "Proceed with this key anyway? (y/n) [n]: " proceed_key
                    if [[ ! "$proceed_key" =~ ^[Yy]$ ]]; then
                        NEW_OPENAI=$CUR_OPENAI
                    fi
                fi
            fi

            update_env_key "DEFAULT_LLM_PROVIDER" "$NEW_PROVIDER"
            update_env_key "DEFAULT_EMBEDDING_PROVIDER" "$NEW_EMBEDDING"
            update_env_key "OPENAI_API_KEY" "$NEW_OPENAI"
            update_env_key "GEMINI_API_KEY" "$NEW_GEMINI"
            update_env_key "ANTHROPIC_API_KEY" "$NEW_ANTHROPIC"
            update_env_key "OLLAMA_HOST" "$NEW_OLLAMA"
            echo -e "${GREEN}✓ AI settings saved.${NC}"
            ;;
        2)
            echo -e "\n${YELLOW}--- WhatsApp Integration Settings ---${NC}"
            CUR_WA_TOKEN=$(get_env_val "WHATSAPP_TOKEN")
            CUR_WA_PHONE_ID=$(get_env_val "WHATSAPP_PHONE_NUMBER_ID")
            CUR_WA_BIZ_ID=$(get_env_val "WHATSAPP_BUSINESS_ACCOUNT_ID")
            CUR_WA_VERIFY=$(get_env_val "WHATSAPP_VERIFY_TOKEN")

            prompt_string "Meta WhatsApp Token" "$CUR_WA_TOKEN" NEW_WA_TOKEN "true"
            prompt_string "Phone Number ID" "$CUR_WA_PHONE_ID" NEW_WA_PHONE_ID "false"
            prompt_string "Business Account ID" "$CUR_WA_BIZ_ID" NEW_WA_BIZ_ID "false"
            prompt_string "Verify Token (Webhook handshakes)" "$CUR_WA_VERIFY" NEW_WA_VERIFY "false"

            if [ "$NEW_WA_TOKEN" != "$CUR_WA_TOKEN" ] && [ -n "$NEW_WA_TOKEN" ]; then
                if ! validate_whatsapp_credentials "$NEW_WA_TOKEN" "$NEW_WA_PHONE_ID"; then
                    log_warn "WhatsApp token formats verified as suspicious (usually begins EA...)." "$LOG_FILE"
                    read -p "Keep this token anyway? (y/n) [n]: " proceed_wa
                    if [[ ! "$proceed_wa" =~ ^[Yy]$ ]]; then
                        NEW_WA_TOKEN=$CUR_WA_TOKEN
                    fi
                fi
            fi

            update_env_key "WHATSAPP_TOKEN" "$NEW_WA_TOKEN"
            update_env_key "WHATSAPP_PHONE_NUMBER_ID" "$NEW_WA_PHONE_ID"
            update_env_key "WHATSAPP_BUSINESS_ACCOUNT_ID" "$NEW_WA_BIZ_ID"
            update_env_key "WHATSAPP_VERIFY_TOKEN" "$NEW_WA_VERIFY"
            echo -e "${GREEN}✓ WhatsApp settings saved.${NC}"
            ;;
        3)
            echo -e "\n${YELLOW}--- Twilio Voice Settings ---${NC}"
            CUR_TW_SID=$(get_env_val "TWILIO_ACCOUNT_SID")
            CUR_TW_TOKEN=$(get_env_val "TWILIO_AUTH_TOKEN")
            CUR_TW_NUM=$(get_env_val "TWILIO_PHONE_NUMBER")

            prompt_string "Twilio Account SID" "$CUR_TW_SID" NEW_TW_SID "false"
            prompt_string "Twilio Auth Token" "$CUR_TW_TOKEN" NEW_TW_TOKEN "true"
            prompt_string "Twilio Phone Number" "$CUR_TW_NUM" NEW_TW_NUM "false"

            update_env_key "TWILIO_ACCOUNT_SID" "$NEW_TW_SID"
            update_env_key "TWILIO_AUTH_TOKEN" "$NEW_TW_TOKEN"
            update_env_key "TWILIO_PHONE_NUMBER" "$NEW_TW_NUM"
            echo -e "${GREEN}✓ Twilio settings saved.${NC}"
            ;;
        4)
            echo -e "\n${YELLOW}--- SMTP Mail Settings ---${NC}"
            CUR_SMTP_HOST=$(get_env_val "MAIL_HOST")
            CUR_SMTP_PORT=$(get_env_val "MAIL_PORT")
            CUR_SMTP_USER=$(get_env_val "MAIL_USERNAME")
            CUR_SMTP_PASS=$(get_env_val "MAIL_PASSWORD")
            CUR_SMTP_ENC=$(get_env_val "MAIL_ENCRYPTION")

            prompt_string "SMTP Host" "$CUR_SMTP_HOST" NEW_SMTP_HOST "false"
            prompt_string "SMTP Port" "$CUR_SMTP_PORT" NEW_SMTP_PORT "false"
            prompt_string "SMTP Username" "$CUR_SMTP_USER" NEW_SMTP_USER "false"
            prompt_string "SMTP Password" "$CUR_SMTP_PASS" NEW_SMTP_PASS "true"
            prompt_string "SMTP Encryption (tls/ssl/null)" "$CUR_SMTP_ENC" NEW_SMTP_ENC "false"

            update_env_key "MAIL_HOST" "$NEW_SMTP_HOST"
            update_env_key "MAIL_PORT" "$NEW_SMTP_PORT"
            update_env_key "MAIL_USERNAME" "$NEW_SMTP_USER"
            update_env_key "MAIL_PASSWORD" "$NEW_SMTP_PASS"
            update_env_key "MAIL_ENCRYPTION" "$NEW_SMTP_ENC"
            echo -e "${GREEN}✓ SMTP settings saved.${NC}"
            ;;
        5)
            echo -e "\n${YELLOW}Applying environment changes and resetting cache...${NC}"
            docker compose restart app queue scheduler >> "$LOG_FILE" 2>&1
            docker compose exec -T --user www-data app php artisan optimize
            docker compose exec -T --user www-data app php artisan queue:restart
            
            # Send test mail if SMTP is configured
            local has_smtp=$(get_env_val "MAIL_PASSWORD")
            if [ -n "$has_smtp" ]; then
                local mail_to=$(get_env_val "MAIL_FROM_ADDRESS")
                log_info "Testing SMTP outbound..." "$LOG_FILE"
                if docker compose exec -T --user www-data app php artisan hermes:test-smtp --to="$mail_to"; then
                    log_info "SMTP connection test succeeded." "$LOG_FILE"
                else
                    log_warn "SMTP connection test failed. Check logs/install.log." "$LOG_FILE"
                fi
            fi
            
            echo -e "${GREEN}✓ Configurations applied successfully.${NC}"
            ;;
        6)
            echo -e "${YELLOW}Exiting configuration manager.${NC}"
            break
            ;;
        *)
            echo -e "${RED}Invalid option selected.${NC}"
            ;;
    esac
done
