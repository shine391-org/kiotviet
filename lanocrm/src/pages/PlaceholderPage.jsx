import React from 'react';
import { useLocation } from 'react-router-dom';

const PlaceholderPage = ({ title = 'Đang phát triển', description }) => {
  const location = useLocation();

  return (
    <div style={{ padding: '24px' }}>
      <h1 style={{ fontSize: '24px', marginBottom: '8px' }}>{title}</h1>
      <p style={{ color: '#595959', marginBottom: '12px' }}>
        {description || 'Tính năng này đang được xây dựng.'}
      </p>
      <div
        style={{
          padding: '12px 16px',
          border: '1px dashed #d9d9d9',
          borderRadius: '8px',
          background: '#fafafa',
          color: '#8c8c8c',
        }}
      >
        <strong>Đường dẫn:</strong> {location.pathname}
      </div>
    </div>
  );
};

export default PlaceholderPage;
