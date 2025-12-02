# Deployment Checklist

## 📋 Pre-Deployment Checklist

### All Environments

- [ ] **Code Review Complete**
  - All PRs reviewed and approved
  - No pending code review comments
  - All tests passing in CI/CD

- [ ] **Documentation Updated**
  - CHANGELOG.md updated
  - API documentation current
  - README files accurate

- [ ] **Dependencies Checked**
  - composer.lock committed
  - package-lock.json committed
  - No security vulnerabilities (`npm audit`, `composer audit`)

- [ ] **Environment Files Prepared**
  - `.env` file configured for target environment
  - Secrets properly managed
  - Database credentials secured

- [ ] **Backup Strategy Confirmed**
  - Backup script tested
  - Backup location verified
  - Restore procedure documented

---

## 🏠 Development Deployment

### Pre-Deploy

- [ ] Docker and Docker Compose installed
- [ ] Ports 3000, 8000, 8080, 3306 available
- [ ] At least 8GB RAM available
- [ ] At least 20GB disk space free

### Deploy Steps

- [ ] Clone repository
  ```bash
  git clone <repository-url>
  cd meomeo2
  ```

- [ ] Start services
  ```bash
  docker compose up -d --build
  ```

- [ ] Monitor startup
  ```bash
  docker compose logs -f api
  ```
  Wait for: `✓ Database initialization completed`

- [ ] Verify setup
  ```bash
  chmod +x scripts/check-migration-status.sh
  ./scripts/check-migration-status.sh
  ```

### Post-Deploy Verification

- [ ] All containers running (`docker ps`)
- [ ] Frontend accessible (http://localhost:3000)
- [ ] Backend API responding (http://localhost:8000/api/health)
- [ ] phpMyAdmin accessible (http://localhost:8080)
- [ ] Database has ~164 tables
- [ ] Demo data loaded
- [ ] Can login with admin credentials

### Rollback (if needed)

- [ ] Stop containers: `docker compose down`
- [ ] Remove volumes: `docker volume rm meomeo2_db_data`
- [ ] Restart: `docker compose up -d --build`

---

## 🧪 Staging Deployment

### Pre-Deploy

- [ ] **Backup Current State**
  ```bash
  ./scripts/db-backup.sh
  ```

- [ ] **Test on Development First**
  - All features tested locally
  - Migration tested locally
  - Seeding tested locally

- [ ] **Server Preparation**
  - Docker installed and updated
  - Docker Compose installed
  - Firewall rules configured
  - SSL certificate ready (if applicable)

- [ ] **Environment Configuration**
  - `.env` file prepared with staging settings
  - `CI_ENVIRONMENT=staging`
  - Strong passwords set
  - Correct database name

### Deploy Steps

- [ ] **SSH to Staging Server**
  ```bash
  ssh user@staging-server
  ```

- [ ] **Pull Latest Code**
  ```bash
  cd /path/to/meomeo2
  git fetch origin
  git checkout <tag-or-branch>
  ```

- [ ] **Update Environment File**
  ```bash
  cp backend-ci/.env.example backend-ci/.env
  nano backend-ci/.env
  ```

- [ ] **Build and Start**
  ```bash
  docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
  ```

- [ ] **Monitor Startup**
  ```bash
  docker-compose logs -f api
  ```

- [ ] **Verify Migration**
  ```bash
  ./scripts/check-migration-status.sh
  ```

- [ ] **Seed Staging Data (Optional)**
  ```bash
  docker exec staging-api php spark db:seed StagingSeeder
  ```

### Post-Deploy Verification

- [ ] All containers running
- [ ] Migration completed successfully
- [ ] Table count correct (~164 tables)
- [ ] API health check passing
- [ ] Frontend loads correctly
- [ ] Can login with test accounts
- [ ] Critical features working:
  - [ ] User authentication
  - [ ] Product listing
  - [ ] Order creation
  - [ ] Invoice generation
  - [ ] Cash transactions

### Monitoring Setup

- [ ] Log monitoring configured
- [ ] Error alerts set up
- [ ] Resource monitoring active
- [ ] Backup cron job configured

### Rollback (if needed)

- [ ] Stop containers
  ```bash
  docker-compose down
  ```

- [ ] Restore database
  ```bash
  ./scripts/db-restore.sh backups/pre-deploy-backup.sql
  ```

- [ ] Checkout previous version
  ```bash
  git checkout <previous-tag>
  ```

- [ ] Rebuild and start
  ```bash
  docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
  ```

---

## 🚀 Production Deployment

### Pre-Deploy (Critical!)

- [ ] **Backup Everything**
  ```bash
  ./scripts/db-backup.sh
  # Verify backup file exists and is not empty
  ls -lh backups/
  ```

- [ ] **Test on Staging**
  - All features tested on staging
  - Migration tested on staging
  - Performance tested on staging
  - Load tested (if applicable)

- [ ] **Deployment Window**
  - Maintenance window scheduled
  - Users notified
  - Team available for support

- [ ] **Rollback Plan Ready**
  - Previous version tagged
  - Rollback procedure documented
  - Team trained on rollback

- [ ] **Security Review**
  - All passwords changed from defaults
  - SSL certificate valid
  - Firewall rules reviewed
  - Security scan completed

### Deploy Steps

- [ ] **Announce Maintenance**
  - Send notification to users
  - Update status page
  - Set maintenance mode (if applicable)

- [ ] **SSH to Production Server**
  ```bash
  ssh user@production-server
  ```

- [ ] **Final Backup**
  ```bash
  cd /opt/lanocrm
  ./scripts/db-backup.sh
  # Copy backup to safe location
  cp backups/latest.sql /backup/production/pre-deploy-$(date +%Y%m%d-%H%M%S).sql
  ```

- [ ] **Pull Latest Code**
  ```bash
  git fetch origin
  git checkout <release-tag>  # Use specific tag, not branch!
  ```

- [ ] **Update Environment File**
  ```bash
  # Verify production settings
  cat backend-ci/.env | grep CI_ENVIRONMENT
  # Should show: CI_ENVIRONMENT = production
  ```

- [ ] **Stop API (Prevent Writes)**
  ```bash
  docker-compose stop api
  ```

- [ ] **Choose Deployment Strategy**

  **Option A: Auto-Migration (Simpler)**
  ```bash
  docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
  docker-compose logs -f api
  ```

  **Option B: Database Import (Safer)**
  ```bash
  # Import pre-migrated database from staging
  docker exec -i prod-db mysql -u lanocrm_user -p lanocrm_prod < production-ready.sql
  docker exec prod-api touch /var/www/html/writable/.db_initialized
  docker-compose start api
  ```

- [ ] **Monitor Startup**
  ```bash
  docker-compose logs -f api
  # Watch for: ✓ Database initialization completed
  ```

- [ ] **Verify Migration**
  ```bash
  ./scripts/check-migration-status.sh
  ```

### Post-Deploy Verification (Critical!)

- [ ] **Container Health**
  ```bash
  docker-compose ps
  # All containers should be "Up"
  ```

- [ ] **Migration Status**
  ```bash
  ./scripts/check-migration-status.sh
  # Should show ~164 tables
  ```

- [ ] **API Health Check**
  ```bash
  curl https://yourdomain.com/api/health
  # Should return 200 OK
  ```

- [ ] **Database Integrity**
  ```bash
  # Check table count
  docker exec prod-db mysql -u lanocrm_user -p -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'lanocrm_prod'"
  # Should be ~164
  ```

- [ ] **Frontend Access**
  ```bash
  curl -I https://yourdomain.com
  # Should return 200 OK
  ```

- [ ] **Authentication Test**
  - Login with admin account
  - Verify JWT token generation
  - Test protected endpoints

- [ ] **Critical Features Test**
  - [ ] User login/logout
  - [ ] Product listing
  - [ ] Order creation
  - [ ] Invoice generation
  - [ ] Payment processing
  - [ ] Cash transactions
  - [ ] Reports generation

- [ ] **Performance Check**
  ```bash
  # Check response times
  curl -w "@curl-format.txt" -o /dev/null -s https://yourdomain.com/api/products
  
  # Check resource usage
  docker stats
  ```

- [ ] **Log Review**
  ```bash
  # Check for errors
  docker-compose logs api | grep -i error | tail -50
  docker-compose logs db | grep -i error | tail -50
  ```

### Post-Deploy Monitoring

- [ ] **Set Up Monitoring**
  - Health check cron job running
  - Error alerts configured
  - Resource monitoring active
  - Log aggregation working

- [ ] **Backup Verification**
  - Post-deploy backup created
  - Backup file integrity verified
  - Backup restore tested (on staging)

- [ ] **Documentation**
  - Deployment notes recorded
  - Any issues documented
  - Lessons learned captured

### Announce Completion

- [ ] Remove maintenance mode
- [ ] Send completion notification to users
- [ ] Update status page
- [ ] Notify team of successful deployment

### Rollback (Emergency Only!)

If critical issues found:

- [ ] **Immediate Actions**
  ```bash
  # Stop new containers
  docker-compose down
  
  # Restore database
  ./scripts/db-restore.sh /backup/production/pre-deploy-*.sql
  
  # Checkout previous version
  git checkout <previous-release-tag>
  
  # Rebuild and start
  docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
  ```

- [ ] **Verify Rollback**
  - All services running
  - Database restored
  - Application functional
  - Users can access system

- [ ] **Post-Rollback**
  - Notify users of rollback
  - Document rollback reason
  - Plan fix for next deployment

---

## 📊 Deployment Summary Template

After each deployment, fill this out:

```
Deployment Summary
==================
Date: YYYY-MM-DD HH:MM
Environment: [Development/Staging/Production]
Version: [tag/commit]
Deployed By: [name]

Pre-Deployment:
- Backup Created: [Yes/No] [filename]
- Tests Passed: [Yes/No]
- Staging Tested: [Yes/No] (Production only)

Deployment:
- Start Time: HH:MM
- End Time: HH:MM
- Duration: XX minutes
- Method: [Auto-Migration/Database Import]
- Downtime: XX minutes

Post-Deployment:
- Migration Status: [Success/Failed]
- Table Count: XXX
- Health Check: [Pass/Fail]
- Critical Features: [All Working/Issues Found]

Issues Encountered:
- [List any issues]

Rollback Required: [Yes/No]

Notes:
- [Any additional notes]
```

---

## 🆘 Emergency Contacts

**During Deployment:**
- DevOps Lead: [contact]
- Backend Lead: [contact]
- Frontend Lead: [contact]
- Database Admin: [contact]

**Escalation:**
- Technical Manager: [contact]
- CTO: [contact]

---

## 📚 Related Documentation

- **Migration Guide:** [`docs/MIGRATION-TROUBLESHOOTING.md`](MIGRATION-TROUBLESHOOTING.md)
- **Environment Guide:** [`docs/DEPLOYMENT-BY-ENVIRONMENT.md`](DEPLOYMENT-BY-ENVIRONMENT.md)
- **Seeding Strategy:** [`docs/SEEDING-STRATEGY.md`](SEEDING-STRATEGY.md)
- **Quick Reference:** [`docs/MIGRATION-QUICK-REFERENCE.md`](MIGRATION-QUICK-REFERENCE.md)

---

## ✅ Sign-Off

**Deployment Approved By:**
- [ ] Technical Lead: _________________ Date: _______
- [ ] DevOps Lead: _________________ Date: _______
- [ ] Project Manager: _________________ Date: _______

**Deployment Completed By:**
- [ ] Deployer: _________________ Date: _______ Time: _______
- [ ] Verified By: _________________ Date: _______ Time: _______