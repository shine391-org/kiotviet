# Test Checklist - Copy into every PR

## Unit Tests ✓
- [ ] Service tests written and passing
- [ ] Repository tests written and passing
- [ ] Validator tests written (if applicable)
- [ ] Edge cases covered (null, empty, invalid)
- [ ] Exception handling tested

## Integration Tests ✓
- [ ] API endpoints tested with MySQL
- [ ] Authentication/authorization works
- [ ] Database transactions rollback correctly
- [ ] File upload works (if API has upload)
- [ ] Cross-module interactions work

## Manual Tests ✓
- [ ] Login flow works
- [ ] CRUD operations work via Postman/curl
- [ ] Error messages display correctly
- [ ] Validation messages are user-friendly

## Code Quality ✓
- [ ] Coverage >= 70% (check: `phpunit --coverage-text`)
- [ ] No PHPUnit warnings/errors
- [ ] Single Responsibility followed
- [ ] Inline docs complete (@agent-* tags)

## Safety Checks ✓
- [ ] Run: `bash .ai/pre-commit-checks.sh` - PASS
- [ ] Auth endpoints still work (test login)
- [ ] Existing features not broken
- [ ] Only modified files within task scope

## Performance (Optional for critical APIs) ✓
- [ ] Query N+1 problems checked
- [ ] Large dataset tested (>1000 records)
- [ ] Response time < 500ms

---

**If ANY item FAILS → FIX before merging!**
