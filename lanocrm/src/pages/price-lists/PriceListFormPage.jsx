import React, { useEffect, useMemo, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { useNavigate, useParams } from 'react-router-dom';
import { Card, Form, Input, Select, DatePicker, InputNumber, Switch, Button, Space, Table, App, Tag } from 'antd';
import dayjs from 'dayjs';
import { PlusOutlined, SaveOutlined, ArrowLeftOutlined, DeleteOutlined } from '@ant-design/icons';
import { createPriceList, fetchPriceList, fetchPriceListItems, savePriceListItems, updatePriceList } from '../../store/slices/priceListSlice';
import * as productApi from '../../api/productApi';
import priceListApi from '../../api/priceListApi';

const PriceListFormPage = () => {
  const { id } = useParams();
  const isEdit = Boolean(id);
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const { message } = App.useApp();
  const { current, currentItems, saving } = useSelector(state => state.priceList);
  const [form] = Form.useForm();
  const [items, setItems] = useState([]);
  const [products, setProducts] = useState([]);
  const [baseOptions, setBaseOptions] = useState([]);

  useEffect(() => {
    if (isEdit) {
      dispatch(fetchPriceList(id));
      dispatch(fetchPriceListItems(id));
    }
    // preload some products for dropdown
    (async () => {
      try {
        const res = await productApi.getProductsWithVariants({ limit: 50, include_variants: true });
        setProducts(res.data || []);
      } catch (e) {
        console.warn('Cannot preload products', e);
      }

      try {
        const baseRes = await priceListApi.getPriceLists({ limit: 100 });
        setBaseOptions(baseRes.data || []);
      } catch (e) {
        console.warn('Cannot preload price lists', e);
      }
    })();
  }, [dispatch, id, isEdit]);

  useEffect(() => {
    if (current && isEdit) {
      form.setFieldsValue({
        name: current.name,
        type: current.type,
        description: current.description,
        apply_to_groups: current.apply_to_groups || [],
        priority: current.priority ?? 0,
        is_active: current.is_active ?? true,
        base_price_list_id: current.base_price_list_id ?? null,
        auto_update: current.auto_update ?? false,
        formula: current.formula ?? '',
        date_range: [
          current.start_date ? dayjs(current.start_date) : null,
          current.end_date ? dayjs(current.end_date) : null,
        ],
      });
    }
  }, [current, form, isEdit]);

  useEffect(() => {
    if (isEdit && currentItems) {
      setItems(currentItems);
    }
  }, [currentItems, isEdit]);

  const productOptions = useMemo(() => (
    products.map(p => ({ value: p.id, label: `${p.code} - ${p.name}`, variants: p.variants || [] }))
  ), [products]);

  const baseListOptions = useMemo(() => (
    baseOptions
      .filter(pl => !isEdit || pl.id !== Number(id))
      .map(pl => ({ value: pl.id, label: `${pl.name} (#${pl.id})` }))
  ), [baseOptions, id, isEdit]);

  const variantOptions = (productId) => {
    const prod = products.find(p => p.id === productId);
    return prod?.variants?.map(v => ({ value: v.id, label: v.variant_name || v.sku || `Variant ${v.id}` })) || [];
  };

  const addRow = () => setItems([...items, { product_id: null, variant_id: null, price: 0, discount_percent: 0, discount_amount: 0 }]);

  const updateItem = (index, field, value) => {
    const next = [...items];
    next[index] = { ...next[index], [field]: value };
    if (field === 'product_id') { next[index].variant_id = null; }
    setItems(next);
  };

  const removeItem = (index) => {
    const next = [...items];
    next.splice(index, 1);
    setItems(next);
  };

  const handleFinish = async (values) => {
    const payload = {
      name: values.name,
      type: values.type || 'custom',
      description: values.description,
      apply_to_groups: values.apply_to_groups || [],
      start_date: values.date_range?.[0] ? values.date_range[0].format('YYYY-MM-DD') : null,
      end_date: values.date_range?.[1] ? values.date_range[1].format('YYYY-MM-DD') : null,
      priority: values.priority ?? 0,
      is_active: values.is_active ?? true,
      base_price_list_id: values.base_price_list_id || null,
      auto_update: values.auto_update ?? false,
      formula: values.formula || null,
    };

    let priceListId = id;
    let actionResult;
    if (isEdit) {
      actionResult = await dispatch(updatePriceList({ id, data: payload }));
      priceListId = id;
    } else {
      actionResult = await dispatch(createPriceList(payload));
      priceListId = actionResult?.payload?.data?.id;
    }

    if (actionResult.error) {
      message.error(actionResult.payload || 'Lưu bảng giá thất bại');
      return;
    }

    if (!isEdit && !priceListId) {
      message.error(actionResult?.payload || 'Tạo bảng giá thất bại');
      return;
    }

    if (priceListId && items.length) {
      const saveResult = await dispatch(savePriceListItems({ id: priceListId, items }));
      if (saveResult.error) {
        message.error(saveResult.payload || 'Lưu sản phẩm thất bại');
        return;
      }
    }

    message.success(isEdit ? 'Đã cập nhật bảng giá' : 'Đã tạo bảng giá');
    navigate('/price-lists');
  };

  const columns = [
    {
      title: 'Sản phẩm',
      dataIndex: 'product_id',
      render: (_, row, index) => (
        <Select
          showSearch
          placeholder="Chọn sản phẩm"
          value={row.product_id}
          options={productOptions}
          onChange={(val) => updateItem(index, 'product_id', val)}
          style={{ width: 220 }}
          optionFilterProp="label"
        />
      ),
    },
    {
      title: 'Phiên bản',
      dataIndex: 'variant_id',
      render: (_, row, index) => (
        <Select
          allowClear
          placeholder="Chọn phiên bản"
          value={row.variant_id}
          options={variantOptions(row.product_id)}
          onChange={(val) => updateItem(index, 'variant_id', val)}
          style={{ width: 180 }}
          disabled={!row.product_id}
        />
      ),
    },
    {
      title: 'Giá mới',
      dataIndex: 'price',
      render: (_, row, index) => (
        <InputNumber
          min={0}
          value={row.price}
          onChange={(val) => updateItem(index, 'price', val ?? 0)}
        />
      ),
    },
    {
      title: 'Giảm %',
      dataIndex: 'discount_percent',
      render: (_, row, index) => (
        <InputNumber
          min={0}
          max={100}
          value={row.discount_percent}
          onChange={(val) => updateItem(index, 'discount_percent', val ?? 0)}
        />
      ),
    },
    {
      title: 'Giảm tiền',
      dataIndex: 'discount_amount',
      render: (_, row, index) => (
        <InputNumber
          min={0}
          value={row.discount_amount}
          onChange={(val) => updateItem(index, 'discount_amount', val ?? 0)}
        />
      ),
    },
    {
      title: 'Xóa',
      key: 'actions',
      render: (_, __, index) => (
        <Button icon={<DeleteOutlined />} danger onClick={() => removeItem(index)} />
      ),
    },
  ];

  return (
    <Card
      title={isEdit ? `Chỉnh sửa bảng giá #${id}` : 'Tạo bảng giá'}
      extra={<Button icon={<ArrowLeftOutlined />} onClick={() => navigate('/price-lists')}>Danh sách</Button>}
    >
      <Form layout="vertical" form={form} onFinish={handleFinish} initialValues={{ is_active: true, priority: 0, auto_update: false }}>
        <Space align="start" size="large" style={{ width: '100%', flexWrap: 'wrap' }}>
          <Form.Item label="Tên bảng giá" name="name" rules={[{ required: true, message: 'Nhập tên bảng giá' }]} style={{ minWidth: 260, flex: 1 }}>
            <Input placeholder="Giá sỉ, Giá VIP..." />
          </Form.Item>
          <Form.Item label="Loại" name="type" style={{ width: 220 }}>
            <Select
              options={[
                { label: 'Base', value: 'base' },
                { label: 'Wholesale', value: 'wholesale' },
                { label: 'Retail', value: 'retail' },
                { label: 'VIP', value: 'vip' },
                { label: 'Custom', value: 'custom' },
              ]}
              defaultValue="custom"
            />
          </Form.Item>
          <Form.Item label="Độ ưu tiên" name="priority" style={{ width: 160 }}>
            <InputNumber min={0} />
          </Form.Item>
          <Form.Item label="Kích hoạt" name="is_active" valuePropName="checked">
            <Switch />
          </Form.Item>
        </Space>

        <Space align="start" size="large" style={{ width: '100%', flexWrap: 'wrap' }}>
          <Form.Item label="Bảng giá gốc" name="base_price_list_id" style={{ minWidth: 260, flex: 1 }}>
            <Select
              allowClear
              placeholder="Chọn bảng giá gốc (tuỳ chọn)"
              options={baseListOptions}
            />
          </Form.Item>
          <Form.Item label="Tự động cập nhật" name="auto_update" valuePropName="checked">
            <Switch />
          </Form.Item>
          <Form.Item label="Công thức giá" name="formula" style={{ minWidth: 260, flex: 1 }}>
            <Input placeholder="Ví dụ: base * 1.05 + 5000 (để trống nếu không dùng)" />
          </Form.Item>
        </Space>

        <Space align="start" size="large" style={{ width: '100%', flexWrap: 'wrap' }}>
          <Form.Item label="Áp dụng cho nhóm KH" name="apply_to_groups" style={{ minWidth: 260, flex: 1 }}>
            <Select
              mode="multiple"
              placeholder="Chọn nhóm khách hàng"
              options={[
                { label: 'Sỉ', value: 1 },
                { label: 'VIP', value: 2 },
                { label: 'Thành viên', value: 3 },
              ]}
            />
          </Form.Item>
          <Form.Item label="Thời gian áp dụng" name="date_range" style={{ minWidth: 280 }}>
            <DatePicker.RangePicker format="YYYY-MM-DD" />
          </Form.Item>
        </Space>

        <Form.Item label="Mô tả" name="description">
          <Input.TextArea rows={3} placeholder="Ghi chú, điều kiện áp dụng..." />
        </Form.Item>

        <Card
          title="Sản phẩm áp giá"
          size="small"
          extra={<Button icon={<PlusOutlined />} onClick={addRow}>Thêm sản phẩm</Button>}
          style={{ marginTop: 16 }}
        >
          <Table
            rowKey={(_, idx) => idx}
            dataSource={items}
            columns={columns}
            pagination={false}
            locale={{ emptyText: 'Chưa có sản phẩm' }}
          />
          <Tag color="blue" style={{ marginTop: 12 }}>Ưu tiên bảng giá sẽ áp dụng cho sản phẩm đầu tiên khớp.</Tag>
        </Card>

        <Space style={{ marginTop: 16 }}>
          <Button type="primary" icon={<SaveOutlined />} htmlType="submit" loading={saving}>
            {isEdit ? 'Cập nhật' : 'Tạo mới'}
          </Button>
          <Button onClick={() => navigate('/price-lists')}>Hủy</Button>
        </Space>
      </Form>
    </Card>
  );
};

export default PriceListFormPage;
