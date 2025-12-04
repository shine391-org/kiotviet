// src/pages/UserListPage.jsx - FIXED FILTER FROM URL
import React, { useEffect, useState, useCallback } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { fetchUsers, deleteUser } from '../store/slices/userSlice';
import { fetchRoles } from '../store/slices/roleSlice';
import { 
  PlusOutlined, 
  EditOutlined, 
  DeleteOutlined, 
  SearchOutlined,
  CloseCircleOutlined 
} from '@ant-design/icons';
import { usePermission } from '../utils/usePermission';
import '../styles/userList.css';

const UserListPage = () => {
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  
  const { users, pagination, loading } = useSelector(state => state.user);
  const roles = useSelector(state => state.role.roles || []);
  const { hasPermission } = usePermission();
  
  const [searchTerm, setSearchTerm] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  
  // ========== FIX: LẤY selectedRole TỪ URL ==========
  const [selectedRole, setSelectedRole] = useState(searchParams.get('role_id') || '');
  const filterRoleName = searchParams.get('role_name') || '';

  // Check permissions
  const canCreate = hasPermission('users.create');
  const canEdit = hasPermission('users.edit');
  const canDelete = hasPermission('users.delete');

  // ========== FIX: CẬP NHẬT selectedRole KHI URL THAY ĐỔI ==========
  useEffect(() => {
    const roleIdFromUrl = searchParams.get('role_id');
    if (roleIdFromUrl) {
      setSelectedRole(roleIdFromUrl);
    } else {
      setSelectedRole('');
    }
  }, [searchParams]);

  // Load roles khi component mount
  useEffect(() => {
    dispatch(fetchRoles({ limit: 100 }));
  }, [dispatch]);

  const loadUsers = useCallback(() => {
    const params = {
      page: currentPage,
      limit: 10,
      search: searchTerm,
    };
    
    // ========== FIX: GỬI role_id NẾU CÓ ==========
    if (selectedRole) {
      params.role_id = selectedRole;
    }
    
    console.log('=== FETCHING USERS WITH PARAMS ===', params); // DEBUG
    dispatch(fetchUsers(params));
  }, [dispatch, currentPage, searchTerm, selectedRole]);

  useEffect(() => {
    loadUsers();
  }, [loadUsers]);

  const handleSearch = (e) => {
    setSearchTerm(e.target.value);
    setCurrentPage(1);
  };

  const handleRoleFilter = (e) => {
    const roleId = e.target.value;
    setSelectedRole(roleId);
    setCurrentPage(1);
    
    if (roleId) {
      const role = roles.find(r => r.id === parseInt(roleId));
      navigate(`/users?role_id=${roleId}&role_name=${encodeURIComponent(role?.name || '')}`, { replace: true });
    } else {
      navigate('/users', { replace: true });
    }
  };

  const handleClearFilter = () => {
    setSelectedRole('');
    setSearchTerm('');
    setCurrentPage(1);
    navigate('/users', { replace: true });
  };

  const handleDelete = async (id, username) => {
    if (window.confirm(`Bạn có chắc muốn xóa người dùng "${username}"?`)) {
      try {
        await dispatch(deleteUser(id)).unwrap();
        alert('Xóa người dùng thành công!');
        loadUsers();
      } catch (error) {
        alert('Lỗi: ' + error);
      }
    }
  };

  const getStatusBadge = (status) => {
    return status === 'active' 
      ? <span className="status-badge active">Hoạt động</span>
      : <span className="status-badge inactive">Khóa</span>;
  };

  const getRoleName = (user) => {
    // ✅ FIX: Backend trả role_description và role_name là flat fields
    if (user.role_description) return user.role_description;
    if (user.role_name) return user.role_name;
    
    // Fallback cho cấu trúc nested (nếu có)
    if (user.role && user.role.description) return user.role.description;
    if (user.role && user.role.name) return user.role.name;
    
    return 'N/A';
  };
  

  return (
    <div className="user-list-container">
      {/* Header */}
      <div className="user-header">
        <div className="user-header-left">
          <h1>👥 Người dùng</h1>
          {filterRoleName && (
            <div className="filter-info">
              <span className="filter-label">Vai trò:</span>
              <span className="filter-value">{filterRoleName}</span>
              <button 
                className="btn-clear-filter" 
                onClick={handleClearFilter}
                title="Xóa bộ lọc"
              >
                <CloseCircleOutlined />
              </button>
            </div>
          )}
        </div>
        
        {canCreate && (
          <button className="btn-primary" onClick={() => navigate('/users/create')}>
            <PlusOutlined /> Thêm người dùng
          </button>
        )}
      </div>

      {/* Filters */}
      <div className="user-filters">
        <div className="search-box">
          <SearchOutlined className="search-icon" />
          <input
            type="text"
            placeholder="Tìm kiếm người dùng..."
            value={searchTerm}
            onChange={handleSearch}
            className="search-input"
          />
        </div>

        <div className="filter-group">
          <label>Vai trò</label>
          <select 
            value={selectedRole} 
            onChange={handleRoleFilter}
            className="filter-select"
          >
            <option value="">Tất cả vai trò</option>
            {roles.map(role => (
              <option key={role.id} value={role.id}>
                {role.name}
              </option>
            ))}
          </select>
        </div>
      </div>

      {/* Table */}
      <div className="user-table-container">
        <div className="user-table-wrapper">
          <table className="user-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Họ tên</th>
                <th>Vai trò</th>
                <th>Trạng thái</th>
                {(canEdit || canDelete) && <th>Thao tác</th>}
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr>
                  <td colSpan="7" className="text-center">Đang tải...</td>
                </tr>
              ) : users && users.length > 0 ? (
                users.map((user) => (
                  <tr key={user.id}>
                    <td>{user.id}</td>
                    <td><strong>{user.username}</strong></td>
                    <td>{user.email}</td>
                    <td>{user.fullname || '-'}</td>
                    <td>{getRoleName(user)}</td>
                    <td>{getStatusBadge(user.status)}</td>
                    {(canEdit || canDelete) && (
                      <td>
                        <div className="user-actions">
                          {canEdit && (
                            <button 
                              className="btn-icon" 
                              onClick={() => navigate(`/users/edit/${user.id}`)}
                              title="Sửa"
                            >
                              <EditOutlined />
                            </button>
                          )}
                          {canDelete && (
                            <button 
                              className="btn-icon delete" 
                              onClick={() => handleDelete(user.id, user.username)}
                              title="Xóa"
                            >
                              <DeleteOutlined />
                            </button>
                          )}
                        </div>
                      </td>
                    )}
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan="7" className="text-center">
                    {selectedRole ? 'Không có người dùng nào với vai trò này' : 'Không có dữ liệu'}
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Pagination */}
      {pagination && pagination.totalPages > 1 && (
        <div className="pagination">
          <button
            onClick={() => setCurrentPage(currentPage - 1)}
            disabled={currentPage === 1}
            className="btn-pagination"
          >
            Trước
          </button>
          <span className="page-info">
            Trang {currentPage} / {pagination.totalPages}
          </span>
          <button
            onClick={() => setCurrentPage(currentPage + 1)}
            disabled={currentPage === pagination.totalPages}
            className="btn-pagination"
          >
            Sau
          </button>
        </div>
      )}
    </div>
  );
};

export default UserListPage;