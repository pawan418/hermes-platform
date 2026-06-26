#!/usr/bin/env bash

# Hermes AI Platform - Diagnostics Wrapper Script
# Targets: Ubuntu 24.04 LTS (x86_64)

set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
INSTALLER_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

# Source libraries
source "${INSTALLER_DIR}/lib/logging.sh"
source "${INSTALLER_DIR}/lib/health.sh"

echo -e "${BLUE}====================================================${NC}"
echo -e "${BLUE}         Hermes System Diagnostics Report           ${NC}"
echo -e "${BLUE}====================================================${NC}"
echo -e "Generated on: $(date '+%Y-%m-%d %H:%M:%S')"

# Trigger deep health verification
run_deep_diagnostics
exit $?
