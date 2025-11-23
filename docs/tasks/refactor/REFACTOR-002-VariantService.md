# REFACTOR-002: VariantService

**Type:** Refactoring
**Priority:** MEDIUM
**Status:** 📋 NOT STARTED
**Pattern to Copy:** [REFACTOR-001-ProductService.md](REFACTOR-001-ProductService.md)

---

## 🎯 OBJECTIVE

Refactor ProductVariantsController from fat controller to clean architecture.

**Target:**
- Extract business logic → VariantService
- Extract database queries → VariantRepository
- Create validation layer → VariantValidator
- Thin controller to routing only

---

## 📊 BEFORE & AFTER

### Before Refactoring

```
app/Controllers/Api/ProductVariantsController.php
├── Mixed business logic + routing
├── Direct DB queries in controller
├── No tests
└── Hard to maintain
```

### After Refactoring

```
VariantsController.php ← Routing only
↓
VariantService.php ← Business logic
↓
VariantRepository.php ← Database queries
↓
VariantValidator.php ← Validation rules
+
VariantServiceTest.php ← Unit tests
```

---

## 📁 FILES TO CREATE/MODIFY

### Create

1. `app/Validators/VariantValidator.php`
2. `app/Repositories/Products/VariantRepository.php`
3. `app/Services/Products/VariantService.php`
4. `tests/Services/VariantServiceTest.php`

### Modify

5. `app/Controllers/Api/ProductVariantsController.php`

---

## 🏗️ ARCHITECTURE PATTERN

**Copy structure from REFACTOR-001:**
- Validator: Validate list filters, create data, update data
- Repository: findAll, findById, create, update, delete queries
- Service: list, get, create, update, delete business logic
- Controller: Thin routing (5-10 lines per method)

---

## 🧪 TESTING REQUIREMENTS

### Unit Tests (Target: 20+ tests)

- [ ] List variants with filters
- [ ] Get variant by ID
- [ ] Create variant with valid data
- [ ] Create variant with invalid data
- [ ] Update variant
- [ ] Delete variant
- [ ] Validation error cases

### Integration Tests

- [ ] API endpoints return correct status codes
- [ ] Database operations work correctly

---

## ✅ ACCEPTANCE CRITERIA

### Functional

- [ ] All API endpoints work exactly as before
- [ ] Response format unchanged (backward compatible)
- [ ] Frontend integration intact
- [ ] Performance maintained

### Technical

- [ ] Controller < 150 lines
- [ ] Service follows single responsibility
- [ ] Repository handles all DB queries
- [ ] Validator has clear validation rules
- [ ] All files have @agent- tags
- [ ] Tests pass 100%
- [ ] Pre-commit checks pass

---

## 📚 REFERENCE

**Pattern to copy:** [REFACTOR-001-ProductService.md](REFACTOR-001-ProductService.md)

**Steps:**
1. Read REFACTOR-001 for pattern
2. Copy Validator structure
3. Copy Repository structure
4. Copy Service structure
5. Update Controller
6. Write tests (copy test pattern)
7. Run tests
8. Create session log

---

## 📝 DELIVERABLES

- [ ] 4 new files created
- [ ] 1 file modified
- [ ] Tests written and passing
- [ ] Session log created
- [ ] Task status updated

---

**Created:** 2025-11-23
**Estimated Effort:** 1 day
**Dependencies:** REFACTOR-001 (completed)
