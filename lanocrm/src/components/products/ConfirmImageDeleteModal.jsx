import React, { useState, useRef } from 'react';
import { Modal, Button, Checkbox, Alert, Spin, Space } from 'antd';
import { ExclamationCircleOutlined, LoadingOutlined } from '@ant-design/icons';
import { message } from 'antd';
import { getImageUrl } from '../../utils/imageUrl';


const ConfirmImageDeleteModal = ({ visible, image, isHardDelete, entityType = 'product', onConfirm, onCancel }) => {
  const [confirmed, setConfirmed] = useState(false);
  const [loading, setLoading] = useState(false);
  const isDeletingRef = useRef(false);

  const handleConfirmDelete = async () => {
    if (isDeletingRef.current) {
      console.warn('⚠️ Already confirming');
      return;
    }

    if (!confirmed) {
      message.warning('Vui lòng xác nhận');
      return;
    }

    try {
      isDeletingRef.current = true;
      setLoading(true);

      console.log(`✅ User confirmed delete for ${entityType}`);

      // ✅ FIX: Just call parent callback - don't call API
      if (onConfirm) {
        await onConfirm();  // Wait for parent to complete delete
      }

      // Reset state
      setConfirmed(false);
    } catch (error) {
      console.error('❌ Confirm error:', error);
      setConfirmed(false);
      if (onCancel) {
        onCancel();
      }
    } finally {
      setLoading(false);
      isDeletingRef.current = false;
    }
  };

  const handleCancel = () => {
    if (isDeletingRef.current) {
      console.warn('⚠️ Cannot cancel during delete');
      return;
    }
    setConfirmed(false);
    if (onCancel) {
      onCancel();
    }
  };

  React.useEffect(() => {
    if (!visible) {
      setConfirmed(false);
      isDeletingRef.current = false;
    }
  }, [visible]);

  if (!image) return null;

  return (
    <Modal
      title={
        <Space>
          <ExclamationCircleOutlined style={{ fontSize: '18px', color: '#faad14' }} />
          <span>
            {isHardDelete ? '🗑️ Xóa ảnh vĩnh viễn' : '⚠️ Xóa ảnh'}
          </span>
        </Space>
      }
      open={visible}
      onCancel={handleCancel}
      footer={[
        <Button
          key="cancel"
          onClick={handleCancel}
          disabled={loading}
          size="large"
        >
          Hủy
        </Button>,
        <Button
          key="delete"
          type="primary"
          danger={isHardDelete}
          onClick={handleConfirmDelete}
          disabled={!confirmed || loading}
          loading={loading}
          size="large"
        >
          {loading ? 'Đang xóa...' : isHardDelete ? 'Xóa vĩnh viễn' : 'Xóa'}
        </Button>,
      ]}
      width={450}
      centered
      maskClosable={false}
      closable={!loading}
    >
      <Spin spinning={loading} indicator={<LoadingOutlined />}>
        <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
          {image.image_url && (
            <div style={{ textAlign: 'center' }}>
              <img
                src={getImageUrl(image.image_url)}
                alt={image.name}
                style={{
                  maxWidth: '140px',
                  maxHeight: '140px',
                  borderRadius: '8px',
                  border: '1px solid #f0f0f0'
                }}
              />
            </div>
          )}


          <Alert
            message={isHardDelete ? '⚠️ Cảnh báo' : '⚠️ Thông báo'}
            description={
              isHardDelete
                ? 'Hành động này KHÔNG THỂ HOÀN TÁC. Ảnh sẽ bị xóa vĩnh viễn khỏi hệ thống.'
                : 'Ảnh sẽ bị xóa khỏi sản phẩm này.'
            }
            type={isHardDelete ? 'error' : 'warning'}
            showIcon
          />

          <div style={{
            padding: '12px',
            backgroundColor: '#fafafa',
            borderRadius: '6px',
            border: '1px solid #f0f0f0'
          }}>
            <strong>📄 Tên file:</strong> <br />
            <code style={{ color: '#1890ff' }}>{image.name}</code>
          </div>

          <Checkbox
            checked={confirmed}
            onChange={(e) => setConfirmed(e.target.checked)}
            disabled={loading}
            style={{
              fontSize: '14px',
              padding: '8px 0'
            }}
          >
            {isHardDelete
              ? '✅ Tôi hiểu ảnh này sẽ bị xóa vĩnh viễn và không thể khôi phục'
              : '✅ Tôi muốn xóa ảnh này'}
          </Checkbox>
        </div>
      </Spin>
    </Modal>
  );
};

export default ConfirmImageDeleteModal;