import deliveryPartners from '../mock/deliveryPartners';
import { DELIVERY_PARTNER_PAGE_SIZES } from '../constants/deliveryPartners';

const normalizeNumber = (value) => {
  if (value === null || value === undefined || value === '') return null;
  const num = Number(value);
  return Number.isNaN(num) ? null : num;
};

const filterPartners = (data, filters = {}) => {
  const {
    search,
    group,
    status,
    integrated,
    fee_from,
    fee_to,
    debt_from,
    debt_to,
    date_from,
    date_to,
  } = filters;

  return data.filter((item) => {
    if (integrated === true && !item.integrated) return false;
    if (integrated === false && item.integrated) return false;

    if (group && group !== 'all' && item.group !== group) return false;

    if (status && status !== 'all' && item.status !== status) return false;

    if (search) {
      const keyword = search.toLowerCase();
      const matches =
        item.code.toLowerCase().includes(keyword) ||
        item.name.toLowerCase().includes(keyword) ||
        (item.phone || '').toLowerCase().includes(keyword);
      if (!matches) return false;
    }

    const feeFromNum = normalizeNumber(fee_from);
    const feeToNum = normalizeNumber(fee_to);
    if (feeFromNum !== null && Number(item.total_shipping_fee) < feeFromNum) return false;
    if (feeToNum !== null && Number(item.total_shipping_fee) > feeToNum) return false;

    const debtFromNum = normalizeNumber(debt_from);
    const debtToNum = normalizeNumber(debt_to);
    if (debtFromNum !== null && Number(item.current_debt) < debtFromNum) return false;
    if (debtToNum !== null && Number(item.current_debt) > debtToNum) return false;

    if (date_from && new Date(item.created_at) < new Date(date_from)) return false;
    if (date_to && new Date(item.created_at) > new Date(date_to)) return false;

    return true;
  });
};

const sortPartners = (data, sortBy, sortOrder = 'ascend') => {
  if (!sortBy) return data;
  const orderFactor = sortOrder === 'descend' ? -1 : 1;
  return [...data].sort((a, b) => {
    const aVal = a[sortBy];
    const bVal = b[sortBy];
    if (typeof aVal === 'string') {
      return aVal.localeCompare(bVal) * orderFactor;
    }
    return (Number(aVal) - Number(bVal)) * orderFactor;
  });
};

const paginate = (data, page = 1, limit = DELIVERY_PARTNER_PAGE_SIZES[0]) => {
  const start = (page - 1) * limit;
  const end = start + limit;
  const sliced = data.slice(start, end);
  return {
    data: sliced,
    pagination: {
      page,
      limit,
      total: data.length,
      total_pages: Math.ceil(data.length / limit) || 1,
    },
  };
};

const aggregate = (data) => {
  return data.reduce(
    (acc, item) => {
      acc.total_orders += Number(item.total_orders || 0);
      acc.total_debt += Number(item.current_debt || 0);
      acc.total_fee += Number(item.total_shipping_fee || 0);
      return acc;
    },
    { total_orders: 0, total_debt: 0, total_fee: 0 }
  );
};

const deliveryPartnerApi = {
  async list(params = {}) {
    const {
      page = 1,
      limit = DELIVERY_PARTNER_PAGE_SIZES[0],
      sort_by = 'code',
      sort_order = 'ascend',
      tab = 'other',
      ...rest
    } = params;

    const integrated = tab === 'integrated' ? true : tab === 'other' ? false : undefined;

    const filtered = filterPartners(deliveryPartners, { ...rest, integrated });
    const sorted = sortPartners(filtered, sort_by, sort_order);
    const { data, pagination } = paginate(sorted, page, limit);
    const summary = aggregate(filtered);

    return {
      data,
      pagination,
      summary,
    };
  },
};

export default deliveryPartnerApi;
