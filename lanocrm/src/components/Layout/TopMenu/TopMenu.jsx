import React, { useState, useEffect, useRef } from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { useSelector } from 'react-redux';
import {
  ShoppingOutlined,
  TeamOutlined,
  DollarOutlined,
  BarChartOutlined,
  SettingOutlined,
  AppstoreOutlined,
  DownOutlined,
  FileOutlined,
  SwapOutlined,
  UndoOutlined,
  UserOutlined,
} from '@ant-design/icons';
import { MenuOutlined, CloseOutlined } from '@ant-design/icons';  // ← Add
import styles from './TopMenu.module.css';

const TopMenu = () => {
  const location = useLocation();
  const user = useSelector((state) => state.auth?.user);
  const [dynamicMenu, setDynamicMenu] = useState([]);
  const [openSubmenu, setOpenSubmenu] = useState(null);
  const menuRef = useRef(null); // ← ADD THIS
  const isInitialMount = useRef(true);
  const isUserClick = useRef(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);  // ← Add


  const groupIconMap = {
    products: <ShoppingOutlined />,
    merchandise: <ShoppingOutlined />,
    customers: <TeamOutlined />,
    partners: <UserOutlined />,
    financial: <DollarOutlined />,
    sales: <BarChartOutlined />,
    invoices: <FileOutlined />,
    returns: <UndoOutlined />,
    inventory: <SwapOutlined />,
    branches: <AppstoreOutlined />,
    users: <UserOutlined />,
    roles: <SettingOutlined />,
    settings: <SettingOutlined />,
  };

  const fullModulesList = [
    {
      key: 'products',
      name: 'Hàng hoá',
      route: '/products',
      icon: 'products',
      submodules: [
        { key: 'products_list', name: 'Danh sách sản phẩm', route: '/products' },
        { key: 'categories', name: 'Danh mục', route: '/product-categories' },
        { key: 'Attributes', name: 'Thuộc tính', route: '/products/Attributes' },
        { key: 'price_lists', name: 'Bảng giá', route: '/price-lists' },
      ],
    },
    {
      key: 'customers',
      name: 'Khách hàng',
      route: '/customers',
      icon: 'customers',
      submodules: [
        { key: 'customer_list', name: 'Danh sách KH', route: '/customers' },
        { key: 'customer_groups', name: 'Nhóm KH', route: '/customers/groups' },
        { key: 'customer_debt', name: 'Công nợ KH', route: '/customers/debt' },
      ],
    },
    {
      key: 'sales',
      name: 'Bán hàng',
      route: '/sales',
      icon: 'sales',
      submodules: [
        { key: 'orders', name: 'Đơn hàng', route: '/sales/orders' },
        { key: 'invoices', name: 'Hóa đơn', route: '/sales/invoices' },
        { key: 'returns', name: 'Trả hàng', route: '/sales/returns' },
      ],
    },
    {
      key: 'inventory',
      name: 'Nhập xuất',
      route: '/inventory',
      icon: 'inventory',
      submodules: [
        { key: 'purchase_orders', name: 'Đơn nhập', route: '/inventory/purchase' },
        { key: 'inventory_transfer', name: 'Điều chuyển', route: '/inventory/transfer' },
        { key: 'inventory_adjustment', name: 'Điều chỉnh kho', route: '/inventory/adjustment' },
      ],
    },
    {
      key: 'financial',
      name: 'Tài chính',
      route: '/financial',
      icon: 'financial',
      submodules: [
        { key: 'cash_book', name: 'Sổ quỹ', route: '/financial/cash-book' },
        { key: 'income_expense', name: 'Thu chi', route: '/financial/income-expense' },
        { key: 'payments', name: 'Thanh toán', route: '/financial/payments' },
        { key: 'reports', name: 'Báo cáo', route: '/financial/reports' },
      ],
    },
    {
      key: 'partners',
      name: 'Đối tác',
      route: '/partners',
      icon: 'partners',
      submodules: [
        { key: 'suppliers', name: 'Nhà cung cấp', route: '/partners/suppliers' },
        { key: 'vendors', name: 'Nhà phân phối', route: '/partners/vendors' },
      ],
    },
    {
      key: 'branches',
      name: 'Chi nhánh',
      route: '/branches',
      icon: 'branches',
      submodules: [],
    },
    {
      key: 'users',
      name: 'Người dùng',
      route: '/users',
      icon: 'users',
      submodules: [
        { key: 'user_list', name: 'Danh sách ND', route: '/users' },
        { key: 'user_roles', name: 'Vai trò', route: '/roles' },
      ],
    },
    {
      key: 'settings',
      name: 'Cài đặt',
      route: '/settings',
      icon: 'settings',
      submodules: [
        { key: 'general', name: 'Tổng quát', route: '/settings/general' },
        { key: 'system', name: 'Hệ thống', route: '/settings/system' },
        { key: 'import_export', name: 'Nhập/Xuất', route: '/settings/import-export' },
      ],
    },
  ];

  useEffect(() => {
    const modules = user?.modules && Array.isArray(user.modules) && user.modules.length > 0
      ? user.modules
      : fullModulesList;

    if (!modules || modules.length === 0) {
      setDynamicMenu([]);
      return;
    }

    const menuItems = modules.map((module) => {
      const moduleKey = module.key || module;
      const icon = groupIconMap[moduleKey] || <AppstoreOutlined />;
      const moduleDetails = fullModulesList.find((m) => m.key === moduleKey);

      const subItems =
        moduleDetails?.submodules && Array.isArray(moduleDetails.submodules)
          ? moduleDetails.submodules.map((sub) => ({
              key: sub.key,
              label: sub.name,
              path: sub.route,
            }))
          : module.submodules?.map((sub) => ({
              key: sub.key,
              label: sub.name,
              path: sub.route || `/${moduleKey}/${sub.key}`,
            })) || [];

      // Ensure price lists item always present under Hàng hoá/products
      if (moduleKey === 'products' && !subItems.some((s) => s.key === 'price_lists')) {
        subItems.unshift({ key: 'price_lists', label: 'Bảng giá', path: '/price-lists' });
      }

      return {
        key: moduleKey,
        label: moduleDetails?.name || module.name || moduleKey,
        icon: icon,
        path: moduleDetails?.route || module.route || `/${moduleKey}`,
        subItems: subItems,
      };
    });

    setDynamicMenu(menuItems);
  }, [user?.modules]);

  useEffect(() => {
    if (isInitialMount.current) {
      isInitialMount.current = false;
      return;
    }

    if (isUserClick.current) {
      isUserClick.current = false;
      return;
    }

    const activeParent = dynamicMenu.find((item) =>
      item.subItems?.some((sub) => location.pathname === sub.path)
    );

    if (activeParent) {
      setOpenSubmenu(activeParent.key);
    }
  }, [location.pathname, dynamicMenu]);

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (menuRef.current && !menuRef.current.contains(event.target)) {
        setOpenSubmenu(null); // ← Close dropdown khi click outside
      }
    };
  
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const toggleSubmenu = (key) => {
    setOpenSubmenu(openSubmenu === key ? null : key);
  };

  const isSubmenuOpen = (menuItem) => {
    return openSubmenu === menuItem.key;
  };

  const isSubmenuActive = (menuItem) => {
    return menuItem.subItems?.some((sub) => location.pathname === sub.path);
  };

  const handleSubmenuClick = () => {
    isUserClick.current = true;
    setOpenSubmenu(null);
    setMobileMenuOpen(false);  // ← Close mobile menu on submenu click
  };

  return (
    <nav className={styles.topMenu} ref={menuRef}>
    <ul className={`${styles.topMenuList} ${mobileMenuOpen ? styles.mobileOpen : ''}`}>
      {dynamicMenu.map((menuItem) => (
        <li key={menuItem.key} className={styles.topMenuItem}>
          {menuItem.subItems && menuItem.subItems.length > 0 ? (
            <div className={styles.topMenuDropdown}>
              <button
                className={`${styles.topMenuLink} ${
                  isSubmenuOpen(menuItem) ? styles.active : ''
                } ${isSubmenuActive(menuItem) ? styles.hasActiveChild : ''}`}
                onClick={() => toggleSubmenu(menuItem.key)}
                type="button"
                title={menuItem.label}
              >
                <span className={styles.topMenuIcon}>{menuItem.icon}</span>
                <span className={styles.topMenuText}>{menuItem.label}</span>
                <span
                  className={`${styles.topMenuArrow} ${
                    isSubmenuOpen(menuItem) ? styles.open : ''
                  }`}
                >
                  <DownOutlined />
                </span>
              </button>

              <ul
                className={`${styles.topSubmenu} ${
                  isSubmenuOpen(menuItem) ? styles.visible : ''
                }`}
              >
                {menuItem.subItems.map((subItem) => (
                  <li key={subItem.key} className={styles.topSubmenuItem}>
                    <NavLink
                      to={subItem.path}
                      className={({ isActive }) =>
                        `${styles.topSubmenuLink} ${
                          isActive ? styles.active : ''
                        }`
                      }
                      onClick={handleSubmenuClick}
                      title={subItem.label}
                      end
                    >
                      {subItem.label}
                    </NavLink>
                  </li>
                ))}
              </ul>
            </div>
          ) : (
            <NavLink
              to={menuItem.path}
              className={({ isActive }) =>
                `${styles.topMenuLink} ${isActive ? styles.active : ''}`
              }
              title={menuItem.label}
              end
            >
              <span className={styles.topMenuIcon}>{menuItem.icon}</span>
              <span className={styles.topMenuText}>{menuItem.label}</span>
            </NavLink>
          )}
        </li>
      ))}
    </ul>

    {/* ===== MOBILE HAMBURGER BUTTON ===== */}
    <button
      className={styles.mobileMenuToggle}
      onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
      type="button"
      aria-label="Toggle menu"
    >
      {mobileMenuOpen ? <CloseOutlined /> : <MenuOutlined />}
    </button>

    {/* ===== MOBILE OVERLAY ===== */}
    {mobileMenuOpen && (
      <div
        className={styles.mobileMenuOverlay}
        onClick={() => setMobileMenuOpen(false)}
      />
    )}
  </nav>
  );
};

export default TopMenu;
