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
  Spin,
  Alert,
  App,
} from 'antd';
import { PlusOutlined, DeleteOutlined, SaveOutlined } from '@ant-design/icons';
import * as attributeApi from '../../api/attributeApi';
import { getImageUrl } from '../../utils/imageUrl';
import styles from './VariantAttributeManager.module.css';

const { Option } = Select;

/**
 * VariantAttributeManager Component
 * @param {Object} props
 * @param {string} props.mode - 'product' | 'variant' ✅ THÊM MỚI
 * @param {number} props.entityId - Product ID hoặc Variant ID ✅ ĐỔI TÊN
 * @param {Function} props.onSaved - Callback khi lưu thành công
 * @param {boolean} props.disabled - Disable edit mode
 */
const VariantAttributeManager = ({
  mode = 'variant', // ✅ THÊM: 'product' | 'variant'
  entityId, // ✅ ĐỔI TÊN: variantId → entityId
  onSaved,
  disabled = false,
  onOptionIdsChange,
}) => {
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [attributes, setAttributes] = useState([]);
  const [entityAttributes, setEntityAttributes] = useState([]); // ✅ ĐỔI TÊN
  const [selectedAttributeId, setSelectedAttributeId] = useState(null);
  const [attributeOptions, setAttributeOptions] = useState({});
  const [selectedOptions, setSelectedOptions] = useState({});
  const [productId, setProductId] = useState(null);
  const [usedOptionsMap, setUsedOptionsMap] = useState({});
  const { message } = App.useApp();

  useEffect(() => {
    if (entityId) {
      loadData();
      if (mode === 'variant') {
        loadUsedOptions();
      }
    }
  }, [entityId, mode]);

  useEffect(() => {
    const validAttrIds = entityAttributes
      .map((va) => va.attribute_id)
      .filter((id, idx, arr) => id && arr.indexOf(id) === idx);

    validAttrIds.forEach((attrId) => {
      const attr = attributes.find((a) => a.id === attrId);
      if (attr && ['select', 'color', 'image'].includes(attr.type)) {
        loadAttributeOptions(attr.id);
      }
    });
  }, [entityAttributes, attributes]);

  // ✅ FIX: Load data theo mode
  const loadData = async () => {
    try {
      setLoading(true);

      // Load danh sách attributes
      const attrRes = await attributeApi.getAttributes();
      if (attrRes.success) {
        setAttributes(attrRes.data);

        // Load options cho từng attribute
        const optionsMap = {};
        for (const attr of attrRes.data) {
          if (['select', 'color', 'image'].includes(attr.type)) {
            const optRes = await attributeApi.getAttributeOptions(attr.id);
            if (optRes.success) {
              optionsMap[attr.id] = optRes.data;
            }
          }
        }
        setAttributeOptions(optionsMap);
      }

      // Load giá trị hiện tại
      let valuesRes;
      if (mode === 'product') {
        valuesRes = await attributeApi.getProductAttributeValues(entityId);
      } else {
        valuesRes = await attributeApi.getVariantAttributeValues(entityId);
      }

      if (valuesRes.success) {
        // GROUP lại theo attribute_id để tránh duplicate
        const groupedAttrs = [];
        const attrMap = {};

        (valuesRes.data || []).forEach(v => {
          const aid = v.attribute_id;
          if (!attrMap[aid]) {
            attrMap[aid] = {
              id: v.id,
              product_id: v.product_id,
              variant_id: v.variant_id,
              attribute_id: v.attribute_id,
              attribute_name: v.attribute_name || (attrRes.data.find(a => a.id === aid)?.name),
              value_text: v.value_text,
              options: []
            };
            groupedAttrs.push(attrMap[aid]);
          }
          if (v.option_id) {
            attrMap[aid].options.push({ option_id: v.option_id });
          }
        });

        setEntityAttributes(groupedAttrs);

        // Chuẩn bị selectedOptions từ data đã lưu
        const opts = {};
        (valuesRes.data || []).forEach(v => {
          if (!opts[v.attribute_id]) opts[v.attribute_id] = [];
          if (v.option_id) opts[v.attribute_id].push(v.option_id);
        });
        setSelectedOptions(opts);
      }
    } catch (error) {
      console.error('Load data error:', error);
    } finally {
      setLoading(false);
    }
  };

  const loadAttributeOptions = async (attributeId) => {
    if (attributeOptions[attributeId]) return;
    try {
      const response = await attributeApi.getAttributeOptions(attributeId);
      if (response.success) {
        setAttributeOptions((prev) => ({
          ...prev,
          [attributeId]: response.data,
        }));
      }
    } catch (error) {
      console.error('Load options error:', error);
    }
  };

  /**
 * Hàm mới: loadUsedOptions - gọi API backend lấy các attribute options đã dùng
 * Dựa vào product_id lấy từ variant hoặc product, trả về object: { attribute_id: [option_id1, option_id2, ...] }
 * Cập nhật state `usedOptionsMap` để disable option.
 */
  const loadUsedOptions = async () => {
    try {
      let targetProductId = productId;

      if (mode === 'variant' && !targetProductId) {
        const variantRes = await attributeApi.getVariantById(entityId);
        if (variantRes.success && variantRes.data) {
          targetProductId = variantRes.data.product_id;
          setProductId(targetProductId);
        }
      } else if (mode === 'product') {
        targetProductId = entityId;
        setProductId(entityId);
      }

      if (!targetProductId) {
        console.warn('Không có productId để lấy used options');
        return;
      }

      const res = await attributeApi.getUsedOptionsByProduct(targetProductId);
      if (res.success && res.data) {
        setUsedOptionsMap(res.data);
      }
    } catch (error) {
      console.error('loadUsedOptions error:', error);
    }
  };

  const handleAddAttribute = async () => {
    if (!selectedAttributeId) {
      message.warning('Vui lòng chọn thuộc tính');
      return;
    }
    if (entityAttributes.some((v) => v.attribute_id === selectedAttributeId)) {
      message.warning('Thuộc tính này đã được thêm');
      return;
    }
    const attr = attributes.find((a) => a.id === selectedAttributeId);
    if (!attr) return;

    if (['select', 'color', 'image'].includes(attr.type)) {
      await loadAttributeOptions(attr.id);
    }

    const newAttr = {
      id: null,
      [mode === 'product' ? 'product_id' : 'variant_id']: entityId, // ✅ FIX
      attribute_id: attr.id,
      attribute_name: attr.name,
      value_text: attr.type === 'text' ? '' : null,
      option_id: null,
    };

    setEntityAttributes(
      [...entityAttributes, newAttr].filter(
        (a, idx, arr) => arr.findIndex(t => t.attribute_id === a.attribute_id) === idx
      )
    );
    setSelectedAttributeId(null);
    message.success(`Đã thêm thuộc tính: ${attr.name}`);
  };

  const handleRemoveAttributeHard = async (attributeId) => {
    setEntityAttributes(
      entityAttributes.filter((v) => v.attribute_id !== attributeId)
    );
    setSelectedOptions(prev => {
      const copy = { ...prev };
      delete copy[attributeId];
      return copy;
    });

    try {
      // Xóa attribute này KHỎI DB BACKEND (dùng request đúng mode)
      if (mode === 'variant') {
        const response = await attributeApi.removeAttributeFromVariant(entityId, attributeId);
        if (response.success) {
          message.success('Gỡ thuộc tính thành công');
          loadData();
        } else {
          message.error(response.message || 'Không thể gỡ thuộc tính');
        }
      } else {
        // PRODUCT MODE: Gọi API xoá toàn bộ giá trị với product_id, attribute_id
        const response = await attributeApi.removeAttributeFromProduct(entityId, attributeId);
        if (response.success) {
          message.success('Đã xoá thuộc tính khỏi sản phẩm');
          loadData();
        } else {
          message.error(response.message || 'Không thể xoá thuộc tính');
        }
      }
    } catch (error) {
      console.error('Error removing attribute:', error);
      message.error(error.message || 'Lỗi khi gỡ thuộc tính');
    }
  };

  const handleUpdateValue = (attributeId, field, value) => {
    setEntityAttributes(prevAttrs =>
      prevAttrs.map((v) =>
        String(v.attribute_id) === String(attributeId)
          ? { ...v, [field]: value }
          : v
      )
    );
  };

  const validateAttributes = () => {
    const errors = [];
    entityAttributes.forEach((va) => {
      const attr = attributes.find((a) => a.id === va.attribute_id);
      if (attr?.is_required === 1) {
        if (attr.type === 'text' && !va.value_text?.trim()) {
          errors.push(`${attr.name} là bắt buộc`);
        } else if (attr.type !== 'text' && !va.option_id) {
          errors.push(`${attr.name} là bắt buộc`);
        }
      }
    });
    return errors;
  };

  const handleSave = async () => {
    const errors = validateAttributes();
    if (errors.length > 0) {
      message.error(errors.join(', '));
      return;
    }

    try {
      setSaving(true);

      // BUILD attributeValues CHUẨN TỪ entityAttributes
      const attributeValues = entityAttributes
        .map(attr => {
          // Trường hợp thuộc tính multi-option (select/image/color với select nhiều!)
          if (Array.isArray(attr.options) && attr.options.length > 0) {
            return attr.options.map(opt => ({
              attribute_id: attr.attribute_id,
              option_id: opt.option_id,
              value_text: attr.value_text ?? null,
            }));
          }
          // Trường hợp text hoặc option đơn
          return [{
            attribute_id: attr.attribute_id,
            option_id: attr.option_id ?? null,
            value_text: attr.value_text ?? null,
          }];
        })
        .flat()
        .filter(av => av.attribute_id);

      // Lọc trùng attribute_id + option_id
      const uniqueAttributeValues = [];
      const seen = new Set();
      for (const val of attributeValues) {
        const key = `${val.attribute_id}-${val.option_id ?? 'null'}`;
        if (!seen.has(key)) {
          seen.add(key);
          uniqueAttributeValues.push(val);
        }
      }

      let response;
      if (mode === 'product') {
        response = await attributeApi.updateProductAttributeValues(entityId, { attribute_values: attributeValues });
      } else {
        response = await attributeApi.syncVariantAttributeValues(entityId, attributeValues);

      }

      if (response.success) {
        message.success('Lưu thuộc tính thành công');
        if (onSaved) onSaved(response.data);
        loadData();
      } else {
        message.error(response.message || 'Không thể lưu thuộc tính');
      }
    } catch (error) {
      message.error(error.message || 'Lỗi lưu thuộc tính');
    } finally {
      setSaving(false);
    }
  };

  const renderAttributeInput = (entityAttr) => {
    const attr = attributes.find((a) => a.id === entityAttr.attribute_id);
    if (!attr) return null;
    const options = attributeOptions[attr.id] || [];

    const optionValue = (entityAttr.option_id !== undefined && entityAttr.option_id !== null)
      ? String(entityAttr.option_id)
      : undefined;

    switch (attr.type) {
      case 'text':
        return (
          <Input
            placeholder="Nhập giá trị..."
            value={entityAttr.value_text || ''}
            onChange={(e) => handleUpdateValue(attr.id, 'value_text', e.target.value)}
            disabled={disabled}
          />
        );
      case 'select':
      case 'color':
      case 'image':
        return (
          <Select
            placeholder={`Chọn ${attr.type === 'color' ? 'màu' : attr.type === 'image' ? 'hình ảnh' : 'giá trị'}`}
            value={optionValue}
            onChange={(valueStr) => {
              const opt = options.find(o => String(o.id) === String(valueStr));
              handleUpdateValue(attr.id, 'option_id', opt ? opt.id : null);
              handleUpdateValue(attr.id, 'value_text', opt?.option_name ?? '');
            }}
            disabled={disabled}
            showSearch
            filterOption={(input, opt) =>
              (opt.children || '').toLowerCase().includes(input.toLowerCase())
            }
            style={{ width: '100%' }}
            allowClear
          >
            {options.map((opt) => (
              <Option key={String(opt.id)} value={String(opt.id)}>
                {opt.option_name}
                {attr.type === 'color' && opt.color_code && (
                  <Tag color={opt.color_code} style={{ marginLeft: 8 }}>
                    Màu
                  </Tag>
                )}
                {attr.type === 'image' && opt.image_url && (
                  <img
                    src={getImageUrl(opt.image_url)}
                    alt={opt.option_name}
                    style={{ width: 30, height: 30, objectFit: 'cover', marginLeft: 8 }}
                  />
                )}
              </Option>
            ))}
          </Select>
        );

      default:
        return null;
    }
  };

  const availableAttributes = attributes.filter(
    (attr) => !entityAttributes.some((v) => v.attribute_id === attr.id)
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
      <Divider orientation="left">
        {mode === 'product' ? 'Thuộc tính sản phẩm' : 'Thuộc tính biến thể'}
      </Divider>

      {/* Form add attribute */}
      {!disabled && (
        <div className={styles.addAttribute}>
          <Space.Compact style={{ width: '100%' }}>
            <Select
              placeholder="Chọn thuộc tính..."
              value={selectedAttributeId}
              onChange={setSelectedAttributeId}
              style={{ flex: 1 }}
              showSearch
              filterOption={(input, option) => option.children.toLowerCase().includes(input.toLowerCase())}
            >
              {availableAttributes.map(attr => (
                <Option key={attr.id} value={attr.id}>
                  <Space>
                    <span>{attr.name}</span>
                    <Tag
                      color={
                        attr.type === 'text' ? 'blue' :
                          attr.type === 'select' ? 'green' :
                            attr.type === 'color' ? 'orange' : 'purple'
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
              disabled={!selectedAttributeId || entityAttributes.some(ea => ea.attribute_id === selectedAttributeId)}
            >
              Thêm
            </Button>
          </Space.Compact>
        </div>
      )}

      {/* Danh sách thuộc tính đã chọn */}
      {entityAttributes.length > 0 ? (
        <div className={styles.attributesList}>
          {entityAttributes.map(entityAttr => {
            const attr = attributes.find(a => a.id === entityAttr.attribute_id);
            if (!attr) return null;

            // Chuẩn hóa: options mapping theo entityAttr.attribute_id
            const selectedOptions = (entityAttr.options || []).map(opt => opt.option_id);
            const availableOptions = (attributeOptions[attr.id] || []).filter(
              opt => !selectedOptions.includes(opt.id)
            );

            return (
              <Card key={attr.id} className={styles.attributeCard} size="small">
                <div className={styles.attributeRow}>
                  <div className={styles.attributeHeader}>
                    <Space>
                      <span className={styles.attributeName}>{attr.name}</span>
                      <Tag
                        color={
                          attr.type === 'text' ? 'blue' :
                            attr.type === 'select' ? 'green' :
                              attr.type === 'color' ? 'orange' : 'purple'
                        }
                      >
                        {attr.type}
                      </Tag>
                      {attr.is_required === 1 && <Tag color="red">Bắt buộc</Tag>}
                    </Space>
                  </div>
                  {!disabled && (
                    <Button
                      type="text"
                      danger
                      size="small"
                      icon={<DeleteOutlined />}
                      onClick={() => handleRemoveAttributeHard(attr.id)}
                    />
                  )}
                </div>
                {/* Multi-select option cho 1 thuộc tính */}
                <div className={styles.attributeInput}>
                  {['select', 'color', 'image'].includes(attr.type) ? (
                    <>
                      <Select
                        placeholder="Chọn giá trị"
                        style={{ width: '100%' }}
                        value={null}
                        onChange={optionId => {
                          setEntityAttributes(entityAttributes.map(ea =>
                            ea.attribute_id === attr.id
                              ? { ...ea, options: [...(ea.options || []), { option_id: optionId }] }
                              : ea
                          ));
                        }}
                        options={availableOptions.map(opt => ({
                          label: opt.option_name,
                          value: opt.id,
                          disabled:
                            Array.isArray(usedOptionsMap[String(attr.id)])
                            && usedOptionsMap[String(attr.id)].map(String).includes(String(opt.id))
                        }))}

                        disabled={disabled || availableOptions.length === 0}
                        showSearch
                        filterOption={(input, opt) =>
                          (opt.label || '').toLowerCase().includes(input.toLowerCase())
                        }
                        // Để đảm bảo options đã disabled sẽ hiển thị rõ là disable
                        popupRender={menu => (
                          <div>{menu}</div>
                        )}
                      />
                      <div style={{ marginTop: 8 }}>
                        {selectedOptions.map(optionId => {
                          const opt = attributeOptions[attr.id]?.find(o => o.id === optionId);
                          const isUsed = Array.isArray(usedOptionsMap[attr.id]) && usedOptionsMap[attr.id].includes(optionId);
                          return (
                            <Tag
                              closable={!disabled}
                              color={isUsed ? 'warning' : 'success'}
                              style={{ marginRight: 8, marginTop: 8 }}
                              key={optionId}
                              onClose={() => {
                                setEntityAttributes(entityAttributes.map(ea =>
                                  ea.attribute_id === attr.id
                                    ? { ...ea, options: ea.options.filter(o => o.option_id !== optionId) }
                                    : ea
                                ));
                              }}
                            >
                              {opt?.option_name || 'unknown'}
                              {isUsed && ' ⚠️'}
                            </Tag>
                          );
                        })}
                      </div>
                    </>
                  ) : (
                    renderAttributeInput(entityAttr)
                  )}
                </div>
              </Card>
            );
          })}
        </div>
      ) : (
        <Empty description="Chưa có thuộc tính nào" image={Empty.PRESENTED_IMAGE_SIMPLE} />
      )}

      {/* Nút lưu & cảnh báo */}
      {!disabled && entityAttributes.length > 0 && (
        <div className={styles.saveButton}>
          <Button type="primary" icon={<SaveOutlined />} onClick={handleSave} loading={saving} block>
            Lưu thuộc tính
          </Button>
          <Alert
            type="info"
            showIcon
            message="Bấm 'Lưu thuộc tính' chỉ lưu cấu hình thuộc tính, sản phẩm chưa chuyển thành biến thể. Bạn phải bấm 'Tạo biến thể' để sinh sản phẩm biến thể."
            style={{ marginTop: 16, marginBottom: 16 }}
          />
        </div>
      )}

      {availableAttributes.length === 0 && entityAttributes.length > 0 && (
        <div className={styles.noMoreAttributes}>
          <Tag color="success">Đã thêm tất cả thuộc tính có sẵn</Tag>
        </div>
      )}
    </div>
  );
};

VariantAttributeManager.propTypes = {
  mode: PropTypes.oneOf(['product', 'variant']).isRequired, // ✅ THÊM
  entityId: PropTypes.number.isRequired, // ✅ ĐỔI TÊN
  onSaved: PropTypes.func,
  disabled: PropTypes.bool,
  onOptionIdsChange: PropTypes.func,
};

export default VariantAttributeManager;