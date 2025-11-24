import { configureStore } from '@reduxjs/toolkit';
import authReducer from './slices/authSlice';
import userReducer from './slices/userSlice';
import branchReducer from './slices/branchSlice';
import roleReducer from './slices/roleSlice';
import productReducer from './slices/productSlice';     // 🆕 NEW
import categoryReducer from './slices/categorySlice';   // 🆕 NEW
import variantReducer from './slices/variantSlice';     // 🆕 ADD variantSlice
import priceListReducer from './slices/priceListSlice';

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
  },
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware({
      serializableCheck: false,
    }),
    devTools: import.meta.env.MODE !== 'production',
});

export default store;
