// src/pages/RoleFormPage.jsx - Form tạo/sửa vai trò
import React, { useState, useEffect, useCallback } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { useNavigate, useParams } from 'react-router-dom';
import { createRole, updateRole, fetchRoleById } from '../store/slices/roleSlice';
import { ArrowLeftOutlined, SaveOutlined } from '@ant-design/icons';

const RoleFormPage = () => {
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const { id } = useParams();
  const isEditMode = Boolean(id);

  const { currentRole, loading } = useSelector((state) => state.role);

  const [formData, setFormData] = useState({
    name: '',
    description: '',
    guard_name: 'api'
  });

  const [errors, setErrors] = useState({});

  // Load role data nếu là edit mode
  const loadRole = useCallback(() => {
    if (id) {
      dispatch(fetchRoleById(id));
    }
  }, [dispatch, id]);

  useEffect(() => {
    if (isEditMode) {
      loadRole();
    }
  }, [isEditMode, loadRole]);

  // Populate form khi có data
  useEffect(() => {
    if (currentRole && isEditMode) {
      setFormData({
        name: currentRole.name || '',
        description: currentRole.description || '',
        guard_name: currentRole.guard_name || 'api'
      });
    }
  }, [currentRole, isEditMode]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    // Clear error khi user nhập
    if (errors[name]) {
      setErrors(prev => ({
        ...prev,
        [name]: ''
      }));
    }
  };

  const validate = () => {
    const newErrors = {};

    if (!formData.name.trim()) {
      newErrors.name = 'Tên vai trò không được để trống';
    } else if (!/^[a-z0-9-]+$/.test(formData.name)) {
      newErrors.name = 'Tên vai trò chỉ được chứa chữ thường, số và dấu gạch ngang';
    }

    if (!formData.description.trim()) {
      newErrors.description = 'Mô tả không được để trống';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!validate()) {
      return;
    }

    try {
      if (isEditMode) {
        // Khi edit, chỉ gửi description và guard_name
        await dispatch(updateRole({
          id,
          roleData: {
            description: formData.description,
            guard_name: formData.guard_name
          }
        })).unwrap();
        alert('Cập nhật vai trò thành công!');
      } else {
        // Khi create, gửi đầy đủ
        await dispatch(createRole(formData)).unwrap();
        alert('Tạo vai trò thành công!');
      }
      navigate('/roles');
    } catch (error) {
      alert('Lỗi: ' + error);
    }
  };

  return (
    <div className="role-form-page">
      <div className="page-header">
        <button className="btn-back" onClick={() => navigate('/roles')}>
          <ArrowLeftOutlined /> Quay lại
        </button>
        <h1>{isEditMode ? '✏️ Chỉnh sửa vai trò' : '➕ Thêm vai trò mới'}</h1>
      </div>

      <div className="form-container">
        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label>
              Tên vai trò <span className="required">*</span>
            </label>
            <input
              type="text"
              name="name"
              value={formData.name}
              onChange={handleChange}
              placeholder="vd: warehouse-staff"
              disabled={isEditMode} // Không cho sửa name khi edit
              className={errors.name ? 'error' : ''}
            />
            {errors.name && <span className="error-message">{errors.name}</span>}
            {isEditMode && (
              <small className="help-text">Tên vai trò không thể thay đổi sau khi tạo</small>
            )}
          </div>

          <div className="form-group">
            <label>
              Mô tả <span className="required">*</span>
            </label>
            <textarea
              name="description"
              value={formData.description}
              onChange={handleChange}
              placeholder="Mô tả vai trò..."
              rows="4"
              className={errors.description ? 'error' : ''}
            />
            {errors.description && <span className="error-message">{errors.description}</span>}
          </div>

          <div className="form-group">
            <label>
              Guard Name <span className="required">*</span>
            </label>
            <select
              name="guard_name"
              value={formData.guard_name}
              onChange={handleChange}
            >
              <option value="api">API</option>
              <option value="web">Web</option>
            </select>
            <small className="help-text">Chọn "API" cho hệ thống backend, "Web" cho frontend</small>
          </div>

          <div className="form-actions">
            <button 
              type="button" 
              className="btn btn-secondary"
              onClick={() => navigate('/roles')}
            >
              Hủy
            </button>
            <button 
              type="submit" 
              className="btn btn-primary"
              disabled={loading}
            >
              <SaveOutlined /> {loading ? 'Đang lưu...' : 'Lưu'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default RoleFormPage;