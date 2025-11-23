# PRICE-007 - Products Integration UI (Tab Bảng Giá)

## MỤC TIÊU NGHIỆP VỤ

Thêm tab "Bảng giá" vào trang Product Detail để hiển thị giá của sản phẩm này trong các bảng giá khác nhau. User có thể:

- Xem sản phẩm đang có giá bao nhiêu trong từng bảng giá
- So sánh giá giữa các bảng giá (VIP, Retail, Wholesale,...)
- Biết bảng giá nào đang active/upcoming/expired
- Nhóm khách hàng nào được áp dụng bảng giá đó

## TRƯỚC KHI BẮT ĐẦU

**Repository:** https://github.com/shine391/kiotviet

**Branch hiện tại:** `feature/price-lists`

**YÊU CẦU BẮT BUỘC:**

1. Đọc toàn bộ file `lanocrm/src/pages/products/ProductEditPage.jsx`
2. Đọc API response từ `GET /api/products/:id?price_list_id=X`
3. Mọi quyết định UI dựa trên pattern hiện có trong ProductEditPage

---

## Bước 1: Khảo Sát Codebase

**Nhiệm vụ:** Đọc và hiểu structure hiện tại

**Questions:**

- ProductEditPage hiện tại có mấy tabs? Tên từng tab là gì?
- Tabs được render bằng component gì? (Ant Design Tabs?)
- File ProductEditPage import components từ đâu?
- Style được định nghĩa ở đâu? (CSS modules? inline styles?)

**Think:**

- Tab mới sẽ nằm ở vị trí nào trong danh sách tabs?
- Cần tạo component riêng cho tab content không?

**Checkpoint:**

- [ ]  Đã đọc ProductEditPage.jsx
- [ ]  Đã hiểu cách tabs được render
- [ ]  Đã biết style pattern (CSS modules vs inline)

---

## Bước 2: Tìm API Endpoint Hiện Có

**Nhiệm vụ:** Verify API đã hoạt động

**Questions:**

- Endpoint `GET /api/products/:id` trả về response như thế nào khi có `price_list_id` param?
- Response có fields nào liên quan đến price lists? (`base_price`, `price_after_discount`, `applied_price_list_id`,...)
- Làm sao để lấy danh sách tất cả price lists? (có endpoint `GET /api/price-lists` không?)

**Test thử:**

```bash
# Test API products với price_list_id
curl http://localhost:8000/api/products/1?price_list_id=2

# Test API lấy danh sách price lists
curl http://localhost:8000/api/price-lists
```

**Think:**

- Cần gọi 2 APIs: 1) lấy product info, 2) lấy tất cả price lists
- Hoặc có API nào trả về product + all prices luôn không?

**Checkpoint:**

- [ ]  Đã test API products với price_list_id
- [ ]  Đã test API price-lists
- [ ]  Đã hiểu response format

---

## Bước 3: Thiết Kế Component Structure

**Nhiệm vụ:** Plan component trước khi code

**Questions:**

- Nên tạo component `ProductPriceListsTab.jsx` riêng không?
- Component này nằm ở folder nào? (`src/components/products/` hay `src/pages/products/`?)
- Table hiển thị giá dùng Ant Design Table hay custom?

**Think về data flow:**

```
ProductEditPage
  └─> ProductPriceListsTab
       ├─> Fetch: GET /api/price-lists
       ├─> For each price list: call GET /api/products/:id?price_list_id=X
       └─> Render: Ant Design Table với columns:
           - Tên bảng giá
           - Giá gốc (base_price)
           - Giá sau discount (final_price)
           - % discount
           - Áp dụng từ-đến (date range)
           - Nhóm khách hàng (customer groups)
           - Trạng thái (active/upcoming/expired)
```

**Options:**

- **Option A:** Fetch all price lists, loop gọi API products từng cái (N+1 calls)
- **Option B:** Backend cung cấp endpoint mới `GET /api/products/:id/prices` trả về tất cả giá luôn

**Checkpoint:**

- [ ]  Đã quyết định component structure
- [ ]  Đã quyết định data fetching strategy
- [ ]  Đã sketch ra columns của table

---

## Bước 4: Tạo Component ProductPriceListsTab

**Nhiệm vụ:** Tạo component mới

**Questions:**

- Component này nhận props gì? (productId?)
- State cần track: loading, priceLists, productPrices, error
- Dùng `useState` + `useEffect` hay custom hook?

**Think:**

- Cần loading indicator khi fetch data
- Cần error handling khi API fail
- Nếu product chưa có trong bảng giá nào → hiển thị "Chưa có trong bảng giá này"

**File cần tạo:** `lanocrm/src/components/products/ProductPriceListsTab.jsx`

**Template structure:**

```jsx
import React, { useState, useEffect } from 'react';
import { Table, Tag, Spin } from 'antd';

const ProductPriceListsTab = ({ productId }) => {
  const [loading, setLoading] = useState(false);
  const [dataSource, setDataSource] = useState([]);

  useEffect(() => {
    // TODO: Fetch price lists
    // TODO: Fetch product prices for each list
  }, [productId]);

  const columns = [
    // TODO: Define columns
  ];

  return (
    <Spin spinning={loading}>
      <Table 
        dataSource={dataSource} 
        columns={columns}
        rowKey="id"
      />
    </Spin>
  );
};

export default ProductPriceListsTab;
```

**Checkpoint:**

- [ ]  Đã tạo file component
- [ ]  Đã import đúng dependencies
- [ ]  Đã setup useState/useEffect skeleton

---

## Bước 5: Implement Data Fetching Logic

**Nhiệm vụ:** Viết logic fetch data

**Questions:**

- Dùng `fetch()` hay `axios`? (Check ProductEditPage dùng gì)
- API base URL lấy từ đâu? (env variable? config file?)
- Có reusable API service nào không? (check `src/api/`)

**Think về error cases:**

- API price-lists fail → Show error message
- API products với price_list_id fail → Mark as "N/A"
- Product không có trong price list → Show "Chưa có giá"

**Checkpoint:**

- [ ]  Đã implement fetchPriceLists()
- [ ]  Đã implement fetchProductPrices()
- [ ]  Đã handle loading states
- [ ]  Đã handle error states

---

## Bước 6: Define Table Columns

**Nhiệm vụ:** Setup columns theo yêu cầu

**Columns cần có:**

1. **Tên bảng giá** (name) - sortable
2. **Giá gốc** (base_price) - format tiền VND
3. **Giá bán** (final_price) - format tiền VND, bold
4. **Giảm giá** (discount) - tính từ base_price - final_price, hiển thị %
5. **Áp dụng từ-đến** (start_date → end_date) - format dd/MM/yyyy
6. **Nhóm KH** (customer_groups) - render tags
7. **Trạng thái** (status) - Tag màu: active (green), upcoming (blue), expired (gray)

**Questions:**

- Làm sao format số tiền VND? (có helper function `formatCurrency` không?)
- Làm sao format date? (dùng `moment.js` hay `dayjs`?)
- Tag color mapping: active → green, upcoming → blue, expired → ?

**Think:**

- Giá bán (final_price) nên highlight (bold, màu xanh) vì đây là giá quan trọng nhất
- Nếu không có giá trong price list → show "-" hoặc "Chưa có"

**Checkpoint:**

- [ ]  Đã define đủ 7 columns
- [ ]  Đã format currency đúng
- [ ]  Đã format date đúng
- [ ]  Đã render status tags đúng màu

---

## Bước 7: Integrate vào ProductEditPage

**Nhiệm vụ:** Thêm tab mới vào Tabs component

**Questions:**

- Tabs hiện tại có key gì? ("info", "variants", "images"?)
- Tab mới có key là "priceLists"?
- Thêm tab ở vị trí nào? (sau tab "images"?)

**Code cần thêm vào ProductEditPage.jsx:**

```jsx
import ProductPriceListsTab from '../../components/products/ProductPriceListsTab';

// Trong phần render Tabs:
<Tabs.TabPane tab="Bảng giá" key="priceLists">
  <ProductPriceListsTab productId={productId} />
</Tabs.TabPane>
```

**Think:**

- Tab mới có cần permission check không? (chỉ admin mới thấy?)
- Tab có cần lazy load không? (chỉ fetch data khi user click vào tab?)

**Checkpoint:**

- [ ]  Đã import component mới
- [ ]  Đã thêm TabPane vào đúng vị trí
- [ ]  Đã pass đúng props (productId)

---

## Bước 8: Styling và UI Polish

**Nhiệm vụ:** Làm đẹp UI cho match với design hiện tại

**Questions:**

- Table có cần pagination không? (nếu có nhiều price lists)
- Table có cần search/filter không?
- Màu sắc, spacing có match với các tab khác không?

**Think:**

- Nếu chưa có data (loading lần đầu) → Show skeleton
- Nếu product chưa có trong bảng giá nào → Show empty state
- Nếu API error → Show error message với nút "Retry"

**UI States:**

1. **Loading:** `<Spin>` wrapper
2. **Empty:** "Sản phẩm này chưa có trong bảng giá nào"
3. **Error:** "Không thể tải dữ liệu bảng giá. [Thử lại]"
4. **Success:** Table với data

**Checkpoint:**

- [ ]  Đã thêm loading state
- [ ]  Đã thêm empty state
- [ ]  Đã thêm error state
- [ ]  UI đẹp, consistent với design hiện tại

---

## ACCEPTANCE CRITERIA

**Tính năng hoạt động:**

- [ ]  Tab "Bảng giá" xuất hiện trong Product Detail page
- [ ]  Click vào tab → Table hiển thị đúng 7 columns
- [ ]  Giá hiển thị đúng format VND (1.000.000 ₫)
- [ ]  Status tag hiển thị đúng màu (active/upcoming/expired)
- [ ]  Nếu product chưa có giá → hiển thị "-"

**UI/UX:**

- [ ]  Loading spinner khi fetch data
- [ ]  Empty state khi chưa có data
- [ ]  Error handling với retry button
- [ ]  Table responsive trên mobile

**Code quality:**

- [ ]  Code theo pattern của ProductEditPage hiện tại
- [ ]  Không có console.error
- [ ]  Component có PropTypes hoặc TypeScript types
- [ ]  Commit message: `feat(frontend): add Price Lists tab to Product Detail (PRICE-007)`

---

## LƯU Ý QUAN TRỌNG

**Trade-offs:**

- **Performance:** Nếu có nhiều price lists (>10), fetch N+1 sẽ chậm → Consider backend endpoint mới
- **Caching:** Nếu user switch tab qua lại → Consider cache data trong component state
- **Real-time:** Giá có thể thay đổi → Consider thêm nút "Refresh" để reload data

**Best practices:**

- Dùng `useMemo` cho computed values (discount %)
- Dùng `useCallback` cho event handlers
- Extract helper functions ra file riêng nếu dài (formatCurrency, formatDateRange)

**Warnings:**

- KHÔNG hardcode API URLs → Dùng config/env
- KHÔNG dùng `var` → Dùng `const`/`let`
- KHÔNG skip error handling → Luôn có try/catch

---

**📌 Nhắc lại:** Đây là Frontend task. Backend API đã có sẵn và hoạt động. Nhiệm vụ của bạn là tạo UI component để hiển thị data, KHÔNG được sửa backend code.