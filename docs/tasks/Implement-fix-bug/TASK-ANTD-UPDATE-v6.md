# TASK: Update Ant Design to v6.0.0

**Type:** Dependency Update (Breaking Changes)
**Priority:** HIGH
**Status:** 📋 Planning
**Branch:** feature/update-antd

---

## 🎯 OBJECTIVE

Update Ant Design from **5.29.1** → **6.0.0** (Major version upgrade)

**Why?**
- Security updates
- Performance improvements
- React 19 compatibility improvements
- New features & bug fixes

**Risks:**
- Breaking changes in v6
- Potential UI regressions
- Component API changes

---

## 📊 CURRENT STATE

### Package Versions
```json
{
  "antd": "^5.29.1",
  "@ant-design/icons": "^6.1.0",
  "react": "^19.2.0",
  "react-dom": "^19.2.0",
  "vite": "^7.2.2"
}
```

### Current Issues
1. **Line 7 in main.jsx:** Using `'antd/dist/reset.css'` (OLD way)
   - Ant Design 5+ uses CSS-in-JS
   - No need to import CSS files

---

## 📋 UPDATE PLAN

### Phase 1: Research & Backup ✅ DONE

- [x] Create feature branch `feature/update-antd`
- [x] Document current state
- [x] Check latest versions

### Phase 2: Update Dependencies

**Step 1: Update Ant Design packages**
```bash
cd lanocrm
npm install antd@latest @ant-design/icons@latest
```

**Step 2: Check peer dependency warnings**
```bash
npm list react react-dom
```

**Expected versions after update:**
- antd: 6.0.0
- @ant-design/icons: latest compatible version
- react: 19.2.0 (keep)
- vite: 7.2.2 (keep)

### Phase 3: Code Changes

**File: `lanocrm/src/main.jsx`**

```diff
- import 'antd/dist/reset.css'; // 🆕 Ant Design styles
```

Remove this line completely. Ant Design 5+ uses CSS-in-JS, styles are injected automatically.

**Result:**
```jsx
import { ConfigProvider } from 'antd';
import viVN from 'antd/locale/vi_VN';
// No CSS import needed!
```

### Phase 4: Test Breaking Changes

**Common breaking changes in Ant Design 6.0:**

1. **Component API changes**
   - Check all Ant Design components used
   - Review deprecation warnings in console

2. **Theme token changes**
   - ConfigProvider theme might have new token names

3. **Icon changes**
   - @ant-design/icons might have removed/renamed icons

**Files to test:**
```bash
# Search all Ant Design component usage
grep -r "from 'antd'" lanocrm/src --include="*.jsx" --include="*.js"
```

### Phase 5: Visual Regression Testing

**Manual testing required:**
- [ ] Login page
- [ ] Dashboard
- [ ] Tables (Products, Users, Roles)
- [ ] Forms (Create/Edit)
- [ ] Modals & Drawers
- [ ] Notifications/Messages
- [ ] Date pickers
- [ ] Select dropdowns
- [ ] Buttons & Icons

---

## 🧪 TESTING STRATEGY

### 1. Development Testing
```bash
cd lanocrm
npm run dev
```

**Check:**
- No console errors
- No warning about deprecated APIs
- All pages render correctly

### 2. Unit Tests
```bash
npm test
```

**Expect:**
- All tests pass
- No new warnings

### 3. Build Testing
```bash
npm run build
npm run preview
```

**Check:**
- Build succeeds
- Bundle size (should be similar or smaller)
- Production build works

---

## 🚨 ROLLBACK PLAN

If update fails:

```bash
git checkout feature/price-lists
git branch -D feature/update-antd
```

Or revert changes:
```bash
cd lanocrm
npm install antd@5.29.1 @ant-design/icons@6.1.0
git checkout main.jsx
```

---

## 📝 CHECKLIST

### Pre-Update
- [x] Create feature branch
- [x] Document current versions
- [ ] Take screenshots of key pages (before)

### Update Phase
- [ ] Update antd to 6.0.0
- [ ] Update @ant-design/icons if needed
- [ ] Remove CSS import from main.jsx
- [ ] Fix any peer dependency warnings

### Testing Phase
- [ ] Dev server runs without errors
- [ ] Visual regression check (compare screenshots)
- [ ] Test all CRUD operations
- [ ] Test forms & validation
- [ ] Test responsive design
- [ ] Unit tests pass
- [ ] Build succeeds

### Post-Update
- [ ] Take screenshots (after)
- [ ] Document breaking changes encountered
- [ ] Update this task file with findings
- [ ] Create commit
- [ ] Test on staging (if applicable)

---

## 📚 REFERENCES

- Ant Design 6.0 Release Notes: https://ant.design/changelog
- Migration Guide: https://ant.design/docs/react/migration-v6
- Vite + Ant Design: https://ant.design/docs/react/use-with-vite

---

## 🎯 SUCCESS CRITERIA

- [ ] antd updated to 6.0.0
- [ ] No console errors in dev
- [ ] No visual regressions
- [ ] All tests pass
- [ ] Build succeeds
- [ ] Performance maintained or improved

---

**Created:** 2025-11-23
**Branch:** feature/update-antd
**Estimated Time:** 2-3 hours (including testing)
