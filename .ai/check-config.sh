#!/bin/bash

# Configuration for pre-commit checks

# Quality thresholds (warnings only)
export CONTROLLER_MAX_LINES=250
export SERVICE_MAX_LINES=500
export REPOSITORY_MAX_LINES=400
export METHOD_MAX_LINES=50

# Skip certain checks (use with caution)
export SKIP_TESTS=${SKIP_TESTS:-false}
export SKIP_SCOPE=${SKIP_SCOPE:-false}
export SKIP_QUALITY=${SKIP_QUALITY:-false}

# Docker container name
export DOCKER_CONTAINER="meomeo2-api-1"
