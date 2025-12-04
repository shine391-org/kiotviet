---
title: "Playwright WSL Configuration Guide"
id: "PLAYWRIGHT-WSL-01"
version: "4.0"
status: "Active"
module: "Testing"
type: "Guide"
tags: ["playwright", "wsl", "e2e", "configuration", "troubleshooting"]
purpose: "Provides comprehensive guide for Playwright configuration and troubleshooting on WSL environments"
location: "docs/testing"
updated: "2025-12-03"
changes: "Updated YAML frontmatter for documentation consolidation"
related_to:
  - id: "TESTING-RULES-01"
    description: "Comprehensive testing rules and guidelines"
  - id: "FRONTEND-TESTING-01"
    description: "Frontend testing guide with real database integration"
  - id: "BACKEND-TESTING-01"
    description: "Backend testing guide with test database only"
  - id: "TEST-CHECKLIST-01"
    description: "Mandatory checklist for all testing changes"
---

# Playwright WSL Configuration Guide

## 🚨 WSL-Specific Considerations

Playwright on WSL (Windows Subsystem for Linux) requires special configuration to work properly. This guide covers common issues and solutions.

## 🔧 Initial Setup

### 1. Install Playwright Browsers

```bash
cd ~/projects/kiotviet/lanocrm

# Standard installation
npx playwright install

# WSL-specific installation with dependencies
npx playwright install --with-deps chromium

# If permission issues occur
sudo npx playwright install --with-deps chromium
```

### 2. Install System Dependencies

```bash
# Update package list
sudo apt-get update

# Install required system packages for Playwright
sudo apt-get install -y \
    wget \
    ca-certificates \
    fonts-liberation \
    libasound2 \
    libatk-bridge2.0-0 \
    libatk1.0-0 \
    libc6 \
    libcairo2 \
    libcups2 \
    libdbus-1-3 \
    libexpat1 \
    libfontconfig1 \
    libgbm1 \
    libgcc1 \
    libglib2.0-0 \
    libgtk-3-0 \
    libnspr4 \
    libnss3 \
    libpango-1.0-0 \
    libpangocairo-1.0-0 \
    libstdc++6 \
    libx11-6 \
    libx11-xcb1 \
    libxcb1 \
    libxcomposite1 \
    libxcursor1 \
    libxdamage1 \
    libxext6 \
    libxfixes3 \
    libxi6 \
    libxrandr2 \
    libxrender1 \
    libxss1 \
    libxtst6 \
    lsb-release \
    xdg-utils
```

## 🐛 Common Issues and Solutions

### Issue 1: Permission Denied Errors

**Symptoms:**
```
Error: EACCES: permission denied, access '/home/user/.cache/ms-playwright'
```

**Solutions:**
```bash
# Fix Playwright cache permissions
sudo chmod -R 755 ~/.cache/ms-playwright

# Run tests with admin rights (if needed)
sudo npm run test:e2e -- --project=chromium tests/e2e/product-flows.spec.ts

# Or add user to appropriate groups
sudo usermod -aG docker,video $USER
# Logout and login again
```

### Issue 2: Browser Installation Fails

**Symptoms:**
```
Error: Failed to install chromium
Error: EACCES: permission denied
```

**Solutions:**
```bash
# Install with admin rights
sudo npx playwright install --with-deps chromium

# Or install manually
sudo apt-get update
sudo apt-get install -y chromium-browser

# Then tell Playwright to use system browser
npx playwright install chromium
```

### Issue 3: Network Connection Issues

**Symptoms:**
```
Error: connect ECONNREFUSED 127.0.0.1:8080
Error: getaddrinfo ENOTFOUND localhost
```

**Solutions:**
```bash
# Test network connectivity
ping localhost
ping 127.0.0.1

# If ping fails, install network tools
sudo apt-get install -y net-tools iputils-ping

# Check if backend is running
docker-compose ps
docker-compose up -d db-test api

# Test API connection
curl http://localhost:8080/api/health
```

### Issue 4: Display/X11 Issues

**Symptoms:**
```
Error: Cannot open display: :0
Error: GLXBadFBConfig
```

**Solutions:**
```bash
# Set display environment
export DISPLAY=:0

# Install X11 forwarding
sudo apt-get install -y x11-apps

# Test X11 forwarding
xeyes

# For WSL2, you might need X server on Windows
# Install VcXsrv or Xming on Windows
export DISPLAY=$(cat /etc/resolv.conf | grep nameserver | awk '{print $2}'):0
```

### Issue 5: Docker Issues on WSL

**Symptoms:**
```
Error: Cannot connect to the Docker daemon
Error: permission denied while trying to connect to the Docker daemon socket
```

**Solutions:**
```bash
# Start Docker service
sudo service docker start

# Add user to docker group
sudo usermod -aG docker $USER
# Logout and login again

# Or run Docker with sudo
sudo docker-compose up -d db-test api
```

## 📋 Recommended Playwright Config for WSL

Update your `playwright.config.ts` with these WSL-specific settings:

```typescript
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    
    // WSL-specific settings
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : undefined,
    
    // Increase timeout for WSL
    timeout: 60000,
    expect: {
        timeout: 10000,
    },
    
    reporter: 'html',
    
    use: {
        // Use localhost for WSL
        baseURL: process.env.BASE_URL || 'http://localhost:3000',
        trace: 'on-first-retry',
        // Increase screenshot timeout for WSL
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    
    projects: [
        {
            name: 'chromium',
            use: { 
                ...devices['Desktop Chrome'],
                // WSL-specific launch options
                launchOptions: {
                    args: [
                        '--no-sandbox',
                        '--disable-setuid-sandbox',
                        '--disable-dev-shm-usage',
                        '--disable-gpu'
                    ]
                }
            },
        },
        // Add other browsers as needed
        {
            name: 'firefox',
            use: { ...devices['Desktop Firefox'] },
        },
    ],
    
    // WSL-specific web server configuration
    webServer: {
        command: 'npm run dev',
        url: 'http://localhost:3000',
        reuseExistingServer: !process.env.CI,
        timeout: 120000, // 2 minutes for WSL
    },
});
```

## 🚀 Running E2E Tests on WSL

### Standard Commands

```bash
cd ~/projects/kiotviet/lanocrm

# Primary E2E command (example)
npm run test:e2e -- --project=chromium tests/e2e/product-flows.spec.ts

# All E2E tests
npm run test:e2e

# With UI for debugging
npm run test:e2e -- --ui

# With headed mode (show browser)
npm run test:e2e -- --headed

# With increased timeout
npm run test:e2e -- --timeout=60000
```

### WSL-Specific Commands

```bash
# With admin rights (if needed)
sudo npm run test:e2e -- --project=chromium tests/e2e/product-flows.spec.ts

# With custom display
export DISPLAY=:0
npm run test:e2e -- --project=chromium tests/e2e/product-flows.spec.ts

# With debugging
npm run test:e2e -- --debug --project=chromium tests/e2e/product-flows.spec.ts

# With trace viewer
npm run test:e2e -- --trace on --project=chromium tests/e2e/product-flows.spec.ts
```

## 🔍 Troubleshooting Checklist

### Before Running Tests

- [ ] Docker is running: `sudo service docker start`
- [ ] Backend is accessible: `curl http://localhost:8080/api/health`
- [ ] Playwright browsers installed: `npx playwright install --with-deps chromium`
- [ ] Permissions are correct: `sudo chmod -R 755 ~/.cache/ms-playwright`
- [ ] Network is working: `ping localhost`

### If Tests Fail

1. **Check browser installation**:
   ```bash
   npx playwright install --with-deps chromium
   ```

2. **Check permissions**:
   ```bash
   sudo chmod -R 755 ~/.cache/ms-playwright
   ```

3. **Check network connectivity**:
   ```bash
   ping localhost
   curl http://localhost:8080/api/health
   ```

4. **Check Docker status**:
   ```bash
   sudo service docker start
   docker-compose ps
   ```

5. **Run with admin rights**:
   ```bash
   sudo npm run test:e2e -- --project=chromium tests/e2e/product-flows.spec.ts
   ```

## 📞 Getting Help

If you encounter issues not covered here:

1. **Check Playwright documentation**: https://playwright.dev/docs/troubleshooting
2. **Check WSL documentation**: https://docs.microsoft.com/en-us/windows/wsl/
3. **Contact admin** for network/package installation issues
4. **Create an issue** in the project repository with:
   - Error message
   - WSL version (`wsl --version`)
   - Playwright version (`npx playwright --version`)
   - Steps to reproduce

---

**Remember**: WSL requires special configuration for Playwright. Always test with the specific command pattern provided and don't hesitate to use admin rights when needed.