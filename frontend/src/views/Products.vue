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
      <!-- ... (Keep your existing filters HTML) ... -->
    </div>

    <!-- Quick Search Row -->
    <div class="quick-search-grid">
      <!-- ... (Keep your existing quick search HTML) ... -->
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
      <!-- ... (Keep your existing pagination HTML) ... -->
    </div>

    <!-- Loading overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
    </div>

    <!-- Import Modal -->
    <ImportModal v-if="showImportModal" :show-modal="showImportModal" @close="showImportModal = false" />

    <!-- Toast Notifications -->
    <Toast :show="toast.show" :type="toast.type" :title="toast.title" :message="toast.message" @close="hideToast" />
  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router'; // <-- NEW
import axios from 'axios';
import ImportModal from '../components/ImportModal.vue'; // Updated path
import Toast from '../components/Toast.vue'; // Updated path

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
  { text: "Actions", value: "actions", width: 100, show: true, sortable: false }
]);

const activeColumnDefs = computed(() => {
  return allHeaders.value.filter(h => h.show).map(h => {
    const col = { headerName: h.text, field: h.value, width: h.width, flex: h.flex, sortable: h.sortable !== false };
    if (h.value === 'actions') {
      col.cellRenderer = () => `<button class="action-icon-btn" title="Copy Row"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg></button>`;
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

// --- UPDATED: API Calls (Removed hardcoded http://127.0.0.1:8000/api ) ---
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

// ... (Keep remaining functions: onGridReady, onSortChanged, toggleDark, formatDate, onCellClicked) ...
const onGridReady = (params) => { params.api.sizeColumnsToFit(); };
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
const onCellClicked = async (params) => { /* your existing logic */ };

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
/* Paste all your existing CSS from App.vue here */
/* (Keep all the CSS exactly as it was) */
</style>
