import React, { useEffect, useState } from 'react';
import { Select, Space } from 'antd';
import * as attributeApi from '../../api/attributeApi';

const { Option } = Select;

const AttributeFilter = ({ onChange, resetTrigger }) => {
  const [attributes, setAttributes] = useState([]);
  const [attributeOptions, setAttributeOptions] = useState({});
  const [selectedAttrId, setSelectedAttrId] = useState(null);
  const [selectedOptionIds, setSelectedOptionIds] = useState([]);

  // Reset filter khi resetTrigger thay đổi
  useEffect(() => {
    setSelectedAttrId(null);
    setSelectedOptionIds([]);
    onChange([]);
  }, [resetTrigger]);

  // Load danh sách thuộc tính
  useEffect(() => {
    const loadAttributes = async () => {
      const res = await attributeApi.getAttributes();
      if (res.success) {
        setAttributes(res.data);
      }
    };
    loadAttributes();
  }, []);

  // Load options khi chọn thuộc tính
  useEffect(() => {
    if (!selectedAttrId) return;

    const loadOptions = async () => {
      const res = await attributeApi.getAttributeOptions(selectedAttrId);
      if (res.success) {
        setAttributeOptions((prev) => ({
          ...prev,
          [selectedAttrId]: res.data,
        }));
      }
    };

    if (!attributeOptions[selectedAttrId]) {
      loadOptions();
    }
  }, [selectedAttrId]);

  // Xử lý khi thay đổi thuộc tính
  const handleAttributeChange = (attrId) => {
    setSelectedAttrId(attrId);
    setSelectedOptionIds([]);
    if (!attrId) onChange([]);
  };

  // Xử lý khi thay đổi options
  const handleOptionsChange = (optionIds) => {
    setSelectedOptionIds(optionIds);
    if (!selectedAttrId || !optionIds || optionIds.length === 0) {
      onChange([]);
      return;
    }
    const filters = optionIds.map(optionId => ({
      attribute_id: selectedAttrId,
      option_id: optionId,
    }));
    onChange(filters);
  };

  return (
    <Space style={{ width: '100%' }}>
      <Select
        placeholder="Chọn thuộc tính"
        value={selectedAttrId}
        onChange={handleAttributeChange}
        style={{ minWidth: 200 }}
        showSearch
        allowClear
        filterOption={(input, option) =>
          option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
        }
      >
        {attributes.map(attr => (
          <Option key={attr.id} value={attr.id}>
            {attr.name}
          </Option>
        ))}
      </Select>

      {selectedAttrId && (
        <Select
          mode="multiple"
          placeholder="Chọn giá trị"
          value={selectedOptionIds}
          onChange={handleOptionsChange}
          style={{ minWidth: 250 }}
          allowClear
          showSearch
          filterOption={(input, option) =>
            option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
          }
        >
          {(attributeOptions[selectedAttrId] || []).map(opt => (
            <Option key={opt.id} value={opt.id}>
              {opt.option_name}
            </Option>
          ))}
        </Select>
      )}
    </Space>
  );
};

export default AttributeFilter;