#!/bin/bash

###############################################################################
# Deployment Test Script
# 
# Tests deployment on fresh machine to verify all fixes work end-to-end
# 
# Usage:
#   ./scripts/test-deployment.sh [environment]
#
# Environments:
#   dev      - Development (default)
#   staging  - Staging
#   prod     - Production (requires confirmation)
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
ENVIRONMENT="${1:-dev}"
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
LOG_FILE="${PROJECT_ROOT}/logs/deployment-test-${TIMESTAMP}.log"

# Create logs directory if not exists
mkdir -p "${PROJECT_ROOT}/logs"

###############################################################################
# Helper Functions
###############################################################################

log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

success() {
    echo -e "${GREEN}✓${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}✗${NC} $1" | tee -a "$LOG_FILE"
}

warning() {
    echo -e "${YELLOW}⚠${NC} $1" | tee -a "$LOG_FILE"
}

section() {
    echo "" | tee -a "$LOG_FILE"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}" | tee -a "$LOG_FILE"
    echo -e "${BLUE}  $1${NC}" | tee -a "$LOG_FILE"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}" | tee -a "$LOG_FILE"
    echo "" | tee -a "$LOG_FILE"
}

###############################################################################
# Pre-flight Checks
###############################################################################

preflight_checks() {
    section "Pre-flight Checks"
    
    # Check Docker
    if ! command -v docker &> /dev/null; then
        error "Docker is not installed"
        exit 1
    fi
    success "Docker is installed"
    
    # Check Docker Compose
    if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
        error "Docker Compose is not installed"
        exit 1
    fi
    success "Docker Compose is installed"
    
    # Check required ports
    log "Checking required ports..."
    REQUIRED_PORTS=(3000 8000 8080 3306)
    for port in "${REQUIRED_PORTS[@]}"; do
        if lsof -Pi :$port -sTCP:LISTEN -t >/dev/null 2>&1; then
            warning "Port $port is already in use"
        else
            success "Port $port is available"
        fi
    done
    
    # Check disk space (need at least 10GB)
    AVAILABLE_SPACE=$(df -BG "$PROJECT_ROOT" | awk 'NR==2 {print $4}' | sed 's/G//')
    if [ "$AVAILABLE_SPACE" -lt 10 ]; then
        warning "Low disk space: ${AVAILABLE_SPACE}GB available (recommended: 20GB+)"
    else
        success "Sufficient disk space: ${AVAILABLE_SPACE}GB available"
    fi
    
    # Check RAM (need at least 4GB)
    TOTAL_RAM=$(free -g | awk 'NR==2 {print $2}')
    if [ "$TOTAL_RAM" -lt 4 ]; then
        warning "Low RAM: ${TOTAL_RAM}GB (recommended: 8GB+)"
    else
        success "Sufficient RAM: ${TOTAL_RAM}GB"
    fi
}

###############################################################################
# Environment Setup
###############################################################################

setup_environment() {
    section "Environment Setup: $ENVIRONMENT"
    
    cd "$PROJECT_ROOT"
    
    case "$ENVIRONMENT" in
        dev)
            log "Setting up development environment..."
            COMPOSE_FILES="-f docker-compose.yml -f docker-compose.override.yml"
            ;;
        staging)
            log "Setting up staging environment..."
            COMPOSE_FILES="-f docker-compose.yml -f docker-compose.prod.yml"
            ;;
        prod)
            warning "Production deployment test!"
            read -p "Are you sure you want to test production deployment? (yes/no): " confirm
            if [ "$confirm" != "yes" ]; then
                error "Production deployment cancelled"
                exit 1
            fi
            log "Setting up production environment..."
            COMPOSE_FILES="-f docker-compose.yml -f docker-compose.prod.yml"
            ;;
        *)
            error "Invalid environment: $ENVIRONMENT"
            echo "Valid environments: dev, staging, prod"
            exit 1
            ;;
    esac
    
    success "Environment configured: $ENVIRONMENT"
}

###############################################################################
# Deployment Test
###############################################################################

test_deployment() {
    section "Deployment Test"
    
    # Stop existing containers
    log "Stopping existing containers..."
    docker-compose $COMPOSE_FILES down 2>&1 | tee -a "$LOG_FILE" || true
    success "Containers stopped"
    
    # Build and start
    log "Building and starting containers..."
    if docker-compose $COMPOSE_FILES up -d --build 2>&1 | tee -a "$LOG_FILE"; then
        success "Containers started"
    else
        error "Failed to start containers"
        exit 1
    fi
    
    # Wait for services to be ready
    log "Waiting for services to be ready..."
    sleep 10
    
    # Check container status
    log "Checking container status..."
    if docker-compose $COMPOSE_FILES ps | grep -q "Up"; then
        success "Containers are running"
    else
        error "Some containers are not running"
        docker-compose $COMPOSE_FILES ps | tee -a "$LOG_FILE"
        exit 1
    fi
}

###############################################################################
# Migration Test
###############################################################################

test_migration() {
    section "Migration Test"
    
    # Wait for migration to complete
    log "Waiting for migration to complete (max 5 minutes)..."
    TIMEOUT=300
    ELAPSED=0
    
    while [ $ELAPSED -lt $TIMEOUT ]; do
        if docker-compose $COMPOSE_FILES logs api 2>&1 | grep -q "✓ Database initialization completed"; then
            success "Migration completed successfully"
            break
        fi
        
        if docker-compose $COMPOSE_FILES logs api 2>&1 | grep -q "✗ Database initialization failed"; then
            error "Migration failed"
            docker-compose $COMPOSE_FILES logs api | tail -50 | tee -a "$LOG_FILE"
            exit 1
        fi
        
        sleep 5
        ELAPSED=$((ELAPSED + 5))
        echo -n "." | tee -a "$LOG_FILE"
    done
    
    if [ $ELAPSED -ge $TIMEOUT ]; then
        error "Migration timeout after ${TIMEOUT}s"
        exit 1
    fi
    
    # Check migration status using diagnostic script
    log "Running migration status check..."
    if [ -f "${PROJECT_ROOT}/scripts/check-migration-status.sh" ]; then
        bash "${PROJECT_ROOT}/scripts/check-migration-status.sh" 2>&1 | tee -a "$LOG_FILE"
    else
        warning "Migration status script not found"
    fi
}

###############################################################################
# Service Health Checks
###############################################################################

test_services() {
    section "Service Health Checks"
    
    # Test database connection
    log "Testing database connection..."
    if docker-compose $COMPOSE_FILES exec -T db mysql -u root -proot_password -e "SELECT 1" &>/dev/null; then
        success "Database connection OK"
    else
        error "Database connection failed"
        exit 1
    fi
    
    # Count tables
    log "Counting database tables..."
    TABLE_COUNT=$(docker-compose $COMPOSE_FILES exec -T db mysql -u root -proot_password -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'lanocrm_shop'" 2>/dev/null | tail -1)
    if [ "$TABLE_COUNT" -ge 160 ]; then
        success "Database has $TABLE_COUNT tables (expected ~164)"
    else
        warning "Database has only $TABLE_COUNT tables (expected ~164)"
    fi
    
    # Test API health endpoint
    log "Testing API health endpoint..."
    sleep 5  # Give API time to start
    if curl -f http://localhost:8000/api/health &>/dev/null; then
        success "API health check passed"
    else
        warning "API health check failed (may need more time to start)"
    fi
    
    # Test frontend
    log "Testing frontend..."
    if curl -f http://localhost:3000 &>/dev/null; then
        success "Frontend is accessible"
    else
        warning "Frontend is not accessible (may need more time to start)"
    fi
    
    # Test phpMyAdmin
    if [ "$ENVIRONMENT" = "dev" ]; then
        log "Testing phpMyAdmin..."
        if curl -f http://localhost:8080 &>/dev/null; then
            success "phpMyAdmin is accessible"
        else
            warning "phpMyAdmin is not accessible"
        fi
    fi
}

###############################################################################
# Seeding Test
###############################################################################

test_seeding() {
    section "Seeding Test"
    
    case "$ENVIRONMENT" in
        dev)
            log "Checking DevDemoSeeder execution..."
            if docker-compose $COMPOSE_FILES logs api 2>&1 | grep -q "DevDemoSeeder"; then
                success "DevDemoSeeder executed"
            else
                warning "DevDemoSeeder may not have executed"
            fi
            ;;
        staging)
            log "Staging seeding test..."
            warning "Manual seeding required for staging"
            echo "Run: docker exec staging-api php spark db:seed StagingSeeder" | tee -a "$LOG_FILE"
            ;;
        prod)
            log "Production seeding test..."
            warning "Manual seeding required for production"
            echo "Run: docker exec prod-api php spark db:seed ProductionSeeder" | tee -a "$LOG_FILE"
            ;;
    esac
}

###############################################################################
# Cleanup Test
###############################################################################

test_cleanup() {
    section "Cleanup Test"
    
    read -p "Do you want to clean up test containers? (yes/no): " cleanup
    if [ "$cleanup" = "yes" ]; then
        log "Stopping containers..."
        docker-compose $COMPOSE_FILES down 2>&1 | tee -a "$LOG_FILE"
        success "Containers stopped"
        
        read -p "Remove volumes? (yes/no): " remove_volumes
        if [ "$remove_volumes" = "yes" ]; then
            log "Removing volumes..."
            docker-compose $COMPOSE_FILES down -v 2>&1 | tee -a "$LOG_FILE"
            success "Volumes removed"
        fi
    else
        log "Containers left running for manual testing"
    fi
}

###############################################################################
# Generate Report
###############################################################################

generate_report() {
    section "Test Report"
    
    echo "" | tee -a "$LOG_FILE"
    echo "Deployment Test Summary" | tee -a "$LOG_FILE"
    echo "======================" | tee -a "$LOG_FILE"
    echo "Environment: $ENVIRONMENT" | tee -a "$LOG_FILE"
    echo "Timestamp: $(date)" | tee -a "$LOG_FILE"
    echo "Log file: $LOG_FILE" | tee -a "$LOG_FILE"
    echo "" | tee -a "$LOG_FILE"
    
    # Container status
    echo "Container Status:" | tee -a "$LOG_FILE"
    docker-compose $COMPOSE_FILES ps 2>&1 | tee -a "$LOG_FILE"
    echo "" | tee -a "$LOG_FILE"
    
    # Resource usage
    echo "Resource Usage:" | tee -a "$LOG_FILE"
    docker stats --no-stream 2>&1 | tee -a "$LOG_FILE"
    echo "" | tee -a "$LOG_FILE"
    
    success "Test completed! Check log file: $LOG_FILE"
}

###############################################################################
# Main Execution
###############################################################################

main() {
    log "Starting deployment test for environment: $ENVIRONMENT"
    
    preflight_checks
    setup_environment
    test_deployment
    test_migration
    test_services
    test_seeding
    generate_report
    test_cleanup
    
    success "Deployment test completed successfully!"
}

# Run main function
main