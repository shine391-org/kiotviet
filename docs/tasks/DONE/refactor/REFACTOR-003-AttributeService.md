# REFACTOR-003: AttributeService

**Type:** Refactoring
**Priority:** MEDIUM
**Status:** 📋 NOT STARTED
**Pattern to Copy:** [REFACTOR-001-ProductService.md](REFACTOR-001-ProductService.md)

---

## 🎯 OBJECTIVE

Refactor AttributesController from fat controller to clean architecture.

**Target:**
- Extract business logic → AttributeService
- Extract database queries → AttributeRepository
- Create validation layer → AttributeValidator
- Thin controller to routing only

---

## 📊 BEFORE & AFTER

### Before Refactoring

```
app/Controllers/Api/AttributesController.php
├── Mixed business logic + routing
├── Direct DB queries in controller
├── No tests
└── Hard to maintain
```

### After Refactoring

```
AttributesController.php ← Routing only
↓
AttributeService.php ← Business logic
↓
AttributeRepository.php ← Database queries
↓
AttributeValidator.php ← Validation rules
+
AttributeServiceTest.php ← Unit tests
```

---

## 📁 FILES TO CREATE/MODIFY

### Create

1. `app/Validators/AttributeValidator.php`
2. `app/Repositories/Products/AttributeRepository.php`
3. `app/Services/Products/AttributeService.php`
4. `tests/Services/AttributeServiceTest.php`

### Modify

5. `app/Controllers/Api/AttributesController.php`

---

## 🏗️ ARCHITECTURE PATTERN

**Copy structure from REFACTOR-001:**
- Validator: Validate list filters, create data, update data
- Repository: findAll, findById, create, update, delete queries
- Service: list, get, create, update, delete business logic
- Controller: Thin routing (5-10 lines per method)

**Note:** Attributes may have options (one-to-many relationship). Handle in Repository layer.

---

## 🧪 TESTING REQUIREMENTS

### Unit Tests (Target: 20+ tests)

- [ ] List attributes with filters
- [ ] Get attribute by ID
- [ ] Get attribute with options
- [ ] Create attribute with valid data
- [ ] Create attribute with invalid data
- [ ] Update attribute
- [ ] Delete attribute
- [ ] Validation error cases

### Integration Tests

- [ ] API endpoints return correct status codes
- [ ] Database operations work correctly
- [ ] Attribute-option relationships handled properly

---

## ✅ ACCEPTANCE CRITERIA

### Functional

- [ ] All API endpoints work exactly as before
- [ ] Response format unchanged (backward compatible)
- [ ] Frontend integration intact
- [ ] Performance maintained
- [ ] Attribute options relationship works

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
3. Copy Repository structure (handle options relationship)
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
