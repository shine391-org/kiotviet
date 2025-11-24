# YYYY-MM-DD - {Task Name}

**Related Task:** [TASK-XXX]({link-to-task-file})

## Tasks Completed

- [ ] Item 1
- [ ] Item 2
- [ ] Item 3

## Files Touched

### New Files
- `backend-ci/app/Services/...`
- `backend-ci/app/Repositories/...`

### Modified Files
- `backend-ci/app/Controllers/...`

### Tests
- `tests/Services/...Test.php`
- `tests/Integration/...Test.php`

## Testing

**Unit Tests (SQLite):**
```bash
docker exec meomeo2-api-1 vendor/bin/phpunit tests/Services/...
```
Result: X tests, Y assertions ✅/❌

**Integration Tests (MySQL):**
```bash
docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml tests/Integration/...
```
Result: X tests, Y assertions ✅/❌

**Frontend Tests:**
```bash
npm test
```
Result: ✅/❌

## Notes / Issues

- Note 1
- Issue encountered: ...
- Fix applied: ...

## Next Steps

- [ ] Next task to do
- [ ] Follow-up work needed
