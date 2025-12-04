#!/bin/bash

# Test Quality Gate Script for LANO CRM
# This script validates code quality metrics before allowing merges

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
BACKEND_DIR="backend-ci"
FRONTEND_DIR="lanocrm"
MIN_COVERAGE=${MIN_COVERAGE:-70}  # Default minimum coverage
MIN_MSI=${MIN_MSI:-80}            # Default minimum MSI score
SKIP_MUTATION=${SKIP_MUTATION:-false}

# Quality gate results
BACKEND_COVERAGE_PASSED=false
FRONTEND_COVERAGE_PASSED=false
MUTATION_PASSED=false
OVERALL_PASSED=false

echo -e "${BLUE}🚪 LANO CRM Test Quality Gate${NC}"
echo "=================================="
echo ""

# Function to show usage
show_usage() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  --min-coverage N    Set minimum coverage percentage (default: 70)"
    echo "  --min-msi N         Set minimum MSI score (default: 80)"
    echo "  --skip-mutation     Skip mutation testing"
    echo "  --help              Show this help message"
    echo ""
    echo "Environment Variables:"
    echo "  MIN_COVERAGE        Minimum coverage percentage"
    echo "  MIN_MSI             Minimum MSI score"
    echo "  SKIP_MUTATION       Set to 'true' to skip mutation testing"
    echo ""
    echo "Examples:"
    echo "  $0                           # Run all quality checks"
    echo "  $0 --min-coverage 80         # Require 80% coverage"
    echo "  $0 --skip-mutation           # Skip mutation testing"
    echo "  MIN_COVERAGE=75 $0           # Set coverage via env var"
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --min-coverage)
            MIN_COVERAGE="$2"
            shift 2
            ;;
        --min-msi)
            MIN_MSI="$2"
            shift 2
            ;;
        --skip-mutation)
            SKIP_MUTATION=true
            shift
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

echo -e "${YELLOW}📋 Quality Gate Configuration:${NC}"
echo "  Minimum Coverage: $MIN_COVERAGE%"
echo "  Minimum MSI Score: $MIN_MSI%"
echo "  Skip Mutation Testing: $SKIP_MUTATION"
echo ""

# Function to check backend coverage
check_backend_coverage() {
    echo -e "${BLUE}🔍 Checking Backend Test Coverage...${NC}"
    
    if [ ! -d "$BACKEND_DIR" ]; then
        echo -e "${RED}❌ Backend directory not found: $BACKEND_DIR${NC}"
        return 1
    fi
    
    cd "$BACKEND_DIR"
    
    # Check if composer dependencies are installed
    if [ ! -d "vendor" ]; then
        echo -e "${YELLOW}📦 Installing composer dependencies...${NC}"
        composer install --prefer-dist --no-progress
    fi
    
    # Run tests with coverage
    echo -e "${YELLOW}🧪 Running backend tests with coverage...${NC}"
    if vendor/bin/phpunit --coverage-clover=coverage/clover.xml --coverage-text; then
        # Extract coverage percentage from clover.xml
        if [ -f "coverage/clover.xml" ]; then
            COVERAGE=$(php -r "
                \$xml = simplexml_load_file('coverage/clover.xml');
                \$metrics = \$xml->project->metrics;
                \$covered = (int)\$metrics['coveredstatements'];
                \$total = (int)\$metrics['statements'];
                if (\$total > 0) {
                    echo round((\$covered / \$total) * 100, 2);
                } else {
                    echo '0';
                }
            ")
            
            echo -e "${BLUE}📊 Backend Coverage: ${COVERAGE}%${NC}"
            
            if (( $(echo "$COVERAGE >= $MIN_COVERAGE" | bc -l) )); then
                echo -e "${GREEN}✅ Backend coverage passed (${COVERAGE}% ≥ ${MIN_COVERAGE}%)${NC}"
                BACKEND_COVERAGE_PASSED=true
            else
                echo -e "${RED}❌ Backend coverage failed (${COVERAGE}% < ${MIN_COVERAGE}%)${NC}"
            fi
        else
            echo -e "${RED}❌ Coverage report not generated${NC}"
        fi
    else
        echo -e "${RED}❌ Backend tests failed${NC}"
    fi
    
    cd ..
}

# Function to check frontend coverage
check_frontend_coverage() {
    echo -e "${BLUE}🔍 Checking Frontend Test Coverage...${NC}"
    
    if [ ! -d "$FRONTEND_DIR" ]; then
        echo -e "${RED}❌ Frontend directory not found: $FRONTEND_DIR${NC}"
        return 1
    fi
    
    cd "$FRONTEND_DIR"
    
    # Check if npm dependencies are installed
    if [ ! -d "node_modules" ]; then
        echo -e "${YELLOW}📦 Installing npm dependencies...${NC}"
        npm ci
    fi
    
    # Run tests with coverage
    echo -e "${YELLOW}🧪 Running frontend tests with coverage...${NC}"
    if CI=true npm run test:coverage; then
        # Extract coverage from coverage summary
        if [ -f "coverage/coverage-summary.json" ]; then
            COVERAGE=$(node -e "
                const coverage = require('./coverage/coverage-summary.json');
                console.log(coverage.total.lines.pct);
            ")
            
            echo -e "${BLUE}📊 Frontend Coverage: ${COVERAGE}%${NC}"
            
            if (( $(echo "$COVERAGE >= $MIN_COVERAGE" | bc -l) )); then
                echo -e "${GREEN}✅ Frontend coverage passed (${COVERAGE}% ≥ ${MIN_COVERAGE}%)${NC}"
                FRONTEND_COVERAGE_PASSED=true
            else
                echo -e "${RED}❌ Frontend coverage failed (${COVERAGE}% < ${MIN_COVERAGE}%)${NC}"
            fi
        else
            echo -e "${YELLOW}⚠️  Frontend coverage report not found, but tests passed${NC}"
            FRONTEND_COVERAGE_PASSED=true
        fi
    else
        echo -e "${RED}❌ Frontend tests failed${NC}"
    fi
    
    cd ..
}

# Function to check mutation testing
check_mutation_testing() {
    if [ "$SKIP_MUTATION" = true ]; then
        echo -e "${YELLOW}⏭️  Skipping mutation testing${NC}"
        MUTATION_PASSED=true
        return 0
    fi
    
    echo -e "${BLUE}🔍 Checking Mutation Testing...${NC}"
    
    # Run mutation testing with our script
    if MIN_MSI="$MIN_MSI" ./scripts/mutation-test.sh; then
        echo -e "${GREEN}✅ Mutation testing passed (MSI ≥ ${MIN_MSI}%)${NC}"
        MUTATION_PASSED=true
    else
        echo -e "${RED}❌ Mutation testing failed${NC}"
    fi
}

# Function to generate quality report
generate_quality_report() {
    echo ""
    echo -e "${BLUE}📊 Quality Gate Report${NC}"
    echo "======================="
    echo ""
    
    echo -e "Backend Coverage:    $([ "$BACKEND_COVERAGE_PASSED" = true ] && echo "${GREEN}✅ PASS${NC}" || echo "${RED}❌ FAIL${NC}")"
    echo -e "Frontend Coverage:   $([ "$FRONTEND_COVERAGE_PASSED" = true ] && echo "${GREEN}✅ PASS${NC}" || echo "${RED}❌ FAIL${NC}")"
    echo -e "Mutation Testing:    $([ "$MUTATION_PASSED" = true ] && echo "${GREEN}✅ PASS${NC}" || echo "${RED}❌ FAIL${NC}")"
    echo ""
    
    if [ "$BACKEND_COVERAGE_PASSED" = true ] && [ "$FRONTEND_COVERAGE_PASSED" = true ] && [ "$MUTATION_PASSED" = true ]; then
        echo -e "${GREEN}🎉 Overall Quality Gate: ✅ PASS${NC}"
        OVERALL_PASSED=true
    else
        echo -e "${RED}🚫 Overall Quality Gate: ❌ FAIL${NC}"
        echo ""
        echo -e "${YELLOW}💡 To fix quality gate failures:${NC}"
        
        if [ "$BACKEND_COVERAGE_PASSED" = false ]; then
            echo "  • Add more backend tests to reach ${MIN_COVERAGE}% coverage"
        fi
        
        if [ "$FRONTEND_COVERAGE_PASSED" = false ]; then
            echo "  • Add more frontend tests to reach ${MIN_COVERAGE}% coverage"
        fi
        
        if [ "$MUTATION_PASSED" = false ]; then
            echo "  • Improve test assertions to reach ${MIN_MSI}% mutation score"
        fi
    fi
}

# Main execution
echo -e "${BLUE}🚀 Starting Quality Gate Checks...${NC}"
echo ""

check_backend_coverage
echo ""

check_frontend_coverage
echo ""

check_mutation_testing
echo ""

generate_quality_report

# Exit with appropriate code
if [ "$OVERALL_PASSED" = true ]; then
    echo ""
    echo -e "${GREEN}🎉 Quality gate passed! Code is ready for merge.${NC}"
    exit 0
else
    echo ""
    echo -e "${RED}🚫 Quality gate failed! Please address the issues above.${NC}"
    exit 1
fi