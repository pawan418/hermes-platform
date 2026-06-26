#!/usr/bin/env bash

# Hermes AI Platform - Modular Installer Orchestrator
# Targets: Ubuntu 24.04 LTS (x86_64)

set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
INSTALLER_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

# Source libraries
source "${INSTALLER_DIR}/lib/logging.sh"
source "${INSTALLER_DIR}/lib/version.sh"
source "${INSTALLER_DIR}/lib/security.sh"
source "${INSTALLER_DIR}/lib/network.sh"
source "${INSTALLER_DIR}/lib/docker.sh"
source "${INSTALLER_DIR}/lib/wizard.sh"
source "${INSTALLER_DIR}/lib/backup.sh"
source "${INSTALLER_DIR}/lib/health.sh"

LOG_FILE="logs/install.log"
touch "$LOG_FILE"
chmod 600 "$LOG_FILE"

# Track current step for the recovery menu
CURRENT_STEP=1
WIZARD_FILE=".wizard_env"

show_installer_banner() {
    echo -e "${BLUE}====================================================${NC}"
    echo -e "${BLUE}         Hermes AI Enterprise Installer v1.0        ${NC}"
    echo -e "${BLUE}====================================================${NC}"
}

# Graphical Progress Bar
print_step_progress() {
    local step=$1
    local total=7
    local message=$2
    local percent=$(( (step * 100) / total ))
    local completed=$(( percent / 5 ))
    local remaining=$(( 20 - completed ))
    
    local bar=""
    for ((i=0; i<completed; i++)); do bar="${bar}█"; done
    for ((i=0; i<remaining; i++)); do bar="${bar}░"; done
    
    echo -e "\n${BLUE}Step ${step}/${total} [${bar}] ${percent}% - ${message}${NC}"
}

# Centralized Recovery Menu
handle_step_failure() {
    local failed_step=$1
    echo -e "\n${RED}✗ Execution failed at Step $failed_step/7.${NC}" >&2
    
    while true; do
        echo -e "\n${YELLOW}=== Recovery Menu ===${NC}"
        echo "1) Retry the failed step"
        echo "2) Repair the installation (Reset permissions and configs)"
        echo "3) Restore from a previous backup"
        echo "4) View error logs"
        echo "5) Exit installation"
        read -p "Select recovery action [1]: " choice
        choice=${choice:-1}
        
        case "$choice" in
            1)
                log_info "Retrying Step $failed_step..." "$LOG_FILE"
                return 0 # Continue execution flow
                ;;
            2)
                log_info "Launching repair.sh..." "$LOG_FILE"
                bash "${SCRIPT_DIR}/repair.sh" || true
                ;;
            3)
                log_info "Launching restore.sh..." "$LOG_FILE"
                read -p "Enter backup archive path: " backup_path
                if [ -f "$backup_path" ]; then
                    bash "${SCRIPT_DIR}/restore.sh" "$backup_path" --force || true
                else
                    log_err "Backup file not found." "$LOG_FILE"
                fi
                ;;
            4)
                if [ -f logs/error.log ]; then
                    echo -e "\n${YELLOW}=== logs/error.log ===${NC}"
                    tail -n 30 logs/error.log
                else
                    echo -e "\n${YELLOW}No error log file found.${NC}"
                fi
                ;;
            5)
                log_info "Exiting installer." "$LOG_FILE"
                exit 1
                ;;
            *)
                echo -e "${RED}Invalid selection.${NC}"
                ;;
        esac
    done
}

run_step_with_recovery() {
    local step_no=$1
    local step_name=$2
    local step_command=$3
    
    CURRENT_STEP=$step_no
    print_step_progress "$step_no" "$step_name"
    
    while true; do
        if eval "$step_command"; then
            break
        else
            handle_step_failure "$step_no"
        fi
    done
}

# ----------------------------------------------------
# INSTALLER STEPS DEFINITION
# ----------------------------------------------------

step_1_system_checks() {
    log_info "Starting pre-flight system validation checks..." "$LOG_FILE"
    
    # 1. OS details
    if [ -f /etc/os-release ]; then
        local os_name=$(grep -oP 'NAME="\K[^"]+' /etc/os-release)
        local os_version=$(grep -oP 'VERSION_ID="\K[^"]+' /etc/os-release)
        if [[ "$os_name" != *"Ubuntu"* ]] || [ "$os_version" != "24.04" ]; then
            log_warn "Ubuntu 24.04 LTS is highly recommended (Detected: $os_name $os_version)." "$LOG_FILE"
        fi
    fi

    # 2. CPU/RAM check
    local cpu_cores=$(nproc)
    if [ "$cpu_cores" -lt 4 ]; then
        log_err "Requires minimum 4 CPU cores (Detected: $cpu_cores)." "$LOG_FILE"
        return 1
    fi

    local total_ram_kb=$(awk '/MemTotal/ {print $2}' /proc/meminfo)
    local total_ram_gb=$((total_ram_kb / 1024 / 1024))
    if [ "$total_ram_gb" -lt 8 ]; then
        log_err "Requires minimum 8 GB RAM (Detected: $total_ram_gb GB)." "$LOG_FILE"
        return 1
    fi

    # 3. Disk checks
    local free_disk_gb=$(df -BG / | awk 'NR==2 {print $4}' | sed 's/G//')
    if [ "$free_disk_gb" -lt 80 ]; then
        log_err "Requires minimum 80 GB free disk space (Detected: $free_disk_gb GB)." "$LOG_FILE"
        return 1
    fi

    local inode_used_pct=$(df -i / | awk 'NR==2 {print $5}' | sed 's/%//')
    if [ "$inode_used_pct" -gt 95 ]; then
        log_err "Inode usage is critically high ($inode_used_pct%)." "$LOG_FILE"
        return 1
    fi

    # 4. Network
    if ! verify_network_connectivity; then
        log_err "Internet verification failed. Required repositories are unreachable." "$LOG_FILE"
        return 1
    fi

    log_info "Pre-flight checks passed." "$LOG_FILE"
    return 0
}

step_2_docker_validation() {
    validate_docker_setup "$LOG_FILE"
}

step_3_interactive_wizard() {
    rm -f "$WIZARD_FILE"
    touch "$WIZARD_FILE"
    chmod 600 "$WIZARD_FILE"
    
    # 1. Existing installation detection
    if [ -f .env ] && [ -f docker-compose.yml ]; then
        echo -e "\n${YELLOW}Existing Hermes installation detected!${NC}"
        echo "1) Fresh Installation (Wipes existing database and volumes!)"
        echo "2) Upgrade Existing Installation"
        echo "3) Repair Existing Installation"
        echo "4) Exit"
        read -p "Select option [2]: " existing_opt
        existing_opt=${existing_opt:-2}
        
        case "$existing_opt" in
            1)
                read -p "Are you absolutely sure? This cannot be undone! (y/n) [n]: " confirm_wipe
                if [[ ! "$confirm_wipe" =~ ^[Yy]$ ]]; then
                    log_info "Aborted fresh install. Exiting." "$LOG_FILE"
                    exit 0
                fi
                echo "INSTALL_ACTION=fresh" >> "$WIZARD_FILE"
                ;;
            2)
                echo "INSTALL_ACTION=upgrade" >> "$WIZARD_FILE"
                # Delegate to upgrade script immediately
                bash "${SCRIPT_DIR}/upgrade.sh"
                exit 0
                ;;
            3)
                echo "INSTALL_ACTION=repair" >> "$WIZARD_FILE"
                # Delegate to repair script immediately
                bash "${SCRIPT_DIR}/repair.sh"
                exit 0
                ;;
            *)
                log_info "Exiting." "$LOG_FILE"
                exit 0
                ;;
        esac
    else
        echo "INSTALL_ACTION=fresh" >> "$WIZARD_FILE"
    fi

    # 2. Installation Profile
    echo -e "\n${BLUE}Select Installation Profile:${NC}"
    echo "1) Development (Debug active, local configurations)"
    echo "2) Production  (Optimized Laravel caches, queues active)"
    echo "3) Enterprise  (Hardened presets, monitoring, auto-backups)"
    read -p "Select profile option [2]: " profile_opt
    profile_opt=${profile_opt:-2}
    
    case "$profile_opt" in
        1)
            echo "APP_PROFILE=development" >> "$WIZARD_FILE"
            echo "APP_DEBUG=true" >> "$WIZARD_FILE"
            echo "APP_ENV=local" >> "$WIZARD_FILE"
            ;;
        3)
            echo "APP_PROFILE=enterprise" >> "$WIZARD_FILE"
            echo "APP_DEBUG=false" >> "$WIZARD_FILE"
            echo "APP_ENV=production" >> "$WIZARD_FILE"
            ;;
        *)
            echo "APP_PROFILE=production" >> "$WIZARD_FILE"
            echo "APP_DEBUG=false" >> "$WIZARD_FILE"
            echo "APP_ENV=production" >> "$WIZARD_FILE"
            ;;
    esac

    # 3. Dynamic URLs
    echo -e "\n${BLUE}Configure URLs (No hardcoded localhost):${NC}"
    prompt_string "Application Primary URL" "http://localhost:8080" APP_URL "false"
    prompt_string "Application API URL" "$APP_URL/api" API_URL "false"
    prompt_string "Administration Portal URL" "$APP_URL/admin" ADMIN_URL "false"
    
    echo "APP_URL=\"$APP_URL\"" >> "$WIZARD_FILE"
    echo "API_URL=\"$API_URL\"" >> "$WIZARD_FILE"
    echo "ADMIN_URL=\"$ADMIN_URL\"" >> "$WIZARD_FILE"

    # 4. Timezone
    echo -e "\n${BLUE}Configure System Timezone:${NC}"
    read -p "Enter default timezone [Asia/Kolkata]: " tz_input
    tz_input=${tz_input:-Asia/Kolkata}
    if [ ! -f "/usr/share/zoneinfo/$tz_input" ]; then
        tz_input="Asia/Kolkata"
    fi
    echo "SYSTEM_TIMEZONE=\"$tz_input\"" >> "$WIZARD_FILE"

    # 5. Credentials
    echo -e "\n${BLUE}Configure Administrator Account:${NC}"
    read -p "Administrator Name [Administrator]: " admin_name
    admin_name=${admin_name:-Administrator}
    echo "ADMIN_NAME=\"$admin_name\"" >> "$WIZARD_FILE"
    
    while true; do
        read -p "Administrator Email [admin@lspl.xyz]: " admin_email
        admin_email=${admin_email:-admin@lspl.xyz}
        if [[ "$admin_email" =~ ^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$ ]]; then
            echo "ADMIN_EMAIL=\"$admin_email\"" >> "$WIZARD_FILE"
            break
        else
            echo -e "${RED}Invalid email format.${NC}"
        fi
    done

    echo "1) Auto-generate a secure random password (recommended)"
    echo "2) Enter custom password"
    read -p "Select option [1]: " pass_choice
    pass_choice=${pass_choice:-1}
    
    if [ "$pass_choice" = "1" ]; then
        local auto_pass=$(generate_secure_password 16)
        echo "ADMIN_PASS=\"$auto_pass\"" >> "$WIZARD_FILE"
        echo "AUTO_GENERATED_PASS=true" >> "$WIZARD_FILE"
    else
        while true; do
            read -s -p "Enter custom password: " custom_pass
            echo ""
            validate_password_strength "$custom_pass"
            local strength=$?
            if [ $strength -ne 0 ]; then
                echo -e "${RED}Password is too weak. Requirements: >=8 chars, uppercase, lowercase, digit, symbol(!@#%^*).${NC}"
                continue
            fi
            read -s -p "Confirm custom password: " confirm_pass
            echo ""
            if [ "$custom_pass" = "$confirm_pass" ]; then
                echo "ADMIN_PASS=\"$custom_pass\"" >> "$WIZARD_FILE"
                echo "AUTO_GENERATED_PASS=false" >> "$WIZARD_FILE"
                break
            else
                echo -e "${RED}Passwords do not match.${NC}"
            fi
        done
    fi

    # 6. OpenAI Verification
    echo -e "\n${BLUE}OpenAI Configuration:${NC}"
    read -p "Do you want to configure OpenAI API Key? (y/n) [n]: " config_openai
    config_openai=${config_openai:-n}
    if [[ "$config_openai" =~ ^[Yy]$ ]]; then
        while true; do
            read -s -p "Enter OpenAI API Key: " openai_key
            echo ""
            log_info "Verifying API key connectivity..." "$LOG_FILE"
            if validate_openai_auth "$openai_key"; then
                log_info "OpenAI Key verified." "$LOG_FILE"
                echo "OPENAI_API_KEY=\"$openai_key\"" >> "$WIZARD_FILE"
                break
            else
                log_warn "OpenAI verification failed." "$LOG_FILE"
                echo "1) Retry entering key"
                echo "2) Proceed anyway"
                echo "3) Skip OpenAI configuration"
                read -p "Select option [1]: " opt
                opt=${opt:-1}
                if [ "$opt" = "2" ]; then
                    echo "OPENAI_API_KEY=\"$openai_key\"" >> "$WIZARD_FILE"
                    break
                elif [ "$opt" = "3" ]; then
                    echo "OPENAI_API_KEY=\"\"" >> "$WIZARD_FILE"
                    break
                fi
            fi
        done
    else
        echo "OPENAI_API_KEY=\"\"" >> "$WIZARD_FILE"
    fi

    # 7. Google Credentials Verification
    echo -e "\n${BLUE}Google Authentication Configuration:${NC}"
    read -p "Do you want to configure Google OAuth credentials now? (y/n) [n]: " config_google
    config_google=${config_google:-n}
    if [[ "$config_google" =~ ^[Yy]$ ]]; then
        while true; do
            read -p "Google OAuth Client ID: " g_client_id
            read -s -p "Google OAuth Client Secret: " g_secret
            echo ""
            log_info "Verifying Google configuration..." "$LOG_FILE"
            validate_google_credentials "$g_client_id" "$g_secret"
            local g_status=$?
            if [ $g_status -eq 0 ]; then
                log_info "Google settings verified successfully." "$LOG_FILE"
                echo "GOOGLE_CLIENT_ID=\"$g_client_id\"" >> "$WIZARD_FILE"
                echo "GOOGLE_CLIENT_SECRET=\"$g_secret\"" >> "$WIZARD_FILE"
                break
            elif [ $g_status -eq 2 ]; then
                log_warn "Invalid Client ID format. Should end in 'apps.googleusercontent.com'." "$LOG_FILE"
            else
                log_warn "Unable to verify. (Network issues or keys incorrect)." "$LOG_FILE"
            fi
            read -p "Proceed anyway? (y/n) [n]: " g_proceed
            if [[ "$g_proceed" =~ ^[Yy]$ ]]; then
                echo "GOOGLE_CLIENT_ID=\"$g_client_id\"" >> "$WIZARD_FILE"
                echo "GOOGLE_CLIENT_SECRET=\"$g_secret\"" >> "$WIZARD_FILE"
                break
            fi
        done
    else
        echo "GOOGLE_CLIENT_ID=\"\"" >> "$WIZARD_FILE"
        echo "GOOGLE_CLIENT_SECRET=\"\"" >> "$WIZARD_FILE"
    fi

    # 8. WhatsApp Cloud Configuration
    echo -e "\n${BLUE}Meta WhatsApp API Configuration:${NC}"
    read -p "Do you want to configure WhatsApp now? (y/n) [n]: " config_wa
    config_wa=${config_wa:-n}
    if [[ "$config_wa" =~ ^[Yy]$ ]]; then
        while true; do
            read -s -p "Meta WhatsApp Token: " wa_token
            echo ""
            read -p "WhatsApp Phone Number ID: " wa_phone_id
            read -p "WhatsApp Business Account ID: " wa_biz_id
            
            validate_whatsapp_credentials "$wa_token" "$wa_phone_id"
            local wa_status=$?
            if [ $wa_status -eq 0 ]; then
                log_info "WhatsApp format structural verify passed." "$LOG_FILE"
                echo "WHATSAPP_TOKEN=\"$wa_token\"" >> "$WIZARD_FILE"
                echo "WHATSAPP_PHONE_NUMBER_ID=\"$wa_phone_id\"" >> "$WIZARD_FILE"
                echo "WHATSAPP_BUSINESS_ACCOUNT_ID=\"$wa_biz_id\"" >> "$WIZARD_FILE"
                break
            else
                log_warn "Credentials failed structural verification check." "$LOG_FILE"
                read -p "Proceed anyway? (y/n) [n]: " wa_proceed
                if [[ "$wa_proceed" =~ ^[Yy]$ ]]; then
                    echo "WHATSAPP_TOKEN=\"$wa_token\"" >> "$WIZARD_FILE"
                    echo "WHATSAPP_PHONE_NUMBER_ID=\"$wa_phone_id\"" >> "$WIZARD_FILE"
                    echo "WHATSAPP_BUSINESS_ACCOUNT_ID=\"$wa_biz_id\"" >> "$WIZARD_FILE"
                    break
                fi
            fi
        done
    else
        echo "WHATSAPP_TOKEN=\"\"" >> "$WIZARD_FILE"
        echo "WHATSAPP_PHONE_NUMBER_ID=\"\"" >> "$WIZARD_FILE"
        echo "WHATSAPP_BUSINESS_ACCOUNT_ID=\"\"" >> "$WIZARD_FILE"
    fi

    # 9. SMTP Configuration
    echo -e "\n${BLUE}SMTP configuration:${NC}"
    read -p "Do you want to configure SMTP Mail settings now? (y/n) [n]: " config_smtp
    config_smtp=${config_smtp:-n}
    if [[ "$config_smtp" =~ ^[Yy]$ ]]; then
        read -p "SMTP Host [smtp.mailgun.org]: " smtp_host
        smtp_host=${smtp_host:-smtp.mailgun.org}
        read -p "SMTP Port [587]: " smtp_port
        smtp_port=${smtp_port:-587}
        read -p "SMTP Username: " smtp_user
        read -s -p "SMTP Password: " smtp_pass
        echo ""
        read -p "SMTP Encryption (tls/ssl/none) [tls]: " smtp_enc
        smtp_enc=${smtp_enc:-tls}
        if [ "$smtp_enc" = "none" ]; then smtp_enc="null"; fi
        
        echo "SMTP_CONFIGURED=true" >> "$WIZARD_FILE"
        echo "SMTP_HOST=\"$smtp_host\"" >> "$WIZARD_FILE"
        echo "SMTP_PORT=\"$smtp_port\"" >> "$WIZARD_FILE"
        echo "SMTP_USER=\"$smtp_user\"" >> "$WIZARD_FILE"
        echo "SMTP_PASS=\"$smtp_pass\"" >> "$WIZARD_FILE"
        echo "SMTP_ENC=\"$smtp_enc\"" >> "$WIZARD_FILE"
    else
        echo "SMTP_CONFIGURED=false" >> "$WIZARD_FILE"
    fi
}

step_4_compile_env() {
    # Calls configure-env script to generate .env based on WIZARD_FILE
    bash "${SCRIPT_DIR}/configure-env.sh"
}

step_5_deploy_services() {
    # Calls install-services script
    bash "${SCRIPT_DIR}/install-services.sh"
}

step_6_initialize_laravel() {
    log_info "Initializing Laravel database and parameters..." "$LOG_FILE"
    
    # 1. APP_KEY generation (only generate if missing)
    if ! grep -q "^APP_KEY=base64:" .env || [ -z "$(grep "^APP_KEY=" .env | cut -d'=' -f2-)" ]; then
        log_info "APP_KEY is missing. Generating key..." "$LOG_FILE"
        docker compose exec -T --user www-data app php artisan key:generate --force --no-interaction >> "$LOG_FILE" 2>&1
    fi
    
    # 2. Database migrations and rollbacks
    log_info "Snapshotting database schema pre-migrations..." "$LOG_FILE"
    # Execute pg_dump
    local db_user=$(grep "^DB_USERNAME=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "hermes")
    local db_name=$(grep "^DB_DATABASE=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "hermes")
    local db_pass=$(grep "^DB_PASSWORD=" .env | cut -d'=' -f2- | tr -d '\r\n"' || echo "")
    
    local pre_mig_file=".pre_install_migration_backup.sql"
    rm -f "$pre_mig_file"
    execute_pg_dump "$db_user" "$db_name" "$db_pass" "$pre_mig_file" "$LOG_FILE" || true
    
    log_info "Running schema migrations..." "$LOG_FILE"
    if ! docker compose exec -T --user www-data app php artisan migrate --force >> "$LOG_FILE" 2>&1; then
        log_err "Laravel migration failed. Reverting database state..." "$LOG_FILE"
        if [ -f "$pre_mig_file" ]; then
            execute_pg_restore "$db_user" "$db_name" "$db_pass" "$pre_mig_file" "$LOG_FILE" || true
            rm -f "$pre_mig_file"
        fi
        return 1
    fi
    rm -f "$pre_mig_file"
    
    # 3. Seeding and Admin
    log_info "Running seeders..." "$LOG_FILE"
    docker compose exec -T --user www-data app php artisan db:seed --force >> "$LOG_FILE" 2>&1
    
    source "$WIZARD_FILE"
    log_info "Creating admin account..." "$LOG_FILE"
    docker compose exec -T --user www-data app php artisan hermes:create-admin \
        --name="$ADMIN_NAME" \
        --email="$ADMIN_EMAIL" \
        --password="$ADMIN_PASS" >> "$LOG_FILE" 2>&1

    # 4. Storage, bootstrap, caching optimizations matching profile
    log_info "Bootstrapping application storage links and collections..." "$LOG_FILE"
    docker compose exec -T --user www-data app php artisan storage:link >> "$LOG_FILE" 2>&1 || true
    docker compose exec -T --user www-data app php artisan hermes:bootstrap >> "$LOG_FILE" 2>&1
    
    if [ "$APP_PROFILE" = "development" ]; then
        docker compose exec -T --user www-data app php artisan optimize:clear >> "$LOG_FILE" 2>&1
    else
        # Production/Enterprise optimized caching
        log_info "Optimizing caches (config, route, views)..." "$LOG_FILE"
        docker compose exec -T --user www-data app php artisan optimize >> "$LOG_FILE" 2>&1
        docker compose exec -T --user www-data app php artisan config:cache >> "$LOG_FILE" 2>&1
        docker compose exec -T --user www-data app php artisan route:cache >> "$LOG_FILE" 2>&1
        docker compose exec -T --user www-data app php artisan view:cache >> "$LOG_FILE" 2>&1
    fi
    
    docker compose exec -T --user www-data app php artisan queue:restart >> "$LOG_FILE" 2>&1
    return 0
}

step_7_deep_diagnostics() {
    run_deep_diagnostics
}

display_installation_summary() {
    source "$WIZARD_FILE"
    
    local end_time=$(date '+%Y-%m-%d %H:%M:%S')
    local laravel_version=$(docker compose exec -T app php artisan --version 2>/dev/null || echo "Unknown")
    local php_version=$(docker compose exec -T app php -r 'echo PHP_VERSION;' 2>/dev/null || echo "Unknown")
    local pg_version=$(docker compose exec -T db psql --version 2>/dev/null || echo "16-alpine")
    local redis_version=$(docker compose exec -T redis redis-server --version 2>/dev/null | awk '{print $3}' || echo "7-alpine")
    local qdrant_version=$(docker compose exec -T app curl -s http://qdrant:6333/info 2>/dev/null | grep -oP '"version":\s*"\K[^"]+' || echo "Latest")
    local minio_version="Latest"
    local n8n_version="Latest"
    local hermes_version=$(cat "${INSTALLER_DIR}/VERSION" 2>/dev/null || echo "1.0.0")

    echo -e "\n${BLUE}====================================================${NC}"
    echo -e "${GREEN}      Hermes AI Platform Installed Successfully!   ${NC}"
    echo -e "${BLUE}====================================================${NC}"
    echo -e "Hermes Version:         ${GREEN}${hermes_version}${NC}"
    echo -e "Laravel Version:        ${GREEN}${laravel_version}${NC}"
    echo -e "PHP Version:            ${GREEN}${php_version}${NC}"
    echo -e "Docker Version:         ${GREEN}$(docker version --format '{{.Server.Version}}' 2>/dev/null || echo "Running")${NC}"
    echo -e "PostgreSQL Version:     ${GREEN}${pg_version}${NC}"
    echo -e "Redis Version:          ${GREEN}${redis_version}${NC}"
    echo -e "Qdrant Version:         ${GREEN}${qdrant_version}${NC}"
    echo -e "MinIO Version:          ${GREEN}${minio_version}${NC}"
    echo -e "n8n Version:            ${GREEN}${n8n_version}${NC}"
    echo -e "Installation Mode:      ${GREEN}${APP_PROFILE}${NC}"
    echo -e "Completion Time:        ${GREEN}${end_time}${NC}"
    echo -e "Application URL:        ${GREEN}${APP_URL}${NC}"
    echo -e "API Endpoint:           ${GREEN}${API_URL}${NC}"
    echo -e "Administration Portal:  ${GREEN}${ADMIN_URL}${NC}"
    echo -e ""
    echo -e "Generated Credentials:"
    echo -e "  - Login Email:        ${GREEN}${ADMIN_EMAIL}${NC}"
    if [ "$AUTO_GENERATED_PASS" = "true" ]; then
        echo -e "  - Password:           ${RED}${ADMIN_PASS}${NC} (Displaying once: copy and save!)"
    else
        echo -e "  - Password:           ${GREEN}[Custom Password Configured]${NC}"
    fi
    echo -e ""
    echo -e "Log Locations:          ${GREEN}logs/install.log, logs/error.log${NC}"
    echo -e "Backup Location:        ${GREEN}backups/${NC}"
    echo -e "Management Commands:    ${YELLOW}hermes status, hermes doctor, hermes backup${NC}"
    echo -e "${BLUE}====================================================${NC}"
}

# ----------------------------------------------------
# MAIN PROCESS EXECUTION
# ----------------------------------------------------

show_installer_banner

run_step_with_recovery 1 "Verifying hardware thresholds and internet routing" "step_1_system_checks"
run_step_with_recovery 2 "Auditing container runtime and upgrades compatibility" "step_2_docker_validation"
run_step_with_recovery 3 "Collecting wizard configurations and validating auth" "step_3_interactive_wizard"
run_step_with_recovery 4 "Compiling preset environment variables file (.env)" "step_4_compile_env"
run_step_with_recovery 5 "Launching container infrastructure services stack" "step_5_deploy_services"
run_step_with_recovery 6 "Running Laravel migrations, seeders, and optimizations" "step_6_initialize_laravel"
run_step_with_recovery 7 "Executing post-flight deep health check diagnostics" "step_7_deep_diagnostics"

display_installation_summary
rm -f "$WIZARD_FILE"
