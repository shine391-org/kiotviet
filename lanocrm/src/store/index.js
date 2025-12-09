import { configureStore } from '@reduxjs/toolkit';
import authReducer from './slices/authSlice';
import userReducer from './slices/userSlice';
import branchReducer from './slices/branchSlice';
import roleReducer from './slices/roleSlice';
import productReducer from './slices/productSlice';     // 🆕 NEW
import categoryReducer from './slices/categorySlice';   // 🆕 NEW
import variantReducer from './slices/variantSlice';     // 🆕 ADD variantSlice
import priceListReducer from './slices/priceListSlice';
import customerReducer from './slices/customerSlice';
import dashboardReducer from './slices/dashboardSlice';
import cashReducer from './slices/cashSlice';
import ordersReducer from './slices/orderSlice';
import invoiceReducer from './slices/invoiceSlice';
import returnReducer from './slices/returnSlice';
import deliveryPartnerReducer from './slices/deliveryPartnerSlice';
import shipmentReducer from './slices/shipmentSlice';
import transferReducer from './slices/transferSlice';
import stockAuditReducer from './slices/stockAuditSlice';
import disposalReducer from './slices/disposalSlice';
import supplierReducer from './slices/supplierSlice';
import purchaseReducer from './slices/purchaseSlice';
import purchaseReturnReducer from './slices/purchaseReturnSlice';

const store = configureStore({
  reducer: {
    auth: authReducer,
    user: userReducer,
    branch: branchReducer,
    role: roleReducer,
    product: productReducer,       // 🆕 NEW
    category: categoryReducer,     // 🆕 NEW
    variant: variantReducer,       // 🆕 NEW 
    priceList: priceListReducer,
    customer: customerReducer,
    dashboard: dashboardReducer,
    cash: cashReducer,
    orders: ordersReducer,
    invoices: invoiceReducer,
    returns: returnReducer,
    deliveryPartners: deliveryPartnerReducer,
    shipments: shipmentReducer,
    transfers: transferReducer,
    stockAudits: stockAuditReducer,
    disposals: disposalReducer,
    supplier: supplierReducer,
    purchases: purchaseReducer,
    purchaseReturns: purchaseReturnReducer,
  },
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware({
      serializableCheck: false,
    }),
  devTools: import.meta.env.MODE !== 'production',
});

export default store;
