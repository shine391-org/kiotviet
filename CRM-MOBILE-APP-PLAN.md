# 📱 PHASE 10: MOBILE APP (REACT NATIVE) - EXTENSION PLAN

**Added to Master Plan:** October 29, 2025, 8:11 PM  
**Priority:** After Phase 9 (Web Deployment Complete)  
**Duration:** 12-16 weeks  
**Status:** 🟡 PLANNING

---

## 📊 OVERVIEW

### Objectives
- ✅ Build native mobile app for iOS & Android
- ✅ Real-time sync with web CRM
- ✅ Support offline mode with local storage
- ✅ Native features (camera, notifications, location)
- ✅ Match KiotViet mobile app UX
- ✅ LANO brand consistency

### Target Users
1. **Store Managers**: Dashboard, reports, inventory alerts
2. **Sales Staff**: POS, product lookup, customer management
3. **Warehouse Staff**: Stock management, receiving, transfers
4. **Admins**: Full access to all features

### Key Features (Based on KiotViet App)
1. Dashboard with real-time stats
2. Mobile POS (offline-capable)
3. Product search & barcode scanner
4. Inventory management
5. Orders & customers
6. Reports & analytics
7. Push notifications
8. Multi-branch support

---

## 💻 TECH STACK

### Mobile Framework
```json
{
  "framework": "React Native",
  "version": "0.73.x (latest stable)",
  "language": "TypeScript",
  "navigation": "React Navigation 6",
  "state_management": "Redux Toolkit",
  "ui_library": "React Native Paper",
  "icons": "react-native-vector-icons",
  "storage": "AsyncStorage + WatermelonDB (offline)",
  "api": "Axios + React Query",
  "push_notifications": "Firebase Cloud Messaging",
  "barcode_scanner": "react-native-vision-camera + MLKit",
  "charts": "react-native-chart-kit",
  "forms": "React Hook Form",
  "validation": "Zod"
}
```

### Backend Requirements
```json
{
  "api_versioning": "v1 (REST)",
  "websockets": "Socket.io (real-time updates)",
  "push_service": "Firebase Admin SDK",
  "file_storage": "Local server or S3",
  "authentication": "JWT + Refresh Token",
  "sync_strategy": "Last-Write-Wins with conflict resolution"
}
```

---

## 📁 PROJECT STRUCTURE

```
lanocrm-mobile/
├── android/                    # Android native code
├── ios/                        # iOS native code
├── src/
│   ├── api/
│   │   ├── axios.config.ts
│   │   ├── endpoints.ts
│   │   ├── authApi.ts
│   │   ├── productApi.ts
│   │   ├── posApi.ts
│   │   ├── orderApi.ts
│   │   └── syncApi.ts
│   │
│   ├── components/
│   │   ├── common/
│   │   │   ├── Button.tsx
│   │   │   ├── Input.tsx
│   │   │   ├── Card.tsx
│   │   │   ├── Badge.tsx
│   │   │   ├── SearchBar.tsx
│   │   │   └── Loading.tsx
│   │   ├── layout/
│   │   │   ├── Header.tsx
│   │   │   ├── TabBar.tsx
│   │   │   └── DrawerMenu.tsx
│   │   ├── products/
│   │   │   ├── ProductCard.tsx
│   │   │   ├── ProductList.tsx
│   │   │   └── ProductDetail.tsx
│   │   └── pos/
│   │       ├── POSCart.tsx
│   │       ├── POSPayment.tsx
│   │       └── POSInvoice.tsx
│   │
│   ├── screens/
│   │   ├── auth/
│   │   │   ├── LoginScreen.tsx
│   │   │   └── SplashScreen.tsx
│   │   ├── dashboard/
│   │   │   └── DashboardScreen.tsx
│   │   ├── pos/
│   │   │   ├── POSScreen.tsx
│   │   │   └── POSHistoryScreen.tsx
│   │   ├── products/
│   │   │   ├── ProductListScreen.tsx
│   │   │   ├── ProductDetailScreen.tsx
│   │   │   └── BarcodeScanner.tsx
│   │   ├── inventory/
│   │   │   ├── InventoryScreen.tsx
│   │   │   └── StockCheckScreen.tsx
│   │   ├── orders/
│   │   │   ├── OrderListScreen.tsx
│   │   │   └── OrderDetailScreen.tsx
│   │   ├── customers/
│   │   │   ├── CustomerListScreen.tsx
│   │   │   └── CustomerDetailScreen.tsx
│   │   └── reports/
│   │       ├── ReportsScreen.tsx
│   │       └── ReportDetailScreen.tsx
│   │
│   ├── navigation/
│   │   ├── AppNavigator.tsx
│   │   ├── AuthNavigator.tsx
│   │   ├── MainNavigator.tsx
│   │   └── POSNavigator.tsx
│   │
│   ├── store/
│   │   ├── store.ts
│   │   └── slices/
│   │       ├── authSlice.ts
│   │       ├── productSlice.ts
│   │       ├── posSlice.ts
│   │       ├── orderSlice.ts
│   │       ├── syncSlice.ts
│   │       └── settingsSlice.ts
│   │
│   ├── services/
│   │   ├── database/
│   │   │   ├── schema.ts
│   │   │   ├── models/
│   │   │   └── sync.ts
│   │   ├── notifications/
│   │   │   └── pushNotifications.ts
│   │   └── utils/
│   │       ├── offline.ts
│   │       └── permissions.ts
│   │
│   ├── hooks/
│   │   ├── useAuth.ts
│   │   ├── useOffline.ts
│   │   ├── usePermissions.ts
│   │   ├── useSync.ts
│   │   └── useCamera.ts
│   │
│   ├── constants/
│   │   ├── colors.ts
│   │   ├── sizes.ts
│   │   └── api.ts
│   │
│   ├── types/
│   │   ├── api.types.ts
│   │   ├── models.types.ts
│   │   └── navigation.types.ts
│   │
│   └── utils/
│       ├── formatters.ts
│       ├── validators.ts
│       └── helpers.ts
│
├── .env.development
├── .env.production
├── app.json
├── package.json
├── tsconfig.json
└── README.md
```

---

## 🗓️ IMPLEMENTATION PHASES

### Phase 10.1: Project Setup (Week 1)
**Duration:** 5 days

#### Day 1-2: Initialize Project
```bash
# Create React Native project with TypeScript
npx react-native init LANOCRMMobile --template react-native-template-typescript

# Install core dependencies
npm install @react-navigation/native @react-navigation/stack
npm install @react-navigation/bottom-tabs @react-navigation/drawer
npm install react-native-screens react-native-safe-area-context
npm install react-native-gesture-handler react-native-reanimated
npm install @reduxjs/toolkit react-redux
npm install axios @tanstack/react-query
npm install react-native-paper
npm install react-native-vector-icons
npm install @react-native-async-storage/async-storage
npm install @nozbe/watermelondb
npm install react-hook-form zod @hookform/resolvers
npm install socket.io-client
npm install @react-native-firebase/app @react-native-firebase/messaging
npm install react-native-vision-camera react-native-worklets-core
npm install react-native-chart-kit
```

#### Day 3: Configure Navigation
- Setup React Navigation
- Create navigators structure
- Configure deep linking

#### Day 4: Setup Redux Store
- Configure Redux Toolkit
- Create initial slices
- Setup React Query

#### Day 5: Design System Setup
- Create color palette
- Typography system
- Component templates

**Checklist:**
- [ ] Project initialized
- [ ] Dependencies installed
- [ ] Navigation configured
- [ ] Redux store setup
- [ ] Design system created
- [ ] Test: App runs on iOS simulator
- [ ] Test: App runs on Android emulator
- [ ] Commit: "Phase 10.1: Mobile Project Setup"

---

### Phase 10.2: Authentication & Core Layout (Week 2)
**Duration:** 5 days

#### Day 1-2: Authentication
**Screens:**
- SplashScreen.tsx
- LoginScreen.tsx

**Features:**
- JWT authentication
- Biometric login (Face ID/Touch ID)
- Remember me
- Auto-login with refresh token

**API Integration:**
- Login endpoint
- Token refresh
- User profile

#### Day 3-4: Main Layout
**Components:**
- DrawerMenu.tsx (side menu)
- TabBar.tsx (bottom navigation)
- Header.tsx

**Navigation Structure:**
```
Main Tabs:
├─ Dashboard
├─ POS
├─ Products
├─ Orders
└─ More (Drawer)
```

#### Day 5: Settings & Profile
**Screens:**
- ProfileScreen.tsx
- SettingsScreen.tsx
- SelectBranchScreen.tsx

**Checklist:**
- [ ] Login works
- [ ] Token stored securely
- [ ] Biometric auth works
- [ ] Navigation structure complete
- [ ] Settings persisted
- [ ] Test: Login → Dashboard flow
- [ ] Commit: "Phase 10.2: Auth & Layout"

---

### Phase 10.3: Dashboard (Week 3)
**Duration:** 5 days

#### Features:
1. **Stats Cards**
   - Today's revenue
   - Today's orders
   - Low stock alerts
   - Pending orders

2. **Quick Actions**
   - Start selling (POS)
   - Add product
   - View reports
   - Check inventory

3. **Recent Activity**
   - Recent orders
   - Recent customers
   - Stock movements

4. **Charts**
   - Revenue chart (7 days)
   - Top products (pie chart)
   - Sales by channel (bar chart)

**API Endpoints:**
```
GET /api/mobile/dashboard/stats
GET /api/mobile/dashboard/recent-orders
GET /api/mobile/dashboard/charts
```

**Checklist:**
- [ ] Dashboard UI complete
- [ ] Stats cards show real data
- [ ] Charts render correctly
- [ ] Pull to refresh works
- [ ] Quick actions work
- [ ] Test: Data updates real-time
- [ ] Commit: "Phase 10.3: Dashboard"

---

### Phase 10.4: Mobile POS (Week 4-5)
**Duration:** 10 days

#### Day 1-3: POS Main Screen
**Layout:**
```
┌──────────────────────────┐
│  Customer: [Select ▼]    │
├──────────────────────────┤
│  [Search Products...]    │
├──────────────────────────┤
│  Product Grid            │
│  ┌──┐ ┌──┐ ┌──┐         │
│  │  │ │  │ │  │         │
│  └──┘ └──┘ └──┘         │
├──────────────────────────┤
│  Cart (3 items)          │
│  • Product A  x2  200đ   │
│  • Product B  x1  100đ   │
│  ─────────────────────   │
│  Total: 300,000đ         │
│  [💳 Thanh toán]         │
└──────────────────────────┘
```

**Features:**
- Product search (with barcode scanner)
- Add to cart
- Quantity adjustment
- Remove items
- Apply discount
- Customer selection

#### Day 4-6: Payment Screen
**Payment Methods:**
- Cash (with change calculator)
- Card
- Bank transfer
- Momo
- VNPay
- Split payment

**Features:**
- Payment method selection
- Tendered amount input
- Change calculation
- Note/memo field
- Print/email invoice option

#### Day 7-8: Offline Mode
**Features:**
- Queue orders when offline
- Sync when back online
- Local product cache
- Conflict resolution

**Storage:**
- WatermelonDB for offline data
- AsyncStorage for settings

#### Day 9-10: Invoice & Printing
**Features:**
- Invoice preview
- Thermal printer support (Bluetooth)
- Email invoice
- SMS notification
- Invoice history

**Checklist:**
- [ ] POS screen complete
- [ ] Product search works
- [ ] Cart management works
- [ ] Payment processing works
- [ ] Offline mode works
- [ ] Invoice generation works
- [ ] Bluetooth printing works
- [ ] Test: Complete sale offline
- [ ] Test: Sync when back online
- [ ] Commit: "Phase 10.4: Mobile POS"

---

### Phase 10.5: Products & Inventory (Week 6-7)
**Duration:** 10 days

#### Day 1-3: Product Management
**Screens:**
- ProductListScreen.tsx
- ProductDetailScreen.tsx
- BarcodeScanner.tsx

**Features:**
- List products with images
- Search & filters
- Barcode scanning
- View product details
- Check stock by branch
- View variants
- Price history

#### Day 4-5: Barcode Scanner
**Features:**
- Scan barcode/QR code
- Continuous scanning mode
- Flashlight toggle
- Manual entry fallback

**Libraries:**
- react-native-vision-camera
- MLKit (Google ML Kit)

#### Day 6-8: Inventory Management
**Screens:**
- InventoryScreen.tsx
- StockCheckScreen.tsx
- StockTransferScreen.tsx

**Features:**
- View stock by branch
- Low stock alerts
- Stock check (physical count)
- Stock transfer between branches
- Receive stock
- Adjust stock

#### Day 9-10: Product Creation (Limited)
**Features:**
- Quick add product
- Upload photo (camera)
- Basic fields only
- Full edit on web

**Checklist:**
- [ ] Product list works
- [ ] Product search works
- [ ] Barcode scanner works
- [ ] Stock check works
- [ ] Stock transfer works
- [ ] Test: Scan product → View stock
- [ ] Test: Create product → Sync to web
- [ ] Commit: "Phase 10.5: Products & Inventory"

---

### Phase 10.6: Orders & Customers (Week 8)
**Duration:** 5 days

#### Day 1-2: Orders
**Screens:**
- OrderListScreen.tsx
- OrderDetailScreen.tsx

**Features:**
- View all orders
- Filter by status, date
- Order details
- Update order status
- Cancel order
- Reorder

#### Day 3-4: Customers
**Screens:**
- CustomerListScreen.tsx
- CustomerDetailScreen.tsx
- CustomerFormScreen.tsx

**Features:**
- Customer list
- Search customers
- View customer profile
- Purchase history
- Loyalty points
- Create customer
- Edit customer

#### Day 5: Integration
- Link orders to customers
- Customer selection in POS
- Customer notifications

**Checklist:**
- [ ] Order list works
- [ ] Order detail works
- [ ] Status update works
- [ ] Customer list works
- [ ] Customer CRUD works
- [ ] Test: Create customer → Use in POS
- [ ] Commit: "Phase 10.6: Orders & Customers"

---

### Phase 10.7: Reports & Analytics (Week 9)
**Duration:** 5 days

#### Day 1-2: Reports Dashboard
**Screens:**
- ReportsScreen.tsx
- ReportDetailScreen.tsx

**Report Types:**
1. Revenue Report
2. Sales by Product
3. Sales by Channel
4. Sales by Staff
5. Inventory Valuation
6. Best Sellers
7. Slow Moving Items

#### Day 3-4: Charts & Visualizations
**Charts:**
- Line chart (revenue trend)
- Bar chart (sales by category)
- Pie chart (sales by channel)
- Donut chart (payment methods)

**Library:**
- react-native-chart-kit

#### Day 5: Export Reports
**Features:**
- Export to PDF
- Export to Excel
- Share via email
- Print report

**Checklist:**
- [ ] Reports load correctly
- [ ] Charts render correctly
- [ ] Filters work
- [ ] Export works
- [ ] Test: Generate report → Export
- [ ] Commit: "Phase 10.7: Reports"

---

### Phase 10.8: Real-time Sync & Notifications (Week 10)
**Duration:** 5 days

#### Day 1-2: WebSocket Integration
**Features:**
- Connect to Socket.io server
- Listen for events:
  - New order
  - Stock update
  - Price change
  - Low stock alert
  - Order status change

**Implementation:**
```typescript
// src/services/websocket.ts
import io from 'socket.io-client';

const socket = io('https://banhang.tuidanam.org', {
  auth: { token: userToken }
});

socket.on('order:new', (order) => {
  dispatch(addNewOrder(order));
  showNotification('New order received');
});

socket.on('stock:low', (product) => {
  showNotification(`Low stock: ${product.name}`);
});
```

#### Day 3-4: Push Notifications
**Setup:**
- Firebase Cloud Messaging (FCM)
- iOS APNs
- Android FCM

**Notification Types:**
1. New order
2. Low stock alert
3. Order status change
4. Payment received
5. Daily summary

**Features:**
- Background notifications
- Foreground notifications
- Notification actions
- Badge count
- Deep linking

#### Day 5: Sync Service
**Features:**
- Auto sync every 5 minutes
- Manual sync (pull to refresh)
- Sync status indicator
- Conflict resolution
- Sync history

**Checklist:**
- [ ] WebSocket connects
- [ ] Real-time updates work
- [ ] Push notifications work
- [ ] Notification actions work
- [ ] Sync works offline
- [ ] Test: Update on web → Receives on mobile
- [ ] Commit: "Phase 10.8: Sync & Notifications"

---

### Phase 10.9: Performance & Optimization (Week 11)
**Duration:** 5 days

#### Day 1: Performance Audit
- [ ] Check bundle size
- [ ] Identify slow screens
- [ ] Check memory usage
- [ ] Profile with Flipper

#### Day 2-3: Optimizations
- [ ] Image optimization (lazy loading)
- [ ] List virtualization (FlatList)
- [ ] Memoization (React.memo, useMemo)
- [ ] Code splitting
- [ ] Reduce bundle size

#### Day 4: Offline Optimization
- [ ] Optimize WatermelonDB queries
- [ ] Cache images locally
- [ ] Preload common data
- [ ] Smart sync (delta sync)

#### Day 5: Battery & Network
- [ ] Reduce API calls
- [ ] Batch updates
- [ ] Background task optimization
- [ ] Network usage monitoring

**Checklist:**
- [ ] App size < 50MB
- [ ] Start time < 2s
- [ ] Memory usage < 150MB
- [ ] Battery usage optimized
- [ ] Commit: "Phase 10.9: Optimization"

---

### Phase 10.10: Testing (Week 12)
**Duration:** 5 days

#### Day 1-2: Unit Testing
- [ ] API services
- [ ] Redux slices
- [ ] Utils & helpers
- [ ] Hooks

**Tools:**
- Jest
- React Native Testing Library

#### Day 3: Integration Testing
- [ ] Login flow
- [ ] POS checkout flow
- [ ] Product creation flow
- [ ] Sync flow

#### Day 4: E2E Testing
- [ ] Critical user flows
- [ ] POS end-to-end
- [ ] Offline scenario

**Tools:**
- Detox (E2E testing)

#### Day 5: Manual Testing
- [ ] Test on iOS device
- [ ] Test on Android device
- [ ] Test all features
- [ ] Fix bugs

**Checklist:**
- [ ] Unit tests pass
- [ ] Integration tests pass
- [ ] E2E tests pass
- [ ] No critical bugs
- [ ] Commit: "Phase 10.10: Testing"

---

### Phase 10.11: App Store Preparation (Week 13-14)
**Duration:** 10 days

#### Day 1-3: iOS Preparation
**Requirements:**
- [ ] App icon (all sizes)
- [ ] Launch screen
- [ ] App Store screenshots (5.5", 6.5")
- [ ] App Store description
- [ ] Privacy policy
- [ ] Support URL
- [ ] TestFlight beta testing

**App Store Connect:**
- [ ] Create app listing
- [ ] Upload screenshots
- [ ] Write description
- [ ] Set pricing (Free)
- [ ] Submit for review

#### Day 4-6: Android Preparation
**Requirements:**
- [ ] App icon (adaptive icon)
- [ ] Splash screen
- [ ] Play Store screenshots (Phone, Tablet, 7", 10")
- [ ] Feature graphic (1024x500)
- [ ] Play Store description
- [ ] Privacy policy
- [ ] Generate signed APK/AAB

**Google Play Console:**
- [ ] Create app listing
- [ ] Upload screenshots
- [ ] Write description
- [ ] Set pricing (Free)
- [ ] Internal testing track
- [ ] Submit for review

#### Day 7-10: Beta Testing
- [ ] Distribute via TestFlight (iOS)
- [ ] Distribute via Internal Testing (Android)
- [ ] Collect feedback
- [ ] Fix critical issues
- [ ] Iterate

**Checklist:**
- [ ] App submitted to App Store
- [ ] App submitted to Play Store
- [ ] Beta testing complete
- [ ] Critical issues fixed
- [ ] Commit: "Phase 10.11: Store Submission"

---

### Phase 10.12: Launch & Monitoring (Week 15-16)
**Duration:** 10 days

#### Day 1-5: Soft Launch
- [ ] Release to beta users (100 users)
- [ ] Monitor crashes (Firebase Crashlytics)
- [ ] Monitor analytics (Firebase Analytics)
- [ ] Collect feedback
- [ ] Fix critical bugs

#### Day 6-8: Public Launch
- [ ] Update to production track
- [ ] Announce on LANO website
- [ ] Send email to customers
- [ ] Social media posts
- [ ] Monitor reviews

#### Day 9-10: Post-Launch Support
- [ ] Monitor crash reports
- [ ] Respond to reviews
- [ ] Fix urgent bugs
- [ ] Plan v1.1 updates

**Checklist:**
- [ ] App live on App Store
- [ ] App live on Play Store
- [ ] Zero critical bugs
- [ ] Positive user feedback
- [ ] Monitoring setup complete
- [ ] Commit: "Phase 10.12: Launch Complete"

---

## 🔧 BACKEND UPDATES REQUIRED

### New API Endpoints

#### Mobile-Specific Endpoints
```php
// Mobile Dashboard
GET /api/mobile/dashboard/stats
GET /api/mobile/dashboard/recent-activity
GET /api/mobile/dashboard/charts

// Mobile POS
POST /api/mobile/pos/checkout
POST /api/mobile/pos/sync-offline-orders
GET /api/mobile/pos/settings

// Sync API
GET /api/mobile/sync/products?last_sync=timestamp
GET /api/mobile/sync/customers?last_sync=timestamp
GET /api/mobile/sync/orders?last_sync=timestamp
POST /api/mobile/sync/resolve-conflict

// Push Notifications
POST /api/mobile/device/register
POST /api/mobile/notifications/preferences
```

#### WebSocket Events
```javascript
// Server-side (Socket.io)
io.on('connection', (socket) => {
  socket.on('join:branch', (branchId) => {
    socket.join(`branch:${branchId}`);
  });
  
  // Broadcast events
  io.to(`branch:${branchId}`).emit('order:new', order);
  io.to(`branch:${branchId}`).emit('stock:update', product);
});
```

### Database Changes
```sql
-- Device tokens for push notifications
CREATE TABLE device_tokens (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    device_id VARCHAR(255) NOT NULL,
    platform ENUM('ios', 'android') NOT NULL,
    token TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_device (user_id, device_id)
);

-- Sync log for conflict resolution
CREATE TABLE sync_log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    device_id VARCHAR(255) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id BIGINT NOT NULL,
    action ENUM('create', 'update', 'delete') NOT NULL,
    data JSON,
    synced BOOLEAN DEFAULT FALSE,
    synced_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 📊 FEATURES COMPARISON: WEB vs MOBILE

| Feature | Web CRM | Mobile App | Priority |
|---------|---------|------------|----------|
| Dashboard | ✅ Full | ✅ Essential | High |
| POS | ✅ Full | ✅ Full + Offline | Critical |
| Product Management | ✅ Full CRUD | ⚠️ Limited (View + Quick Add) | High |
| Inventory | ✅ Full | ✅ Stock Check + Transfer | High |
| Orders | ✅ Full | ✅ View + Update Status | High |
| Customers | ✅ Full | ✅ Full | High |
| Reports | ✅ Advanced | ⚠️ Basic (Top 5 reports) | Medium |
| Users/Roles | ✅ Full | ❌ View Only | Low |
| Settings | ✅ Full | ⚠️ App Settings Only | Medium |
| Multi-Branch | ✅ Full | ✅ Switch Branch | High |
| Permissions | ✅ Full | ✅ Same as Web | High |
| Offline Mode | ❌ No | ✅ POS + Products | Critical |
| Barcode Scanner | ❌ No | ✅ Camera | Critical |
| Push Notifications | ❌ No | ✅ Yes | High |
| Thermal Printer | ❌ No | ✅ Bluetooth | Medium |

---

## 🔐 SECURITY CONSIDERATIONS

### Authentication
- [ ] JWT with short expiry (15 min)
- [ ] Refresh token (7 days)
- [ ] Biometric authentication (optional)
- [ ] Auto-logout after inactivity
- [ ] Device binding

### Data Security
- [ ] Encrypt sensitive data in AsyncStorage
- [ ] Use Keychain (iOS) / Keystore (Android) for tokens
- [ ] HTTPS only
- [ ] Certificate pinning
- [ ] Obfuscate API keys

### Permissions
- [ ] Same RBAC as web
- [ ] Check permissions offline (cached)
- [ ] Sync permissions on login

---

## 📈 ANALYTICS & MONITORING

### Firebase Analytics Events
```typescript
// Track key user actions
analytics().logEvent('pos_checkout_complete', {
  order_id: order.id,
  total: order.total,
  payment_method: order.payment_method
});

analytics().logEvent('product_scanned', {
  product_id: product.id,
  barcode: barcode
});

analytics().logEvent('stock_checked', {
  product_id: product.id,
  branch_id: branch.id
});
```

### Crashlytics
- Automatic crash reporting
- Non-fatal error logging
- Custom log messages

### Performance Monitoring
- Screen load times
- API response times
- Network requests
- App startup time

---

## 📱 APP STORE OPTIMIZATION (ASO)

### App Name
**iOS:** LANO CRM - Quản lý bán hàng  
**Android:** LANO CRM - Quản lý bán hàng

### Keywords
```
Vietnamese:
quản lý bán hàng, pos, thu ngân, quản lý kho, 
bán hàng online, quản lý shop, crm, khách hàng

English:
pos system, retail management, inventory, 
sales management, crm, point of sale
```

### Description Template
```
LANO CRM - Giải pháp quản lý bán hàng toàn diện

🎯 Tính năng nổi bật:
• Bán hàng nhanh chóng với POS di động
• Quét mã vạch tìm sản phẩm
• Quản lý kho hàng real-time
• Báo cáo doanh thu chi tiết
• Hỗ trợ offline, đồng bộ tự động
• Thông báo đẩy tức thì
• Hỗ trợ đa chi nhánh

📊 Quản lý mọi lúc, mọi nơi:
Theo dõi doanh thu, đơn hàng, tồn kho ngay trên di động.
Đồng bộ dữ liệu tự động với web CRM.

💳 Thanh toán đa dạng:
Hỗ trợ tiền mặt, thẻ, chuyển khoản, Momo, VNPay.

🔒 Bảo mật cao:
Xác thực 2 lớp, mã hóa dữ liệu, đăng nhập vân tay.

---

Liên hệ: support@lano.vn
Website: https://lano.vn
```

---

## 💰 COST ESTIMATION

### One-Time Costs
- Developer license (iOS): $99/year
- Developer license (Android): $25 one-time
- Design assets: ~$500
- Code signing certificate: Included in iOS license

### Recurring Costs
- Firebase (Free tier sufficient for start)
- Push notifications (FCM: Free)
- API server (existing)
- App Store hosting (Included in license)

**Total First Year:** ~$624 + development time

---

## 📅 DEPLOYMENT SCHEDULE

```
Timeline Overview (16 weeks):

Week 1:    ████ Project Setup
Week 2:    ████ Auth & Layout
Week 3:    ████ Dashboard
Week 4-5:  ████████ Mobile POS
Week 6-7:  ████████ Products & Inventory
Week 8:    ████ Orders & Customers
Week 9:    ████ Reports
Week 10:   ████ Sync & Notifications
Week 11:   ████ Optimization
Week 12:   ████ Testing
Week 13-14:████████ Store Preparation
Week 15-16:████████ Launch & Monitoring

Total: 16 weeks (4 months)
```

**Milestones:**
- ✅ Week 4: POS working (internal demo)
- ✅ Week 8: Core features complete (alpha)
- ✅ Week 12: Beta ready (TestFlight/Internal Testing)
- ✅ Week 16: Public launch (App Store + Play Store)

---

## ✅ FINAL CHECKLIST

### Pre-Development
- [ ] Confirm backend API readiness
- [ ] Setup Firebase project
- [ ] Obtain Apple Developer account
- [ ] Obtain Google Play Developer account
- [ ] Prepare app icons & assets

### Development (Week 1-12)
- [ ] Complete all phases 10.1 - 10.10
- [ ] Test on real devices (iOS + Android)
- [ ] Fix all critical bugs
- [ ] Performance optimization
- [ ] Security audit

### App Store Submission (Week 13-14)
- [ ] Prepare all store assets
- [ ] Write app descriptions
- [ ] Create privacy policy
- [ ] Submit to App Store
- [ ] Submit to Play Store

### Launch (Week 15-16)
- [ ] Beta testing complete
- [ ] Public release
- [ ] Marketing materials ready
- [ ] Support team trained
- [ ] Monitoring setup

### Post-Launch
- [ ] Monitor crash reports
- [ ] Respond to user feedback
- [ ] Plan v1.1 features
- [ ] Continuous improvement

---

## 📚 RESOURCES

### Documentation
- React Native: https://reactnative.dev/
- React Navigation: https://reactnavigation.org/
- WatermelonDB: https://nozbe.github.io/WatermelonDB/
- Firebase: https://rnfirebase.io/
- Vision Camera: https://react-native-vision-camera.com/

### Design Reference
- KiotViet Mobile App (iOS/Android)
- Material Design (Android)
- Human Interface Guidelines (iOS)

### Testing Tools
- Flipper (debugging)
- React Native Debugger
- Detox (E2E testing)
- Jest (unit testing)

---

## 🔄 SYNC WITH WEB CRM

### Sync Strategy
1. **Real-time sync** (WebSocket): Instant updates for critical data
2. **Periodic sync** (REST API): Every 5 minutes for non-critical data
3. **Manual sync** (Pull to refresh): User-initiated
4. **Offline queue**: Queue actions when offline, sync when back online

### Conflict Resolution
```typescript
// Last-Write-Wins strategy
if (serverUpdatedAt > localUpdatedAt) {
  // Server version is newer
  updateLocal(serverData);
} else if (localUpdatedAt > serverUpdatedAt) {
  // Local version is newer
  syncToServer(localData);
} else {
  // Same timestamp, compare checksums
  if (serverChecksum !== localChecksum) {
    // Manual resolution required
    showConflictDialog(serverData, localData);
  }
}
```

---

**Last Updated:** October 29, 2025, 8:11 PM +07  
**Version:** 1.0  
**Status:** 🟡 PLANNING COMPLETE - Ready for implementation after web Phase 9

---

**END OF MOBILE APP EXTENSION PLAN**
