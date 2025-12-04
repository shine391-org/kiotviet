// src/api/transferApi.js
import axiosInstance from './axios';
import mockTransfers, { totals as mockTotals, transferDetails } from '../mock/transfers';

const USE_MOCK = true;

const toDate = (value) => (value ? new Date(value) : null);

const inRange = (value, enabled, mode, from, to) => {
  if (!enabled) return true;
  if (!value) return false;

  let start = null;
  let end = null;

  if (mode === 'this_year') {
    const now = new Date();
    start = new Date(now.getFullYear(), 0, 1, 0, 0, 0);
    end = new Date(now.getFullYear(), 11, 31, 23, 59, 59, 999);
  } else {
    start = from ? toDate(from) : null;
    end = to ? new Date(toDate(to).setHours(23, 59, 59, 999)) : null;
  }

  const v = toDate(value);
  if (start && v < start) return false;
  if (end && v > end) return false;
  return true;
};

const applyFilters = (data, params = {}) => {
  const {
    search = '',
    statuses = [],
    fromBranches = [],
    toBranches = [],
    transferDateEnabled = false,
    receiveDateEnabled = false,
    dateMode = 'this_year',
    customFrom = null,
    customTo = null,
    receivingStatus = 'all',
  } = params;

  let result = [...data];

  if (search?.trim()) {
    const term = search.trim().toLowerCase();
    result = result.filter((s) => s.code.toLowerCase().includes(term));
  }

  if (statuses?.length) {
    result = result.filter((s) => statuses.includes(s.status));
  }

  if (fromBranches?.length) {
    result = result.filter((s) => fromBranches.includes(s.fromBranch));
  }
  if (toBranches?.length) {
    result = result.filter((s) => toBranches.includes(s.toBranch));
  }

  result = result.filter((s) =>
    inRange(s.transferDate, transferDateEnabled, dateMode, customFrom, customTo)
  );
  result = result.filter((s) =>
    inRange(s.receiveDate, receiveDateEnabled, dateMode, customFrom, customTo)
  );

  if (receivingStatus === 'matched') {
    result = result.filter((s) => Number(s.quantityReceived || 0) >= Number(s.quantitySent || 0));
  } else if (receivingStatus === 'mismatch') {
    result = result.filter((s) => Number(s.quantityReceived || 0) < Number(s.quantitySent || 0));
  }

  return result;
};

const applySort = (data, sort) => {
  if (!sort) return data;
  const [field, direction] = sort.split(',');
  const factor = direction === 'asc' ? 1 : -1;
  return [...data].sort((a, b) => {
    const av = a[field];
    const bv = b[field];
    if (av === bv) return 0;
    return av > bv ? factor : -factor;
  });
};

const paginate = (data, page = 1, limit = 15) => {
  const start = (page - 1) * limit;
  const end = start + limit;
  const sliced = data.slice(start, end);
  return {
    data: sliced,
    pagination: {
      page,
      limit,
      total: data.length,
      total_pages: Math.ceil(data.length / limit),
    },
  };
};

const buildSummary = (rows) => ({
  totalQtySent: rows.reduce((sum, r) => sum + Number(r.quantitySent || 0), 0),
  totalValueSent: rows.reduce((sum, r) => sum + Number(r.valueSent || 0), 0),
  totalQtyReceived: rows.reduce((sum, r) => sum + Number(r.quantityReceived || 0), 0),
  totalValueReceived: rows.reduce((sum, r) => sum + Number(r.valueReceived || 0), 0),
  totalItems: rows.reduce((sum, r) => sum + Number(r.totalItems || 0), 0),
});

const transferApi = {
  getTransfers: async (params = {}) => {
    if (!USE_MOCK) {
      const response = await axiosInstance.get('/inventory/transfers', { params });
      return response.data;
    }

    const {
      page = 1,
      limit = 15,
      sort = 'transferDate,desc',
    } = params;

    let filtered = applyFilters(mockTransfers, params);
    filtered = applySort(filtered, sort);
    const summary = buildSummary(filtered);
    const { data, pagination } = paginate(filtered, Number(page) || 1, Number(limit) || 15);

    return {
      data,
      pagination,
      summary: Object.keys(summary).length ? summary : mockTotals,
      success: true,
    };
  },

  getTransfer: async (code) => {
    if (!USE_MOCK) {
      const response = await axiosInstance.get(`/inventory/transfers/${code}`);
      return response.data;
    }
    const foundDetail = transferDetails[code];
    const foundList = mockTransfers.find((t) => t.code === code);
    if (!foundDetail && !foundList) {
      return { success: false, message: 'Không tìm thấy phiếu chuyển' };
    }
    return {
      success: true,
      transfer: { ...(foundList || {}), ...(foundDetail || {}) },
    };
  },

  duplicateTransfer: async (code) => {
    if (!USE_MOCK) {
      const response = await axiosInstance.post(`/inventory/transfers/${code}/duplicate`);
      return response.data;
    }
    const detail = transferDetails[code];
    const listRow = mockTransfers.find((t) => t.code === code);
    if (!detail || !listRow) {
      return { success: false, message: 'Không tìm thấy phiếu chuyển để sao chép' };
    }
    const newCode = `${code}-CP${Date.now().toString().slice(-4)}`;
    const cloned = {
      ...listRow,
      id: newCode,
      code: newCode,
      status: 'draft',
      createdAt: new Date().toISOString(),
      transferDate: new Date().toISOString(),
      receiveDate: null,
    };
    mockTransfers.unshift(cloned);
    transferDetails[newCode] = {
      ...detail,
      id: newCode,
      code: newCode,
      status: 'draft',
      receivingNotes: '',
    };
    return { success: true, transfer: cloned };
  },

  openTransfer: async (code) => {
    if (!USE_MOCK) {
      const response = await axiosInstance.post(`/inventory/transfers/${code}/open`);
      return response.data;
    }
    const row = mockTransfers.find((t) => t.code === code);
    if (!row) return { success: false, message: 'Không tìm thấy phiếu chuyển' };
    row.status = 'in_transit';
    if (transferDetails[code]) {
      transferDetails[code].status = 'in_transit';
    }
    return { success: true, transfer: row };
  },

  saveReceivingNotes: async (code, receivingNotes) => {
    if (!USE_MOCK) {
      const response = await axiosInstance.post(`/inventory/transfers/${code}/notes`, { receivingNotes });
      return response.data;
    }
    if (!transferDetails[code]) {
      transferDetails[code] = {};
    }
    transferDetails[code].receivingNotes = receivingNotes;
    const row = mockTransfers.find((t) => t.code === code);
    if (row) row.notes = receivingNotes;
    return { success: true, receivingNotes };
  },
};

export default transferApi;
