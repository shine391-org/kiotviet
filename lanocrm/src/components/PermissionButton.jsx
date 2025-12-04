/**
 * PermissionButton Component
 * Button chỉ hiển thị khi user có quyền
 * 
 * @file src/components/PermissionButton.jsx
 */

import React from 'react';
import { Button } from 'antd';
import { usePermission } from '../utils/usePermission';

/**
 * Button với permission check
 * @param {string} permission - Permission required
 * @param {React.ReactNode} children - Button content
 * @param {Object} props - Ant Design Button props
 */
const PermissionButton = ({ permission, children, ...props }) => {
  const { hasPermission } = usePermission();

  if (!hasPermission(permission)) {
    return null;
  }

  return <Button {...props}>{children}</Button>;
};

export default PermissionButton;