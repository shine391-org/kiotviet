import React from 'react';
import ReactDOM from 'react-dom/client';
import { Provider } from 'react-redux';
import { BrowserRouter } from 'react-router-dom';
import { ConfigProvider } from 'antd';
import viVN from 'antd/locale/vi_VN';
// No CSS import needed - Ant Design 6.x uses CSS-in-JS
import { ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css'; // 🆕 Toast styles
import store from './store';
import App from './App';

// ===== PHASE 1: DESIGN SYSTEM (New tokens) =====
import './styles/variables.css';    // 1. Design tokens ONLY
import './styles/fonts.css';        // 2. Typography

// ===== EXISTING STYLES (Keep for now - Phase 1) =====
// Giữ nguyên các import CSS cũ để không phá vỡ giao diện
import './styles/App.css';
import './styles/Sidebar.css';
import './styles/Header.css';
import './styles/login.css';
import './styles/roleList.css';
import './styles/roleForm.css';
import './styles/BranchPage.css';
import './styles/userList.css';
import './styles/userForm.css';
//import './styles/MainLayout.css';
//import './styles/index.css';

// ===== NEW GLOBAL STYLES (Phase 1.2 - after testing) =====
// Uncomment sau khi test tokens ổn
// import './styles/reset.css';     
// import './styles/global.css';    
// import './styles/utilities.css'; 

const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(
  <React.StrictMode>
    <Provider store={store}>
      <BrowserRouter>
        <ConfigProvider 
          locale={viVN}
          theme={{
            token: {
              colorPrimary: '#1890ff',
              colorSuccess: '#52c41a',
              colorWarning: '#faad14',
              colorError: '#ff4d4f',
              borderRadius: 4,
              fontSize: 14,
              fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            },
          }}
        >
          <App />
          <ToastContainer
            position="top-right"
            autoClose={3000}
            hideProgressBar={false}
            newestOnTop
            closeOnClick
            rtl={false}
            pauseOnFocusLoss
            draggable
            pauseOnHover
            theme="light"
          />
        </ConfigProvider>
      </BrowserRouter>
    </Provider>
  </React.StrictMode>
);