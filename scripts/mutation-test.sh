
#!/bin/bash

# Mutation Testing Script for LANO CRM
# This script runs infection mutation testing with proper setup and validation

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
BACKEND_DIR="backend-ci"
COVERAGE_DIR="$BACKEND_DIR/coverage"
INFECTION_CONFIG="$BACKEND_DIR/infection.json.dist"
BASELINE_FILE="$BACKEND_DIR/infection-baseline.json"
MIN_MSI=${MIN_MSI:-80}  # Default minimum MSI score

echo -e "${BLUE}🧬 LANO CRM Mutation Testing${NC}"
echo "=================================="

# Check if we're in the right directory
if [ ! -d "$BACKEND_DIR" ]; then
    echo -e "${RED}❌ Error: backend-ci directory not found${NC}"
    echo "Please run this script from the project root directory"
    exit 1
fi

# Function to show usage
show_usage() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  --baseline     Create baseline for future comparisons"
    echo "  --compare      Compare against baseline"
    echo "  --min-msi N    Set minimum MSI score (default: 80)"
    echo "  --help         Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0                    # Run mutation testing"
    echo "  $0 --baseline         # Create baseline"
    echo "  $0 --compare          # Compare with baseline"
    echo "  $0 --min-msi 85       # Require 85% MSI score"
}

# Parse command line arguments
CREATE_BASELINE=false
COMPARE_BASELINE=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --baseline)
            CREATE_BASELINE=true
            shift
            ;;
        --compare)
            COMPARE_BASELINE=true
            shift
            ;;
        --min-msi)
            MIN_MSI="$2"
            shift 2
            ;;
        --help)
            show_usage
            exit 0
            ;;
        *)
            echo -e "${RED}❌ Unknown option: $1${NC}"
            show_usage
            exit 1
            ;;
    esac
done

# Change to backend directory
cd "$BACKEND_DIR"

echo -e "${YELLOW}📋 Configuration:${NC}"
echo "  Backend Directory: $BACKEND_DIR"
echo "  Minimum MSI Score: $MIN_MSI%"
echo "  Create Baseline: $CREATE_BASELINE"
echo "  Compare Baseline: $COMPARE_BASELINE"
echo ""

# Check if composer dependencies are installed
if [ ! -d "vendor" ]; then
    echo -e "${YELLOW}📦 Installing composer dependencies...${NC}"
    composer install --prefer-dist --no-progress
fi

# Check if infection is installed
if [ ! -f "vendor/bin/infection" ]; then
    echo -e "${RED}❌ Infection not found. Installing...${NC}"
    composer require --dev infection/infection
fi

# Create coverage directory if it doesn't exist
mkdir -p "$COVERAGE_DIR"

echo -e "${YELLOW}🧪 Running PHPUnit tests with coverage...${NC}"
if ! vendor/bin/phpunit --coverage-xml="$COVERAGE_DIR/coverage-xml" --log-junit="$COVERAGE_DIR/junit.xml"; then
    echo -e "${RED}❌ PHPUnit tests failed. Mutation testing requires passing tests.${NC}"
    exit 1
fi

echo -e "${GREEN}✅ PHPUnit tests passed${NC}"

# Build infection command
INFECTION_CMD="vendor/bin/infection --configuration=$INFECTION_CONFIG --threads=4"

if [ "$CREATE_BASELINE" = true ]; then
    echo -e "${YELLOW}📊 Creating mutation testing baseline...${NC}"
    INFECTION_CMD="$INFECTION_CMD --baseline=$BASELINE_FILE"
elif [ "$COMPARE_BASELINE" = true ] && [ -f "$BASELINE_FILE" ]; then
    echo -e "${YELLOW}📊 Comparing against baseline...${NC}"
    INFECTION_CMD="$INFECTION_CMD --baseline=$BASELINE_FILE"
fi

INFECTION_CMD="$INFECTION_CMD --min-msi=$MIN_MSI"

echo -e "${YELLOW}🧬 Running mutation testing...${NC}"
echo "Command: $INFECTION_CMD"
echo ""

# Run infection
if eval "$INFECTION_CMD"; then
    echo -e "${GREEN}✅ Mutation testing passed with MSI ≥ $MIN_MSI%${NC}"
    
    # Show summary if available
    if [ -f "summary.log" ]; then
        echo ""
        echo -e "${BLUE}📊 Mutation Testing Summary:${NC}"
        cat summary.log
    fi
    
    exit 0
else
    echo -e "${RED}❌ Mutation testing failed${NC}"
    
    # Show summary if available
    if [ -f "summary.log" ]; then
        echo ""
        echo -e "${BLUE}📊 Mutation Testing Summary:${NC}"
        cat summary.log
    fi
    
    echo ""
    echo -e "${YELLOW}💡 Tips to improve mutation score:${NC}"
    echo "  1. Add more specific assertions"
    echo "  2. Test edge cases and error conditions"
    echo "  3. Remove redundant or unnecessary code"
    echo "  4. Add tests for uncovered code paths"
    echo "  5. Review escaped mutants for false positives"
    
    exit 1
fi