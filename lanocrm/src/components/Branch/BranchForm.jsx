import React, { useState, useEffect } from 'react';
import { useDispatch } from 'react-redux';
import { createBranch, updateBranch } from '../../store/slices/branchSlice';
import { CloseOutlined } from '@ant-design/icons';

const BranchForm = ({ branch, onClose, onSuccess }) => {
  const dispatch = useDispatch();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  
  const [formData, setFormData] = useState({
    code: '',
    name: '',
    address: '',
    phone: '',
    email: '',
    status: 'active',
  });

  useEffect(() => {
    if (branch) {
      setFormData({
        code: branch.code || '',
        name: branch.name || '',
        address: branch.address || '',
        phone: branch.phone || '',
        email: branch.email || '',
        status: branch.status || 'active',
      });
    }
  }, [branch]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData({
      ...formData,
      [name]: value,
    });
    // Clear error when user types
    if (error) setError('');
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    // Validation
    if (!formData.code || !formData.name) {
      setError('Vui lòng điền đầy đủ mã và tên chi nhánh');
      return;
    }
    // Kiểm tra nếu ngừng hoạt động chi nhánh mặc định
    if (branch && branch.is_default === 1 && formData.status === 'inactive') {
      setError('Không thể ngừng hoạt động chi nhánh mặc định');
      return;
    }
    setLoading(true);
    setError('');

    try {
      if (branch) {
        // Update
        await dispatch(updateBranch({ id: branch.id, branchData: formData })).unwrap();
      } else {
        // Create
        await dispatch(createBranch(formData)).unwrap();
      }
      onSuccess();
    } catch (err) {
      setError(err || 'Có lỗi xảy ra, vui lòng thử lại');
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      {/* Modal Overlay */}
      <div className="modal-overlay" onClick={onClose}></div>

      {/* Modal Content */}
      <div className="modal-content">
        {/* Modal Header */}
        <div className="modal-header">
          <h2>{branch ? '✏️ Sửa chi nhánh' : '➕ Thêm chi nhánh mới'}</h2>
          <button className="close-btn" onClick={onClose}>
            <CloseOutlined />
          </button>
        </div>

        {/* Modal Body */}
        <form onSubmit={handleSubmit} className="modal-form">
          {error && (
            <div className="form-error">
              ❌ {error}
            </div>
          )}

          <div className="form-row">
            <div className="form-group">
              <label>
                Mã chi nhánh <span className="required">*</span>
              </label>
              <input
                type="text"
                name="code"
                value={formData.code}
                onChange={handleChange}
                placeholder="VD: CN001"
                className="form-control"
                disabled={!!branch} // Không cho sửa mã khi edit
              />
            </div>

            <div className="form-group">
              <label>
                Tên chi nhánh <span className="required">*</span>
              </label>
              <input
                type="text"
                name="name"
                value={formData.name}
                onChange={handleChange}
                placeholder="VD: Chi nhánh Hà Nội"
                className="form-control"
              />
            </div>
          </div>

          <div className="form-group">
            <label>Địa chỉ</label>
            <input
              type="text"
              name="address"
              value={formData.address}
              onChange={handleChange}
              placeholder="VD: 123 Đường ABC, Quận 1, TP.HCM"
              className="form-control"
            />
          </div>

          <div className="form-row">
            <div className="form-group">
              <label>Số điện thoại</label>
              <input
                type="text"
                name="phone"
                value={formData.phone}
                onChange={handleChange}
                placeholder="VD: 0912345678"
                className="form-control"
              />
            </div>

            <div className="form-group">
              <label>Email</label>
              <input
                type="email"
                name="email"
                value={formData.email}
                onChange={handleChange}
                placeholder="VD: chinhanh@lano.vn"
                className="form-control"
              />
            </div>
          </div>

          <div className="form-group">
            <label>Trạng thái</label>
            <select
              name="status"
              value={formData.status}
              onChange={handleChange}
              className="form-control"
            >
              <option value="active">Hoạt động</option>
              <option value="inactive">Ngừng hoạt động</option>
            </select>
          </div>

          {/* Modal Footer */}
          <div className="modal-footer">
            <button
              type="button"
              className="btn-secondary"
              onClick={onClose}
              disabled={loading}
            >
              Hủy
            </button>
            <button
              type="submit"
              className="btn-primary"
              disabled={loading}
            >
              {loading ? 'Đang xử lý...' : (branch ? 'Cập nhật' : 'Thêm mới')}
            </button>
          </div>
        </form>
      </div>
    </>
  );
};

export default BranchForm;