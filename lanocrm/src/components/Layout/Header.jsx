import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useDispatch, useSelector } from 'react-redux';
import { logout } from '../../store/slices/authSlice';
import {
  BellOutlined,
  SettingOutlined,
  UserOutlined,
  LogoutOutlined,
} from '@ant-design/icons';
import TopMenu from './TopMenu';
import '../../styles/Header.css';

const Header = ({ onToggleSidebar }) => {
  const navigate = useNavigate();
  const dispatch = useDispatch();
  const { user } = useSelector((state) => state.auth);
  const [showUserMenu, setShowUserMenu] = useState(false);

  const handleLogout = () => {
    dispatch(logout());
    navigate('/login');
  };

  return (
    <header className="main-header">
      {/* ROW 1: Logo + Icons + User */}
      <div className="header-top">
      <Link to="/dashboard" className="logo">
          <img src="/logo.png" alt="LanoCRM" className="logo-image"/>
        </Link>
        <div className="header-right">
          <button
            className="header-icon-btn notification-btn"
            aria-label="Notifications"
            title="Thông báo"
          >
            <BellOutlined />
            <span className="notification-badge">3</span>
          </button>

          <button
            className="header-icon-btn settings-btn"
            onClick={() => navigate('/settings/general')}
            aria-label="Settings"
            title="Cài đặt hệ thống"
          >
            <SettingOutlined />
          </button>

          <div className="user-menu">
            <button
              className="user-menu-btn"
              onClick={() => setShowUserMenu(!showUserMenu)}
              aria-label="User menu"
              title={user?.name || 'Admin'}
            >
              <div className="user-avatar">
                {user?.name?.charAt(0).toUpperCase() || 'A'}
              </div>
              <span className="user-username">{user?.name || 'Admin'}</span>
            </button>

            {showUserMenu && (
              <div className="user-dropdown-menu">
                <div className="dropdown-header">
                  <div className="dropdown-user-info">
                    <div className="dropdown-avatar">
                      {user?.name?.charAt(0).toUpperCase() || 'A'}
                    </div>
                    <div className="dropdown-user-details">
                      <div className="dropdown-username">{user?.name || 'Admin'}</div>
                      <div className="dropdown-email">{user?.email || 'admin@lano.com'}</div>
                    </div>
                  </div>
                </div>

                <div className="dropdown-divider" />

                <div className="dropdown-menu-items">
                  {/* Profile: View/Edit current user info */}
                  <Link
                    to="/profile"
                    className="dropdown-item"
                    onClick={() => setShowUserMenu(false)}
                    title="Xem và sửa thông tin cá nhân"
                  >
                    <UserOutlined />
                    <span>Thông tin cá nhân</span>
                  </Link>

                  {/* Account Settings: Edit account details */}
                  <Link
                    to="/settings/account"
                    className="dropdown-item"
                    onClick={() => setShowUserMenu(false)}
                    title="Cài đặt tài khoản người dùng"
                  >
                    <SettingOutlined />
                    <span>Cài đặt tài khoản</span>
                  </Link>
                </div>

                <div className="dropdown-divider" />

                <button
                  className="dropdown-item logout-item"
                  onClick={() => {
                    handleLogout();
                    setShowUserMenu(false);
                  }}
                >
                  <LogoutOutlined />
                  <span>Đăng xuất</span>
                </button>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* ROW 2: TopMenu (Sticky) */}
      <div className="header-bottom">
        <TopMenu />
      </div>
    </header>
  );
};

export default Header;