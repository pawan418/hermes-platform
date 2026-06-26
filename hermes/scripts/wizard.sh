#!/usr/bin/env bash

# Hermes AI Platform - Interactive Wizard Module
# Targets: Ubuntu 24.04 LTS (x86_64)

set -o pipefail
set -o errexit

# Load shared library
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/common.sh"

WIZARD_DATA=".wizard_env"
rm -f "$WIZARD_DATA"
touch "$WIZARD_DATA"
chmod 600 "$WIZARD_DATA"

# Detect existing installation
detect_existing_installation() {
    if [ -f .env ] && [ -f docker-compose.yml ]; then
        echo -e "${YELLOW}====================================================${NC}"
        echo -e "${YELLOW}       Existing Hermes Installation Detected        ${NC}"
        echo -e "${YELLOW}====================================================${NC}"
        echo "1) Fresh Installation (WARNING: Overwrites data!)"
        echo "2) Upgrade Existing Installation"
        echo "3) Repair Installation"
        echo "4) Exit"
        read -p "Select option [2]: " existing_opt
        existing_opt=${existing_opt:-2}
        
        case "$existing_opt" in
            1)
                read -p "Are you absolutely sure you want to perform a Fresh Installation? This will wipe your existing database! (y/n) [n]: " confirm_fresh
                if [[ ! "$confirm_fresh" =~ ^[Yy]$ ]]; then
                    log_info "Aborted fresh installation. Exiting."
                    exit 0
                fi
                echo "INSTALL_ACTION=fresh" >> "$WIZARD_DATA"
                ;;
            2)
                echo "INSTALL_ACTION=upgrade" >> "$WIZARD_DATA"
                ;;
            3)
                echo "INSTALL_ACTION=repair" >> "$WIZARD_DATA"
                ;;
            *)
                log_info "Exiting installer."
                exit 0
                ;;
        esac
    else
        echo "INSTALL_ACTION=fresh" >> "$WIZARD_DATA"
    fi
}

configure_profile() {
    echo -e "\n${BLUE}Select Installation Profile:${NC}"
    echo "1) Development (Debug enabled, local testing config)"
    echo "2) Production  (Optimized, production settings)"
    echo "3) Enterprise  (Optimized, monitoring active, hardened config)"
    read -p "Select option [2]: " profile_opt
    profile_opt=${profile_opt:-2}
    
    case "$profile_opt" in
        1)
            echo "APP_PROFILE=development" >> "$WIZARD_DATA"
            echo "APP_DEBUG=true" >> "$WIZARD_DATA"
            echo "APP_ENV=local" >> "$WIZARD_DATA"
            ;;
        3)
            echo "APP_PROFILE=enterprise" >> "$WIZARD_DATA"
            echo "APP_DEBUG=false" >> "$WIZARD_DATA"
            echo "APP_ENV=production" >> "$WIZARD_DATA"
            ;;
        *)
            echo "APP_PROFILE=production" >> "$WIZARD_DATA"
            echo "APP_DEBUG=false" >> "$WIZARD_DATA"
            echo "APP_ENV=production" >> "$WIZARD_DATA"
            ;;
    esac
}

configure_timezone() {
    echo -e "\n${BLUE}Configure System Timezone:${NC}"
    read -p "Enter default timezone [Asia/Kolkata]: " tz_input
    tz_input=${tz_input:-Asia/Kolkata}
    
    # Check if timezone is valid on Linux system
    if [ -f "/usr/share/zoneinfo/$tz_input" ]; then
        echo "SYSTEM_TIMEZONE=$tz_input" >> "$WIZARD_DATA"
        log_info "Timezone set to $tz_input."
    else
        log_warn "Timezone $tz_input is invalid or not found. Defaulting to Asia/Kolkata."
        echo "SYSTEM_TIMEZONE=Asia/Kolkata" >> "$WIZARD_DATA"
    fi
}

configure_admin_account() {
    echo -e "\n${BLUE}Configure Administrator Account:${NC}"
    read -p "Admin Name [Administrator]: " admin_name
    admin_name=${admin_name:-Administrator}
    echo "ADMIN_NAME=\"$admin_name\"" >> "$WIZARD_DATA"
    
    while true; do
        read -p "Admin Email [admin@lspl.xyz]: " admin_email
        admin_email=${admin_email:-admin@lspl.xyz}
        if [[ "$admin_email" =~ ^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$ ]]; then
            echo "ADMIN_EMAIL=\"$admin_email\"" >> "$WIZARD_DATA"
            break
        else
            echo -e "${RED}Invalid email format. Try again.${NC}"
        fi
    done
    
    echo -e "\n${BLUE}Configure Administrator Password:${NC}"
    echo "1) Auto-generate a secure random password (recommended)"
    echo "2) Enter custom password"
    read -p "Select option [1]: " pass_choice
    pass_choice=${pass_choice:-1}
    
    if [ "$pass_choice" = "1" ]; then
        local auto_pass
        auto_pass=$(generate_secure_password 16)
        echo "ADMIN_PASS=\"$auto_pass\"" >> "$WIZARD_DATA"
        echo "AUTO_GENERATED_PASS=true" >> "$WIZARD_DATA"
        log_info "Secure password will be generated and displayed at completion."
    else
        while true; do
            read -s -p "Enter custom password: " custom_pass
            echo ""
            
            validate_password_strength "$custom_pass"
            local strength_status=$?
            if [ $strength_status -ne 0 ]; then
                echo -e "${RED}Password is too weak. Requirements:${NC}"
                echo "- Minimum 8 characters"
                echo "- At least one uppercase letter (A-Z)"
                echo "- At least one lowercase letter (a-z)"
                echo "- At least one number (0-9)"
                echo "- At least one symbol (!@#%^*)"
                continue
            fi
            
            read -s -p "Confirm custom password: " confirm_pass
            echo ""
            
            if [ "$custom_pass" = "$confirm_pass" ]; then
                echo "ADMIN_PASS=\"$custom_pass\"" >> "$WIZARD_DATA"
                echo "AUTO_GENERATED_PASS=false" >> "$WIZARD_DATA"
                break
            else
                echo -e "${RED}Passwords do not match. Try again.${NC}"
            fi
        done
    fi
}

configure_openai() {
    echo -e "\n${BLUE}OpenAI API Key Configuration:${NC}"
    read -p "Do you want to configure OpenAI API key now? (y/n) [n]: " config_openai
    config_openai=${config_openai:-n}
    
    if [[ "$config_openai" =~ ^[Yy]$ ]]; then
        while true; do
            read -s -p "Enter OpenAI API Key: " openai_key
            echo ""
            
            if [ -z "$openai_key" ]; then
                echo -e "${YELLOW}API Key cannot be blank.${NC}"
                continue
            fi
            
            log_info "Verifying OpenAI API Key connection..."
            local http_code
            http_code=$(curl -s -o /dev/null -w "%{http_code}" \
                -H "Authorization: Bearer $openai_key" \
                https://api.openai.com/v1/models || echo "000")
            
            if [ "$http_code" = "200" ]; then
                log_info "OpenAI API Key verified successfully."
                echo "OPENAI_API_KEY=\"$openai_key\"" >> "$WIZARD_DATA"
                break
            else
                log_warn "OpenAI API Key verification failed (HTTP Status: $http_code)."
                echo "1) Retry entering key"
                echo "2) Proceed with this key anyway"
                echo "3) Skip OpenAI configuration"
                read -p "Select option [1]: " verify_opt
                verify_opt=${verify_opt:-1}
                
                if [ "$verify_opt" = "2" ]; then
                    echo "OPENAI_API_KEY=\"$openai_key\"" >> "$WIZARD_DATA"
                    break
                elif [ "$verify_opt" = "3" ]; then
                    echo "OPENAI_API_KEY=\"\"" >> "$WIZARD_DATA"
                    break
                fi
            fi
        done
    else
        echo "OPENAI_API_KEY=\"\"" >> "$WIZARD_DATA"
    fi
}

configure_smtp() {
    echo -e "\n${BLUE}SMTP Configuration:${NC}"
    read -p "Do you want to configure SMTP Mail settings now? (y/n) [n]: " config_smtp
    config_smtp=${config_smtp:-n}
    
    if [[ "$config_smtp" =~ ^[Yy]$ ]]; then
        read -p "SMTP Host [smtp.mailgun.org]: " smtp_host
        smtp_host=${smtp_host:-smtp.mailgun.org}
        
        read -p "SMTP Port [587]: " smtp_port
        smtp_port=${smtp_port:-587}
        
        read -p "SMTP Username [postmaster@yourdomain.com]: " smtp_user
        smtp_user=${smtp_user:-postmaster@yourdomain.com}
        
        read -s -p "SMTP Password: " smtp_pass
        echo ""
        
        read -p "SMTP Encryption (tls/ssl/none) [tls]: " smtp_enc
        smtp_enc=${smtp_enc:-tls}
        if [ "$smtp_enc" = "none" ]; then smtp_enc="null"; fi
        
        echo "SMTP_CONFIGURED=true" >> "$WIZARD_DATA"
        echo "SMTP_HOST=\"$smtp_host\"" >> "$WIZARD_DATA"
        echo "SMTP_PORT=\"$smtp_port\"" >> "$WIZARD_DATA"
        echo "SMTP_USER=\"$smtp_user\"" >> "$WIZARD_DATA"
        echo "SMTP_PASS=\"$smtp_pass\"" >> "$WIZARD_DATA"
        echo "SMTP_ENC=\"$smtp_enc\"" >> "$WIZARD_DATA"
    else
        echo "SMTP_CONFIGURED=false" >> "$WIZARD_DATA"
    fi
}

run_wizard() {
    detect_existing_installation
    configure_profile
    configure_timezone
    configure_admin_account
    configure_openai
    configure_smtp
    
    log_info "Wizard configurations completed. Saved settings."
}

if [ "${BASH_SOURCE[0]}" = "$0" ]; then
    run_wizard
fi
