// src/api/shipmentApi.js
import axiosInstance from './axios';
import mockShipments from '../mock/shipments';

const USE_MOCK = false; // Toggle easily if backend endpoint sẵn sàng

const toDate = (value) => (value ? new Date(value) : null);

const inRange = (value, from, to) => {
  if (!value) return true;
  const d = toDate(value);
  if (from && d < toDate(from)) return false;
  if (to) {
    const end = new Date(to);
    end.setHours(23, 59, 59, 999);
    if (d > end) return false;
  }
  return true;
};

const applyFilters = (data, params = {}) => {
  const {
    search = '',
    statuses = [],
    partners = [],
    created_from = null,
    created_to = null,
    completed_from = null,
    completed_to = null,
    areas = [],
    cod = 'all',
    branch = null,
    branches = [],
  } = params;

  let result = [...data];

  if (search?.trim()) {
    const term = search.trim().toLowerCase();
    result = result.filter((s) => s.code.toLowerCase().includes(term) || s.invoice_code.toLowerCase().includes(term));
  }

  if (statuses?.length) {
    result = result.filter((s) => statuses.includes(s.delivery_status));
  }

  if (partners?.length) {
    result = result.filter((s) => partners.includes(s.delivery_partner));
  }

  if (areas?.length) {
    result = result.filter((s) => {
      const key = `${s.area_path?.[0] || ''}/${s.area_path?.[1] || ''}`;
      return areas.includes(key);
    });
  }

  if (cod === 'yes') {
    result = result.filter((s) => Number(s.cod_amount || 0) > 0);
  } else if (cod === 'no') {
    result = result.filter((s) => Number(s.cod_amount || 0) === 0);
  }

  if (branch || (branches && branches.length)) {
    const pool = branches && branches.length ? branches : [branch];
    result = result.filter((s) => pool.includes(s.branch_name) || pool.includes(s.branch_id));
  }

  result = result.filter((s) => inRange(s.created_at, created_from, created_to));
  result = result.filter((s) => inRange(s.delivery_time, completed_from, completed_to));

  return result;
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

const shipmentApi = {
  /**
   * Lấy danh sách vận đơn (mock hoặc backend)
   */
  getShipments: async (params = {}) => {
    if (!USE_MOCK) {
      const response = await axiosInstance.get('/shipments', { params });
      return response.data;
    }

    const {
      page = 1,
      limit = 15,
      sort = 'created_at,desc',
      search,
      statuses,
      partners,
      created_from,
      created_to,
      completed_from,
      completed_to,
      areas,
      cod,
      branch,
    } = params;

    let filtered = applyFilters(mockShipments, {
      search,
      statuses,
      partners,
      created_from,
      created_to,
      completed_from,
      completed_to,
      areas,
      cod,
      branch,
    });

    filtered = applySort(filtered, sort);
    const summary = {
      cod_total: filtered.reduce((sum, s) => sum + Number(s.cod_amount || 0), 0),
    };

    const { data, pagination } = paginate(filtered, Number(page) || 1, Number(limit) || 15);
    return {
      data,
      pagination,
      summary,
      success: true,
    };
  },

  /**
   * Lấy chi tiết vận đơn
   */
  getShipment: async (id) => {
    if (!USE_MOCK) {
      const response = await axiosInstance.get(`/shipments/${id}`);
      return response.data;
    }

    const found = mockShipments.find((s) => String(s.id) === String(id));
    if (!found) {
      return { data: null, success: false, message: 'Không tìm thấy vận đơn' };
    }
    return {
      data: found,
      success: true,
    };
  },
};

export default shipmentApi;
