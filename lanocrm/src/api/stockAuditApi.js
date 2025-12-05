import axiosInstance from './axios';
import stockAudits, { stockAuditDetails } from '../mock/stockAudits';

const USE_MOCK = true;

const toDate = (value) => (value ? new Date(value) : null);

const inRange = (value, mode = 'this_month', from = null, to = null) => {
  if (!value) return false;

  let start;
  let end;

  if (mode === 'this_month') {
    const now = new Date();
    start = new Date(now.getFullYear(), now.getMonth(), 1, 0, 0, 0, 0);
    end = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59, 999);
  } else {
    start = from ? toDate(from) : null;
    end = to ? new Date(toDate(to).setHours(23, 59, 59, 999)) : null;
  }

  const target = toDate(value);
  if (start && target < start) return false;
  if (end && target > end) return false;
  return true;
};

const applyFilters = (rows, params = {}) => {
  const {
    search = '',
    statuses = [],
    creators = [],
    dateMode = 'this_month',
    customFrom = null,
    customTo = null,
  } = params;

  let result = [...rows];

  if (search?.trim()) {
    const term = search.trim().toLowerCase();
    result = result.filter((row) => row.code.toLowerCase().includes(term));
  }

  if (statuses?.length) {
    result = result.filter((row) => statuses.includes(row.status));
  }

  if (creators?.length) {
    result = result.filter((row) => creators.includes(row.creatorName));
  }

  if (dateMode) {
    result = result.filter((row) => inRange(row.createdTime, dateMode, customFrom, customTo));
  }

  return result;
};

const applySort = (rows, sort) => {
  if (!sort) return rows;
  const [field, direction] = sort.split(',');
  const factor = direction === 'asc' ? 1 : -1;
  return [...rows].sort((a, b) => {
    const av = a[field];
    const bv = b[field];
    if (av === bv) return 0;
    return av > bv ? factor : -factor;
  });
};

const paginate = (rows, page = 1, limit = 15) => {
  const start = (page - 1) * limit;
  const data = rows.slice(start, start + limit);
  return {
    data,
    pagination: {
      page,
      limit,
      total: rows.length,
      total_pages: Math.ceil(rows.length / limit),
    },
  };
};

const buildSummary = (rows) => ({
  totalActualQuantity: rows.reduce((sum, r) => sum + Number(r.actualQuantity || 0), 0),
  totalActualValue: rows.reduce((sum, r) => sum + Number(r.totalActualValue || 0), 0),
  totalDifferenceQty: rows.reduce((sum, r) => sum + Number(r.differenceQuantity || 0), 0),
  totalDifferenceValue: rows.reduce((sum, r) => sum + Number(r.differenceValue || 0), 0),
});

const stockAuditApi = {
  getAudits: async (params = {}) => {
    if (!USE_MOCK) {
      const response = await axiosInstance.get('/inventory/stock-audits', { params });
      return response.data;
    }

    const {
      page = 1,
      limit = 15,
      sort = 'createdTime,desc',
    } = params;

    let filtered = applyFilters(stockAudits, params);
    filtered = applySort(filtered, sort);
    const summary = buildSummary(filtered);
    const { data, pagination } = paginate(filtered, Number(page) || 1, Number(limit) || 15);

    return {
      data,
      pagination,
      summary,
      success: true,
    };
  },

  getAudit: async (code) => {
    if (!USE_MOCK) {
      const response = await axiosInstance.get(`/inventory/stock-audits/${code}`);
      return response.data;
    }

    const detail = stockAuditDetails[code];
    const row = stockAudits.find((a) => a.code === code);
    if (!detail && !row) {
      return { success: false, message: 'Không tìm thấy phiếu kiểm kho' };
    }
    return { success: true, audit: { ...(row || {}), ...(detail?.audit || {}) } };
  },
};

export default stockAuditApi;
