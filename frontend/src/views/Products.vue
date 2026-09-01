<template>
  <div id="app-container">
    <header class="app-header">
      <div class="header-left">
        <button class="mobile-menu-btn" @click="showMobileMenu = !showMobileMenu" v-if="isMobile">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
        </button>
        <h1>GESTION D'ARRIVAGE</h1>

        <!-- Import Button -->
        <button class="import-btn glass-btn header-action-btn" @click="showImportModal = true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="18" height="18">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          Import Excel
        </button>

        <div class="export-group">
          <!-- Export Button -->
          <button class="export-btn glass-btn header-action-btn" @click="exportToExcel(exportMode)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="18" height="18">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Export Excel
          </button>

          <button class="glass-btn header-action-btn" @click="showMultiSearchModal = true">
            Multi-Search
          </button>

          <div class="column-toggle-wrapper" style="position: relative;">
            <button class="glass-btn header-action-btn" @click="showColumnMenu = !showColumnMenu">
              Columns
            </button>
            <div v-if="showColumnMenu" class="menu-overlay" @click="showColumnMenu = false"></div>
            <div v-if="showColumnMenu" class="column-menu glass-card">
              <label v-for="(header, index) in allHeaders" :key="index" class="column-checkbox">
                <input type="checkbox" v-model="header.show" />
                {{ header.text }}
              </label>
            </div>
          </div>
        </div>

        <!-- Theme Toggle -->
        <button class="theme-toggle" @click="toggleDark">
          <svg v-if="isDark" class="icon" viewBox="0 0 20 20" fill="currentColor" width="20" height="20">
            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4.707 2.707a1 1 0 010 1.414L13.414 9a1 1 0 01-1.414 0L11 8.414l-1 1-2.293-2.293a1 1 0 011.414-1.414L10 7.586l2.293-2.293a1 1 0 011.414 0zM10 15a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zm-5.657-4.707a1 1 0 011.414 0l1.293 1.293a1 1 0 01-1.414 1.414L4.343 12.414a1 1 0 010-1.414zM10 11a1 1 0 011 1v3a1 1 0 11-2 0v-3a1 1 0 011-1z" clip-rule="evenodd" />
          </svg>
          <svg v-else class="icon" viewBox="0 0 20 20" fill="currentColor" width="20" height="20">
            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
          </svg>
        </button>

        <!-- NEW: Logout Button -->
        <button class="glass-btn header-action-btn" @click="handleLogout" style="color: #ff4d4f; border-color: rgba(255,77,79,0.3);">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="18" height="18">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
          </svg>
          Logout
        </button>
      </div>

      <div class="search-wrapper">
        <input type="text" id="search" v-model="searchValue" placeholder="Search products..." />
      </div>
    </header>

    <!-- Advanced Filters -->
    <div v-if="showFilters" class="filters-panel glass-card">
      <div class="filters-grid">
        <div class="filter-group">
          <label>Date From</label>
          <input type="date" v-model="filters.dateFrom" class="glass-input" />
        </div>
        <div class="filter-group">
          <label>Date To</label>
          <input type="date" v-model="filters.dateTo" class="glass-input" />
        </div>
        <div class="filter-group">
          <label>Fournisseur</label>
          <select v-model="filters.fournisseur" class="glass-input">
            <option value="">All Suppliers</option>
            <option v-for="sup in fournisseurs" :key="'filter-'+sup.id" :value="sup.nom">{{ sup.nom }}</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Min Price</label>
          <input type="number" v-model="filters.minPrice" placeholder="0" class="glass-input" />
        </div>
        <div class="filter-group">
          <label>Max Price</label>
          <input type="number" v-model="filters.maxPrice" placeholder="999999" class="glass-input" />
        </div>
        <div class="filter-actions">
          <button class="btn-secondary" @click="clearFilters">Clear</button>
          <button class="btn-primary" @click="applyFilters">Apply</button>
        </div>
      </div>
    </div>

    <!-- Quick Search Row -->
    <div class="quick-search-grid">
      <div class="search-group">
        <label>Reference</label>
        <input v-model="searchRef" placeholder="Search by ref..." class="search-input" />
      </div>
      <div class="search-group">
        <label>Marque</label>
        <input v-model="searchMarque" placeholder="Search by marque..." class="search-input" />
      </div>
      <div class="search-group">
        <label>Date</label>
        <input type="text" v-model="searchDate" placeholder="" class="search-input" />
      </div>
      <div class="search-group">
        <label>Fournisseur</label>
        <input v-model="searchFournisseur" placeholder="Search by fournisseur..." class="search-input" />
      </div>
      <div class="search-group">
        <label>Produit</label>
        <input v-model="searchDesignation" placeholder="Search by designation..." class="search-input" />
      </div>
    </div>

    <!-- AG GRID TABLE -->
    <div class="grid-wrapper" :class="isDark ? 'ag-theme-alpine-dark' : 'ag-theme-alpine'">
      <ag-grid-vue
        style="width: 100%; height: 100%;"
        :theme="'legacy'"
        :columnDefs="activeColumnDefs"
        :rowData="items"
        :defaultColDef="defaultColDef"
        :rowSelection="'multiple'"
        :animateRows="true"
        @grid-ready="onGridReady"
        @sort-changed="onSortChanged"
        @cell-clicked="onCellClicked"
        @cell-key-down="onCellKeyDown"
        @selection-changed="onSelectionChanged"
      >
      </ag-grid-vue>
    </div>

    <!-- SELECTION TOOLBAR -->
    <div v-if="selectedRows.length > 0" class="selection-toolbar glass-card">
      <div class="selection-info">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="9 11 12 14 22 4"></polyline>
          <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path>
        </svg>
        <span><strong>{{ selectedRows.length }}</strong> row{{ selectedRows.length > 1 ? 's' : '' }} selected</span>
      </div>
      <div class="selection-actions">
        <button class="btn-primary selection-btn" @click="showCopyMenu = true">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
          </svg>
          Select Columns
        </button>

        <button class="btn-primary selection-btn" @click="copySelectedRows">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
          </svg>
          Copy to Clipboard
        </button>

        <button class="btn-danger selection-btn" @click="deleteSelectedRows">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            <line x1="10" y1="11" x2="10" y2="17"></line>
            <line x1="14" y1="11" x2="14" y2="17"></line>
          </svg>
          Delete Selected
        </button>

        <button class="btn-secondary selection-btn" @click="deselectAll">Clear Selection</button>
      </div>
    </div>

    <!-- Copy Columns Modal -->
    <div v-if="showCopyMenu" class="modal-overlay" @click.self="showCopyMenu = false">
      <div class="modal" style="max-width: 400px; width: 90%; padding: 2rem;">
        <h3 style="margin: 0 0 1rem; color: var(--text-primary);">Select Columns to Copy</h3>
        <label class="column-checkbox" style="margin-bottom: 0.5rem;">
          <input type="checkbox" :checked="copyAllColumns" @change="toggleCopyAll" />
          Select All Columns
        </label>
        <div class="copy-menu-divider"></div>
        <label v-for="col in copyableColumns" :key="col.field" class="column-checkbox" style="margin-bottom: 0.4rem;">
          <input type="checkbox" v-model="col.selected" />
          {{ col.label }}
        </label>
        <div class="copy-menu-divider"></div>
        <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
          <button class="btn-primary" @click="showCopyMenu = false">Done</button>
        </div>
      </div>
    </div>

    <!-- CUSTOM PAGINATION -->
    <div class="custom-pagination glass-card">
      <div class="page-info">
        Showing {{ serverItemsLength === 0 ? 0 : (serverOptions.page - 1) * serverOptions.rowsPerPage + 1 }}
        to {{ Math.min(serverOptions.page * serverOptions.rowsPerPage, serverItemsLength) }}
        of {{ serverItemsLength }} entries
      </div>
      <div class="page-controls">
        <button class="btn-secondary page-btn" :disabled="serverOptions.page === 1" @click="serverOptions.page = 1">First</button>
        <button class="btn-secondary page-btn" :disabled="serverOptions.page === 1" @click="serverOptions.page--">Prev</button>
        <span class="page-num">Page {{ serverOptions.page }} of {{ totalPages }}</span>
        <button class="btn-secondary page-btn" :disabled="serverOptions.page >= totalPages" @click="serverOptions.page++">Next</button>
        <button class="btn-secondary page-btn" :disabled="serverOptions.page >= totalPages" @click="serverOptions.page = totalPages">Last</button>
        <select v-model="serverOptions.rowsPerPage" class="glass-input rows-select">
          <option :value="10">10 / page</option>
          <option :value="50">50 / page</option>
          <option :value="100">100 / page</option>
          <option :value="300">300 / page</option>
          <option :value="500">500 / page</option>
          <option :value="1000">1000 / page</option>
        </select>
      </div>
    </div>

    <!-- Loading overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
    </div>

    <!-- Import Modal -->
    <ImportModal v-if="showImportModal" :show-modal="showImportModal" @close="showImportModal = false" />

    <!-- Multi-Search Export Modal -->
    <MultiSearchExportModal
      v-if="showMultiSearchModal"
      @close="showMultiSearchModal = false"
      @toast="onMultiSearchToast"
    />

    <!-- Toast Notifications -->
    <Toast :show="toast.show" :type="toast.type" :title="toast.title" :message="toast.message" @close="hideToast" />

    <!-- Edit Product Modal -->
    <div v-if="editingProduct" class="modal-overlay">
      <div class="modal" style="max-width: 520px; width: 90%; padding: 2rem; border-radius: 16px;">
        <h3 style="margin: 0 0 1.5rem; color: var(--text-primary);">Edit Product</h3>
        <div class="filter-group" style="margin-bottom: 1rem;">
          <label>Reference</label>
          <input v-model="editingProduct.ref" class="glass-input" />
        </div>
        <div class="filter-group" style="margin-bottom: 1rem;">
          <label>Designation</label>
          <input v-model="editingProduct.designation" class="glass-input" />
        </div>
        <div class="filter-group" style="margin-bottom: 1rem;">
          <label>Marque</label>
          <input v-model="editingProduct.marque" class="glass-input" />
        </div>
        <div class="filter-group" style="margin-bottom: 1rem;">
          <label>Prix</label>
          <input v-model.number="editingProduct.prix" type="number" step="0.01" min="0" class="glass-input" />
        </div>
        <div class="filter-group" style="margin-bottom: 1rem;">
          <label>Fournisseur</label>
          <input v-model="editingProduct.fournisseur" class="glass-input" />
        </div>
        <div class="filter-group" style="margin-bottom: 1.5rem;">
          <label>Date</label>
          <input v-model="editingProduct.date" type="date" class="glass-input" />
        </div>
        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
          <button class="btn-secondary" @click="editingProduct = null">Cancel</button>
          <button class="btn-primary" @click="saveProduct" :disabled="savingProduct">
            {{ savingProduct ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router'; // <-- NEW
import axios from 'axios';
import ImportModal from '../components/ImportModal.vue'; // Updated path
import Toast from '../components/Toast.vue'; // Updated path
import MultiSearchExportModal from '../components/MultiSearchExportModal.vue';

// AG Grid Imports
import { AgGridVue } from 'ag-grid-vue3';
import { ModuleRegistry, AllCommunityModule } from 'ag-grid-community';
import 'ag-grid-community/styles/ag-grid.css';
import 'ag-grid-community/styles/ag-theme-alpine.css';

ModuleRegistry.registerModules([AllCommunityModule]);

const router = useRouter(); // <-- NEW

// --- ALL YOUR EXISTING REFS AND GRID CONFIG GO HERE ---
// (Keep defaultColDef, allHeaders, activeColumnDefs, etc. exactly as they were)
const defaultColDef = { sortable: true, resizable: true, minWidth: 100 };
const allHeaders = ref([
  { text: "Reference", value: "ref", show: true },
  { text: "Designation", value: "designation", flex: 1, show: true },
  { text: "Marque", value: "marque", show: true },
  { text: "Prix", value: "prix", show: true },
  { text: "Date", value: "date_display", width: 140, show: true },
  { text: "Fournisseur", value: "fournisseur", show: true },
  { text: "Actions", value: "actions", width: 120, show: true, sortable: false }
]);

const activeColumnDefs = computed(() => {
  return allHeaders.value.filter(h => h.show).map(h => {
    const col = { headerName: h.text, field: h.value, width: h.width, flex: h.flex, sortable: h.sortable !== false };
    if (h.value === 'actions') {
      col.cellRenderer = (params) => {
        return `<button class="action-icon-btn" title="Edit Product" data-action="edit" data-id="${params.data.id}">
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                  </svg>
                </button>
                <button class="action-icon-btn" title="Copy Row" data-action="copy" data-id="${params.data.id}">
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                  </svg>
                </button>`;
      };
    }
    return col;
  });
});

// ... (Keep your existing refs: items, loading, searchRef, etc.) ...
const items = ref([]);
const loading = ref(true);
const serverItemsLength = ref(0);
const searchRef = ref('');
const searchMarque = ref('');
const searchDate = ref('');
const searchFournisseur = ref('');
const searchDesignation = ref('');
const searchValue = ref('');
const showImportModal = ref(false);
const showMultiSearchModal = ref(false);
const showFilters = ref(false);
const exportMode = ref('filtered');
const isDark = ref(false);
const showColumnMenu = ref(false);
const isMobile = ref(false);
const showMobileMenu = ref(false);
const fournisseurs = ref([]);
const toast = ref({ show: false, type: 'info', title: '', message: '' });

const serverOptions = ref({ page: 1, rowsPerPage: 100, sortBy: 'id', sortType: 'asc' });
const filters = ref({ dateFrom: '', dateTo: '', fournisseur: '', minPrice: '', maxPrice: '' });

const totalPages = computed(() => {
  return Math.ceil(serverItemsLength.value / serverOptions.value.rowsPerPage) || 1;
});

const applyFilters = () => {
  loadFromServer();
  showToast('success', 'Filters Applied', 'Products filtered successfully');
};

const clearFilters = () => {
  filters.value = { dateFrom: '', dateTo: '', fournisseur: '', minPrice: '', maxPrice: '' };
  loadFromServer();
  showToast('info', 'Filters Cleared', 'All filters have been reset');
};

let debounceTimer;
const debounceHandler = (delay) => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => loadFromServer(), delay);
};

const showToast = (type, title, message, duration = 5000) => {
  toast.value = { show: true, type, title, message };
  if (duration > 0) setTimeout(() => { toast.value.show = false; }, duration);
};
const hideToast = () => { toast.value.show = false; };

const onMultiSearchToast = ({ type, title, message }) => {
  showToast(type, title, message);
};

// --- NEW: Logout Function ---
const handleLogout = async () => {
  try {
    await axios.post('/logout'); // Call backend to revoke token
  } catch (error) {
    console.error('Logout error', error);
  } finally {
    localStorage.removeItem('auth_token'); // Remove token
    delete axios.defaults.headers.common['Authorization']; // Remove from axios
    router.push('/login'); // Redirect to login
  }
};

// --- UPDATED: API Calls (Removed hardcoded https://api.gda.sarlpro.com/api ) ---
const loadFournisseurs = async () => {
  try {
    const { data } = await axios.get('/fournisseurs'); // Uses global base URL
    fournisseurs.value = Array.isArray(data) ? data : [];
  } catch (error) {
    console.error('Failed to load fournisseurs', error);
  }
};

const loadFromServer = async () => {
  loading.value = true;
  try {
    const params = {
      page: serverOptions.value.page,
      rowsPerPage: serverOptions.value.rowsPerPage,
      sortBy: serverOptions.value.sortBy,
      sortType: serverOptions.value.sortType,
      ref: searchRef.value, marque: searchMarque.value, date: searchDate.value,
      fournisseur: searchFournisseur.value, designation: searchDesignation.value, searchValue: searchValue.value,
      ...filters.value
    };

    const response = await axios.get('/products', { params }); // Uses global base URL
    items.value = response.data.rows.map(item => ({
      ...item,
      date_display: formatDate(item.date)
    }));
    serverItemsLength.value = response.data.total;
  } catch (error) {
    console.error("Failed to fetch data:", error);
    if (error.response && error.response.status === 401) {
      handleLogout(); // Auto logout if token expired
    }
  }
  loading.value = false;
};

// --- UPDATED: Secure Export Function ---
const exportToExcel = async () => {
  try {
    showToast('info', `Exporting...`, 'Server is generating Excel file. Download will start shortly.');

    let params = {};
    if (exportMode.value === 'filtered') {
      params = {
        ref: searchRef.value, marque: searchMarque.value, date: searchDate.value,
        fournisseur: filters.value.fournisseur || searchFournisseur.value,
        designation: searchDesignation.value, searchValue: searchValue.value,
        dateFrom: filters.value.dateFrom, dateTo: filters.value.dateTo,
        minPrice: filters.value.minPrice, maxPrice: filters.value.maxPrice,
      };
    }

    // Securely fetch the file using Axios so the Bearer token is included
    const response = await axios.get('/products/export', {
      params,
      responseType: 'blob' // CRITICAL for downloading files via API
    });

    // Create a temporary link to trigger the browser download
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    const modeText = exportMode.value === 'filtered' ? 'filtered' : 'all';
    link.setAttribute('download', `products_${modeText}_export_${new Date().toISOString().split('T')[0]}.xlsx`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url); // Clean up memory

  } catch (error) {
    console.error('Failed to initiate export:', error);
    showToast('error', 'Export Failed', 'Could not download the file.');
  }
};

const onGridReady = (params) => { gridApi.value = params.api; params.api.sizeColumnsToFit(); };
const onSortChanged = () => { /* your existing logic */ };
const toggleDark = () => {
  isDark.value = !isDark.value;
  localStorage.setItem('darkMode', isDark.value);
  document.documentElement.classList.toggle('dark');
};
const formatDate = (dateString) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString('en-US', { month: 'numeric', day: 'numeric', year: 'numeric' });
};
const editingProduct = ref(null);
const savingProduct = ref(false);
const gridApi = ref(null);

// --- SELECTION + COPY + DELETE ---
const selectedRows = ref([]);
const showCopyMenu = ref(false);
const copyableColumns = ref([
  { field: 'ref', label: 'Reference', selected: true },
  { field: 'designation', label: 'Designation', selected: true },
  { field: 'marque', label: 'Marque', selected: true },
  { field: 'prix', label: 'Prix', selected: true },
  { field: 'fournisseur', label: 'Fournisseur', selected: true },
  { field: 'date_display', label: 'Date', selected: true },
]);

const copyAllColumns = computed({
  get: () => copyableColumns.value.every(c => c.selected),
  set: () => {}
});

const toggleCopyAll = () => {
  const newVal = !copyAllColumns.value;
  copyableColumns.value.forEach(c => c.selected = newVal);
};

const onSelectionChanged = () => {
  if (gridApi.value) {
    selectedRows.value = gridApi.value.getSelectedRows();
  }
};

const copySelectedRows = async () => {
  const cols = copyableColumns.value.filter(c => c.selected).map(c => c.field);
  if (cols.length === 0) {
    showToast('warning', 'No Columns', 'Select at least one column to copy.');
    return;
  }

  const lines = selectedRows.value.map(row => {
    return cols.map(field => {
      const val = row[field] ?? '';
      return `${val}`;
    }).join(' | ');
  });

  const text = lines.join('\n');
  try {
    await navigator.clipboard.writeText(text);
    showToast('success', 'Copied!', `${selectedRows.value.length} row(s) copied to clipboard.`);
    showCopyMenu.value = false;
  } catch (err) {
    showToast('error', 'Error', 'Failed to copy to clipboard.');
  }
};

const deleteSelectedRows = async () => {
  const count = selectedRows.value.length;
  if (!confirm(`Are you sure you want to delete ${count} product(s)?`)) return;

  const ids = selectedRows.value.map(r => r.id);
  try {
    await axios.post('/products/bulk-delete', { ids });
    showToast('success', 'Deleted', `${count} product(s) deleted.`);
    selectedRows.value = [];
    loadFromServer();
  } catch (err) {
    console.error('Bulk delete failed:', err);
    showToast('error', 'Error', err.response?.data?.message || 'Failed to delete products.');
  }
};

const deselectAll = () => {
  if (gridApi.value) {
    gridApi.value.deselectAll();
  }
  selectedRows.value = [];
};

const onCellClicked = async (params) => {
  const target = params.event?.target?.closest('[data-action]');
  const action = target?.getAttribute('data-action');

  if (action === 'copy') {
    const row = params.data;
    const textToCopy = `Ref: ${row.ref} | Produit: ${row.designation} | Marque: ${row.marque} | Prix: ${row.prix} DH | Fournisseur: ${row.fournisseur}`;
    try {
      await navigator.clipboard.writeText(textToCopy);
      showToast('success', 'Copied!', 'Product details copied to clipboard.', 3000);
    } catch (err) {
      console.error('Failed to copy text: ', err);
      showToast('error', 'Error', 'Failed to copy to clipboard.');
    }
  } else if (action === 'edit') {
    editingProduct.value = { ...params.data };
  }
};

const onCellKeyDown = async (params) => {
  const event = params.event;
  if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'c' && navigator.clipboard) {
    const value = params.value ?? '';
    if (value === '') return;
    event.preventDefault();
    try {
      await navigator.clipboard.writeText(String(value));
      showToast('success', 'Copied!', `"${value}" copied to clipboard.`, 2000);
    } catch (err) {
      console.error('Failed to copy text: ', err);
      showToast('error', 'Error', 'Failed to copy to clipboard.');
    }
  }
};

const saveProduct = async () => {
  if (!editingProduct.value) return;
  savingProduct.value = true;
  try {
    await axios.put(`/products/${editingProduct.value.id}`, {
      ref: editingProduct.value.ref,
      designation: editingProduct.value.designation,
      marque: editingProduct.value.marque,
      prix: editingProduct.value.prix,
      fournisseur: editingProduct.value.fournisseur,
      date: editingProduct.value.date,
    });
    showToast('success', 'Saved', 'Product updated successfully.');
    editingProduct.value = null;
    loadFromServer();
  } catch (err) {
    console.error('Failed to save product:', err);
    showToast('error', 'Error', err.response?.data?.message || 'Failed to update product.');
  } finally {
    savingProduct.value = false;
  }
};

watch(() => serverOptions.value.page, () => loadFromServer());
watch(() => serverOptions.value.rowsPerPage, () => { serverOptions.value.page = 1; loadFromServer(); });
watch([searchRef, searchMarque, searchDate, searchFournisseur, searchDesignation, searchValue], () => {
  serverOptions.value.page = 1;
  debounceHandler(300);
}, { deep: true });

onMounted(() => {
  const saved = localStorage.getItem('darkMode');
  isDark.value = saved === 'true';
  if (isDark.value) document.documentElement.classList.add('dark');
  loadFournisseurs();
  loadFromServer();
});
</script>

<style scoped>
/* Selection Toolbar */
.selection-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1.5rem;
  margin-bottom: 0.75rem;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(29, 78, 216, 0.04));
  border: 1px solid rgba(59, 130, 246, 0.2);
  border-radius: 12px;
  flex-shrink: 0;
}

.selection-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--text-primary);
  font-size: 0.9rem;
}

.selection-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.selection-btn {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 1rem;
  font-size: 0.85rem;
  border-radius: 8px;
  cursor: pointer;
  white-space: nowrap;
}

.btn-danger {
  background: #ef4444;
  color: white;
  border: none;
  font-weight: 500;
  cursor: pointer;
}

.btn-danger:hover {
  background: #dc2626;
}

/* Copy Dropdown */
.copy-dropdown-wrapper {
  position: relative;
}

.copy-menu {
  position: absolute;
  top: 110%;
  right: 0;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 1rem;
  box-shadow: var(--shadow-md);
  z-index: 100;
  min-width: 220px;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.copy-menu-header {
  font-weight: 600;
  font-size: 0.85rem;
}

.copy-menu-divider {
  height: 1px;
  background: var(--border-color);
  margin: 0.25rem 0;
}

.column-checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  color: var(--text-primary);
  font-size: 0.875rem;
}

.menu-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  z-index: 99;
  cursor: default;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}

.modal {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow);
  border-radius: 16px;
}

/* Pagination */
.custom-pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-radius: 12px;
  background: var(--bg-card);
  box-shadow: var(--shadow);
  margin-top: auto;
  flex-shrink: 0;
}

.page-info { color: var(--text-secondary); font-size: 0.875rem; }
.page-controls { display: flex; align-items: center; gap: 1rem; }
.page-btn { padding: 0.5rem 1rem; font-size: 0.875rem; }
.page-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.page-num { font-weight: 600; color: var(--text-primary); }
.rows-select { padding: 0.5rem; width: auto; font-size: 0.875rem; }

/* AG Grid */
.ag-theme-alpine-dark {
  --ag-background-color: var(--bg-card);
  --ag-header-background-color: #1e293b;
  --ag-border-color: var(--border-color);
  --ag-row-border-color: var(--border-color);
}
.ag-theme-alpine {
  --ag-background-color: var(--bg-card);
  --ag-header-background-color: #f8f9ff;
  --ag-border-color: var(--border-color);
  --ag-row-border-color: var(--border-color);
}
</style>
