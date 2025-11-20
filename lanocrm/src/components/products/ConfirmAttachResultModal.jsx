import React from 'react';
import { Modal, Button, Alert, Space } from 'antd';
import { 
  CheckCircleOutlined, 
  WarningOutlined, 
  InfoCircleOutlined 
} from '@ant-design/icons';

const ConfirmAttachResultModal = ({ visible, result, onClose, entityType = 'product' }) => {
  if (!result) return null;

  const { 
    attached_count = 0, 
    restored_count = 0, 
    duplicate_count = 0, 
    total_success = 0,
    message = ''
  } = result;

  const hasSuccess = total_success > 0;
  const allDuplicates = duplicate_count > 0 && total_success === 0;

  const getTitle = () => {
    if (hasSuccess) {
      return (
        <Space>
          <CheckCircleOutlined style={{ fontSize: '18px', color: '#52c41a' }} />
          <span>✅ Gắn ảnh thành công</span>
        </Space>
      );
    } else if (allDuplicates) {
      return (
        <Space>
          <WarningOutlined style={{ fontSize: '18px', color: '#faad14' }} />
          <span>⚠️ Ảnh đã tồn tại</span>
        </Space>
      );
    } else {
      return (
        <Space>
          <InfoCircleOutlined style={{ fontSize: '18px', color: '#1890ff' }} />
          <span>ℹ️ Thông báo</span>
        </Space>
      );
    }
  };

  const getMessage = () => {
    if (typeof message === 'string' && message.trim() !== '') {
      return message;
    }
    const parts = [];
    if (attached_count > 0) parts.push(`✅ Gắn ${attached_count} ảnh mới`);
    if (restored_count > 0) parts.push(`♻️ Khôi phục ${restored_count} ảnh`);
    if (duplicate_count > 0) parts.push(`⚠️ Bỏ qua ${duplicate_count} ảnh đã có`);
    return parts.length > 0 ? parts.join('. ') : '⚠️ Không có ảnh mới được gắn';
  };

  return (
    <Modal
      title={getTitle()}
      open={visible}
      onCancel={onClose}
      footer={[
        <Button key="ok" type="primary" onClick={onClose} size="large">
          OK
        </Button>,
      ]}
      width={450}
      centered
      maskClosable={false}
    >
      <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
        <Alert
          message={getMessage()}
          type={hasSuccess ? 'success' : allDuplicates ? 'warning' : 'info'}
          showIcon
          style={{ fontSize: '14px' }}
        />
        {(attached_count > 0 || restored_count > 0 || duplicate_count > 0) && (
          <div style={{
            padding: '12px',
            backgroundColor: '#fafafa',
            borderRadius: '6px',
            border: '1px solid #f0f0f0'
          }}>
            <strong>📊 Chi tiết:</strong>
            <ul style={{ margin: '8px 0 0 0', paddingLeft: '20px' }}>
              {attached_count > 0 && (
                <li>✅ Ảnh mới: <strong>{attached_count}</strong></li>
              )}
              {restored_count > 0 && (
                <li>♻️ Khôi phục: <strong>{restored_count}</strong></li>
              )}
              {duplicate_count > 0 && (
                <li>⚠️ Đã có: <strong>{duplicate_count}</strong></li>
              )}
            </ul>
          </div>
        )}
        <div style={{ fontSize: '13px', color: '#999' }}>
          {hasSuccess
            ? <span>✅ Tôi hiểu ảnh này sẽ được gắn vào {entityType} và không thể hoàn tác</span>
            : <span>ℹ️ Không có thay đổi nào được thực hiện</span>
          }
        </div>
      </div>
    </Modal>
    
  );
};
export default ConfirmAttachResultModal;