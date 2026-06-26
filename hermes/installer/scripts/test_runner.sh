#!/usr/bin/env bash

# Hermes AI Platform - Shell Script Linter and Test Runner
# Targets: Ubuntu 24.04 LTS (x86_64)

set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
INSTALLER_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

# Source libraries
source "${INSTALLER_DIR}/lib/logging.sh"

run_lint_tests() {
    echo -e "${BLUE}=== Running Shell Check Code Auditing Lint Tests ===${NC}"
    if command -v shellcheck &>/dev/null; then
        if shellcheck "${INSTALLER_DIR}"/bin/hermes "${INSTALLER_DIR}"/scripts/*.sh "${INSTALLER_DIR}"/lib/*.sh; then
            log_info "All shell scripts passed ShellCheck validation tests."
            return 0
        else
            log_err "ShellCheck auditing failed. Please fix formatting warnings."
            return 1
        fi
    else
        log_warn "ShellCheck is not installed. Skipping static lint verification."
        return 0
    fi
}

run_lint_tests
exit $?
