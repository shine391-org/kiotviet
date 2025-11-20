import React from 'react';
import { Result, Button } from 'antd';
import { useNavigate } from 'react-router-dom';
import { LockOutlined } from '@ant-design/icons';

/**
 * ForbiddenPage (403 Error)
 * Hiển thị khi user không có quyền truy cập
 */
const ForbiddenPage = () => {
  const navigate = useNavigate();

  const handleBackToDashboard = () => {
    navigate('/dashboard');
  };

  const handleGoBack = () => {
    navigate(-1);
  };

  return (
    <div style={{ 
      display: 'flex', 
      justifyContent: 'center', 
      alignItems: 'center', 
      minHeight: '100vh',
      background: '#f0f2f5'
    }}>
      <Result
        status="403"
        icon={<LockOutlined style={{ fontSize: 72, color: '#ff4d4f' }} />}
        title="403 - Không có quyền truy cập"
        subTitle="Xin lỗi, bạn không có quyền truy cập trang này. Vui lòng liên hệ quản trị viên nếu bạn cho rằng đây là lỗi."
        extra={[
          <Button type="primary" key="dashboard" onClick={handleBackToDashboard}>
            Về Dashboard
          </Button>,
          <Button key="back" onClick={handleGoBack}>
            Quay lại
          </Button>,
        ]}
      />
    </div>
  );
};

export default ForbiddenPage;