import React from 'react';
import { useSelector } from 'react-redux';
import { Outlet, useLocation } from 'react-router-dom';
import Header from '../Layout/Header';

/**
 * MainLayout - KiotViet Style (No Sidebar)
 * Structure:
 * - Header (2-row sticky)
 *   - Row 1: Logo + Icons + User
 *   - Row 2: TopMenu (sticky)
 * - Main Content (full-width)
 */
const MainLayout = ({ children }) => {
  const location = useLocation();
  const user = useSelector((state) => state.auth?.user);

  return (
    <div className="main-layout">
      {/* ===== HEADER (2-Row Sticky) ===== */}
      <Header />

      {/* ===== MAIN CONTENT (Full-Width) ===== */}
      <main className="main-content">
        <div className="content-wrapper">
          {/* Router Outlet */}
          <Outlet />
          
          {/* Or use children if passed */}
          {children}
        </div>
      </main>
    </div>
  );
};

export default MainLayout;
