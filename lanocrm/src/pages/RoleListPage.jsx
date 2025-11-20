// src/pages/RoleListPage.jsx
import React, { useEffect, useState, useCallback } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { fetchRoles, deleteRole } from '../store/slices/roleSlice';
import { 
  PlusOutlined, 
  EditOutlined, 
  DeleteOutlined, 
  SearchOutlined,
  KeyOutlined // 🆕 THÊM ICON GÁN QUYỀN
} from '@ant-design/icons';
import '../styles/roleList.css';

const RoleListPage = () => {
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const { roles, pagination, loading } = useSelector((state) => state.role);
  const [searchTerm, setSearchTerm] = useState('');
  const [currentPage, setCurrentPage] = useState(1);

  const loadRoles = useCallback(() => {
    dispatch(fetchRoles({ page: currentPage, limit: 10, search: searchTerm }));
  }, [dispatch, currentPage, searchTerm]);

  useEffect(() => {
    loadRoles();
  }, [loadRoles]);

  const handleSearch = (e) => {
    setSearchTerm(e.target.value);
    setCurrentPage(1);
  };

  const handleEdit = (roleId) => {
    navigate(`/roles/edit/${roleId}`);
  };

  // 🆕 HANDLE PERMISSIONS - BỔ SUNG HÀM MỚI
  const handlePermissions = (roleId) => {
    navigate(`/roles/${roleId}/permissions`);
  };

  const handleDelete = async (id, name, userCount) => {
    if (userCount > 0) {
      alert(`Không thể xóa vai trò "${name}" vì đang có ${userCount} người dùng sử dụng.`);
      return;
    }

    if (window.confirm(`Bạn có chắc muốn xóa vai trò "${name}"?`)) {
      try {
        await dispatch(deleteRole(id)).unwrap();
        alert('Xóa vai trò thành công!');
        loadRoles();
      } catch (error) {
        alert('Lỗi: ' + error);
      }
    }
  };

  const handleViewUsers = (roleId, roleName) => {
    navigate(`/users?role_id=${roleId}&role_name=${encodeURIComponent(roleName)}`);
  };

  const getGuardBadge = (guardName) => {
    return guardName === 'api' ? 'API' : 'Web';
  };

  return (
    <div className="role-list-container">
      <div className="role-list-header">
        <h2>Quản lý vai trò</h2>
      </div>

      <div className="role-list-filters">
        <div className="search-box">
          <SearchOutlined />
          <input
            type="text"
            placeholder="Tìm kiếm vai trò..."
            value={searchTerm}
            onChange={handleSearch}
          />
        </div>
        <button className="btn-primary" onClick={() => navigate('/roles/create')}>
          <PlusOutlined /> Tạo vai trò mới
        </button>
      </div>

      {loading ? (
        <div className="loading">Đang tải...</div>
      ) : (
        <table className="role-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Tên vai trò</th>
              <th>Mô tả</th>
              <th>Guard</th>
              <th>Số người dùng</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            {roles && roles.length > 0 ? (
              roles.map((role) => (
                <tr key={role.id}>
                  <td>{role.id}</td>
                  <td>{role.name}</td>
                  <td>{role.description}</td>
                  <td>
                    <span className={`badge badge-${role.guard_name}`}>
                      {getGuardBadge(role.guard_name)}
                    </span>
                  </td>
                  <td>
                    <span 
                      className="user-count"
                      onClick={() => handleViewUsers(role.id, role.name)}
                      style={{ cursor: 'pointer' }}
                      title="Xem danh sách người dùng"
                    >
                      {role.user_count || 0} người dùng
                    </span>
                  </td>
                  <td>
                    <div className="action-buttons">
                      {/* 🆕 THÊM BUTTON PHÂN QUYỀN */}
                      <button
                        className="btn-icon btn-permission"
                        onClick={() => handlePermissions(role.id)}
                        title="Gán quyền"
                      >
                        <KeyOutlined />
                      </button>

                      {role.is_system !== '1' && (
                        <>
                          <button
                            className="btn-icon btn-edit"
                            onClick={() => handleEdit(role.id)}
                            title="Chỉnh sửa"
                          >
                            <EditOutlined />
                          </button>
                          <button
                            className="btn-icon btn-delete"
                            onClick={() => handleDelete(role.id, role.name, role.user_count)}
                            title="Xóa"
                          >
                            <DeleteOutlined />
                          </button>
                        </>
                      )}
                    </div>
                  </td>
                </tr>
              ))
            ) : (
              <tr>
                <td colSpan="6" style={{ textAlign: 'center' }}>
                  Không có dữ liệu
                </td>
              </tr>
            )}
          </tbody>
        </table>
      )}

      {/* Pagination */}
      {pagination && pagination.total > 0 && (
        <div className="pagination">
          <button
            onClick={() => setCurrentPage(currentPage - 1)}
            disabled={currentPage === 1}
          >
            ← Trước
          </button>
          <span>
            Trang {currentPage} / {pagination.totalPages}
          </span>
          <button
            onClick={() => setCurrentPage(currentPage + 1)}
            disabled={currentPage === pagination.totalPages}
          >
            Sau →
          </button>
        </div>
      )}
    </div>
  );
};

export default RoleListPage;