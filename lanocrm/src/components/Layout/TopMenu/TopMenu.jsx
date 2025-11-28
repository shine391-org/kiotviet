import React, { useState, useEffect, useRef } from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { useSelector } from 'react-redux';
import { DownOutlined, MenuOutlined, CloseOutlined } from '@ant-design/icons';
import {
  AppstoreOutlined,
  ShoppingOutlined,
  FileTextOutlined,
  TeamOutlined,
  WalletOutlined,
  BarChartOutlined,
  ShopOutlined,
  ShoppingCartOutlined,
} from '@ant-design/icons';
import styles from './TopMenu.module.css';

const MENU_CONFIG = [
  {
    key: 'overview',
    name: 'Tổng quan',
    path: '/dashboard',
    subItems: [],
  },
  {
    key: 'products',
    name: 'Hàng hóa',
    path: '/products',
    subItems: [
      { key: 'products_list', label: 'Danh sách hàng hóa', path: '/products', section: 'Hàng hóa' },
      { key: 'price_lists', label: 'Thiết lập giá', path: '/price-lists', section: 'Hàng hóa' },

      { key: 'inventory_transfer', label: 'Chuyển hàng', path: '/inventory/transfer', section: 'Kho hàng' },
      { key: 'inventory_audit', label: 'Kiểm kho', path: '/inventory/audit', section: 'Kho hàng' },
      { key: 'inventory_dispose', label: 'Xuất hủy', path: '/inventory/dispose', section: 'Kho hàng' },

      { key: 'suppliers', label: 'Nhà cung cấp', path: '/partners/suppliers', section: 'Nhập hàng' },
      { key: 'purchase_orders', label: 'Nhập hàng', path: '/inventory/purchase', section: 'Nhập hàng' },
      { key: 'purchase_returns', label: 'Trả hàng nhập', path: '/inventory/purchase-returns', section: 'Nhập hàng' },

      { key: 'categories', label: 'Danh mục', path: '/product-categories', section: 'Khác' },
      { key: 'attributes', label: 'Thuộc tính', path: '/products/attributes', section: 'Khác' },
    ],
  },
  {
    key: 'orders',
    name: 'Đơn hàng',
    path: '/orders',
    subItems: [
      { key: 'order_create', label: 'Đặt hàng', path: '/orders/create' },
      { key: 'invoices', label: 'Hóa đơn', path: '/orders/invoices' },
      { key: 'returns', label: 'Trả hàng', path: '/orders/returns' },
      { key: 'delivery_partner', label: 'Đối tác giao hàng', path: '/orders/delivery-partners' },
      { key: 'shipment', label: 'Vận đơn', path: '/orders/shipments' },
    ],
  },
  {
    key: 'customers',
    name: 'Khách hàng',
    path: '/customers',
    subItems: [
      { key: 'customer_list', label: 'Danh sách khách hàng', path: '/customers' },
      { key: 'voucher', label: 'Voucher', path: '/customers/vouchers' },
    ],
  },
  {
    key: 'cashbook',
    name: 'Sổ quỹ',
    path: '/cash',
    subItems: [],
  },
  {
    key: 'reports',
    name: 'Báo cáo',
    path: '/reports',
    subItems: [
      { key: 'daily', label: 'Cuối ngày', path: '/reports/daily' },
      { key: 'sales', label: 'Bán hàng', path: '/reports/sales' },
      { key: 'orders', label: 'Đặt hàng', path: '/reports/orders' },
      { key: 'products', label: 'Hàng hóa', path: '/reports/products' },
      { key: 'customers', label: 'Khách hàng', path: '/reports/customers' },
      { key: 'suppliers', label: 'Nhà cung cấp', path: '/reports/suppliers' },
      { key: 'staff', label: 'Nhân viên', path: '/reports/staff' },
      { key: 'channels', label: 'Kênh bán hàng', path: '/reports/channels' },
      { key: 'finance', label: 'Tài chính', path: '/reports/finance' },
    ],
  },
  {
    key: 'online',
    name: 'Bán online',
    path: '/online',
    subItems: [],
  },
];

const MENU_ICONS = {
  overview: AppstoreOutlined,
  products: ShoppingOutlined,
  orders: FileTextOutlined,
  customers: TeamOutlined,
  cashbook: WalletOutlined,
  reports: BarChartOutlined,
  online: ShopOutlined,
};

const TopMenu = () => {
  const location = useLocation();
  const user = useSelector((state) => state.auth?.user);
  const [dynamicMenu, setDynamicMenu] = useState([]);
  const [openSubmenu, setOpenSubmenu] = useState(null);
  const menuRef = useRef(null);
  const isInitialMount = useRef(true);
  const isUserClick = useRef(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  useEffect(() => {
    const hasModules = user?.modules && Array.isArray(user.modules) && user.modules.length > 0;
    const userModuleKeys = hasModules ? user.modules.map((m) => m.key || m) : null;

    let menuItems = hasModules
      ? MENU_CONFIG.filter((item) => userModuleKeys.includes(item.key))
      : MENU_CONFIG;

    if (!menuItems || menuItems.length === 0) {
      menuItems = MENU_CONFIG;
    }

    const requiredKeys = ['overview', 'cashbook'];
    requiredKeys.forEach((req) => {
      if (!menuItems.some((m) => m.key === req)) {
        const found = MENU_CONFIG.find((m) => m.key === req);
        if (found) menuItems = [...menuItems, found];
      }
    });

    const normalized = (menuItems || []).map((item) => ({
      ...item,
      label: item.label || item.name || item.key,
      subItems: (item.subItems || []).map((sub) => ({
        ...sub,
        label: sub.label || sub.name || sub.key,
      })),
    }));

    if (!normalized || normalized.length === 0) {
      setDynamicMenu([]);
      return;
    }

    setDynamicMenu(normalized);
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
        setOpenSubmenu(null);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const toggleSubmenu = (key) => {
    // bấm lại đúng menu đang mở thì đóng
    if (openSubmenu === key) {
      setOpenSubmenu(null);
      return;
    }
    // mở menu mới, tự đóng mọi menu khác
    setOpenSubmenu(key);
};

  const isSubmenuOpen = (menuItem) => openSubmenu === menuItem.key;

  const isSubmenuActive = (menuItem) =>
    menuItem.subItems?.some((sub) => location.pathname === sub.path);

  const handleSubmenuClick = () => {
    isUserClick.current = true;
    setOpenSubmenu(null);
    setMobileMenuOpen(false);
  };

  const renderGroupedSubmenu = (menuItem) => {
    const groups = (menuItem.subItems || []).reduce((acc, item) => {
      const section = item.section || 'Khác';
      if (!acc[section]) acc[section] = [];
      acc[section].push(item);
      return acc;
    }, {});

    const columns = Object.entries(groups);

    return (
      <ul
        className={`${styles.topSubmenu} ${isSubmenuOpen(menuItem) ? styles.visible : ''}`}
      >
        <div className={styles.topSubmenuColumns}>
          {columns.map(([section, items]) => (
            <div key={section} className={styles.topSubmenuColumn}>
              {section !== 'Khác' && (
                <div className={styles.topSubmenuSection}>{section}</div>
              )}
              <div
                className={`${styles.topSubmenuItems} ${
                  section === 'Khác' ? styles.topSubmenuItemsWithDivider : ''
                }`}
              >
                {items.map((subItem) => (
                  <div key={subItem.key} className={styles.topSubmenuItem}>
                    {subItem.path ? (
                      <NavLink
                        to={subItem.path}
                        className={({ isActive }) =>
                          `${styles.topSubmenuLink} ${isActive ? styles.active : ''}`
                        }
                        onClick={handleSubmenuClick}
                        title={subItem.label}
                        end
                      >
                        {subItem.label}
                      </NavLink>
                    ) : (
                      <span className={styles.topSubmenuLinkDisabled}>
                        {subItem.label}
                      </span>
                    )}
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>
      </ul>
    );
  };

  return (
    <nav className={styles.topMenu} ref={menuRef}>
      <div className={styles.menuRow}>
        <ul className={`${styles.topMenuList} ${mobileMenuOpen ? styles.mobileOpen : ''}`}>
          {dynamicMenu.map((menuItem) => {
            const Icon = MENU_ICONS[menuItem.key];
            return (
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
                      {Icon && (
                        <span className={styles.topMenuIcon}>
                          <Icon />
                        </span>
                      )}
                      <span className={styles.topMenuText}>{menuItem.label}</span>
                    </button>

                    {renderGroupedSubmenu(menuItem)}
                  </div>
                ) : (
                  <>
                    {menuItem.path ? (
                      <NavLink
                        to={menuItem.path}
                        className={({ isActive }) =>
                          `${styles.topMenuLink} ${isActive ? styles.active : ''}`
                        }
                        title={menuItem.label}
                        end
                        onClick={() => {
                          setOpenSubmenu(null);       // đóng mọi submenu đang mở
                          setMobileMenuOpen(false);   // nếu đang ở hamburger thì đóng luôn menu
                        }}
                      >
                        {Icon && (
                          <span className={styles.topMenuIcon}>
                            <Icon />
                          </span>
                        )}
                        <span className={styles.topMenuText}>{menuItem.label}</span>
                      </NavLink>
                    ) : (
                      <button
                        className={`${styles.topMenuLink} ${styles.topMenuLinkDisabled}`}
                        type="button"
                        onClick={() => {
                          setOpenSubmenu(null);
                          setMobileMenuOpen(false);
                        }}
                      >
                        {Icon && (
                          <span className={styles.topMenuIcon}>
                            <Icon />
                          </span>
                        )}
                        <span className={styles.topMenuText}>{menuItem.label}</span>
                      </button>
                    )}

                  </>
                )}
              </li>
            );
          })}
        </ul>
      </div>

      <NavLink to="/sales" className={styles.ctaButton}>
        <ShoppingCartOutlined />
        <span>Bán hàng</span>
      </NavLink>

      {/* MOBILE HAMBURGER BUTTON */}
      <button
        className={styles.mobileMenuToggle}
        onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
        type="button"
        aria-label="Toggle menu"
      >
        {mobileMenuOpen ? <CloseOutlined /> : <MenuOutlined />}
      </button>

      {/* MOBILE OVERLAY */}
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