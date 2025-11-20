import React, { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { 
  fetchBranches, 
  deleteBranch,
  setDefaultBranch 
} from '../store/slices/branchSlice';
import BranchForm from '../components/Branch/BranchForm';
import {
  PlusOutlined,
  EditOutlined,
  DeleteOutlined,
  SearchOutlined,
} from '@ant-design/icons';

const BranchPage = () => {
  const dispatch = useDispatch();
  const { branches, loading, error } = useSelector((state) => state.branch);
  
  const [showForm, setShowForm] = useState(false);
  const [editingBranch, setEditingBranch] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');

  useEffect(() => {
    dispatch(fetchBranches());
  }, [dispatch]);

  const handleAddNew = () => {
    setEditingBranch(null);
    setShowForm(true);
  };

  const handleEdit = (branch) => {
    setEditingBranch(branch);
    setShowForm(true);
  };

  const handleDelete = async (id, branch) => {
    // Kiểm tra chi nhánh mặc định
    if (branch.is_default == 1 || branch.is_default === '1') {
      alert('Không thể xóa chi nhánh mặc định. Vui lòng đặt chi nhánh khác làm mặc định trước.');
      return;
    }
    
    // Kiểm tra số lượng chi nhánh
    if (branches.length <= 1) {
      alert('Không thể xóa chi nhánh cuối cùng. Hệ thống phải có ít nhất 1 chi nhánh.');
      return;
    }
    
    if (window.confirm('Bạn có chắc muốn xóa chi nhánh này?')) {
      try {
        await dispatch(deleteBranch(id)).unwrap();
        dispatch(fetchBranches());
      } catch (error) {
        alert(error || 'Có lỗi xảy ra');
      }
    }
  };

  const handleSetDefault = async (id, branch) => {
    if (branch.is_default == 1 || branch.is_default === '1') {
      alert('Chi nhánh này đã là mặc định');
      return;
    }
    
    if (branches.length <= 1) {
      alert('Chi nhánh này đang là chi nhánh duy nhất, tự động là mặc định');
      return;
    }
    
    if (window.confirm('Đặt chi nhánh này làm mặc định?')) {
      try {
        await dispatch(setDefaultBranch(id)).unwrap();
        alert('Đã đặt chi nhánh mặc định thành công');
        dispatch(fetchBranches());
      } catch (error) {
        alert(error || 'Có lỗi xảy ra');
      }
    }
  };

  const handleCloseForm = () => {
    setShowForm(false);
    setEditingBranch(null);
  };

  const handleFormSuccess = () => {
    setShowForm(false);
    setEditingBranch(null);
    dispatch(fetchBranches());
  };

  // Filter branches
  const filteredBranches = branches.filter(branch =>
    branch.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    branch.code?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    branch.address?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div className="branch-page">
      {/* Page Header */}
      <div className="page-header">
        <h1>🏢 Quản lý Chi nhánh</h1>
        <button className="btn-primary" onClick={handleAddNew}>
          <PlusOutlined /> Thêm chi nhánh
        </button>
      </div>

      {/* Search & Filter */}
      <div className="search-section">
        <div className="search-box">
          <SearchOutlined className="search-icon" />
          <input
            type="text"
            placeholder="Tìm kiếm theo tên, mã, địa chỉ..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="search-input"
          />
        </div>
      </div>

      {/* Error Message */}
      {error && (
        <div className="error-banner">
          ❌ {error}
        </div>
      )}

      {/* Loading */}
      {loading && (
        <div className="loading-container">
          <div className="spinner"></div>
          <p>Đang tải dữ liệu...</p>
        </div>
      )}

      {/* Branch List */}
      {!loading && (
        <>
          {/* Desktop Table */}
          <div className="table-container desktop-only">
            <table className="branch-table">
              <thead>
                <tr>
                  <th>Mã CN</th>
                  <th>Tên chi nhánh</th>
                  <th>Địa chỉ</th>
                  <th>Số điện thoại</th>
                  <th>Trạng thái</th>
                  <th>Mặc định</th>
                  <th>Thao tác</th>
                </tr>
              </thead>
              <tbody>
                {filteredBranches.length > 0 ? (
                  filteredBranches.map((branch) => (
                    <tr key={branch.id}>
                      <td>{branch.code}</td>
                      <td className="branch-name">{branch.name}</td>
                      <td>{branch.address}</td>
                      <td>{branch.phone}</td>
                      <td>
                        <span className={`status-badge ${branch.status === 'active' ? 'active' : 'inactive'}`}>
                          {branch.status === 'active' ? 'Hoạt động' : 'Ngừng'}
                        </span>
                      </td>
                      <td>
                        {branch.is_default == 1 || branch.is_default === '1' ? (
                          <span className="default-badge">⭐ Mặc định</span>
                        ) : branches.length > 1 ? (
                          <button 
                            className="btn-set-default"
                            onClick={() => handleSetDefault(branch.id, branch)}
                            title="Đặt làm mặc định"
                          >
                            Đặt mặc định
                          </button>
                        ) : (
                          <span className="default-badge-auto">⭐ Mặc định</span>
                        )}
                      </td>                     
                      <td>
                        <div className="action-buttons">
                          <button 
                            className="btn-icon edit"
                            onClick={() => handleEdit(branch)}
                            title="Sửa"
                          >
                            <EditOutlined />
                          </button>
                          <button 
                            className="btn-icon delete"
                            onClick={() => handleDelete(branch.id, branch)}
                            title="Xóa"
                            disabled={branch.is_default === 1}
                            style={{ opacity: branch.is_default === 1 ? 0.5 : 1 }}
                          >
                            <DeleteOutlined />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan="6" className="empty-message">
                      {searchTerm ? 'Không tìm thấy kết quả' : 'Chưa có chi nhánh nào'}
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>

          {/* Mobile Card List */}
          <div className="mobile-list mobile-only">
            {filteredBranches.length > 0 ? (
              filteredBranches.map((branch) => (
                <div key={branch.id} className="branch-card">
                  <div className="card-header">
                    <div>
                      <h3>{branch.name}</h3>
                      <div style={{ display: 'flex', gap: '8px', alignItems: 'center', marginTop: '4px' }}>
                        <span className="branch-code">{branch.code}</span>
                        {(branch.is_default == 1 || branch.is_default === '1') && (
                          <span className="default-badge-small">⭐ Mặc định</span>
                        )}
                        {branches.length === 1 && !branch.is_default && (
                          <span className="default-badge-small">⭐ Mặc định</span>
                        )}
                      </div>
                    </div>
                    <span className={`status-badge ${branch.status === 'active' ? 'active' : 'inactive'}`}>
                      {branch.status === 'active' ? 'Hoạt động' : 'Ngừng'}
                    </span>
                  </div>
                  <div className="card-body">
                    <div className="info-row">
                      <span className="label">📍 Địa chỉ:</span>
                      <span>{branch.address}</span>
                    </div>
                    <div className="info-row">
                      <span className="label">📞 Điện thoại:</span>
                      <span>{branch.phone}</span>
                    </div>
                  </div>
                  <div className="card-actions">
                    {(branch.is_default != 1 && branch.is_default !== '1' && branches.length > 1) && (
                      <button 
                        className="btn-card default"
                        onClick={() => handleSetDefault(branch.id, branch)}
                      >
                        ⭐ Đặt mặc định
                      </button>
                    )}
                    <button 
                      className="btn-card edit"
                      onClick={() => handleEdit(branch)}
                    >
                      <EditOutlined /> Sửa
                    </button>
                    <button 
                      className="btn-card delete"
                      onClick={() => handleDelete(branch.id, branch)}
                      disabled={branch.is_default == 1 || branch.is_default === '1' || branches.length === 1}
                      style={{ 
                        opacity: (branch.is_default == 1 || branch.is_default === '1' || branches.length === 1) ? 0.5 : 1,
                        cursor: (branch.is_default == 1 || branch.is_default === '1' || branches.length === 1) ? 'not-allowed' : 'pointer'
                      }}
                    >
                      <DeleteOutlined /> Xóa
                    </button>
                  </div>
                </div>
              ))
            ) : (
              <div className="empty-state">
                <div className="empty-icon">🏢</div>
                <p>{searchTerm ? 'Không tìm thấy kết quả' : 'Chưa có chi nhánh nào'}</p>
              </div>
            )}
          </div>
        </>
      )}

      {/* Branch Form Modal */}
      {showForm && (
        <BranchForm
          branch={editingBranch}
          onClose={handleCloseForm}
          onSuccess={handleFormSuccess}
        />
      )}
    </div>
  );
};

export default BranchPage;