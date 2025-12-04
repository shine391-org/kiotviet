// src/pages/RolePermissionsPage.jsx
import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import roleApi from '../api/roleApi';
import { 
  Box, 
  Typography, 
  Card, 
  CardContent, 
  Checkbox, 
  FormControlLabel,
  Button,
  Alert,
  CircularProgress,
  TextField,
  Accordion,
  AccordionSummary,
  AccordionDetails,
  Chip,
  Grid,
  Paper
} from '@mui/material';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import SearchIcon from '@mui/icons-material/Search';
import SaveIcon from '@mui/icons-material/Save';
import ArrowBackIcon from '@mui/icons-material/ArrowBack';

const RolePermissionsPage = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  
  // States
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [role, setRole] = useState(null);
  const [allPermissions, setAllPermissions] = useState({});
  const [selectedPermissions, setSelectedPermissions] = useState([]);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [expandedModules, setExpandedModules] = useState({});

  // Module names mapping (Vietnamese)
  const moduleNames = {
    'branches': 'Chi nhánh',
    'users': 'Người dùng',
    'roles': 'Vai trò',
    'products': 'Hàng hóa',
    'customers': 'Khách hàng',
    'orders': 'Đơn hàng',
    'reports': 'Báo cáo',
    'cash': 'Sổ quỹ',
    'settings': 'Cài đặt',
    'inventory': 'Kho hàng',
    'partners': 'Đối tác',
    'invoices': 'Hóa đơn',
    'returns': 'Trả hàng',
    'purchase_orders': 'Đơn mua hàng',
    'customer_groups': 'Nhóm khách hàng',
    'product_categories': 'Danh mục SP',
    'shipments': 'Vận chuyển'
  };

  // Load data
  useEffect(() => {
    loadData();
  }, [id]);

  const loadData = async () => {
    setLoading(true);
    setError(null);
    try {
      // 1. Load role info
      const roleResponse = await roleApi.getRoleById(id);
      setRole(roleResponse);

      // 2. Load all permissions (grouped by module)
      const permissionsResponse = await roleApi.getAllPermissions();
      console.log('All permissions:', permissionsResponse);
      setAllPermissions(permissionsResponse || {});
      
      // Auto-expand all modules initially
      const expanded = {};
      Object.keys(permissionsResponse || {}).forEach(module => {
        expanded[module] = true;
      });
      setExpandedModules(expanded);

      // 3. Load role's current permissions
      const rolePermsResponse = await roleApi.getRolePermissions(id);
      console.log('Role permissions:', rolePermsResponse);
      const currentPermIds = Array.isArray(rolePermsResponse)
        ? rolePermsResponse.map(p => p.id)
        : [];
      setSelectedPermissions(currentPermIds);

      setLoading(false);
    } catch (err) {
      console.error('Error loading data:', err);
      setError(err.response?.data?.message || 'Không thể tải dữ liệu');
      setLoading(false);
    }
  };

  // Toggle single permission
  const togglePermission = (permissionId) => {
    setSelectedPermissions(prev => {
      if (prev.includes(permissionId)) {
        return prev.filter(id => id !== permissionId);
      } else {
        return [...prev, permissionId];
      }
    });
  };

  // Toggle all permissions of a module
  const toggleModule = (modulePermissions) => {
    const moduleIds = modulePermissions.map(p => p.id);
    const allSelected = moduleIds.every(id => selectedPermissions.includes(id));
    
    if (allSelected) {
      setSelectedPermissions(prev => prev.filter(id => !moduleIds.includes(id)));
    } else {
      setSelectedPermissions(prev => {
        const newSelected = [...prev];
        moduleIds.forEach(id => {
          if (!newSelected.includes(id)) {
            newSelected.push(id);
          }
        });
        return newSelected;
      });
    }
  };

  // Save permissions
  const handleSave = async () => {
    setSaving(true);
    setError(null);
    setSuccess(null);
    
    try {
      await roleApi.assignRolePermissions(id, selectedPermissions);
      setSuccess('✅ Cập nhật quyền thành công!');
      
      setTimeout(() => setSuccess(null), 3000);
    } catch (err) {
      console.error('Error saving permissions:', err);
      setError(err.response?.data?.message || 'Không thể lưu quyền');
    } finally {
      setSaving(false);
    }
  };

  // Check if module is fully selected
  const isModuleFullySelected = (modulePermissions) => {
    const moduleIds = modulePermissions.map(p => p.id);
    return moduleIds.length > 0 && moduleIds.every(id => selectedPermissions.includes(id));
  };

  // Check if module is partially selected
  const isModulePartiallySelected = (modulePermissions) => {
    const moduleIds = modulePermissions.map(p => p.id);
    const selectedInModule = moduleIds.filter(id => selectedPermissions.includes(id));
    return selectedInModule.length > 0 && selectedInModule.length < moduleIds.length;
  };

  // Toggle accordion expansion
  const handleAccordionChange = (module) => {
    setExpandedModules(prev => ({
      ...prev,
      [module]: !prev[module]
    }));
  };

  // Filter permissions by search term
  const filteredModules = Object.keys(allPermissions).filter(module => {
    if (!searchTerm) return true;
    
    const moduleName = moduleNames[module] || module;
    const moduleMatch = moduleName.toLowerCase().includes(searchTerm.toLowerCase());
    
    const permissionMatch = allPermissions[module].some(p => 
      p.display_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      p.name.toLowerCase().includes(searchTerm.toLowerCase())
    );
    
    return moduleMatch || permissionMatch;
  });

  // Loading state
  if (loading) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" minHeight="60vh">
        <CircularProgress />
      </Box>
    );
  }

  return (
    <Box sx={{ p: 3 }}>
      {/* Header */}
      <Paper elevation={2} sx={{ p: 3, mb: 3 }}>
        <Box display="flex" alignItems="center" justifyContent="space-between" mb={2}>
          <Box display="flex" alignItems="center" gap={2}>
            <Button 
              startIcon={<ArrowBackIcon />} 
              onClick={() => navigate('/roles')}
              variant="outlined"
            >
              Quay lại
            </Button>
            <Box>
              <Typography variant="h5" fontWeight="bold">
                Phân quyền cho Role: {role?.name}
              </Typography>
              {role?.description && (
                <Typography variant="body2" color="text.secondary">
                  {role.description}
                </Typography>
              )}
            </Box>
          </Box>
          <Chip 
            label={`${selectedPermissions.length} quyền đã chọn`} 
            color="primary" 
            variant="outlined"
          />
        </Box>

        {/* Search bar */}
        <TextField
          fullWidth
          placeholder="Tìm kiếm module hoặc permission..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          InputProps={{
            startAdornment: <SearchIcon sx={{ mr: 1, color: 'text.secondary' }} />
          }}
        />
      </Paper>

      {/* Error/Success messages */}
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      {success && <Alert severity="success" sx={{ mb: 2 }}>{success}</Alert>}

      {/* Permissions List */}
      <Grid container spacing={2}>
        {filteredModules.map((module) => {
          const permissions = allPermissions[module];
          const isFullySelected = isModuleFullySelected(permissions);
          const isPartiallySelected = isModulePartiallySelected(permissions);
          const selectedCount = permissions.filter(p => selectedPermissions.includes(p.id)).length;

          return (
            <Grid item xs={12} md={6} key={module}>
              <Accordion 
                expanded={expandedModules[module] || false}
                onChange={() => handleAccordionChange(module)}
                elevation={1}
              >
                <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                  <Box display="flex" alignItems="center" justifyContent="space-between" width="100%">
                    <Box display="flex" alignItems="center" gap={2}>
                      <Checkbox
                        checked={isFullySelected}
                        indeterminate={isPartiallySelected}
                        onChange={() => toggleModule(permissions)}
                        onClick={(e) => e.stopPropagation()}
                      />
                      <Typography fontWeight="bold">
                        {moduleNames[module] || module}
                      </Typography>
                    </Box>
                    <Chip 
                      label={`${selectedCount}/${permissions.length}`} 
                      size="small" 
                      color={isFullySelected ? 'success' : isPartiallySelected ? 'warning' : 'default'}
                    />
                  </Box>
                </AccordionSummary>
                <AccordionDetails>
                  <Grid container spacing={1}>
                    {permissions.map((permission) => (
                      <Grid item xs={12} sm={6} key={permission.id}>
                        <FormControlLabel
                          control={
                            <Checkbox
                              checked={selectedPermissions.includes(permission.id)}
                              onChange={() => togglePermission(permission.id)}
                              size="small"
                            />
                          }
                          label={
                            <Box>
                              <Typography variant="body2" fontWeight={500}>
                                {permission.display_name || permission.name}
                              </Typography>
                              <Typography variant="caption" color="text.secondary">
                                {permission.name}
                              </Typography>
                            </Box>
                          }
                        />
                      </Grid>
                    ))}
                  </Grid>
                </AccordionDetails>
              </Accordion>
            </Grid>
          );
        })}
      </Grid>

      {/* Save button */}
      <Box display="flex" justifyContent="center" mt={4}>
        <Button
          variant="contained"
          size="large"
          startIcon={saving ? <CircularProgress size={20} color="inherit" /> : <SaveIcon />}
          onClick={handleSave}
          disabled={saving}
          sx={{ minWidth: 200 }}
        >
          {saving ? 'Đang lưu...' : 'Lưu thay đổi'}
        </Button>
      </Box>
    </Box>
  );
};

export default RolePermissionsPage;
