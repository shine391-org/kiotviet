---
title: "Testing Maintenance Procedures"
id: "MAINTENANCE-01"
version: "4.0"
status: "Active"
module: "Testing"
type: "Guide"
tags: ["testing", "maintenance", "automation", "monitoring", "procedures"]
purpose: "Comprehensive guide for testing maintenance procedures including daily, weekly, monthly, and quarterly tasks with automation scripts."
location: "docs/testing"
updated: "2025-12-03"
changes: "Updated YAML frontmatter for documentation consolidation"
related_to:
  - id: "BACKEND-TESTING-01"
    description: "Backend testing guide with test database only"
  - id: "FRONTEND-TESTING-01"
    description: "Frontend testing guide with real database integration"
  - id: "TESTING-PATTERNS-01"
    description: "Testing patterns and assertions reference"
  - id: "TEST-CHECKLIST-01"
    description: "Mandatory checklist for all testing changes"
---

# Testing Maintenance Procedures - LANO CRM

## 📋 Mục Lục

1. [Daily Maintenance](#daily-maintenance)
2. [Weekly Maintenance](#weekly-maintenance)
3. [Monthly Maintenance](#monthly-maintenance)
4. [Quarterly Reviews](#quarterly-reviews)
5. [Emergency Procedures](#emergency-procedures)
6. [Automation Scripts](#automation-scripts)

---

## 📅 Daily Maintenance

### 1. Test Health Check

#### Script: `scripts/daily-test-check.sh`

```bash
#!/bin/bash

# Daily Test Health Check
# Run this script daily to ensure test suite health

set -e

echo "🔍 Daily Test Health Check - $(date)"
echo "=================================="

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Check if tests can run
echo -e "${YELLOW}🧪 Running quick test suite...${NC}"
cd backend-ci

if vendor/bin/phpunit tests/Unit/ --no-coverage; then
    echo -e "${GREEN}✅ Unit tests passed${NC}"
else
    echo -e "${RED}❌ Unit tests failed${NC}"
    exit 1
fi

# Check database connection
echo -e "${YELLOW}🗄️ Checking database connection...${NC}"
if php spark db:info > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Database connection OK${NC}"
else
    echo -e "${RED}❌ Database connection failed${NC}"
    exit 1
fi

# Check test database
echo -e "${YELLOW}🗄️ Checking test database...${NC}"
if php spark migrate:status --group tests > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Test database OK${NC}"
else
    echo -e "${RED}❌ Test database issues${NC}"
    exit 1
fi

echo -e "${GREEN}🎉 Daily health check completed successfully${NC}"
```

#### Setup Daily Cron Job

```bash
# Add to crontab: crontab -e
0 8 * * * /path/to/project/scripts/daily-test-check.sh >> /var/log/test-health.log 2>&1
```

### 2. Coverage Monitor

#### Script: `scripts/coverage-monitor.sh`

```bash
#!/bin/bash

# Daily Coverage Monitor
# Track coverage trends and alert on drops

COVERAGE_FILE="coverage/daily-coverage.txt"
THRESHOLD=75

echo "📊 Coverage Monitor - $(date)"
echo "==========================="

cd backend-ci

# Run coverage
vendor/bin/phpunit --coverage-text --no-coverage > $COVERAGE_FILE

# Extract coverage percentage
COVERAGE=$(grep "Lines:" $COVERAGE_FILE | awk '{print $2}' | sed 's/%//')

echo "Current Coverage: ${COVERAGE}%"

# Check threshold
if (( $(echo "$COVERAGE < $THRESHOLD" | bc -l) )); then
    echo "⚠️ Coverage below threshold ($THRESHOLD%)"
    # Send alert (implement notification system)
    # send_alert "Coverage dropped to ${COVERAGE}%"
else
    echo "✅ Coverage OK"
fi

# Store for trend analysis
echo "$(date),${COVERAGE}" >> coverage/trend.csv
```

---

## 📆 Weekly Maintenance

### 1. Full Test Suite Run

#### Script: `scripts/weekly-full-test.sh`

```bash
#!/bin/bash

# Weekly Full Test Suite
# Run complete test suite with all checks

echo "🧪 Weekly Full Test Suite - $(date)"
echo "=================================="

cd backend-ci

# Clean up
echo "🧹 Cleaning up..."
rm -rf coverage/
rm -rf .infection/

# Run full test suite with coverage
echo "📊 Running full test suite with coverage..."
vendor/bin/phpunit --coverage-html=coverage/html --coverage-clover=coverage/clover.xml

# Check coverage threshold
echo "📈 Checking coverage threshold..."
php coverage-checker.php coverage/clover.xml 75

# Run mutation testing
echo "🧬 Running mutation testing..."
MIN_MSI=80 ./scripts/mutation-test.sh

# Generate reports
echo "📋 Generating reports..."
mkdir -p reports/weekly/
cp coverage/clover.xml reports/weekly/
cp infection.html reports/weekly/
cp summary.log reports/weekly/

echo "✅ Weekly test suite completed"
echo "📊 Reports available in reports/weekly/"
```

### 2. Test Performance Analysis

#### Script: `scripts/performance-analysis.sh`

```bash
#!/bin/bash

# Weekly Test Performance Analysis
# Analyze test execution times and identify slow tests

echo "⚡ Test Performance Analysis - $(date)"
echo "====================================="

cd backend-ci

# Run tests with timing
echo "⏱️ Running tests with timing..."
vendor/bin/phpunit --log-junit=reports/performance/junit.xml --testdox-text=reports/performance/testdox.txt

# Analyze slow tests
echo "📈 Analyzing slow tests..."
python3 scripts/analyze-performance.py reports/performance/junit.xml > reports/performance/slow-tests.txt

# Generate report
echo "📋 Performance report generated:"
cat reports/performance/slow-tests.txt

# Alert on very slow tests
if grep -q ">10\.0" reports/performance/slow-tests.txt; then
    echo "⚠️ Very slow tests detected (>10s)"
fi
```

### 3. Dependency Updates

#### Script: `scripts/update-dependencies.sh`

```bash
#!/bin/bash

# Weekly Dependency Updates
# Check and update testing dependencies

echo "📦 Dependency Update Check - $(date)"
echo "=================================="

cd backend-ci

# Check for outdated packages
echo "🔍 Checking for outdated packages..."
composer outdated --direct

# Update testing dependencies
echo "⬆️ Updating testing dependencies..."
composer update --dry-run phpunit/phpunit infection/infection --with-all-dependencies

# Ask for confirmation
read -p "Apply updates? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    composer update phpunit/phpunit infection/infection --with-all-dependencies
    
    # Run tests to verify updates
    echo "🧪 Running tests to verify updates..."
    vendor/bin/phpunit tests/Unit/
    
    if [ $? -eq 0 ]; then
        echo "✅ Updates applied successfully"
    else
        echo "❌ Updates caused test failures"
        echo "🔄 Rolling back..."
        composer update phpunit/phpunit infection/infection --with-all-dependencies --prefer-stable
    fi
fi
```

---

## 🗓️ Monthly Maintenance

### 1. Test Suite Audit

#### Script: `scripts/monthly-audit.sh`

```bash
#!/bin/bash

# Monthly Test Suite Audit
# Comprehensive audit of test suite health

echo "🔍 Monthly Test Suite Audit - $(date)"
echo "===================================="

cd backend-ci

# Test count analysis
echo "📊 Test count analysis..."
echo "Unit tests: $(find tests/Unit -name '*Test.php' | wc -l)"
echo "Integration tests: $(find tests/Integration -name '*Test.php' | wc -l)"
echo "Feature tests: $(find tests/Feature -name '*Test.php' | wc -l)"

# Coverage analysis
echo "📈 Coverage analysis..."
vendor/bin/phpunit --coverage-text=reports/monthly/coverage.txt

# Mutation score analysis
echo "🧬 Mutation score analysis..."
MIN_MSI=80 ./scripts/mutation-test.sh > reports/monthly/mutation.txt

# Code quality checks
echo "🔍 Code quality checks..."
# Run static analysis if available
if command -v phpstan &> /dev/null; then
    vendor/bin/phpstan analyse app --level=5 --no-progress > reports/monthly/phpstan.txt
fi

# Generate summary report
echo "📋 Generating summary report..."
cat > reports/monthly/summary.md << EOF
# Monthly Test Suite Audit - $(date)

## Test Statistics
- Unit Tests: $(find tests/Unit -name '*Test.php' | wc -l)
- Integration Tests: $(find tests/Integration -name '*Test.php' | wc -l)
- Feature Tests: $(find tests/Feature -name '*Test.php' | wc -l)

## Coverage
$(grep "Lines:" reports/monthly/coverage.txt)

## Mutation Score
$(grep "MSI:" reports/monthly/mutation.txt)

## Recommendations
$(cat reports/monthly/recommendations.txt)
EOF

echo "✅ Monthly audit completed"
echo "📋 Report available in reports/monthly/summary.md"
```

### 2. Test Data Cleanup

#### Script: `scripts/cleanup-test-data.sh`

```bash
#!/bin/bash

# Monthly Test Data Cleanup
# Clean up old test data and logs

echo "🧹 Test Data Cleanup - $(date)"
echo "============================="

cd backend-ci

# Clean old coverage reports
echo "🗑️ Cleaning old coverage reports..."
find coverage/ -name "*.html" -mtime +30 -delete
find coverage/ -name "*.xml" -mtime +30 -delete

# Clean old mutation reports
echo "🗑️ Cleaning old mutation reports..."
find . -name "infection*.log" -mtime +30 -delete
find . -name "infection*.html" -mtime +30 -delete

# Clean old test logs
echo "🗑️ Cleaning old test logs..."
find writable/logs/ -name "log-*.php" -mtime +7 -delete

# Clean temporary files
echo "🗑️ Cleaning temporary files..."
find . -name ".infection" -type d -exec rm -rf {} + 2>/dev/null || true
find . -name "coverage" -type d -exec rm -rf {} + 2>/dev/null || true

# Optimize database
echo "🗄️ Optimizing test database..."
php spark db:optimize --group tests

echo "✅ Cleanup completed"
```

---

## 📊 Quarterly Reviews

### 1. Test Strategy Review

#### Checklist: `docs/testing/quarterly-review-checklist.md`

```markdown
# Quarterly Test Strategy Review

## Test Coverage Analysis
- [ ] Overall coverage ≥ 75%
- [ ] Critical modules coverage ≥ 90%
- [ ] New features have adequate tests
- [ ] Legacy code coverage improving

## Test Quality Metrics
- [ ] Mutation score ≥ 80%
- [ ] Test execution time < 5 minutes
- [ ] Flaky tests < 1%
- [ ] Test failure rate < 5%

## Infrastructure Health
- [ ] CI/CD pipeline stable
- [ ] Test environment performance
- [ ] Database performance
- [ ] Resource utilization

## Team Adoption
- [ ] Team following testing guidelines
- [ ] New team members trained
- [ ] Test reviews happening
- [ ] Documentation up to date

## Tooling and Automation
- [ ] Testing tools up to date
- [ ] Automation scripts working
- [ ] Monitoring in place
- [ ] Alerting functional

## Action Items
- [ ] Identify areas for improvement
- [ ] Plan training sessions
- [ ] Update documentation
- [ ] Schedule tool upgrades
```

### 2. Tooling Assessment

#### Script: `scripts/assess-tools.sh`

```bash
#!/bin/bash

# Quarterly Tooling Assessment
# Assess and upgrade testing tools

echo "🔧 Tooling Assessment - $(date)"
echo "=============================="

cd backend-ci

# Check PHPUnit version
echo "📋 PHPUnit version:"
vendor/bin/phpunit --version

# Check Infection version
echo "📋 Infection version:"
vendor/bin/infection --version

# Check Xdebug status
echo "📋 Xdebug status:"
php -m | grep xdebug || echo "Xdebug not installed"

# Check for security updates
echo "🔍 Checking for security updates..."
composer audit

# Check performance
echo "⚡ Performance benchmark:"
time vendor/bin/phpunit tests/Unit/ --no-coverage

# Generate recommendations
echo "💡 Recommendations:"
echo "- Consider upgrading PHPUnit if major version available"
echo "- Monitor mutation score trends"
echo "- Evaluate new testing tools"
echo "- Review test performance optimization"
```

---

## 🚨 Emergency Procedures

### 1. Test Suite Failure

#### Emergency Checklist

```bash
#!/bin/bash

# Emergency Test Suite Recovery
# Use when test suite completely fails

echo "🚨 Emergency Test Suite Recovery"
echo "==============================="

cd backend-ci

# Step 1: Check basic environment
echo "🔍 Step 1: Environment check"
php --version
composer --version
docker --version

# Step 2: Reset database
echo "🗄️ Step 2: Reset database"
docker-compose down -v
docker-compose up -d db
sleep 30
docker exec meomeo2-api-1 php spark migrate:fresh --all

# Step 3: Reinstall dependencies
echo "📦 Step 3: Reinstall dependencies"
rm -rf vendor/
composer install --prefer-dist --no-progress

# Step 4: Run minimal test
echo "🧪 Step 4: Run minimal test"
vendor/bin/phpunit tests/Unit/HealthTest.php --verbose

if [ $? -eq 0 ]; then
    echo "✅ Emergency recovery successful"
else
    echo "❌ Emergency recovery failed"
    echo "📞 Contact development team"
fi
```

### 2. CI/CD Pipeline Failure

#### Recovery Script: `scripts/ci-recovery.sh`

```bash
#!/bin/bash

# CI/CD Pipeline Recovery
# Recover from CI/CD failures

echo "🔄 CI/CD Pipeline Recovery"
echo "=========================="

# Check recent commits
echo "📋 Recent commits:"
git log --oneline -5

# Check for breaking changes
echo "🔍 Checking for breaking changes..."
git diff HEAD~1 --name-only | grep -E "(composer\.json|phpunit\.xml|infection\.json)"

# Reset to last known good state
echo "🔄 Resetting to last known good state..."
git checkout HEAD~1

# Run tests
echo "🧪 Running tests..."
vendor/bin/phpunit

if [ $? -eq 0 ]; then
    echo "✅ Recovery successful"
    echo "📋 Investigate changes in latest commit"
else
    echo "❌ Recovery failed"
    echo "🔄 Resetting further back..."
    git checkout HEAD~1
    vendor/bin/phpunit
fi
```

---

## 🤖 Automation Scripts

### 1. Health Monitor Service

#### Script: `scripts/health-monitor.sh`

```bash
#!/bin/bash

# Continuous Health Monitor
# Monitor test suite health and send alerts

MONITOR_INTERVAL=300  # 5 minutes
ALERT_THRESHOLD=3      # Alert after 3 consecutive failures
FAILURE_COUNT=0

while true; do
    echo "🔍 Health check - $(date)"
    
    cd backend-ci
    
    # Quick health check
    if vendor/bin/phpunit tests/Unit/HealthTest.php --no-coverage > /dev/null 2>&1; then
        echo "✅ Health check passed"
        FAILURE_COUNT=0
    else
        echo "❌ Health check failed"
        FAILURE_COUNT=$((FAILURE_COUNT + 1))
        
        if [ $FAILURE_COUNT -ge $ALERT_THRESHOLD ]; then
            echo "🚨 Alert threshold reached!"
            # Send alert (implement notification)
            # send_alert "Test suite health check failed $FAILURE_COUNT times"
        fi
    fi
    
    sleep $MONITOR_INTERVAL
done
```

### 2. Automated Report Generator

#### Script: `scripts/generate-reports.sh`

```bash
#!/bin/bash

# Automated Report Generator
# Generate comprehensive testing reports

REPORT_DIR="reports/$(date +%Y-%m-%d)"
mkdir -p $REPORT_DIR

echo "📊 Generating Reports - $(date)"
echo "============================="

cd backend-ci

# Coverage report
echo "📈 Generating coverage report..."
vendor/bin/phpunit --coverage-html=$REPORT_DIR/coverage --coverage-clover=$REPORT_DIR/clover.xml

# Mutation report
echo "🧬 Generating mutation report..."
MIN_MSI=80 ./scripts/mutation-test.sh > $REPORT_DIR/mutation.txt

# Performance report
echo "⚡ Generating performance report..."
vendor/bin/phpunit --log-junit=$REPORT_DIR/junit.xml
python3 scripts/analyze-performance.py $REPORT_DIR/junit.xml > $REPORT_DIR/performance.txt

# Summary report
echo "📋 Generating summary..."
cat > $REPORT_DIR/summary.md << EOF
# Test Suite Report - $(date)

## Coverage
$(grep "Lines:" $REPORT_DIR/coverage.txt 2>/dev/null || echo "Coverage report not available")

## Mutation Score
$(grep "MSI:" $REPORT_DIR/mutation.txt 2>/dev/null || echo "Mutation report not available")

## Performance
$(head -10 $REPORT_DIR/performance.txt 2>/dev/null || echo "Performance report not available")

## Recommendations
- Review slow tests (>5s)
- Improve coverage in critical modules
- Address escaped mutants
- Optimize test data setup
EOF

echo "✅ Reports generated in $REPORT_DIR"
echo "📋 Summary: $REPORT_DIR/summary.md"
```

### 3. Backup and Restore

#### Script: `scripts/backup-test-environment.sh`

```bash
#!/bin/bash

# Backup Test Environment
# Backup test configuration and data

BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
mkdir -p $BACKUP_DIR

echo "💾 Backing up test environment - $(date)"
echo "===================================="

cd backend-ci

# Backup configuration
echo "📋 Backing up configuration..."
cp .env $BACKUP_DIR/
cp phpunit.xml $BACKUP_DIR/
cp infection.json.dist $BACKUP_DIR/

# Backup database schema
echo "🗄️ Backing up database schema..."
php spark db:export --group tests > $BACKUP_DIR/schema.sql

# Backup test data (optional)
echo "📊 Backing up test data..."
if [ "$1" = "--with-data" ]; then
    php spark db:backup --group tests > $BACKUP_DIR/data.sql
fi

# Backup dependencies
echo "📦 Backing up dependencies..."
composer show --installed > $BACKUP_DIR/dependencies.txt

echo "✅ Backup completed: $BACKUP_DIR"
```

#### Script: `scripts/restore-test-environment.sh`

```bash
#!/bin/bash

# Restore Test Environment
# Restore test configuration and data

if [ -z "$1" ]; then
    echo "Usage: $0 <backup_directory>"
    exit 1
fi

BACKUP_DIR=$1

echo "🔄 Restoring test environment from $BACKUP_DIR"
echo "============================================="

cd backend-ci

# Restore configuration
echo "📋 Restoring configuration..."
cp $BACKUP_DIR/.env .
cp $BACKUP_DIR/phpunit.xml .
cp $BACKUP_DIR/infection.json.dist .

# Restore database
echo "🗄️ Restoring database..."
docker-compose down -v
docker-compose up -d db
sleep 30
docker exec meomeo2-db-1 mysql -u root -proot lanocrm_test < $BACKUP_DIR/schema.sql

# Restore test data if available
if [ -f "$BACKUP_DIR/data.sql" ]; then
    echo "📊 Restoring test data..."
    docker exec meomeo2-db-1 mysql -u root -proot lanocrm_test < $BACKUP_DIR/data.sql
fi

# Restore dependencies
echo "📦 Restoring dependencies..."
composer install

echo "✅ Restore completed"
echo "🧪 Running verification test..."
vendor/bin/phpunit tests/Unit/HealthTest.php
```

---

## 📋 Maintenance Schedule

### Daily Tasks
- [ ] Run health check script
- [ ] Monitor coverage trends
- [ ] Check for immediate failures

### Weekly Tasks
- [ ] Run full test suite
- [ ] Performance analysis
- [ ] Dependency updates check
- [ ] Generate weekly reports

### Monthly Tasks
- [ ] Comprehensive test suite audit
- [ ] Test data cleanup
- [ ] Documentation review
- [ ] Team training sessions

### Quarterly Tasks
- [ ] Strategy review
- [ ] Tooling assessment
- [ ] Infrastructure review
- [ ] Process optimization

---

## 📞 Emergency Contacts

### Primary Contacts
- **Test Lead**: [Contact Information]
- **DevOps**: [Contact Information]
- **System Admin**: [Contact Information]

### Escalation Path
1. **Level 1**: Team Lead
2. **Level 2**: Development Manager
3. **Level 3**: CTO

---

*Document last updated: 2025-12-03*
*Version: 1.0*