# Frontend Testing Guide - Consolidated

See archived versions for detailed historical patterns:
- archive/old-docs/testing/FE-TESTING-GUIDE.md
- archive/old-docs/testing/FE-TESTING-PATTERNS.md  
- archive/old-docs/testing/FE-TEST-CHECKLIST.md

## Quick Start

```bash
cd lanocrm
npm test                    # Unit/integration
npm run test:coverage       # With coverage  
npm run test:e2e           # Playwright E2E
```

## Requirements
- Coverage ≥ 70%
- All tests pass before commit
- E2E for critical flows

For complete guide, see: TESTING.md
