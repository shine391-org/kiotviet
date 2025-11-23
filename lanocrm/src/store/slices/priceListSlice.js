import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import priceListApi from '../../api/priceListApi';

const initialState = {
  items: [],
  pagination: { page: 1, limit: 20, total: 0, total_pages: 0 },
  current: null,
  currentItems: [],
  loading: false,
  saving: false,
  deleting: false,
  error: null,
  createSuccess: false,
  updateSuccess: false,
};

export const fetchPriceLists = createAsyncThunk(
  'priceList/fetchAll',
  async (params, { rejectWithValue }) => {
    try { return await priceListApi.getPriceLists(params); }
    catch (err) { return rejectWithValue(err.response?.data?.message || err.message); }
  }
);

export const fetchPriceList = createAsyncThunk(
  'priceList/fetchOne',
  async (id, { rejectWithValue }) => {
    try { return await priceListApi.getPriceList(id); }
    catch (err) { return rejectWithValue(err.response?.data?.message || err.message); }
  }
);

export const createPriceList = createAsyncThunk(
  'priceList/create',
  async (data, { rejectWithValue }) => {
    try { return await priceListApi.createPriceList(data); }
    catch (err) { return rejectWithValue(err.response?.data?.message || err.message); }
  }
);

export const updatePriceList = createAsyncThunk(
  'priceList/update',
  async ({ id, data }, { rejectWithValue }) => {
    try { return await priceListApi.updatePriceList(id, data); }
    catch (err) { return rejectWithValue(err.response?.data?.message || err.message); }
  }
);

export const deletePriceList = createAsyncThunk(
  'priceList/delete',
  async (id, { rejectWithValue }) => {
    try { return await priceListApi.deletePriceList(id); }
    catch (err) { return rejectWithValue(err.response?.data?.message || err.message); }
  }
);

export const fetchPriceListItems = createAsyncThunk(
  'priceList/fetchItems',
  async (id, { rejectWithValue }) => {
    try { return await priceListApi.getItems(id); }
    catch (err) { return rejectWithValue(err.response?.data?.message || err.message); }
  }
);

export const savePriceListItems = createAsyncThunk(
  'priceList/saveItems',
  async ({ id, items }, { rejectWithValue }) => {
    try { return await priceListApi.saveItems(id, items); }
    catch (err) { return rejectWithValue(err.response?.data?.message || err.message); }
  }
);

const priceListSlice = createSlice({
  name: 'priceList',
  initialState,
  reducers: {
    resetPriceListState(state) {
      state.error = null;
      state.createSuccess = false;
      state.updateSuccess = false;
    }
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchPriceLists.pending, (state) => { state.loading = true; state.error = null; })
      .addCase(fetchPriceLists.fulfilled, (state, action) => {
        state.loading = false;
        state.items = action.payload.data || [];
        state.pagination = action.payload.pagination || state.pagination;
      })
      .addCase(fetchPriceLists.rejected, (state, action) => { state.loading = false; state.error = action.payload; })

      .addCase(fetchPriceList.pending, (state) => { state.loading = true; state.error = null; })
      .addCase(fetchPriceList.fulfilled, (state, action) => {
        state.loading = false;
        state.current = action.payload.data || null;
      })
      .addCase(fetchPriceList.rejected, (state, action) => { state.loading = false; state.error = action.payload; })

      .addCase(createPriceList.pending, (state) => { state.saving = true; state.createSuccess = false; state.error = null; })
      .addCase(createPriceList.fulfilled, (state, action) => {
        state.saving = false;
        state.createSuccess = true;
        if (action.payload?.data) { state.current = action.payload.data; }
      })
      .addCase(createPriceList.rejected, (state, action) => { state.saving = false; state.error = action.payload; })

      .addCase(updatePriceList.pending, (state) => { state.saving = true; state.updateSuccess = false; state.error = null; })
      .addCase(updatePriceList.fulfilled, (state) => { state.saving = false; state.updateSuccess = true; })
      .addCase(updatePriceList.rejected, (state, action) => { state.saving = false; state.error = action.payload; })

      .addCase(deletePriceList.pending, (state) => { state.deleting = true; })
      .addCase(deletePriceList.fulfilled, (state) => { state.deleting = false; })
      .addCase(deletePriceList.rejected, (state, action) => { state.deleting = false; state.error = action.payload; })

      .addCase(fetchPriceListItems.fulfilled, (state, action) => {
        state.currentItems = action.payload.data || [];
      })
      .addCase(savePriceListItems.pending, (state) => { state.saving = true; })
      .addCase(savePriceListItems.fulfilled, (state) => { state.saving = false; })
      .addCase(savePriceListItems.rejected, (state, action) => { state.saving = false; state.error = action.payload; });
  }
});

export const { resetPriceListState } = priceListSlice.actions;
export default priceListSlice.reducer;
