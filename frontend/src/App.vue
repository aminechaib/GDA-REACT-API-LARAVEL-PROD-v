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
          <button class="export-btn glass-btn header-action-btn" @click="exportToExcel(exportMode)" :title="exportMode === 'filtered' ? 'Export current filtered view' : 'Export all products'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="18" height="18">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Export Excel
          </button>

          <div class="column-toggle-wrapper" style="position: relative;">
            <!-- Columns Button -->
            <button class="glass-btn header-action-btn" @click="showColumnMenu = !showColumnMenu">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
              </svg>
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

        <button class="theme-toggle" @click="toggleDark" :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'">
          <svg v-if="isDark" class="icon" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4.707 2.707a1 1 0 010 1.414L13.414 9a1 1 0 01-1.414 0L11 8.414l-1 1-2.293-2.293a1 1 0 011.414-1.414L10 7.586l2.293-2.293a1 1 0 011.414 0zM10 15a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zm-5.657-4.707a1 1 0 011.414 0l1.293 1.293a1 1 0 01-1.414 1.414L4.343 12.414a1 1 0 010-1.414zM10 11a1 1 0 011 1v3a1 1 0 11-2 0v-3a1 1 0 011-1z" clip-rule="evenodd" />
          </svg>
          <svg v-else class="icon" viewBox="0 0 20 20" fill="currentColor">
            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
          </svg>
        </button>
      </div>
      <div class="search-wrapper">
        <svg class="search-icon" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
        </svg>
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
        <input type="date" v-model="searchDate" class="search-input" />
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
        :animateRows="true"
        @grid-ready="onGridReady"
        @sort-changed="onSortChanged"
        @cell-clicked="onCellClicked"
      >
      </ag-grid-vue>
    </div>



    <!-- CUSTOM PAGINATION -->
    <div class="custom-pagination glass-card">
      <div class="page-info">
        Showing {{ serverItemsLength === 0 ? 0 : (serverOptions.page - 1) * serverOptions.rowsPerPage + 1 }}
        to {{ Math.min(serverOptions.page * serverOptions.rowsPerPage, serverItemsLength) }}
        of {{ serverItemsLength }} entries
      </div>
            <div class="page-controls">
        <!-- First & Prev -->
        <button class="btn-secondary page-btn" :disabled="serverOptions.page === 1" @click="serverOptions.page = 1">First</button>
        <button class="btn-secondary page-btn" :disabled="serverOptions.page === 1" @click="serverOptions.page--">Prev</button>

        <!-- Page Info -->
        <span class="page-num">Page {{ serverOptions.page }} of {{ totalPages }}</span>

        <!-- Next & Last -->
        <button class="btn-secondary page-btn" :disabled="serverOptions.page >= totalPages" @click="serverOptions.page++">Next</button>
        <button class="btn-secondary page-btn" :disabled="serverOptions.page >= totalPages" @click="serverOptions.page = totalPages">Last</button>

        <select v-model="serverOptions.rowsPerPage" class="glass-input rows-select">
          <option :value="10">10 / page</option>
          <option :value="50">50 / page</option>
          <option :value="100">100 / page</option>
          <option :value="300">300 / page</option>
        </select>
      </div>

    </div>

    <!-- Loading overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
    </div>

    <!-- Import Modal -->
    <ImportModal v-if="showImportModal" :show-modal="showImportModal" @close="showImportModal = false" />

    <!-- Toast Notifications -->
    <Toast
      :show="toast.show"
      :type="toast.type"
      :title="toast.title"
      :message="toast.message"
      @close="hideToast"
    />
  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import ImportModal from './components/ImportModal.vue';
import './assets/new-ui.css';
import Toast from './components/Toast.vue';
import axios from 'axios';

// AG Grid Imports
import { AgGridVue } from 'ag-grid-vue3';
import { ModuleRegistry, AllCommunityModule } from 'ag-grid-community';
import 'ag-grid-community/styles/ag-grid.css';
import 'ag-grid-community/styles/ag-theme-alpine.css';

ModuleRegistry.registerModules([AllCommunityModule]);
// AG Grid Configuration
const defaultColDef = {
  sortable: true,
  resizable: true, // Smooth drag-to-resize enabled globally!
  minWidth: 100,
};

// Add the Actions column to the end
const allHeaders = ref([
//   { text: "ID", value: "id", width: 80, show: true },
  { text: "Reference", value: "ref", show: true },
  { text: "Designation", value: "designation", flex: 1, show: true },
  { text: "Marque", value: "marque", show: true },
  { text: "Prix", value: "prix", show: true },
  { text: "Date", value: "date_display", width: 140, show: true },
  { text: "Fournisseur", value: "fournisseur", show: true },
  { text: "Actions", value: "actions", width: 100, show: true, sortable: false } // <-- NEW
]);

const activeColumnDefs = computed(() => {
  return allHeaders.value.filter(h => h.show).map(h => {
    const col = {
      headerName: h.text,
      field: h.value,
      width: h.width,
      flex: h.flex,
      sortable: h.sortable !== false, // Disable sorting for Actions
    };

    // Inject the Copy Button HTML into the Actions column
    if (h.value === 'actions') {
      col.cellRenderer = () => {
        return `<button class="action-icon-btn" title="Copy Row">
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


const gridApi = ref(null);

const onGridReady = (params) => {
  gridApi.value = params.api;
  // Auto-size columns to fit the screen on load
  params.api.sizeColumnsToFit();
};

const onSortChanged = () => {
  // Get the current sort state from AG Grid
  const sortState = gridApi.value.getColumnState().find(s => s.sort != null);
  if (sortState) {
    serverOptions.value.sortBy = sortState.colId;
    serverOptions.value.sortType = sortState.sort;
  } else {
    serverOptions.value.sortBy = 'id';
    serverOptions.value.sortType = 'asc';
  }
  loadFromServer();
};

const showColumnMenu = ref(false);

// Shared debounce vars/functions
let debounceTimer;
const debounceHandler = (delay) => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => loadFromServer(), delay);
};

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
const totalValue = ref(0);
const uniqueFournisseurs = ref(0);
const recentImports = ref(0);
const showFilters = ref(false);
const exportMode = ref('filtered');

const filters = ref({
  dateFrom: '',
  dateTo: '',
  fournisseur: '',
  minPrice: '',
  maxPrice: ''
});

watch(filters, () => debounceHandler(500), { deep: true });

const fournisseurs = ref([]);
const showMobileMenu = ref(false);
const isMobile = ref(false);

const toast = ref({ show: false, type: 'info', title: '', message: '' });

const showToast = (type, title, message, duration = 5000) => {
  toast.value = { show: true, type, title, message };
  if (duration > 0) setTimeout(() => { toast.value.show = false; }, duration);
};

const hideToast = () => { toast.value.show = false; };

const loadFournisseurs = async () => {
  try {
    const { data } = await axios.get('https://api.gda.sarlpro.com/api/fournisseurs' );
    fournisseurs.value = Array.isArray(data) ? data : [];
  } catch (error) {
    console.error('Failed to load fournisseurs', error);
  }
};

const applyFilters = () => {
  loadFromServer();
  showToast('success', 'Filters Applied', 'Products filtered successfully');
};

const clearFilters = () => {
  filters.value = { dateFrom: '', dateTo: '', fournisseur: '', minPrice: '', maxPrice: '' };
  loadFromServer();
  showToast('info', 'Filters Cleared', 'All filters have been reset');
};

const serverOptions = ref({
  page: 1,
  rowsPerPage: 100,
  sortBy: 'id',
  sortType: 'asc',
});

// Watch pagination changes to trigger API calls
watch(() => serverOptions.value.page, () => loadFromServer());
watch(() => serverOptions.value.rowsPerPage, () => {
  serverOptions.value.page = 1;
  loadFromServer();
});

const totalPages = computed(() => {
  return Math.ceil(serverItemsLength.value / serverOptions.value.rowsPerPage) || 1;
});

const isDark = ref(false);

const toggleDark = () => {
  isDark.value = !isDark.value;
  localStorage.setItem('darkMode', isDark.value);
  document.documentElement.classList.toggle('dark');
};

const exportToExcel = () => {
  try {
    showToast('info', `Exporting ${exportMode.value === 'filtered' ? 'filtered' : 'all'} products...`, 'Server is generating Excel file. Download will start shortly.');
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
    const filteredParams = Object.entries(params)
      .filter(([_, value]) => value !== '' && value !== null)
      .reduce((acc, [key, value]) => ({ ...acc, [key]: value }), {});

    const queryString = new URLSearchParams(filteredParams).toString();
    const url = `https://api.gda.sarlpro.com/api/products/export?${queryString}`;

    const link = document.createElement('a' );
    link.href = url;
    const modeText = exportMode.value === 'filtered' ? 'filtered' : 'all';
    link.setAttribute('download', `products_${modeText}_export_${new Date().toISOString().split('T')[0]}.xlsx`);
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } catch (error) {
    console.error('Failed to initiate export:', error);
    showToast('error', 'Export Failed', 'Could not create the download link. Check console.');
  }
};

onMounted(() => {
  const saved = localStorage.getItem('darkMode');
  isDark.value = saved === 'true';
  if (isDark.value) {
    document.documentElement.classList.add('dark');
  }
  loadFournisseurs();

  const checkMobile = () => {
    isMobile.value = window.innerWidth < 768;
  };
  checkMobile();
  window.addEventListener('resize', checkMobile);
});

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    month: 'numeric',
    day: 'numeric',
    year: 'numeric'
  });
};

const onCellClicked = async (params) => {
  // Check if the user clicked the 'actions' column
  if (params.colDef.field === 'actions') {
    const row = params.data;

    // Format the text you want to copy
    const textToCopy = `Ref: ${row.ref} | Produit: ${row.designation} | Marque: ${row.marque} | Prix: ${row.prix} DH | Fournisseur: ${row.fournisseur}`;

    try {
      // Copy to clipboard
      await navigator.clipboard.writeText(textToCopy);
      showToast('success', 'Copied!', 'Product details copied to clipboard.', 3000);
    } catch (err) {
      console.error('Failed to copy text: ', err);
      showToast('error', 'Error', 'Failed to copy to clipboard.');
    }
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
      ref: searchRef.value,
      marque: searchMarque.value,
      date: searchDate.value,
      fournisseur: searchFournisseur.value,
      designation: searchDesignation.value,
      searchValue: searchValue.value,
    };

    if (filters.value.dateFrom) params.dateFrom = filters.value.dateFrom;
    if (filters.value.dateTo) params.dateTo = filters.value.dateTo;
    if (filters.value.fournisseur) params.fournisseur = filters.value.fournisseur;
    if (filters.value.minPrice) params.minPrice = filters.value.minPrice;
    if (filters.value.maxPrice) params.maxPrice = filters.value.maxPrice;

    const response = await axios.get('https://api.gda.sarlpro.com/api/products', { params } );
    items.value = response.data.rows.map(item => ({
      ...item,
      date_display: formatDate(item.date)
    }));
    serverItemsLength.value = response.data.total;

    const pageItems = response.data.rows;
    totalValue.value = pageItems.reduce((sum, item) => sum + (parseFloat(item.prix) || 0), 0);
    uniqueFournisseurs.value = new Set(pageItems.map(item => item.fournisseur).filter(Boolean)).size;

    const sevenDaysAgo = new Date();
    sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);
    recentImports.value = pageItems.filter(item => {
      const itemDate = new Date(item.date);
      return itemDate >= sevenDaysAgo;
    }).length;

  } catch (error) {
    console.error("Failed to fetch data:", error);
    alert("Failed to load data from the server. Check console for details.");
  }
  loading.value = false;
};

// Quick search debounce
watch([searchRef, searchMarque, searchDate, searchFournisseur, searchDesignation, searchValue], () => {
  serverOptions.value.page = 1; // Reset to page 1 on search
  debounceHandler(300);
}, { deep: true });

loadFromServer();
</script>

<style>
:root {
  --primary-color: #1d638c;
  --primary-dark: #16547a;
  --bg-light: #f8f9ff;
  --bg-card: #ffffff;
  --text-primary: #2c3e50;
  --text-secondary: #64748b;
  --border-light: #e2e8f0;
  --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.dark {
  --bg-light: #0f172a;
  --bg-card: #1e293b;
  --text-primary: #f1f5f9;
  --text-secondary: #94a3b8;
  --border-light: #334155;
  --shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

html, body {
  margin: 0;
  padding: 0;
  height: 100%;
  font-family: Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: var(--bg-light);
  color: var(--text-primary);
  transition: background-color 0.3s, color 0.3s;
}

#app-container {
  display: flex;
  flex-direction: column;
  height: 100vh;
  padding: 1rem;
  background: var(--bg-light);
  transition: background 0.3s;
  box-sizing: border-box;
}

.app-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-shrink: 0;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
  padding: 1.5rem 2rem;
  border-radius: 12px;
  color: white;
  box-shadow: var(--shadow);
}

.export-group {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.export-mode {
  background: rgba(255,255,255,0.2);
  border: 1px solid rgba(255,255,255,0.3);
  color: white;
  border-radius: 8px;
  min-width: 80px;
}

.dark .export-mode {
  background: rgba(0,0,0,0.3);
  border-color: rgba(255,255,255,0.2);
}

/* AG Grid Wrapper Styles */
.grid-wrapper {
  flex-grow: 1;
  min-height: 400px;
  margin-bottom: 1rem;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: var(--shadow);
  background: var(--bg-card);
}

/* Custom Pagination Styles */
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

.page-info {
  color: var(--text-secondary);
  font-size: 0.875rem;
}

.page-controls {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.page-btn {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-num {
  font-weight: 600;
  color: var(--text-primary);
}

.rows-select {
  padding: 0.5rem;
  width: auto;
  font-size: 0.875rem;
}

.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid var(--border-light);
  border-top: 4px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.column-menu {
  position: absolute;
  top: 120%;
  right: 0;
  background: var(--bg-card);
  border: 1px solid var(--border-light);
  border-radius: 12px;
  padding: 1rem;
  box-shadow: var(--shadow);
  z-index: 100;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  min-width: 160px;
}

.column-checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  color: var(--text-primary);
  font-size: 0.875rem;
}

.column-toggle-wrapper {
  display: flex;
  align-items: center;
}

.menu-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 99;
  cursor: default;
}

/* Fix AG Grid dark mode borders to match your theme */
.ag-theme-alpine-dark {
  --ag-background-color: var(--bg-card);
  --ag-header-background-color: #1e293b;
  --ag-border-color: var(--border-light);
  --ag-row-border-color: var(--border-light);
}
.ag-theme-alpine {
  --ag-background-color: var(--bg-card);
  --ag-header-background-color: #f8f9ff;
  --ag-border-color: var(--border-light);
  --ag-row-border-color: var(--border-light);
}
.header-action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  height: 42px; /* Forces them all to be exactly the same height */
  padding: 0 1.25rem;
  white-space: nowrap;
  box-sizing: border-box;
}
/* Action Button inside AG Grid */
.action-icon-btn {
  background: transparent;
  border: none;
  color: var(--text-secondary);
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 8px;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  width: 100%;
}

.action-icon-btn:hover {
  color: var(--primary-color);
  background: rgba(29, 99, 140, 0.1);
  transform: scale(1.05);
}

.dark .action-icon-btn:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
}


</style>
