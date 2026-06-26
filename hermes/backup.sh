#!/usr/bin/env bash
# Backward compatibility wrapper for Hermes CLI
exec "$(dirname "$0")/installer/bin/hermes" backup "$@"
