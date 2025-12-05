/**
 * Attribute Selector Component
 * @file src/components/products/AttributeSelector.jsx
 * @description Reusable component for selecting and assigning attributes to simple products
 */

import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import {
  Card,
  Select,
  Input,
  Button,
  Space,
  Tag,
  Divider,
  Empty,
  message,
  Spin,
} from 'antd';
import { PlusOutlined, DeleteOutlined } from '@ant-design/icons';
import * as attributeApi from '../../api/attributeApi';
import { getImageUrl } from '../../utils/imageUrl';
import styles from './AttributeSelector.module.css';


const { Option } = Select;

/**
 * AttributeSelector Component
 * 
 * @param {Object} props
 * @param {Array} props.value - Array of { attribute_id, value_text?, option_id? }
 * @param {Function} props.onChange - Callback when value changes
 * @param {string} props.productType - Product type (goods|combo|service)
 * @param {boolean} props.disabled - Disable all inputs
 */
const AttributeSelector = ({ value = [], onChange, productType = 'goods', disabled = false }) => {
  const [loading, setLoading] = useState(false);
  const [attributes, setAttributes] = useState([]);
  const [selectedAttributeId, setSelectedAttributeId] = useState(null);
  const [attributeOptions, setAttributeOptions] = useState({});

  // Load attributes on mount
  useEffect(() => {
    loadAttributes();
  }, []);

  // Load options for selected attributes
  useEffect(() => {
    if (value.length > 0) {
      loadOptionsForSelectedAttributes();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [value]); // ✅ THÊM comment disable warning  

  /**
   * Load all active attributes
   */
  const loadAttributes = async () => {
    try {
      setLoading(true);
      const response = await attributeApi.getAttributes({ status: 'active' });

      if (response.success) {
        setAttributes(response.data);
      }
    } catch (error) {
      console.error('Load attributes error:', error);
      message.error('Lỗi tải danh sách thuộc tính');
    } finally {
      setLoading(false);
    }
  };

  /**
   * Load options for attributes that need them (select/color/image)
   */
  const loadOptionsForSelectedAttributes = async () => {
    try {
      const attributeIds = value.map((v) => v.attribute_id);
      const needOptionsTypes = ['select', 'color', 'image'];

      for (const attrId of attributeIds) {
        const attr = attributes.find((a) => a.id === attrId);
        if (attr && needOptionsTypes.includes(attr.type) && !attributeOptions[attrId]) {
          const response = await attributeApi.getAttributeOptions(attrId);
          if (response.success) {
            setAttributeOptions((prev) => ({
              ...prev,
              [attrId]: response.data,
            }));
          }
        }
      }
    } catch (error) {
      console.error('Load options error:', error);
    }
  };

  /**
   * Add new attribute
   */
  const handleAddAttribute = async () => {
    if (!selectedAttributeId) {
      message.warning('Vui lòng chọn thuộc tính');
      return;
    }

    // Check if already added
    if (value.some((v) => v.attribute_id === selectedAttributeId)) {
      message.warning('Thuộc tính này đã được thêm');
      return;
    }

    const attr = attributes.find((a) => a.id === selectedAttributeId);
    if (!attr) return;

    // Load options if needed
    if (['select', 'color', 'image'].includes(attr.type)) {
      try {
        const response = await attributeApi.getAttributeOptions(selectedAttributeId);
        if (response.success) {
          setAttributeOptions((prev) => ({
            ...prev,
            [selectedAttributeId]: response.data,
          }));
        }
      } catch (error) {
        console.error('Load options error:', error);
      }
    }

    // Add to value
    const newValue = [
      ...value,
      {
        attribute_id: selectedAttributeId,
        value_text: attr.type === 'text' ? '' : null,
        option_id: attr.type !== 'text' ? null : null,
      },
    ];

    onChange(newValue);
    setSelectedAttributeId(null);
    message.success(`Đã thêm thuộc tính: ${attr.name}`);
  };

  /**
   * Remove attribute
   */
  const handleRemoveAttribute = (attributeId) => {
    const newValue = value.filter((v) => v.attribute_id !== attributeId);
    onChange(newValue);
    message.success('Đã xóa thuộc tính');
  };

  /**
   * Update attribute value
   */
  const handleUpdateAttributeValue = (attributeId, field, fieldValue) => {
    const newValue = value.map((v) => {
      if (v.attribute_id === attributeId) {
        return { ...v, [field]: fieldValue };
      }
      return v;
    });
    onChange(newValue);
  };

  /**
   * Render input based on attribute type
   */
  const renderAttributeInput = (attrValue) => {
    const attr = attributes.find((a) => a.id === attrValue.attribute_id);
    if (!attr) return null;

    const options = attributeOptions[attr.id] || [];

    switch (attr.type) {
      case 'text':
        return (
          <Input
            placeholder="Nhập giá trị..."
            value={attrValue.value_text || ''}
            onChange={(e) =>
              handleUpdateAttributeValue(attr.id, 'value_text', e.target.value)
            }
            disabled={disabled}
          />
        );

      case 'select':
        return (
          <Select
            placeholder="Chọn giá trị..."
            value={attrValue.option_id}
            onChange={(optionId) => {
              const option = options.find((o) => o.id === optionId);
              handleUpdateAttributeValue(attr.id, 'option_id', optionId);
              handleUpdateAttributeValue(
                attr.id,
                'value_text',
                option?.value_text || ''
              );
            }}
            disabled={disabled}
            style={{ width: '100%' }}
            showSearch
            filterOption={(input, option) =>
              option.children.toLowerCase().includes(input.toLowerCase())
            }
          >
            {options.map((opt) => (
              <Option key={opt.id} value={opt.id}>
                {opt.option_name}
              </Option>
            ))}
          </Select>
        );

      case 'color':
        return (
          <Select
            placeholder="Chọn màu..."
            value={attrValue.option_id}
            onChange={(optionId) => {
              const option = options.find((o) => o.id === optionId);
              handleUpdateAttributeValue(attr.id, 'option_id', optionId);
              handleUpdateAttributeValue(
                attr.id,
                'value_text',
                option?.value_text || ''
              );
            }}
            disabled={disabled}
            style={{ width: '100%' }}
          >
            {options.map((opt) => (
              <Option key={opt.id} value={opt.id}>
                <Space>
                  <div
                    style={{
                      width: 20,
                      height: 20,
                      backgroundColor: opt.color_code,
                      border: '1px solid #d9d9d9',
                      borderRadius: 4,
                    }}
                  />
                  <span>{opt.option_name}</span>
                </Space>
              </Option>
            ))}
          </Select>
        );

      case 'image':
        return (
          <Select
            placeholder="Chọn hình ảnh..."
            value={attrValue.option_id}
            onChange={(optionId) => {
              const option = options.find((o) => o.id === optionId);
              handleUpdateAttributeValue(attr.id, 'option_id', optionId);
              handleUpdateAttributeValue(
                attr.id,
                'value_text',
                option?.value_text || ''
              );
            }}
            disabled={disabled}
            style={{ width: '100%' }}
          >
            {options.map((opt) => (
              <Option key={opt.id} value={opt.id}>
                <Space>
                  {opt.image_url && (
                    <img
                      src={getImageUrl(opt.image_url)}
                      alt={opt.option_name}
                      style={{
                        width: 30,
                        height: 30,
                        objectFit: 'cover',
                        borderRadius: 4,
                      }}
                    />
                  )}
                  <span>{opt.option_name}</span>
                </Space>
              </Option>
            ))}
          </Select>
        );


      default:
        return null;
    }
  };

  /**
   * Get available attributes (not yet added)
   */
  const availableAttributes = attributes.filter(
    (attr) => !value.some((v) => v.attribute_id === attr.id)
  );

  if (loading) {
    return (
      <div className={styles.loading}>
        <Spin tip="Đang tải thuộc tính..." />
      </div>
    );
  }

  return (
    <div className={styles.container}>
      <Divider orientation="left">Thuộc tính sản phẩm</Divider>

      {/* Add new attribute */}
      {!disabled && (
        <div className={styles.addAttribute}>
          <Space.Compact style={{ width: '100%' }}>
            <Select
              placeholder="Chọn thuộc tính..."
              value={selectedAttributeId}
              onChange={setSelectedAttributeId}
              style={{ flex: 1 }}
              showSearch
              filterOption={(input, option) =>
                option.children.toLowerCase().includes(input.toLowerCase())
              }
            >
              {availableAttributes.map((attr) => (
                <Option key={attr.id} value={attr.id}>
                  <Space>
                    <span>{attr.name}</span>
                    <Tag
                      color={
                        attr.type === 'text'
                          ? 'blue'
                          : attr.type === 'select'
                            ? 'green'
                            : attr.type === 'color'
                              ? 'orange'
                              : 'purple'
                      }
                    >
                      {attr.type}
                    </Tag>
                  </Space>
                </Option>
              ))}
            </Select>
            <Button
              type="primary"
              icon={<PlusOutlined />}
              onClick={handleAddAttribute}
              disabled={!selectedAttributeId}
            >
              Thêm
            </Button>
          </Space.Compact>
        </div>
      )}

      {/* Selected attributes list */}
      {value.length > 0 ? (
        <div className={styles.attributesList}>
          {value.map((attrValue) => {
            const attr = attributes.find((a) => a.id === attrValue.attribute_id);
            if (!attr) return null;

            return (
              <Card
                key={attr.id}
                className={styles.attributeCard}
                size="small"
              >
                <div className={styles.attributeRow}>
                  <div className={styles.attributeHeader}>
                    <Space>
                      <span className={styles.attributeName}>{attr.name}</span>
                      <Tag
                        color={
                          attr.type === 'text'
                            ? 'blue'
                            : attr.type === 'select'
                              ? 'green'
                              : attr.type === 'color'
                                ? 'orange'
                                : 'purple'
                        }
                      >
                        {attr.type}
                      </Tag>
                      {attr.is_required === 1 && (
                        <Tag color="red">Bắt buộc</Tag>
                      )}
                    </Space>
                  </div>

                  {!disabled && (
                    <Button
                      type="text"
                      danger
                      size="small"
                      icon={<DeleteOutlined />}
                      onClick={() => handleRemoveAttribute(attr.id)}
                    />
                  )}
                </div>

                <div className={styles.attributeInput}>
                  {renderAttributeInput(attrValue)}
                </div>
              </Card>
            );
          })}
        </div>
      ) : (
        <Empty
          description="Chưa có thuộc tính nào"
          image={Empty.PRESENTED_IMAGE_SIMPLE}
        />
      )}

      {availableAttributes.length === 0 && value.length > 0 && (
        <div className={styles.noMoreAttributes}>
          <Tag color="success">Đã thêm tất cả thuộc tính có sẵn</Tag>
        </div>
      )}
    </div>
  );
};

AttributeSelector.propTypes = {
  value: PropTypes.arrayOf(
    PropTypes.shape({
      attribute_id: PropTypes.number.isRequired,
      value_text: PropTypes.string,
      option_id: PropTypes.number,
    })
  ),
  onChange: PropTypes.func.isRequired,
  productType: PropTypes.oneOf(['goods', 'combo', 'service']),
  disabled: PropTypes.bool,
};

export default AttributeSelector;