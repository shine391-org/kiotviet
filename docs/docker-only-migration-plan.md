# Docker-Only Migration Plan
**Mục tiêu:** Loại bỏ hoàn toàn PHP/Composer ở WSL level để tránh environment confusion

## 🎯 Tại sao cần Docker-Only?

### **Vấn đề hiện tại:**
```bash
# ❌ NHẦM LẪN: Có thể chạy cả hai cách
cd backend-ci && php spark serve        # WSL PHP
docker exec meomeo2-api-1 php spark serve  # Docker PHP

# → Kết quả: Different environments, different configs
```

### **Giải pháp Docker-Only:**
```bash
# ✅ CHỈ MỘT CÁCH: Docker bắt buộc
docker exec meomeo2-api-1 php spark serve  # Cách duy nhất

# → Kết quả: Consistent environment, no confusion
```

## 📋 Kế hoạch migration

### **Phase 1: Cleanup WSL PHP (Ngay lập tức)**

#### **1.1 Xóa PHP/Composer ở WSL level**
```bash
# Check current PHP installations
which php
php --version
which composer
composer --version

# Remove PHP (CẨN THẬN - có thể ảnh hưởng projects khác)
sudo apt remove php8.4 php8.4-cli php8.4-common
sudo apt autoremove

# Remove Composer
sudo rm /usr/local/bin/composer
```

#### **1.2 Create shell aliases để强制 dùng Docker**
```bash
# Thêm vào ~/.bashrc hoặc ~/.zshrc
alias php='docker exec meomeo2-api-1 php'
alias composer='docker exec meomeo2-api-1 composer'
alias spark='docker exec meomeo2-api-1 php spark'

# Reload shell
source ~/.bashrc
```

#### **1.3 Test aliases**
```bash
php --version        # Should show Docker PHP version
composer --version   # Should show Docker Composer version
spark list          # Should show CodeIgniter commands from Docker
```

### **Phase 2: Fix remaining model issues (Trong ngày)**

#### **2.1 Fix tất cả models với `db_` prefix**
```bash
# Tìm tất cả models cần fix
grep -r "protected \$table = 'db_" backend-ci/app/Models/

# Cần fix:
- OrderModel: 'db_orders' → 'orders'
- OrderItemModel: 'db_order_items' → 'order_items'
- WebhookEventModel: 'db_webhook_events' → 'webhook_events'
- WebhookSubscriptionModel: 'db_webhook_subscriptions' → 'webhook_subscriptions'
```

#### **2.2 Add validation để prevent regression**
```php
// Tạo backend-ci/app/Commands/ValidateModels.php
class ValidateModels extends Command {
    public function run(array $params) {
        $models = [
            'ProductModel', 'OrderModel', 'OrderItemModel',
            'WebhookEventModel', 'WebhookSubscriptionModel'
        ];
        
        foreach ($models as $model) {
            $instance = new $model();
            if (strpos($instance->table, 'db_') === 0) {
                throw new \Exception("Test prefix detected in $model!");
            }
        }
        
        echo "✅ All models validated - no test prefixes found\n";
    }
}
```

### **Phase 3: Environment safeguards (Tuần này)**

#### **3.1 Add environment validation**
```php
// backend-ci/app/Config/Boot/Development.php
if (ENVIRONMENT === 'development') {
    // Validate no test prefixes in development
    $models = glob(APPPATH . 'Models/*.php');
    foreach ($models as $file) {
        $content = file_get_contents($file);
        if (strpos($content, "protected \$table = 'db_") !== false) {
            throw new \Exception('Test table prefix detected in development!');
        }
    }
}
```

#### **3.2 Create Docker-only scripts**
```bash
# backend-ci/docker-spark
#!/bin/bash
docker exec meomeo2-api-1 php spark "$@"

# backend-ci/docker-composer  
#!/bin/bash
docker exec meomeo2-api-1 composer "$@"

# backend-ci/docker-php
#!/bin/bash
docker exec meomeo2-api-1 php "$@"

# Make executable
chmod +x backend-ci/docker-*
```

#### **3.3 Update package.json scripts**
```json
{
  "scripts": {
    "api:migrate": "cd ../backend-ci && docker exec meomeo2-api-1 php spark migrate",
    "api:test": "cd ../backend-ci && docker exec meomeo2-api-1 vendor/bin/phpunit",
    "api:seed": "cd ../backend-ci && docker exec meomeo2-api-1 php spark db:seed"
  }
}
```

### **Phase 4: Documentation & Training (Tuần sau)**

#### **4.1 Update development guide**
```markdown
# Development Workflow - Docker Only

## Start development
```bash
docker-compose up -d
```

## Run PHP commands
```bash
# ✅ Docker-only ways
docker exec meomeo2-api-1 php spark migrate
./backend-ci/docker-spark migrate
spark migrate  # with alias

# ❌ These won't work anymore
php spark migrate  # PHP not found in WSL
```

## Testing
```bash
# Unit tests
docker exec meomeo2-api-1 vendor/bin/phpunit

# Integration tests  
docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml
```
```

#### **4.2 Create onboarding checklist**
```markdown
# New Developer Onboarding

## Prerequisites
- Docker Desktop installed
- VS Code with Docker extension
- Git

## Setup (30 minutes)
1. Clone project
2. Run `docker-compose up -d`
3. Wait for containers to start
4. Test with `docker exec meomeo2-api-1 php spark env`
5. Access FE at http://localhost:3000
6. Access API at http://localhost:8000

## Daily workflow
1. Start containers: `docker-compose up -d`
2. Run migrations: `docker exec meomeo2-api-1 php spark migrate`
3. Run tests: `docker exec meomeo2-api-1 vendor/bin/phpunit`
4. Stop containers: `docker-compose down`
```

## 🚨 Risks & Mitigations

### **Risk 1: Breaking other projects**
**Mitigation:**
- Check what other projects use PHP before removing
- Use aliases instead of complete removal
- Document rollback procedure

### **Risk 2: Team resistance**
**Mitigation:**
- Gradual migration with aliases first
- Clear documentation and training
- Benefits communication

### **Risk 3: Docker performance**
**Mitigation:**
- Optimize Docker configuration
- Use volume mounts properly
- Monitor resource usage

## 📊 Success metrics

### **Technical metrics:**
- ✅ Zero environment confusion incidents
- ✅ 100% consistent test results
- ✅ All developers using Docker commands

### **Process metrics:**
- ✅ Reduced onboarding time (30 mins vs 2 hours)
- ✅ Zero "works on my machine" issues
- ✅ Consistent development environments

## 🔄 Rollback plan

Nếu có vấn đề, có thể rollback:
```bash
# Reinstall PHP in WSL
sudo apt install php8.4 php8.4-cli php8.4-common

# Reinstall Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Remove aliases from ~/.bashrc
```

---

**Timeline:**
- **Phase 1:** Today (1 hour)
- **Phase 2:** Today (2 hours)  
- **Phase 3:** This week (4 hours)
- **Phase 4:** Next week (2 hours)

**Total effort:** ~9 hours
**Risk reduction:** 90% fewer environment issues