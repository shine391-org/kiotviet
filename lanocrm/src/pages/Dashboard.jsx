import React from 'react';
import { useSelector } from 'react-redux';

const Dashboard = () => {
  const { user } = useSelector((state) => state.auth);

  return (
    <div style={{ padding: '0' }}>
      {/* Welcome Card */}
      <div style={{ 
        background: 'white', 
        padding: '24px', 
        borderRadius: '12px',
        boxShadow: '0 2px 8px rgba(0,0,0,0.08)',
        marginBottom: '24px'
      }}>
        <h1 style={{ 
          fontSize: 'clamp(20px, 5vw, 28px)', // Responsive font
          marginBottom: '8px',
          color: '#262626'
        }}>
          Xin chào, {user?.username || 'User'}! 👋
        </h1>
        <p style={{ 
          fontSize: 'clamp(14px, 3vw, 16px)', 
          color: '#8c8c8c',
          margin: 0
        }}>
          Chào mừng bạn đến với LanoCRM
        </p>
      </div>

      {/* Stats Grid - Responsive */}
      <div style={{ 
        display: 'grid', 
        gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
        gap: '16px',
        marginBottom: '24px'
      }}>
        <StatCard 
          icon="💰"
          title="Doanh thu"
          value="0 đ"
          color="#1890ff"
        />
        <StatCard 
          icon="📦"
          title="Đơn hàng"
          value="0"
          color="#52c41a"
        />
        <StatCard 
          icon="👥"
          title="Khách hàng"
          value="0"
          color="#fa8c16"
        />
        <StatCard 
          icon="🏢"
          title="Chi nhánh"
          value="0"
          color="#722ed1"
        />
      </div>

      {/* System Info */}
      <div style={{ 
        background: 'white', 
        padding: 'clamp(16px, 4vw, 24px)', 
        borderRadius: '12px',
        boxShadow: '0 2px 8px rgba(0,0,0,0.08)'
      }}>
        <h2 style={{ 
          fontSize: 'clamp(16px, 4vw, 20px)', 
          marginBottom: '16px' 
        }}>
          ✅ Hệ thống hoạt động tốt
        </h2>
        <ul style={{ 
          lineHeight: '1.8', 
          fontSize: 'clamp(13px, 3vw, 15px)', 
          paddingLeft: '20px',
          margin: 0
        }}>
          <li>Frontend React + Redux hoạt động</li>
          <li>Backend API đã kết nối</li>
          <li>Xác thực JWT đã được cấu hình</li>
          <li>Layout responsive trên mọi thiết bị</li>
        </ul>
      </div>
    </div>
  );
};

// Stat Card Component - Responsive
const StatCard = ({ icon, title, value, color }) => (
  <div style={{ 
    background: 'white', 
    padding: 'clamp(16px, 3vw, 20px)', 
    borderRadius: '12px',
    boxShadow: '0 2px 8px rgba(0,0,0,0.08)',
    display: 'flex',
    alignItems: 'center',
    gap: 'clamp(12px, 2vw, 16px)'
  }}>
    <div style={{ 
      width: 'clamp(48px, 10vw, 56px)',
      height: 'clamp(48px, 10vw, 56px)',
      borderRadius: '12px',
      background: `${color}15`,
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      fontSize: 'clamp(24px, 5vw, 28px)',
      flexShrink: 0
    }}>
      {icon}
    </div>
    <div>
      <div style={{ 
        fontSize: 'clamp(12px, 2.5vw, 14px)', 
        color: '#8c8c8c', 
        marginBottom: '4px' 
      }}>
        {title}
      </div>
      <div style={{ 
        fontSize: 'clamp(20px, 4vw, 24px)', 
        fontWeight: '700', 
        color: '#262626' 
      }}>
        {value}
      </div>
    </div>
  </div>
);

export default Dashboard;