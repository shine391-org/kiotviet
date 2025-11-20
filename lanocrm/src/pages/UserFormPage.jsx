// src/pages/UserFormPage.jsx - LOAD ROLES TỪ REDUX
import React, { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { useNavigate, useParams } from 'react-router-dom';
import { createUser, updateUser, fetchUserById, clearCurrentUser } from '../store/slices/userSlice';
import { fetchRoles } from '../store/slices/roleSlice'; // ← THÊM IMPORT NÀY
import { SaveOutlined, ArrowLeftOutlined, KeyOutlined } from '@ant-design/icons';
import userApi from '../api/userApi';

const UserFormPage = () => {
  const { id } = useParams();
  const isEditMode = Boolean(id);
  const dispatch = useDispatch();
  const navigate = useNavigate();

  const { currentUser, loading } = useSelector((state) => state.user);
  const { roles } = useSelector((state) => state.role); // ← THÊM ROLES TỪ REDUX

  const [formData, setFormData] = useState({
    username: '',
    password: '',
    password_confirm: '',
    full_name: '',
    email: '',
    phone: '',
    branch_id: '',
    role_id: '',
    status: 'active'
  });

  const [showChangePassword, setShowChangePassword] = useState(false);
  const [passwordData, setPasswordData] = useState({
    new_password: '',
    password_confirm: ''
  });

  const [passwordErrors, setPasswordErrors] = useState({});
  const [errors, setErrors] = useState({});
  const [branches, setBranches] = useState([]);
  const [loadingBranches, setLoadingBranches] = useState(false);

  // ✅ Load branches từ database (GIỮ NGUYÊN)
  useEffect(() => {
    const loadBranches = async () => {
      setLoadingBranches(true);
      try {
        const response = await userApi.getBranches();
        setBranches(response.data || []);
      } catch (error) {
        console.error('Lỗi load branches:', error);
        alert('Không thể tải danh sách chi nhánh');
      } finally {
        setLoadingBranches(false);
      }
    };

    loadBranches();
  }, []);

  // ✅ Load roles TỪ REDUX (THAY THẾ ĐOẠN CŨ)
  useEffect(() => {
    dispatch(fetchRoles({ limit: 100 }));
  }, [dispatch]);

  // Load user data khi edit
  useEffect(() => {
    if (isEditMode && id) {
      dispatch(fetchUserById(id));
    }
    return () => {
      dispatch(clearCurrentUser());
    };
  }, [id, isEditMode, dispatch]);

  // Fill form data
  useEffect(() => {
    if (isEditMode && currentUser) {
      setFormData({
        username: currentUser.username || '',
        password: '',
        password_confirm: '',
        full_name: currentUser.full_name || '',
        email: currentUser.email || '',
        phone: currentUser.phone || '',
        branch_id: currentUser.branch_id || '',
        role_id: currentUser.role_id || currentUser.role?.id || '',
        status: currentUser.status || 'active'
      });
    }
  }, [currentUser, isEditMode]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData({
      ...formData,
      [name]: value
    });
    if (errors[name]) {
      setErrors({
        ...errors,
        [name]: ''
      });
    }
  };

  const handlePasswordChange = (e) => {
    const { name, value } = e.target;
    setPasswordData({
      ...passwordData,
      [name]: value
    });
    if (passwordErrors[name]) {
      setPasswordErrors({
        ...passwordErrors,
        [name]: ''
      });
    }
  };

  const validate = () => {
    const newErrors = {};

    if (!formData.username.trim()) {
      newErrors.username = 'Tên đăng nhập không được để trống';
    } else if (formData.username.length < 3) {
      newErrors.username = 'Tên đăng nhập phải có ít nhất 3 ký tự';
    }

    const strongPasswordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;

    if (!isEditMode) {
      if (!formData.password) {
        newErrors.password = 'Mật khẩu không được để trống';
      } else if (!strongPasswordRegex.test(formData.password)) {
        newErrors.password = 'Mật khẩu phải có ít nhất 8 ký tự bao gồm chữ hoa, chữ thường và số';
      }

      if (formData.password !== formData.password_confirm) {
        newErrors.password_confirm = 'Mật khẩu xác nhận không khớp';
      }
    }

    if (formData.email && !/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.email = 'Email không hợp lệ';
    }

    if (formData.phone && !/^[0-9]{10,11}$/.test(formData.phone)) {
      newErrors.phone = 'Số điện thoại phải có 10-11 chữ số';
    }

    if (!formData.role_id) {
      newErrors.role_id = 'Vui lòng chọn nhóm người dùng';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const validatePasswordChange = () => {
    const newErrors = {};
    const strongPasswordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;

    if (!passwordData.new_password) {
      newErrors.new_password = 'Vui lòng nhập mật khẩu mới';
    } else if (!strongPasswordRegex.test(passwordData.new_password)) {
      newErrors.new_password = 'Mật khẩu phải có ít nhất 8 ký tự bao gồm chữ hoa, chữ thường và số';
    }

    if (passwordData.new_password !== passwordData.password_confirm) {
      newErrors.password_confirm = 'Mật khẩu xác nhận không khớp';
    }

    setPasswordErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleChangePassword = async (e) => {
    e.preventDefault();

    if (!validatePasswordChange()) {
      return;
    }

    try {
      const response = await userApi.changePassword(id, passwordData);
      alert(response.message || 'Đổi mật khẩu thành công!');
      setShowChangePassword(false);
      setPasswordData({
        new_password: '',
        password_confirm: ''
      });
    } catch (error) {
      alert('Lỗi: ' + (error.response?.data?.message || error.message || 'Không thể đổi mật khẩu'));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!validate()) {
      return;
    }

    try {
      const submitData = { ...formData };
      delete submitData.password_confirm;

      if (isEditMode) {
        delete submitData.password;
        await dispatch(updateUser({ id, userData: submitData })).unwrap();
        alert('Cập nhật người dùng thành công!');
      } else {
        await dispatch(createUser(submitData)).unwrap();
        alert('Tạo người dùng thành công!');
      }

      navigate('/users');
    } catch (error) {
      alert('Lỗi: ' + (error || 'Không thể lưu dữ liệu'));
    }
  };

  return (
    <div className="user-form-page">
      <div className="page-header">
        <button className="btn-back" onClick={() => navigate('/users')}>
          <ArrowLeftOutlined /> Quay lại
        </button>
        <h1>{isEditMode ? '✏️ Chỉnh sửa người dùng' : '➕ Thêm người dùng mới'}</h1>
      </div>

      <div className="form-container">
        <form onSubmit={handleSubmit}>
          {/* Username */}
          <div className="form-group">
            <label>
              Tên đăng nhập <span className="required">*</span>
            </label>
            <input
              type="text"
              name="username"
              value={formData.username}
              onChange={handleChange}
              disabled={isEditMode}
              className={errors.username ? 'error' : ''}
            />
            {errors.username && <span className="error-message">{errors.username}</span>}
          </div>

          {/* Password (chỉ hiện khi tạo mới) */}
          {!isEditMode && (
            <>
              <div className="form-group">
                <label>
                  Mật khẩu <span className="required">*</span>
                </label>
                <input
                  type="password"
                  name="password"
                  value={formData.password}
                  onChange={handleChange}
                  className={errors.password ? 'error' : ''}
                />
                {errors.password && <span className="error-message">{errors.password}</span>}
              </div>

              <div className="form-group">
                <label>
                  Xác nhận mật khẩu <span className="required">*</span>
                </label>
                <input
                  type="password"
                  name="password_confirm"
                  value={formData.password_confirm}
                  onChange={handleChange}
                  className={errors.password_confirm ? 'error' : ''}
                />
                {errors.password_confirm && <span className="error-message">{errors.password_confirm}</span>}
              </div>
            </>
          )}

          {/* Full Name */}
          <div className="form-group">
            <label>Họ và tên</label>
            <input
              type="text"
              name="full_name"
              value={formData.full_name}
              onChange={handleChange}
            />
          </div>

          {/* Email */}
          <div className="form-group">
            <label>Email</label>
            <input
              type="email"
              name="email"
              value={formData.email}
              onChange={handleChange}
              className={errors.email ? 'error' : ''}
            />
            {errors.email && <span className="error-message">{errors.email}</span>}
          </div>

          {/* Phone */}
          <div className="form-group">
            <label>Số điện thoại</label>
            <input
              type="tel"
              name="phone"
              value={formData.phone}
              onChange={handleChange}
              className={errors.phone ? 'error' : ''}
            />
            {errors.phone && <span className="error-message">{errors.phone}</span>}
          </div>

          {/* ✅ ROLE DROPDOWN - LOAD TỪ REDUX */}
          <div className="form-group">
            <label>
              Vai trò <span className="required">*</span>
            </label>
            <select
              name="role_id"
              value={formData.role_id}
              onChange={handleChange}
              required
              disabled={
                isEditMode && 
                currentUser?.role?.name && 
                (currentUser.role.name.toLowerCase() === 'super-admin' || 
                currentUser.role.name.toLowerCase() === 'superadmin')
              }
            >
              <option value="">Chọn vai trò</option>
              {roles && roles
                .filter(role => {
                  const roleName = role.name.toLowerCase();
                  const isSuperAdmin = roleName === 'super-admin' || roleName === 'superadmin';
                  
                  // Nếu đang edit user super-admin, giữ nguyên option
                  if (isEditMode && currentUser?.role?.name) {
                    const currentRoleName = currentUser.role.name.toLowerCase();
                    if (currentRoleName === 'super-admin' || currentRoleName === 'superadmin') {
                      return true; // Hiển thị tất cả roles kể cả super-admin
                    }
                  }
                  
                  // Ngược lại, ẩn super-admin
                  return !isSuperAdmin;
                })
                .map(role => (
                  <option key={role.id} value={role.id}>
                    {role.description || role.name}
                  </option>
                ))}
            </select>

            {/* THÊM WARNING MESSAGE */}
            {isEditMode && 
            currentUser?.role?.name && 
            (currentUser.role.name.toLowerCase() === 'super-admin' || 
              currentUser.role.name.toLowerCase() === 'superadmin') && (
              <div style={{ 
                color: '#ff4d4f', 
                fontSize: '13px', 
                marginTop: '5px',
                fontWeight: '500'
              }}>
                🔒 Không thể thay đổi vai trò của super-admin
              </div>
            )}

            {errors.role_id && <span className="error-message">{errors.role_id}</span>}
          </div>

          {/* Branch */}
          <div className="form-group">
            <label>Chi nhánh</label>
            <select
              name="branch_id"
              value={formData.branch_id}
              onChange={handleChange}
              disabled={loadingBranches}
            >
              <option value="">Chọn chi nhánh</option>
              {branches.map(branch => (
                <option key={branch.id} value={branch.id}>
                  {branch.name}
                </option>
              ))}
            </select>
          </div>

          {/* Status */}
          <div className="form-group">
            <label>Trạng thái</label>
            <select name="status" value={formData.status} onChange={handleChange}>
              <option value="active">Hoạt động</option>
              <option value="inactive">Khóa</option>
            </select>
          </div>

          {/* Submit Buttons */}
          <div className="form-actions">
            <button type="button" className="btn btn-secondary" onClick={() => navigate('/users')}>
              Hủy
            </button>
            <button type="submit" className="btn btn-primary" disabled={loading}>
              <SaveOutlined /> {loading ? 'Đang lưu...' : 'Lưu'}
            </button>
          </div>
        </form>

        {/* Change Password Section (chỉ hiện khi edit) */}
        {isEditMode && (
          <div className="password-section">
            <button
              type="button"
              className="btn-change-password"
              onClick={() => setShowChangePassword(!showChangePassword)}
            >
              <KeyOutlined /> Đổi mật khẩu
            </button>

            {showChangePassword && (
              <form onSubmit={handleChangePassword} className="password-form">
                <div className="form-group">
                  <label>
                    Mật khẩu mới <span className="required">*</span>
                  </label>
                  <input
                    type="password"
                    name="new_password"
                    value={passwordData.new_password}
                    onChange={handlePasswordChange}
                    className={passwordErrors.new_password ? 'error' : ''}
                  />
                  {passwordErrors.new_password && (
                    <span className="error-message">{passwordErrors.new_password}</span>
                  )}
                </div>

                <div className="form-group">
                  <label>
                    Xác nhận mật khẩu mới <span className="required">*</span>
                  </label>
                  <input
                    type="password"
                    name="password_confirm"
                    value={passwordData.password_confirm}
                    onChange={handlePasswordChange}
                    className={passwordErrors.password_confirm ? 'error' : ''}
                  />
                  {passwordErrors.password_confirm && (
                    <span className="error-message">{passwordErrors.password_confirm}</span>
                  )}
                </div>

                <div className="form-actions">
                  <button
                    type="button"
                    className="btn-cancel"
                    onClick={() => {
                      setShowChangePassword(false);
                      setPasswordData({ new_password: '', password_confirm: '' });
                      setPasswordErrors({});
                    }}
                  >
                    Hủy
                  </button>
                  <button type="submit" className="btn-submit">
                    <SaveOutlined /> Lưu mật khẩu mới
                  </button>
                </div>
              </form>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

export default UserFormPage;