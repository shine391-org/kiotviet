import React from 'react';
import { Alert, Button } from 'antd';
import { useDispatch } from 'react-redux';
import { clearCashError } from '../../store/slices/cashSlice';

const ErrorAlert = ({ error }) => {
  const dispatch = useDispatch();

  if (!error) return null;

  const handleClear = () => dispatch(clearCashError());

  const detailList =
    error.details && Object.keys(error.details).length > 0 ? (
      <ul style={{ paddingLeft: 18, margin: '6px 0' }}>
        {Object.entries(error.details).map(([field, msg]) => (
          <li key={field}>
            <strong>{field}</strong>: {msg}
          </li>
        ))}
      </ul>
    ) : null;

  return (
    <Alert
      style={{ marginTop: 12 }}
      type="error"
      showIcon
      closable
      onClose={handleClear}
      message={error.message || 'Có lỗi xảy ra'}
      description={detailList}
      action={
        <Button size="small" type="text" onClick={handleClear}>
          Đã hiểu
        </Button>
      }
    />
  );
};

export default ErrorAlert;
