#!/bin/bash
# Pre-commit config - Relaxed for current codebase

# File size thresholds (warnings only, not blocking)
export CONTROLLER_MAX_LINES=350
export SERVICE_MAX_LINES=700
export REPOSITORY_MAX_LINES=500

# Coverage threshold (warning only)
export COVERAGE_THRESHOLD=50

# Docker container
export DOCKER_CONTAINER="kiotviet-web-1"

# Skip flags (set to true to bypass)
export SKIP_TESTS=${SKIP_TESTS:-false}
export SKIP_COVERAGE=${SKIP_COVERAGE:-true}
