/**
 * Supplier API (mock-backed while waiting for backend)
 * @agent-layer: frontend-api
 * @agent-pattern: mock-data-adapter
 * @agent-reusable: MEDIUM
 * @file src/api/supplierApi.js
 */

import suppliersMock from '../mock/suppliers';

const delay = (ms = 120) => new Promise((res) => setTimeout(res, ms));

const normalizeStatus = (status) => {
  if (!status) return null;
  if (typeof status === 'string') return status.toUpperCase();
  return null;
};

const filterSuppliers = (list, params = {}) => {
  const search = (params.search || '').toLowerCase().trim();
  const status = normalizeStatus(params.status);
  const minPurchase = params.total_from ? Number(params.total_from) : null;
  const maxPurchase = params.total_to ? Number(params.total_to) : null;

  return list.filter((item) => {
    const matchesSearch =
      !search ||
      item.name?.toLowerCase().includes(search) ||
      item.code?.toLowerCase().includes(search) ||
      item.phone?.toLowerCase().includes(search);

    const matchesStatus = !status || item.status === status;
    const matchesMin = minPurchase === null || item.total_purchase >= minPurchase;
    const matchesMax = maxPurchase === null || item.total_purchase <= maxPurchase;

    return matchesSearch && matchesStatus && matchesMin && matchesMax;
  });
};

const paginate = (list, page = 1, limit = 15) => {
  const start = (page - 1) * limit;
  const sliced = list.slice(start, start + limit);
  return {
    data: sliced,
    pagination: {
      page,
      limit,
      total: list.length,
      total_pages: Math.ceil(list.length / limit),
    },
  };
};

const supplierApi = {
  getSuppliers: async (params = {}) => {
    await delay();
    const filtered = filterSuppliers(suppliersMock, params);
    const page = Number(params.page) || 1;
    const limit = Number(params.limit) || 15;
    return paginate(filtered, page, limit);
  },

  getSupplier: async (id) => {
    await delay();
    const found = suppliersMock.find((item) => Number(item.id) === Number(id));
    if (!found) throw new Error('Supplier not found');
    return { data: found };
  },

  createSupplier: async (body) => {
    await delay();
    const now = Date.now();
    return {
      data: {
        id: now,
        code: body?.code || `NCC${now}`,
        ...body,
        status: body?.status || 'ACTIVE',
        created_at: body?.created_at || new Date().toISOString().slice(0, 10),
        receipts: [],
        payables: [],
      },
    };
  },
};

export default supplierApi;
