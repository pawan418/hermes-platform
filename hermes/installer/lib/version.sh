#!/usr/bin/env bash

# Hermes AI Platform - Version Utilities
# Targets: Ubuntu 24.04 LTS (x86_64)

# Compare versions: Returns 0 (equal), 1 (v1 > v2), 2 (v1 < v2)
version_compare() {
    if [[ "$1" == "$2" ]]; then
        return 0
    fi
    
    local clean_v1=${1#v}
    local clean_v2=${2#v}

    local IFS=.
    local i ver1=($clean_v1) ver2=($clean_v2)
    
    # Fill empty fields in ver1
    for ((i=${#ver1[@]}; i<${#ver2[@]}; i++)); do
        ver1[i]=0
    done
    
    for ((i=0; i<${#ver1[@]}; i++)); do
        if [[ -z ${ver2[i]} ]]; then
            ver2[i]=0
        fi
        
        # Strip alphanumeric suffix if present
        local num1=$(echo "${ver1[i]}" | grep -oE '^[0-9]+' || echo "0")
        local num2=$(echo "${ver2[i]}" | grep -oE '^[0-9]+' || echo "0")

        if ((10#$num1 > 10#$num2)); then
            return 1
        fi
        if ((10#$num1 < 10#$num2)); then
            return 2
        fi
    done
    return 0
}
