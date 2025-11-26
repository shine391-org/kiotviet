---
title: "Frontend Test Checklist"
id: "FE-TEST-CHECKLIST-01"
version: "1.0"
status: "Active"
module: "Frontend Testing"
type: "Checklist"
tags: ["testing", "checklist", "frontend", "react", "pr-process"]
purpose: "Provides a mandatory checklist for all Pull Requests to ensure code quality, test coverage, and functionality for frontend (React) changes."
location: "docs/testing"
related_to:
  - id: "FE-TESTING-GUIDE-01"
    description: "Refer to the comprehensive frontend testing guide."
  - id: "FE-TESTING-PATTERNS-01"
    description: "Refer to this for frontend code examples and patterns to copy."
  - id: "AGENT-GUIDE-01"
    description: "The main AI Agent Guide that mandates this checklist."
---

# Frontend Test Checklist

**Must be 100% Checked Before Merge**

## Unit Tests (Components & Hooks)
- [ ] Component renders without crashing
- [ ] All props (required & optional) tested
- [ ] User interactions (click, type, hover) tested
- [ ] Conditional rendering logic covered
- [ ] Custom hooks logic verified in isolation
- [ ] Utility functions have unit tests
- [ ] No console errors during test run

## Integration Tests (Flows)
- [ ] Form validation (empty, invalid, valid) works
- [ ] API success state (data display) verified
- [ ] API error state (toast/alert) verified
- [ ] API loading state (spinner/skeleton) verified
- [ ] Redux/Context state updates correctly
- [ ] Navigation/Routing works as expected

## E2E Tests (Critical Paths)
- [ ] Login/Logout flow passes
- [ ] Main CRUD operations work end-to-end
- [ ] Page navigation preserves state/url
- [ ] Works on Mobile viewport (if applicable)

## Manual Smoke Test
- [ ] UI looks correct (spacing, fonts, colors)
- [ ] No "Red" errors in Chrome Console
- [ ] No "Yellow" warnings (React keys, deprecated)
- [ ] Tab navigation (Accessibility) works
- [ ] Images/Icons load correctly
- [ ] Uploads/Downloads work (if applicable)
- [ ] Responsive check (Desktop vs Mobile)

## Code Quality & CI
- [ ] Coverage >= 70% (`npm run test:coverage`)
- [ ] `npm test` passes locally
- [ ] `npm run lint` passes (no ESLint errors)
- [ ] No hardcoded strings (use constants/i18n)
- [ ] No `console.log` left in code
- [ ] Unused imports removed

---
**Signed off by:** ____________________
