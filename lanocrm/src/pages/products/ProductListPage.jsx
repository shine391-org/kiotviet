  // src/pages/products/ProductListPage.jsx

  import React, { useEffect, useState, useCallback, useRef } from 'react';
  import { useDispatch, useSelector } from 'react-redux';
  import { useNavigate } from 'react-router-dom';
  import { 
    Button, 
    Input, 
    Space, 
    Row, 
    Col,
    Select,
    Tabs,
    Card,
    Table,
    Modal,
    App,
    Image,
    TreeSelect,
  } from 'antd';
  import { ExclamationCircleOutlined } from '@ant-design/icons';
  import { 
    PlusOutlined, 
    DownloadOutlined, 
    UploadOutlined,
    ReloadOutlined,
    SearchOutlined,
    EditOutlined,
    DeleteOutlined 
  } from '@ant-design/icons';
  import { fetchProducts, deleteProduct, updateSingleProduct, setHighlightedProduct, clearHighlightedProduct } from '../../store/slices/productSlice';
  import { fetchCategories, fetchCategoryTree } from '../../store/slices/categorySlice';
  import ProductTable from '../../components/products/ProductTable';
  import { usePermission } from '../../utils/usePermission';
  import ConfirmDeleteModal from '../../components/products/ConfirmDeleteModal';
  import VariantCloneModal from '../../components/products/VariantCloneModal';
  import AttributeFilter from '../../components/products/AttributeFilter';
  import * as productApi from '../../api/productApi';
  import styles from './ProductListPage.module.css';

  const { Option } = Select;

  const ProductListPage = () => {
    // ✅ ADDED: Get modal hook from App
    const { modal, message } = App.useApp();
    const dispatch = useDispatch();
    const navigate = useNavigate();
    const { hasPermission } = usePermission();
    const fileInputRef = useRef(null);
    
    // Redux state
    const { items, loading, pagination } = useSelector(state => state.product);
    const { items: categories } = useSelector(state => state.category);
    const [expandedRowKeys, setExpandedRowKeys] = useState([]);
    const [attributeFilters, setAttributeFilters] = useState([]);

    const [cloneModalVisible, setCloneModalVisible] = useState(false);
    const [cloneSourceVariant, setCloneSourceVariant] = useState(null);

    const [deletedVariants, setDeletedVariants] = useState([]);
    const [showDeletedModal, setShowDeletedModal] = useState(false);
    const [deletedVariantsCount, setDeletedVariantsCount] = useState({});

    const categoryTree = useSelector((state) => state.category.categoryTree);

    // Local state
    const [searchText, setSearchText] = useState('');
    const [filters, setFilters] = useState({
      page: 1,
      limit: 20,
      search: '',
      category_id: null,
      product_type: null,
      status: null,
      stock_status: null
    });
    const [activeTab, setActiveTab] = useState('all');
    // ✅ NEW: State quản lý biến thể đang được chọn để hiển thị chi tiết
    const [selectedVariantId, setSelectedVariantId] = useState(null);
    const [expandedProductId, setExpandedProductId] = useState(null);
    const [importing, setImporting] = useState(false);
    const [exporting, setExporting] = useState(false);
    
    // Delete modal state
    const [deleteModalVisible, setDeleteModalVisible] = useState(false);
    const [selectedProduct, setSelectedProduct] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);

    //reset filter attributes / optionshandleResetFilters
    const [resetAttributeFilter, setResetAttributeFilter] = useState(0);

    // Fetch categories on mount
    useEffect(() => {
      dispatch(fetchCategories());
    }, [dispatch]);
    
    // Fetch products when filters change
    useEffect(() => {
      dispatch(fetchProducts(filters));
    }, [dispatch, filters]);

    // Category tree
    useEffect(() => {
      dispatch(fetchCategoryTree());
    }, [dispatch]);  

    useEffect(() => {
      if (expandedProductId) {
        const product = items.find(p => p.id === expandedProductId);
        if (product && product.variants && product.variants.length > 0) {
          const exists = product.variants.some(v => v.id === selectedVariantId);
          if (!exists || !selectedVariantId) {
            setSelectedVariantId(product.variants[0].id);
          }
        } else {
          setSelectedVariantId(null);
        }
      }
    }, [items, expandedProductId, selectedVariantId]);
                

    // Debounce utility
    const debounce = (func, wait) => {
      let timeout;
      return function executedFunction(...args) {
        const later = () => {
          clearTimeout(timeout);
          func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
      };
    };

    const formatCurrency = (amount) => {
      if (!amount) return '0₫';
      return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
      }).format(amount);
    };

    // Handle sort change
    const handleSortChange = (sortInfo) => {
      setFilters(prev => ({
        ...prev,
        sort_by: sortInfo.sortBy,
        order: sortInfo.sortOrder.toLowerCase(),
        page: 1
      }));
    };
    
    // Thêm hàm xử lý khi người dùng lưu filter thuộc tính:
    const handleAttributeFiltersChange = (filters) => {
      setAttributeFilters(filters);
    
      setFilters(prev => ({
        ...prev,
        attributes: filters,
        page: 1,
      }));
    };
    
    const getAllCategoryIds = (tree, selectedId) => {
      let ids = [];
      
      const findAndCollect = (nodes) => {
        if (!nodes || !Array.isArray(nodes)) return false;
        
        for (const node of nodes) {
          if (node.id === selectedId) {
            collectIds(node);
            return true;
          }
          if (node.children && node.children.length > 0) {
            if (findAndCollect(node.children)) {
              return true;
            }
          }
        }
        return false;
      };
      
      const collectIds = (node) => {
        ids.push(node.id);
        if (node.children && node.children.length > 0) {
          node.children.forEach(collectIds);
        }
      };
      
      findAndCollect(tree);
      return ids;
    };        
    
    // Handle pagination change
    const handlePaginationChange = (paginationInfo) => {
      setFilters(prev => ({
        ...prev,
        page: paginationInfo.page,
        limit: paginationInfo.limit
      }));
    };

    // Debounced search
    const handleSearchChange = useCallback(
      debounce((value) => {
        setFilters(prev => ({
          ...prev,
          search: value,
          page: 1
        }));
      }, 500),
      []
    );

    const handleSearchInput = (e) => {
      const value = e.target.value;
      setSearchText(value);
      handleSearchChange(value);
    };

    // Chuyển cây từ backend sang treeData cho TreeSelect
    const mapTreeForTreeSelect = (tree) => {
      if (!tree) return [];
      return tree.map(cat => ({
        title: cat.name,
        value: cat.id,
        key: cat.id,
        children: mapTreeForTreeSelect(cat.children)
      }));
    };

    const treeData = mapTreeForTreeSelect(categoryTree);

    // Category filter
    const handleCategoryChange = (selectedValues) => {
      // selectedValues từ TreeSelect là mảng các id đã chọn
      console.log('Selected category IDs:', selectedValues);
      
      if (!selectedValues || selectedValues.length === 0) {
        setFilters(prev => ({
          ...prev,
          category_id: [],
          page: 1,
        }));
      } else {
        // Lấy tất cả ID cha + con của các category đã chọn
        let allCategoryIds = new Set();
        
        selectedValues.forEach(selectedId => {
          const ids = getAllCategoryIds(categoryTree, selectedId);
          ids.forEach(id => allCategoryIds.add(id));
        });
        
        const finalIds = Array.from(allCategoryIds);
        console.log('Filter with category IDs (including children):', finalIds);
        
        setFilters(prev => ({
          ...prev,
          category_id: finalIds,
          page: 1,
        }));
      }
    };            

    // Stock status filter
    const handleStockStatusChange = (value) => {
      setFilters(prev => ({
        ...prev,
        stock_status: value || null,
        page: 1
      }));
    };


    // Product type tabs
    const handleTabChange = (key) => {
      setActiveTab(key);
      const typeMap = {
        'all': null,
        'goods': 'goods',
        'combo': 'combo',
        'service': 'service'
      };
      setFilters(prev => ({
        ...prev,
        product_type: typeMap[key],
        page: 1
      }));
    };

    // Status filter
    const handleStatusChange = (value) => {
      setFilters(prev => ({
        ...prev,
        status: value,
        page: 1
      }));
    };

    // ✅ FIXED: Edit variant handler
    const handleEditVariant = (variant) => {
      if (!hasPermission('products.edit')) {
        message.error('Bạn không có quyền chỉnh sửa biến thể');
        return;
      }
      
      navigate(`/products/variants/edit/${variant.id}`);
    };

    // ✅ ENHANCED: Delete variant với auto-reload và highlight
    const handleDeleteVariant = async (variant, productId) => {
      if (!hasPermission('products.delete')) {
        message.error('Bạn không có quyền xóa biến thể');
        return;
      }

      modal.confirm({
        title: 'Xác nhận xóa biến thể',
        icon: <ExclamationCircleOutlined style={{ color: '#ff4d4f' }} />,
        content: (
          <div>
            <p>Bạn có chắc chắn muốn xóa biến thể này?</p>
            <p style={{ marginTop: 8 }}>
              <strong>SKU:</strong> {variant.sku || variant.code}
            </p>
            <p>
              <strong>Tên:</strong> {variant.variant_name || variant.name}
            </p>
            <p style={{ marginTop: 12, color: '#ff4d4f', fontSize: '13px' }}>
              ⚠️ Hành động này không thể hoàn tác!
            </p>
          </div>
        ),
        okText: 'Xóa',
        okType: 'danger',
        cancelText: 'Hủy',
        onOk: async () => {
          // ✅ Chỉ cho xoá nếu stock = 0
          if ((variant.stock_quantity || variant.stock || 0) > 0) {
            message.error('❌ Không thể xóa! Biến thể còn tồn kho.');
            return Promise.reject();
          }
          
          try {
            const response = await productApi.deleteVariant(variant.id, true);
            
            if (response.success) {
              message.success('Biến thể đã được xóa thành công');
              
              // ✅ FIX 1: Reload danh sách variants của sản phẩm này
              await reloadProductVariants(productId);
              
              // ✅ FIX 2: Nếu là variant đang hiển thị chi tiết → clear selection
              if (selectedVariantId === variant.id) {
                setSelectedVariantId(null);
              }
              
            } else {
              message.error(response.message || 'Không thể xóa biến thể');
            }
          } catch (error) {
            message.error(error.message || 'Có lỗi xảy ra khi xóa biến thể');
          }
        },
      });
    };

    //Hàm load danh sách biến thể đã xóa
    const loadDeletedVariants = async (productId) => {
      try {
        const response = await productApi.getDeletedVariants(productId);
        if (response.success) {
          setDeletedVariants(response.data || []);
          setShowDeletedModal(true);
          
          // Update count (nếu chưa có)
          setDeletedVariantsCount(prev => ({
            ...prev,
            [productId]: (response.data || []).length
          }));
        } else {
          message.error(response.message || 'Không thể tải danh sách biến thể đã xóa');
          setDeletedVariants([]);
        }
      } catch (error) {
        message.error('Không thể tải danh sách biến thể đã xóa');
        console.error(error);
      }
    };

    // Hàm restore biến thể
    const handleRestoreVariant = async (variantId) => {
      try {
        const response = await productApi.restoreVariant(variantId);
        if (response.success) {
          message.success('Khôi phục biến thể thành công');
          
          // ✅ Remove from deleted list (UI update)
          setDeletedVariants(prev => prev.filter(v => v.id !== variantId));
          
          // ✅ Update count
          if (expandedProductId) {
            setDeletedVariantsCount(prev => ({
              ...prev,
              [expandedProductId]: Math.max(0, (prev[expandedProductId] || 0) - 1)
            }));
          }
          
          // ✅ Reload product variants để hiện variant mới restore
          if (expandedProductId) {
            await reloadProductVariants(expandedProductId);
          }
          
          // ✅ Nếu hết deleted variants, đóng modal
          if (deletedVariants.length <= 1) {
            setShowDeletedModal(false);
          }
        } else {
          message.error(response.message || 'Khôi phục biến thể thất bại');
        }
      } catch (error) {
        message.error('Khôi phục biến thể thất bại');
        console.error(error);
      }
    };    

    // ✅ NEW: Reload variants của 1 sản phẩm cụ thể
    const reloadProductVariants = async (productId) => {
      try {
        // Gọi API lấy lại thông tin product + variants
        const response = await productApi.getProductWithVariants(productId);
    
        if (response.success) {
          const updatedProduct = response.data;
    
          // ✅ Cập nhật Redux store: thay thế product cũ bằng product mới
          dispatch(updateSingleProduct(updatedProduct));
    
          // ✅ Nếu không còn variant nào → tự động chuyển về simple product
          if (!updatedProduct.variants || updatedProduct.variants.length === 0) {
            message.info('Sản phẩm đã chuyển về dạng đơn giản (không có biến thể)');
            
            // Reset UI state
            setExpandedProductId(null);
            setSelectedVariantId(null); // vẫn cần nếu muốn collapse luôn
            // Optional: Highlight product row
            dispatch(setHighlightedProduct(productId));
            setTimeout(() => {
              dispatch(clearHighlightedProduct());
              // Scroll to product row
              const productRow = document.getElementById(`product-row-${productId}`);
              if (productRow) {
                productRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
              }
            }, 100);
          }
          // **KHÔNG set lại selectedVariantId ở đây nữa**
          // Nếu còn variants, useEffect sẽ tự động chọn lại variant đầu tiên
          // Scroll/hightlight UI vẫn giữ như cũ nếu cần
        }
      } catch (error) {
        console.error('Lỗi khi reload variants:', error);
        message.error('Không thể tải lại danh sách biến thể');
      }
    };    

    // ✅ NEW: Xử lý khi click vào variant card để hiển thị chi tiết
    const handleVariantSelect = (variantId) => {
      setSelectedVariantId(variantId);
      
      // Optional: Scroll to detail panel
      setTimeout(() => {
        const detailPanel = document.getElementById('variant-detail-panel');
        if (detailPanel) {
          detailPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
      }, 100);
    };

    // ✅ NEW: Xử lý khi expand/collapse product row
    // ✅ Handler khi expand/collapse product
    const handleProductExpand = async (expanded, product) => {
      if (expanded) {
        setExpandedProductId(product.id);
        setExpandedRowKeys([product.id]);
        
        // ✅ AUTO LOAD deleted variants count ngay khi expand
        try {
          const response = await productApi.getDeletedVariants(product.id);
          if (response.success) {
            const deletedCount = (response.data || []).length;
            
            setDeletedVariantsCount(prev => ({
              ...prev,
              [product.id]: deletedCount
            }));
            
            // Optional: Show notification nếu có deleted variants
            if (deletedCount > 0) {
              message.info(`Có ${deletedCount} biến thể đã xóa có thể khôi phục`, 3);
            }
          }
        } catch (error) {
          console.error('Cannot load deleted variants count:', error);
          setDeletedVariantsCount(prev => ({
            ...prev,
            [product.id]: 0
          }));
        }
      } else {
        // Collapse logic
        if (expandedProductId === product.id) {
          setExpandedProductId(null);
          setExpandedRowKeys([]);
          setSelectedVariantId(null);
        }
      }
    };    

    // Refresh data
    const handleRefresh = () => {
      dispatch(fetchProducts(filters));
      message.success('Đã làm mới dữ liệu');
    };

    // Reset all filters
    const handleResetFilters = () => {
      setSearchText('');
      setActiveTab('all');
      setFilters({
        page: 1,
        limit: 20,
        search: '',
        category_id: [],
        product_type: null,
        status: null,
        stock_status: null,
        attributes: [],
      });
      setResetAttributeFilter(v => v + 1); // reset attribute filter UI
      message.info('Đã xóa bộ lọc');
    };

    // Handle import Excel
    const handleImport = () => {
      if (!hasPermission('products.import')) {
        message.error('Bạn không có quyền nhập Excel');
        return;
      }
      if (fileInputRef.current) {
        fileInputRef.current.value = null;
        fileInputRef.current.click();
      }
    };

    const handleImportFileChange = async (event) => {
      const file = event.target.files?.[0];
      if (!file) return;

      const ext = file.name.split('.').pop().toLowerCase();
      if (!['xlsx', 'xls'].includes(ext)) {
        message.error('Vui lòng chọn file Excel (.xlsx hoặc .xls)');
        return;
      }
      if (file.size > 5 * 1024 * 1024) {
        message.error('File vượt quá 5MB');
        return;
      }

      try {
        setImporting(true);
        const res = await productApi.importProducts(file);
        if (res?.success) {
          message.success(`Nhập thành công: ${res.imported} mới, ${res.updated} cập nhật`);
          if (res.failed > 0) {
            message.warning(`Có ${res.failed} dòng lỗi`);
            console.warn('Import errors', res.errors);
          }
          dispatch(fetchProducts(filters));
        } else {
          message.error(res?.message || 'Nhập thất bại');
        }
      } catch (err) {
        message.error(err?.response?.data?.message || err.message || 'Nhập thất bại');
      } finally {
        setImporting(false);
      }
    };

    // Handle export Excel
    const handleExport = async () => {
      if (!hasPermission('products.export')) {
        message.error('Bạn không có quyền xuất Excel');
        return;
      }
      try {
        setExporting(true);
        const blob = await productApi.exportProducts({
          ...filters,
        });
        productApi.downloadFile(blob, `products_${new Date().toISOString().slice(0,19).replace(/[:T]/g,'-')}.xlsx`);
        message.success('Đã xuất Excel');
      } catch (err) {
        message.error(err?.response?.data?.message || err.message || 'Xuất thất bại');
      } finally {
        setExporting(false);
      }
    };

    // Handle add product
    const handleAddProduct = () => {
      if (!hasPermission('products.create')) {
        message.error('Bạn không có quyền tạo sản phẩm');
        return;
      }
      navigate('/products/create');
    };

    const confirmHardDeleteVariant = (variantId, variant) => {
      modal.confirm({
        title: "Xác nhận xóa vĩnh viễn biến thể",
        icon: <ExclamationCircleOutlined style={{ color: '#ff4d4f' }} />,
        content: (
          <div>
            <p>Bạn chắc chắn muốn xóa vĩnh viễn biến thể <b>{variant.variant_name}</b> (SKU: <b>{variant.sku}</b>)?</p>
            <p style={{color:'#ff4d4f', marginTop: 12, fontSize: 13}}>
              ⚠️ Hành động này sẽ xóa vĩnh viễn dữ liệu và KHÔNG THỂ KHÔI PHỤC!
            </p>
          </div>
        ),
        okText: "Xóa vĩnh viễn",
        okType: "danger",
        cancelText: "Hủy",
        onOk: async () => {
          try {
            const response = await productApi.hardDeleteVariant(variantId);
        
            if (response && response.success) {
              message.success(response.message || 'Đã xóa vĩnh viễn biến thể!');
        
              // Remove khỏi deleted list (ĐẢM BẢO variantId là dạng number/string trùng object.id)
              setDeletedVariants(prev => prev.filter(v => `${v.id}` !== `${variantId}`));
        
              // Update count đúng cách
              if (expandedProductId) {
                setDeletedVariantsCount(prev => ({
                  ...prev,
                  [expandedProductId]: Math.max(0, (prev[expandedProductId] || 1) - 1),
                }));
              }
        
              // Đóng modal nếu đã hết deleted
              if ((deletedVariants.length <= 1) || (deletedVariants.filter(v => `${v.id}` !== `${variantId}`).length === 0)) {
                setShowDeletedModal(false);
              }
        
            } else {
              message.error(response?.message || 'Xóa thất bại');
            }
          } catch (e) {
            // Ưu tiên hiển thị đúng lỗi server trả về (nếu có)
            message.error((e && e.message) ? e.message : 'Lỗi server: Không thể xoá');
          }
        }        
      });
    };    

    {/*// Handle edit product
    const handleEditProduct = (productId) => {
      if (!hasPermission('products.edit')) {
        message.error('Bạn không có quyền chỉnh sửa sản phẩm');
        return;
      }
      navigate(`/products/edit/${productId}`);
    };
    */}

    // ✅ NEW: Delete product handler - SAME PATTERN AS VARIANT
    const handleDeleteProduct = async (product) => {
      if (!hasPermission('products.delete')) {
        message.error('Bạn không có quyền xóa sản phẩm');
        return;
      }

      modal.confirm({
        title: 'Xác nhận xóa sản phẩm',
        icon: <ExclamationCircleOutlined style={{ color: '#ff4d4f' }} />,
        content: (
          <div>
            <p>Bạn có chắc chắn muốn xóa sản phẩm này?</p>
            <p style={{ marginTop: 8 }}>
              <strong>Mã:</strong> {product.code}
            </p>
            <p>
              <strong>Tên:</strong> {product.name}
            </p>
            <p style={{ marginTop: 12, color: '#ff4d4f', fontSize: '13px' }}>
              ⚠️ Hành động này không thể hoàn tác!
            </p>
          </div>
        ),
        okText: 'Xóa',
        okType: 'danger',
        cancelText: 'Hủy',
        onOk: async () => {
          // ✅ CHECK: Double check stock before delete
          if ((product.stock_quantity || 0) > 0) {
            message.error('❌ Không thể xóa! Sản phẩm còn tồn kho. Vui lòng xuất bán hết trước.');
            return;
          }
          try {
            const response = await productApi.deleteProduct(product.id, true);
            
            if (response.success) {
              message.success('Sản phẩm đã được xóa thành công');
              dispatch(fetchProducts(filters));
            } else {
              message.error(response.message || 'Không thể xóa sản phẩm');
            }
          } catch (error) {
            message.error(error.message || 'Có lỗi xảy ra khi xóa sản phẩm');
          }
        },
      });
    };

    // Handle delete confirm
    const handleDeleteConfirm = (productId) => {
      setIsDeleting(true);
      dispatch(deleteProduct(productId))
        .unwrap()
        .then(() => {
          message.success('Sản phẩm đã được xóa thành công');
          setDeleteModalVisible(false);
          setSelectedProduct(null);
          // Refresh list
          dispatch(fetchProducts(filters));
        })
        .catch((error) => {
          message.error(error || 'Không thể xóa sản phẩm');
        })
        .finally(() => {
          setIsDeleting(false);
        });
    };

    const handleCloneVariant = (variant) => {
      setCloneSourceVariant(variant);
      setCloneModalVisible(true);
    };
    
    const handleCloneSaved = async (newVariantId) => {
      setCloneModalVisible(false);
      message.success('Biến thể đã được sao chép thành công');
    
      // Reload variants của product cha
      const productId = cloneSourceVariant.product_id;
      if (productId) {
        await reloadProductVariants(productId);
      }
    
      // Auto chọn variant mới
      if (newVariantId) {
        setSelectedVariantId(newVariantId);
      }
    };
    

    // Handle delete cancel
    const handleDeleteCancel = () => {
      setDeleteModalVisible(false);
      setSelectedProduct(null);
    };

    // Tabs items
    const tabItems = [
      { key: 'all', label: 'Tất cả' },
      { key: 'goods', label: 'Hàng hóa' },
      { key: 'combo', label: 'Combo' },
      { key: 'service', label: 'Dịch vụ' }
    ];

    return (
      <div className={styles['product-list-page']}>
        {/* Hidden file input for Excel import */}
        <input
          type="file"
          accept=".xlsx,.xls"
          ref={fileInputRef}
          style={{ display: 'none' }}
          onChange={handleImportFileChange}
        />
        {/* Header */}
        <div className={styles['page-header']}>
          <h1 className={styles['page-title']}>Danh sách hàng hóa</h1>
          <Space>
            {hasPermission('products.import') && (
              <Button 
                icon={<UploadOutlined />}
                loading={importing}
                onClick={handleImport}
              >
                Nhập Excel
              </Button>
            )}
            {hasPermission('products.import') && (
              <Button 
                onClick={async () => {
                  try {
                    const blob = await productApi.downloadImportTemplate();
                    productApi.downloadFile(blob, 'products_import_template.xlsx');
                    message.success('Đã tải file mẫu');
                  } catch (err) {
                    message.error(err?.message || 'Tải file mẫu thất bại');
                  }
                }}
              >
                File mẫu
              </Button>
            )}
            {hasPermission('products.export') && (
              <Button 
                icon={<DownloadOutlined />}
                loading={exporting}
                onClick={handleExport}
              >
                Xuất Excel
              </Button>
            )}
            {hasPermission('products.create') && (
              <Button 
                type="primary" 
                icon={<PlusOutlined />}
                onClick={handleAddProduct}
              >
                Thêm hàng hóa
              </Button>
            )}
          </Space>
        </div>

        {/* Filters Card */}
        <Card className={styles['filters-card']}>
          <Row gutter={[16, 16]} align="middle">
            {/* Left: Product Type Tabs */}
            <Col xs={24} sm={24} md={14} lg={16}>
              <Tabs 
                activeKey={activeTab} 
                onChange={handleTabChange}
                items={tabItems}
                className={styles['product-tabs']}
              />
            </Col>

            {/* Right: Refresh & Reset Buttons */}
            <Col xs={24} sm={24} md={10} lg={8}>
              <Space style={{ width: '100%', justifyContent: 'flex-end' }}>
                <Button 
                  icon={<ReloadOutlined />}
                  onClick={handleRefresh}
                  loading={loading}
                >
                  Làm mới
                </Button>
                <Button onClick={handleResetFilters}>
                  Xóa bộ lọc
                </Button>
              </Space>
            </Col>
          </Row>

          {/* Search & Quick Filters */}
          <Row gutter={[16, 16]} align="middle" style={{ marginTop: 16 }}>
            <Col xs={24} sm={24} md={12} lg={10}>
              <Input
                placeholder="Tìm theo mã, tên, barcode..."
                prefix={<SearchOutlined />}
                allowClear
                value={searchText}
                onChange={handleSearchInput}
              />
            </Col>
            
            <Col xs={12} sm={8} md={6} lg={5}>
            <TreeSelect
              allowClear
              showSearch
              treeDefaultExpandAll
              style={{ width: '100%' }}
              placeholder="Nhóm hàng"
              value={filters.category_id}
              // dropdownStyle={{ maxHeight: 400, overflow: 'auto' }}   <--- DEPRECATED
              treeData={treeData}
              onChange={handleCategoryChange}
              treeCheckable={true}
              showCheckedStrategy={TreeSelect.SHOW_PARENT}
              maxTagCount={2}
              maxTagPlaceholder={(omittedValues) => `+${omittedValues.length} danh mục`}
              // Sửa tại đây 👇
              styles={{
                popup: {
                  root: {
                    maxHeight: 400,
                    overflow: 'auto'
                  }
                }
              }}
            />
            </Col>

            {/* attributes/options Filter */}

            <Col xs={24} style={{ marginTop: 16 }}>
              <AttributeFilter onChange={handleAttributeFiltersChange} 
                resetTrigger={resetAttributeFilter}
              />
            </Col>
          
            {/* Stock Status Filter */}
            <Col xs={24} sm={24} md={6} lg={5}>
              <Select
                placeholder="Tồn kho"
                value={filters.stock_status}
                onChange={handleStockStatusChange}
                allowClear
                style={{ width: '100%' }}
              >
                <Option value="">
                  <span>● Tất cả</span>
                </Option>
                <Option value="in_stock">
                  <span style={{ color: '#52c41a' }}>● Còn hàng</span>
                </Option>
                <Option value="out_of_stock">
                  <span style={{ color: '#f5222d' }}>● Hết hàng</span>
                </Option>
              </Select>
            </Col>

            {/* Status Filter */}
            <Col xs={12} sm={8} md={6} lg={4}>
              <Select
                placeholder="Trạng thái"
                allowClear
                style={{ width: '100%' }}
                onChange={handleStatusChange}
                value={filters.status}
              >
                <Option value="active">Hoạt động</Option>
                <Option value="inactive">Ngừng kinh doanh</Option>
              </Select>
            </Col>
          </Row>

          {/* Summary */}
          <div className={styles['filter-summary']}>
            <span>
              Tìm thấy: <strong>{pagination?.total || 0}</strong> sản phẩm
            </span>
          </div>
        </Card>

        {/* Table */}
        <div className={styles['page-content']}>
          <ProductTable
            products={items || []}
            loading={loading}
            pagination={{
              page: pagination?.page || 1,
              limit: pagination?.limit || 20,
              total: pagination?.total || 0
            }}
            onEditVariant={handleEditVariant}
            onDeleteVariant={handleDeleteVariant}
            onDeleteProduct={handleDeleteProduct}
            onSortChange={handleSortChange}
            onPaginationChange={handlePaginationChange}
            // ✅ NEW: Variant selection handlers
            onVariantSelect={handleVariantSelect}
            onProductExpand={handleProductExpand}
            onCloneVariant={handleCloneVariant}
            onShowDeletedVariants={loadDeletedVariants}
            deletedVariantsCount={deletedVariantsCount}
            expandedRowKeys={expandedRowKeys}
            setExpandedRowKeys={setExpandedRowKeys}
            expandedProductId={expandedProductId}
            selectedVariantId={selectedVariantId}
            // ✅ NEW: Highlighted product for animation
            highlightedProductId={pagination?.highlightedProductId}
          />
        </div>
        {/* Modal clone */}
        {cloneModalVisible && (
          <VariantCloneModal
            open={cloneModalVisible}
            sourceVariant={cloneSourceVariant}
            onCancel={() => setCloneModalVisible(false)}
            onSaved={handleCloneSaved}
          />
        )}

        {/* Delete Confirmation Modal */}
        <ConfirmDeleteModal
          visible={deleteModalVisible}
          product={selectedProduct}
          onConfirm={handleDeleteConfirm}
          onCancel={handleDeleteCancel}
        />
        <Modal
          title="Danh sách biến thể đã xóa"
          open={showDeletedModal}
          onCancel={() => setShowDeletedModal(false)}
          footer={null}
          width={900}
          destroyOnHidden
        >
          <Table
            dataSource={deletedVariants}
            rowKey="id"
            pagination={{ pageSize: 10 }}
            columns={[
              {
                title: 'Ảnh',
                dataIndex: 'image',
                width: 80,
                render: (img) => (
                  <Image 
                    src={img || '/placeholder.png'} 
                    width={50} 
                    height={50} 
                    style={{ objectFit: 'cover', borderRadius: 4 }}
                  />
                )
              },
              { 
                title: 'SKU', 
                dataIndex: 'sku',
                width: 120,
              },
              { 
                title: 'Tên biến thể', 
                dataIndex: 'variant_name',
                ellipsis: true,
              },
              { 
                title: 'Giá bán', 
                dataIndex: 'price',
                width: 120,
                render: (price) => formatCurrency(price || 0)
              },
              { 
                title: 'Tồn kho', 
                dataIndex: 'stock_quantity',
                width: 100,
                align: 'center',
                render: (stock) => stock || 0
              },
              { 
                title: 'Ngày xóa', 
                dataIndex: 'deleted_at',
                width: 150,
                render: (date) => date ? new Date(date).toLocaleString('vi-VN') : '-'
              },
              {
                title: 'Hành động',
                width: 200,
                fixed: 'right',
                render: (_, record) => (
                  <Space>
                    <Button 
                      type="primary" 
                      size="small"
                      onClick={() => handleRestoreVariant(record.id)}
                    >
                      Khôi phục
                    </Button>
                    <Button 
                      danger 
                      size="small"
                      onClick={() => confirmHardDeleteVariant(record.id, record)}
                    >
                      Xóa vĩnh viễn
                    </Button>
                  </Space>
                )
              }
            ]}
          />
        </Modal>
      </div>
    );
  };

  export default ProductListPage;
