// src/components/Layout/Sidebar.jsx - PURE DYNAMIC SIDEBAR
import React, { useState, useEffect } from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { useSelector } from 'react-redux';
import {
  DashboardOutlined,
  ShoppingOutlined,
  TeamOutlined,
  DollarOutlined,
  BarChartOutlined,
  SettingOutlined,
  AppstoreOutlined,
  DownOutlined,
  UpOutlined,
} from '@ant-design/icons';
import '../../styles/Sidebar.css';

const Sidebar = ({ collapsed, onClose }) => {
  const location = useLocation();
  const user = useSelector(state => state.auth.user);
  const [openSubmenu, setOpenSubmenu] = useState(null);
  const [dynamicMenu, setDynamicMenu] = useState([]);

  const canSeePriceLists = (permissions) => {
    const hasPermission = (name) => permissions?.some((p) => p.name === name || p === name);
    const isAdmin = user?.role === 'admin' || user?.role === 'super-admin';
    return isAdmin || hasPermission('price_lists.view') || hasPermission('products.view');
  };

  // Icon mapping for module groups
  const groupIconMap = {
    'merchandise': <ShoppingOutlined />,
    'customer': <TeamOutlined />,
    'finance': <DollarOutlined />,
    'reports': <BarChartOutlined />,
    'system': <SettingOutlined />,
  };

  // Module display names (Vietnamese)
  const moduleDisplayNames = {
    products: 'Danh sách sản phẩm',
    product_categories: 'Danh mục SP',
    price_lists: 'Bảng giá',
    purchase_orders: 'Đơn mua hàng',
    inventory: 'Kho hàng',
    cash: 'Sổ quỹ',
    partners: 'Đối tác',
    customers: 'Khách hàng',
    customer_groups: 'Nhóm KH',
    orders: 'Đơn hàng',
    shipments: 'Vận chuyển',
    returns: 'Trả hàng',
    invoices: 'Hóa đơn',
    reports: 'Báo cáo',
    users: 'Người dùng',
    roles: 'Vai trò',
    branches: 'Chi nhánh',
    settings: 'Cài đặt',
  };

  // Module routes
  const moduleRoutes = {
    'products': '/products',
    'product_categories': '/product-categories',
    'price_lists': '/price-lists',
    'purchase_orders': '/purchase-orders',
    'inventory': '/inventory',
    'cash': '/cash',
    'partners': '/partners',
    'customers': '/customers',
    'customer_groups': '/customer-groups',
    'orders': '/orders',
    'shipments': '/shipments',
    'returns': '/orders/returns',
    'invoices': '/invoices',
    'reports': '/reports',
    'users': '/users',
    'roles': '/roles',
    'branches': '/branches',
    'settings': '/settings',
  };

  // Group display names
  const groupDisplayNames = {
    'merchandise': 'Hàng hóa',
    'customer': 'Khách hàng',
    'finance': 'Tài chính',
    'reports': 'Báo cáo',
    'system': 'Hệ thống',
  };

  // BUILD DYNAMIC MENU từ user permissions (fallback hiển thị cơ bản)
  useEffect(() => {
    const menu = buildDynamicMenu(user?.permissions || []);
    setDynamicMenu(menu);
  }, [user]);

  // AUTO-EXPAND SUBMENU khi URL match
  useEffect(() => {
    const currentGroup = findCurrentGroup(dynamicMenu, location.pathname);
    if (currentGroup) {
      setOpenSubmenu(currentGroup);
    }
  }, [location.pathname, dynamicMenu]);

  // BUILD DYNAMIC MENU FROM USER PERMISSIONS
  const buildDynamicMenu = (permissions) => {
    // Nếu không có quyền và không phải admin -> không hiện menu
    if ((!permissions || permissions.length === 0) && !canSeePriceLists([])) {
      return [];
    }
    let groups = {};

    permissions.forEach(permission => {
      const module = permission.module;
      const moduleGroup = permission.module_group;

      if (!moduleGroup) return;

      // Check if user has .view permission for this module
      const hasViewPermission = permissions.some(
        p => p.module === module && p.name.endsWith('.view')
      );

      if (!hasViewPermission) return;

      // Initialize group
      if (!groups[moduleGroup]) {
        groups[moduleGroup] = {
          key: moduleGroup,
          label: groupDisplayNames[moduleGroup] || moduleGroup,
          icon: groupIconMap[moduleGroup] || <AppstoreOutlined />,
          children: [],
        };
      }

      // Add module if not exists
      const moduleExists = groups[moduleGroup].children.some(m => m.key === module);
      if (!moduleExists) {
        groups[moduleGroup].children.push({
          key: module,
          label: moduleDisplayNames[module] || module,
          path: moduleRoutes[module] || `/${module}`,
        });
      }
    });

    groups = ensurePriceListMenu(groups, permissions);

    return Object.values(groups);
  };

  // Ensure price lists appear under Hàng hóa when user có quyền xem sản phẩm
  const ensurePriceListMenu = (groups, permissions) => {
    const hasProductView = permissions.some(
      (p) => p.module === 'products' && p.name.endsWith('.view')
    );
    if (!hasProductView && !canSeePriceLists(permissions)) { return groups; }

    const updated = { ...groups };
    if (!updated['merchandise']) {
      updated['merchandise'] = {
        key: 'merchandise',
        label: groupDisplayNames['merchandise'] || 'Hàng hóa',
        icon: groupIconMap['merchandise'] || <AppstoreOutlined />,
        children: [],
      };
    }

    const exists = updated['merchandise'].children.some((c) => c.key === 'price_lists');
    if (!exists) {
      updated['merchandise'].children.unshift({
        key: 'price_lists',
        label: moduleDisplayNames['price_lists'],
        path: moduleRoutes['price_lists'],
      });
    }
    return updated;
  };

  const findCurrentGroup = (menuGroups, pathname) => {
    for (const group of menuGroups) {
      const found = group.children.some(m => pathname.startsWith(m.path));
      if (found) return group.key;
    }
    return null;
  };

  const toggleSubmenu = (key) => {
    setOpenSubmenu(openSubmenu === key ? null : key);
  };

  return (
    <>
      <div className={`sidebar ${collapsed ? 'collapsed' : ''}`}>


        {/* Navigation */}
        <nav className="sidebar-nav">
          {/* Dashboard - Always visible */}
          <NavLink
            to="/dashboard"
            className={({ isActive }) =>
              `sidebar-nav-item ${isActive ? 'active' : ''}`
            }
            onClick={onClose}
          >
            <span className="nav-icon">
              <DashboardOutlined />
            </span>
            {!collapsed && <span className="nav-label">Dashboard</span>}
          </NavLink>

          {/* DYNAMIC MODULE GROUPS */}
          {dynamicMenu.map((group) => (
            <div key={group.key} className="nav-group">
              {/* Group Header */}
              <div
                className={`nav-group-header ${
                  openSubmenu === group.key ? 'open' : ''
                }`}
                onClick={() => toggleSubmenu(group.key)}
              >
                <span className="nav-icon">{group.icon}</span>
                {!collapsed && (
                  <>
                    <span className="nav-label">{group.label}</span>
                    <span className="nav-arrow">
                      {openSubmenu === group.key ? <UpOutlined /> : <DownOutlined />}
                    </span>
                  </>
                )}
              </div>

              {/* Submenu Items */}
              {openSubmenu === group.key && !collapsed && (
                <div className="submenu">
                  {group.children.map((child) => (
                    <NavLink
                      key={child.key}
                      to={child.path}
                      className={({ isActive }) =>
                        `submenu-item ${isActive ? 'active' : ''}`
                      }
                      onClick={onClose}
                    >
                      <span className="nav-label">{child.label}</span>
                    </NavLink>
                  ))}
                </div>
              )}
            </div>
          ))}
        </nav>
      </div>

      {/* Overlay for mobile */}
      {!collapsed && <div className="sidebar-overlay" onClick={onClose}></div>}
    </>
  );
};

export default Sidebar;
