// src/App.jsx

import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { useSelector } from 'react-redux';
import { ConfigProvider, App as AntdApp } from 'antd'; // ✅ Thêm ConfigProvider
import viVN from 'antd/locale/vi_VN'; // ✅ Tiếng Việt


// Pages
import Login from './pages/Login';
import UserListPage from './pages/UserListPage';
import UserFormPage from './pages/UserFormPage';
import RoleListPage from './pages/RoleListPage';
import RoleFormPage from './pages/RoleFormPage';
import Dashboard from './pages/Dashboard';
import BranchPage from './pages/BranchPage';
import RolePermissionsPage from './pages/RolePermissionsPage';

// 🆕 PRODUCT PAGES
import ProductListPage from './pages/products/ProductListPage';
import ProductCreatePage from './pages/products/ProductCreatePage';
import ProductEditPage from './pages/products/ProductEditPage';
import VariantEditPage from './pages/products/VariantEditPage';
import PriceListListPage from './pages/price-lists/PriceListListPage';
import PriceListFormPage from './pages/price-lists/PriceListFormPage';

// ✅ THÊM: ATTRIBUTE PAGES
import AttributeListPage from './pages/products/AttributeListPage';
import AttributeCreatePage from './pages/products/AttributeCreatePage';
import AttributeEditPage from './pages/products/AttributeEditPage';

// Customers
import CustomerListPage from './pages/customers/CustomerListPage';

// Layout
import MainLayout from './components/Layout/MainLayout';
import ProtectedRoute from './components/ProtectedRoute';

// Category
import CategoryManagerPage from './pages/categories/CategoryManagerPage';

// CSS
import './styles/App.css';


function App() {
  //const dispatch = useDispatch();
  const isAuthenticated = useSelector(state => state.auth.isAuthenticated);

  return (
    // ✅ WRAP WITH ConfigProvider
    <ConfigProvider
      locale={viVN}
      theme={{
        token: {
          // Tuỳ chỉnh màu sắc theme nếu cần
        },
      }}
    >
      <AntdApp>
        <Routes>
          {/* Public Route - Login */}
          <Route path="/login" element={<Login />} />

          {/* Protected Routes - Wrapped in MainLayout */}
          <Route
            path="/*"
            element={
              isAuthenticated ? (
                <MainLayout>
                  <Routes>
                    {/* Dashboard - Default Route */}
                    <Route path="/" element={<Navigate to="/dashboard" replace />} />
                    <Route path="/dashboard" element={<Dashboard />} />

                    {/* ========== USER ROUTES ========== */}
                    <Route
                      path="/users"
                      element={
                        <ProtectedRoute requiredPermission="users.view">
                          <UserListPage />
                        </ProtectedRoute>
                      }
                    />
                    <Route
                      path="/users/create"
                      element={
                        <ProtectedRoute requiredPermission="users.create">
                          <UserFormPage />
                        </ProtectedRoute>
                      }
                    />
                    <Route
                      path="/users/edit/:id"
                      element={
                        <ProtectedRoute requiredPermission="users.edit">
                          <UserFormPage />
                        </ProtectedRoute>
                      }
                    />

                    {/* ========== ROLE ROUTES ========== */}
                    <Route
                      path="/roles"
                      element={
                        <ProtectedRoute requiredPermission="roles.view">
                          <RoleListPage />
                        </ProtectedRoute>
                      }
                    />
                    <Route
                      path="/roles/create"
                      element={
                        <ProtectedRoute requiredPermission="roles.create">
                          <RoleFormPage />
                        </ProtectedRoute>
                      }
                    />
                    <Route
                      path="/roles/edit/:id"
                      element={
                        <ProtectedRoute requiredPermission="roles.edit">
                          <RoleFormPage />
                        </ProtectedRoute>
                      }
                    />
                    {/* 🆕 ROUTE PHÂN QUYỀN */}
                    <Route
                      path="/roles/:id/permissions"
                      element={
                        <ProtectedRoute requiredPermission="roles.assign-permissions">
                          <RolePermissionsPage />
                        </ProtectedRoute>
                      }
                    />

                    {/* ========== BRANCH ROUTES ========== */}
                    <Route
                      path="/branches"
                      element={
                        <ProtectedRoute requiredPermission="branches.view">
                          <BranchPage />
                        </ProtectedRoute>
                      }
                    />

                    {/* 🆕 NEW - Product Management */}
                    <Route
                      path="/products"
                      element={
                        <ProtectedRoute requiredPermission="products.view">
                          <ProductListPage />
                        </ProtectedRoute>
                      }
                    />

                    {/* 🆕 Create Product */}
                    <Route
                      path="/products/create"
                      element={
                        <ProtectedRoute requiredPermission="products.create">
                          <ProductCreatePage />
                        </ProtectedRoute>
                      }
                    />

                    {/* 🆕 Edit Product */}
                    <Route
                      path="/products/edit/:id"
                      element={
                        <ProtectedRoute requiredPermission="products.edit">
                          <ProductEditPage />
                        </ProtectedRoute>
                      }
                    />

                    {/* 🆕 Edit Variant */}
                    <Route
                      path="/products/variants/edit/:variantId"
                      element={
                        <ProtectedRoute requiredPermission="products.edit">
                          <VariantEditPage />
                        </ProtectedRoute>
                      }
                    />

                    {/* 🆕 Price Lists */}
                    <Route
                      path="/price-lists"
                      element={<PriceListListPage />}
                    />
                    <Route
                      path="/price-lists/create"
                      element={<PriceListFormPage />}
                    />
                    <Route
                      path="/price-lists/edit/:id"
                      element={<PriceListFormPage />}
                    />

                    {/* ========== ✅ Categories product ROUTES ========== */}

                    <Route
                      path="/product-categories"
                      element={
                        <ProtectedRoute requiredPermission="categories.view">
                          <CategoryManagerPage />
                        </ProtectedRoute>
                      }
                    />

                    {/* ========== ✅ ATTRIBUTE ROUTES ========== */}
                    {/* Attribute List */}
                    <Route
                      path="/products/attributes"
                      element={
                        <ProtectedRoute requiredPermission="products.view">
                          <AttributeListPage />
                        </ProtectedRoute>
                      }
                    />

                    {/* Create Attribute */}
                    <Route
                      path="/products/attributes/create"
                      element={
                        <ProtectedRoute requiredPermission="products.create">
                          <AttributeCreatePage />
                        </ProtectedRoute>
                      }
                    />

                    {/* Edit Attribute */}
                    <Route
                      path="/products/attributes/edit/:id"
                      element={
                        <ProtectedRoute requiredPermission="products.edit">
                          <AttributeEditPage />
                        </ProtectedRoute>
                      }
                    />

                    {/* 🆕 Customers */}
                    <Route
                      path="/customers"
                      element={<CustomerListPage />}
                    />

                    {/* ========== 404 PAGE ========== */}
                    <Route
                      path="*"
                      element={
                        <div
                          style={{
                            textAlign: 'center',
                            padding: '50px',
                            minHeight: '60vh',
                            display: 'flex',
                            flexDirection: 'column',
                            justifyContent: 'center',
                            alignItems: 'center'
                          }}
                        >
                          <h1 style={{ fontSize: '48px', marginBottom: '20px' }}>404</h1>
                          <h2 style={{ fontSize: '24px', marginBottom: '10px' }}>Không tìm thấy trang</h2>
                          <p style={{ color: '#666', marginBottom: '30px' }}>
                            Trang bạn đang tìm kiếm không tồn tại hoặc đã bị xóa.
                          </p>
                          <button
                            onClick={() => (window.location.href = '/dashboard')}
                            style={{
                              padding: '10px 30px',
                              fontSize: '16px',
                              background: '#1976d2',
                              color: 'white',
                              border: 'none',
                              borderRadius: '4px',
                              cursor: 'pointer'
                            }}
                          >
                            ← Về trang chủ
                          </button>
                        </div>
                      }
                    />
                  </Routes>
                </MainLayout>
              ) : (
                <Navigate to="/login" replace />
              )
            }
          />
        </Routes>
      </AntdApp>
    </ConfigProvider>
  );
}

export default App;
