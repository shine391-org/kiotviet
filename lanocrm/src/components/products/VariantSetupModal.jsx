import React, { useState, useEffect } from 'react';
import { Modal, Button, message, Typography, Space, Alert } from 'antd'; // ✅ FIX: Import Alert
import { useNavigate } from 'react-router-dom';
import { SettingOutlined, InfoCircleOutlined } from '@ant-design/icons';
import VariantAttributeManager from './VariantAttributeManager';
import * as attributeApi from '../../api/attributeApi';
import styles from './VariantSetupModal.module.css';

const { Text } = Typography;

const VariantSetupModal = ({ 
  visible, 
  onClose, 
  onSuccess, 
  productId, 
  productCode 
}) => {
  const [loading, setLoading] = useState(false);
  const [attributes, setAttributes] = useState([]);
  const [selectedOptions, setSelectedOptions] = useState({});
  const [hasChanges, setHasChanges] = useState(false);

  useEffect(() => {
    if (visible && productId) {
      loadProductAttributes();
    }
  }, [visible, productId]);

  const loadProductAttributes = async () => {
    try {
      const response = await attributeApi.getProductAttributeValues(productId);
      if (response.success) {
        setAttributes(response.data || []);
        
        // ✅ Mapping selectedOptions
        const opts = {};
        (response.data || []).forEach(v => {
          if (!opts[v.attribute_id]) opts[v.attribute_id] = [];
          if (v.option_id) opts[v.attribute_id].push(v.option_id);
        });
        setSelectedOptions(opts);
      }
    } catch (error) {
      console.error('Load product attributes error:', error);
    }
  };
  const navigate = useNavigate();
  const handleGenerateVariants = async () => {
    if (!attributes || attributes.length === 0) {
      message.warning('Vui lòng thêm ít nhất 1 thuộc tính trước khi tạo biến thể');
      return;
    }

    try {
      setLoading(true);
      const response = await attributeApi.generateVariantsFromAttributes(productId);
      
      if (response.success) {
        message.success('Tạo biến thể thành công!');
        onSuccess();
        onClose();
        navigate('/products', { state: { selectedProductId: productId } });
      } else {
        message.error(response.message || 'Lỗi tạo biến thể');
      }
    } catch (error) {
      console.error('Generate variants error:', error);
      message.error(error.message || 'Lỗi tạo biến thể');
    } finally {
      setLoading(false);
    }
  };

  const handleAttributesSaved = (savedAttributes) => {
    setAttributes(savedAttributes);
    setHasChanges(true);
  };

  // ✅ FIX: Tính số biến thể chính xác
  const calculateVariantsCount = (selectedOptions) => {
    const lengths = Object.values(selectedOptions)
      .map(arr => arr.length)
      .filter(len => len > 0);
    if (!lengths.length) return 0;
    return lengths.reduce((acc, l) => acc * l, 1);
  };

  return (
    <Modal
      title={
        <Space>
          <SettingOutlined />
          <span>Thiết lập biến thể cho sản phẩm: {productCode}</span>
        </Space>
      }
      open={visible}
      onCancel={onClose}
      width={800}
      footer={[
        <Button key="cancel" onClick={onClose}>
          Hủy
        </Button>,
        <Button 
          key="generate" 
          type="primary" 
          loading={loading} 
          onClick={handleGenerateVariants}
          disabled={!attributes || attributes.length === 0}
        >
          Tạo biến thể
        </Button>,
      ]}
      className={styles.modal}
      destroyOnClose
    >
      <div className={styles.container}>
        <Alert
          message="Hướng dẫn"
          description={
            <div>
              <Text>1. Thêm các thuộc tính (màu sắc, kích thước, ...) cho sản phẩm</Text><br/>
              <Text>2. Chọn nhiều giá trị cho mỗi thuộc tính</Text><br/>
              <Text>3. Bấm "Tạo biến thể" để tạo các biến thể từ tổ hợp thuộc tính</Text>
            </div>
          }
          type="info"
          icon={<InfoCircleOutlined />}
          showIcon
          style={{ marginBottom: 24 }}
        />

        <VariantAttributeManager
          mode="product"
          entityId={productId}
          onSaved={handleAttributesSaved}
          disabled={false}
        />
        
        {/* ✅ FIX: Hiển thị số biến thể chính xác */}
        {Object.keys(selectedOptions).length > 0 && (
          <Alert
            message={`Sẽ tạo ${calculateVariantsCount(selectedOptions)} biến thể từ tổ hợp giá trị các thuộc tính`}
            type="success"
            showIcon
            style={{ marginTop: 16 }}
          />
        )}
      </div>
    </Modal>
  );
};

export default VariantSetupModal;