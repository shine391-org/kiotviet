import React, { useState, useEffect, useRef } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { loginUser, clearError } from '../store/slices/authSlice';
import { EyeOutlined, EyeInvisibleOutlined } from '@ant-design/icons';

const Login = () => {
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const { loading, error, isAuthenticated } = useSelector((state) => state.auth);

  // Dùng useRef để lưu giá trị không bị reset khi re-render
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [remember, setRemember] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  
  // NOTE: trước đây dùng flag để tránh redirect; gây lỗi không chuyển trang.
  // Giữ ref để biết có click login, nhưng redirect sẽ xảy ra khi isAuthenticated true.
  const isLoginAttempted = useRef(false);

  useEffect(() => {
    // Redirect ngay khi đã đăng nhập (kể cả đã có token từ trước)
    if (isAuthenticated) {
      navigate('/dashboard', { replace: true });
    }
  }, [isAuthenticated, navigate]);

  // Clear error khi user bắt đầu nhập lại
  const handleUsernameChange = (e) => {
    setUsername(e.target.value);
    if (error) {
      dispatch(clearError());
    }
  };

  const handlePasswordChange = (e) => {
    setPassword(e.target.value);
    if (error) {
      dispatch(clearError());
    }
  };

  const handleRememberChange = (e) => {
    setRemember(e.target.checked);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!username || !password) {
      return;
    }

    isLoginAttempted.current = true;

    // Dispatch action - KHÔNG reset state
    await dispatch(loginUser({
      username: username,
      password: password,
    }));
    
    // State vẫn giữ nguyên, không clear
    // Nếu thành công -> useEffect redirect
    // Nếu thất bại -> error hiện, form vẫn giữ nguyên
  };

  return (
    <div className="login-container">
      <div className="login-background"></div>
      
      <div className="login-card">
        <div className="login-header">
          <div className="logo">
            <img src="/logo.png" alt="Lano" className="logo-image" />
          </div>

        </div>

        <form onSubmit={handleSubmit} className="login-form" autoComplete="off">
          <div className="form-group">
            <input
              type="text"
              name="username"
              placeholder="Tên đăng nhập"
              value={username}
              onChange={handleUsernameChange}
              className={`form-input ${error ? 'input-error' : ''}`}
              autoComplete="off"
              autoFocus
            />
          </div>

          <div className="form-group">
            <div className="password-input-wrapper">
              <input
                type={showPassword ? 'text' : 'password'}
                name="password"
                placeholder="Mật khẩu"
                value={password}
                onChange={handlePasswordChange}
                className={`form-input ${error ? 'input-error' : ''}`}
                autoComplete="off"
              />
              <button
                type="button"
                className="password-toggle"
                onClick={() => setShowPassword(!showPassword)}
                tabIndex="-1"
              >
                {showPassword ? <EyeInvisibleOutlined /> : <EyeOutlined />}
              </button>
            </div>
          </div>

          <div className="form-options">
            <label className="checkbox-label">
              <input
                type="checkbox"
                name="remember"
                checked={remember}
                onChange={handleRememberChange}
              />
              <span>Duy trì đăng nhập</span>
            </label>
          </div>

          {error && (
            <div className="error-message">
              ❌ {error}
            </div>
          )}

          <button
            type="submit"
            className="btn-login-full"
            disabled={loading || !username || !password}
          >
            {loading ? 'Đang đăng nhập...' : 'Đăng nhập'}
          </button>
        </form>
      </div>
    </div>
  );
};

export default Login;
