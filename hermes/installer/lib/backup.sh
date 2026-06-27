#!/usr/bin/env bash

# Hermes AI Platform - Unified Backup & Restore Library
# Targets: Ubuntu 24.04 LTS (x86_64)

LIB_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${LIB_DIR}/logging.sh"

# Get Docker Compose project name dynamically
get_compose_project_name() {
    local name
    name=$(docker compose config 2>/dev/null | grep -E "^name:" | head -n1 | awk '{print $2}' | tr -d '\r\n"' || echo "")
    if [ -z "$name" ]; then
        name=$(basename "$(pwd)" | tr -d '[:space:]' | tr '[:upper:]' '[:lower:]' | tr -cd 'a-z0-9_-')
    fi
    echo "$name"
}

# Dump PostgreSQL Database
execute_pg_dump() {
    local db_user=$1
    local db_name=$2
    local db_pass=$3
    local dest_path=$4
    local log_file=$5
    
    if docker compose exec -T db pg_isready -U "$db_user" >/dev/null 2>&1; then
        if docker compose exec -T -e PGPASSWORD="$db_pass" db pg_dump -U "$db_user" -d "$db_name" --clean --if-exists > "$dest_path" 2>> "$log_file"; then
            return 0
        else
            return 2 # Dump failed
        fi
    else
        return 1 # Database container offline
    fi
}

# Restore PostgreSQL Database
execute_pg_restore() {
    local db_user=$1
    local db_name=$2
    local db_pass=$3
    local src_path=$4
    local log_file=$5

    # Recreate public schema to wipe partially migrated states
    docker compose exec -T -e PGPASSWORD="$db_pass" db psql -U "$db_user" -d "$db_name" -c "DROP SCHEMA public CASCADE; CREATE SCHEMA public; ALTER SCHEMA public OWNER TO $db_user;" >> "$log_file" 2>&1
    
    # Load backup SQL
    if docker compose exec -T -e PGPASSWORD="$db_pass" db psql -U "$db_user" -d "$db_name" -f - < "$src_path" >> "$log_file" 2>&1; then
        return 0
    else
        return 1
    fi
}

# Archive Docker Volume using Alpine container
archive_docker_volume() {
    local proj_name=$1
    local vol_name=$2
    local dest_archive=$3
    local log_file=$4
    
    docker run --rm \
      -v "${proj_name}_${vol_name}:/data" \
      -v "$(pwd)/$(dirname "$dest_archive"):/backup" \
      alpine tar -czf "/backup/$(basename "$dest_archive")" -C /data . >> "$log_file" 2>&1
}

# Restore Docker Volume using Alpine container
extract_docker_volume() {
    local proj_name=$1
    local vol_name=$2
    local src_archive=$3
    local log_file=$4
    
    docker run --rm \
      -v "${proj_name}_${vol_name}:/data" \
      -v "$(pwd)/$(dirname "$src_archive"):/backup" \
      alpine sh -c "rm -rf /data/* /data/.* 2>/dev/null || true; tar -xzf /backup/$(basename "$src_archive") -C /data" >> "$log_file" 2>&1
}

# Archive local directory
archive_host_directory() {
    local dir_path=$1
    local dest_archive=$2
    local log_file=$3
    
    if [ -d "$dir_path" ]; then
        tar -czf "$dest_archive" -C "$dir_path" . >> "$log_file" 2>&1
    else
        mkdir -p "$dir_path"
        tar -czf "$dest_archive" -C "$dir_path" . >> "$log_file" 2>&1
    fi
}

# Restore local directory
extract_host_directory() {
    local dir_path=$1
    local src_archive=$2
    local log_file=$3
    
    rm -rf "$dir_path"/*
    mkdir -p "$dir_path"
    if tar -xzf "$src_archive" -C "$dir_path" >> "$log_file" 2>&1; then
        chmod -R 775 "$dir_path" || true
        if getent group www-data &>/dev/null; then
            sudo chown -R :www-data "$dir_path" || true
        fi
        return 0
    else
        return 1
    fi
}
