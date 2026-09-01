<template>
  <div class="modal-overlay" @click.self="closeModal">
    <div class="modal glass animate-pop-in">
      <div class="modal-header">
        <h2>Multi-Search Export</h2>
        <button class="close-btn" @click="closeModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <div class="modal-body">
        <div v-if="isRunning || isDone" class="progress-view">
          <div v-if="isRunning" class="spinner"></div>
          <div v-else class="done-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="40" height="40">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
          </div>

          <template v-if="isRunning">
            <h3>Searching keyword {{ keywordIndex }} of {{ keywordCount }}</h3>
            <p class="current-keyword" v-if="currentKeyword">"{{ currentKeyword }}"</p>

            <div class="progress-track">
              <div
                class="progress-fill"
                :style="{ width: progressPercent + '%' }"
              ></div>
            </div>

            <div class="progress-stats">
              <div class="stat">
                <span class="stat-label">Keyword</span>
                <span class="stat-value">{{ keywordIndex }} / {{ keywordCount }}</span>
              </div>
              <div class="stat">
                <span class="stat-label">Matched rows</span>
                <span class="stat-value">{{ formatNumber(matchedRows) }}</span>
              </div>
              <div class="stat" v-if="maxResults">
                <span class="stat-label">Cap / keyword</span>
                <span class="stat-value">{{ formatNumber(maxResults) }}</span>
              </div>
            </div>
            <p class="hint">This export runs on your device — keep this window open until the download starts.</p>
          </template>

          <template v-else-if="isDone && completed">
            <h3>Export complete!</h3>
            <p class="current-keyword">Matched {{ formatNumber(matchedRows) }} rows across {{ keywordCount }} keyword(s). The file was downloaded to your device.</p>
            <div class="done-actions">
              <button class="btn-primary" @click="closeModal">Done</button>
            </div>
          </template>

          <template v-else-if="isDone && failed">
            <h3>Export failed</h3>
            <p class="error-text">{{ errorMsg || 'Something went wrong fetching rows.' }}</p>
            <button class="btn-secondary" @click="closeModal">Close</button>
          </template>
        </div>

        <div v-else>
          <div class="section">
            <label>Search Keywords (one per line)</label>
            <textarea
              v-model="keywordsInput"
              rows="10"
              placeholder="PLAQUETTE FREIN AR&#10;FILTRE A HUILE&#10;POINTS D'ALLUMAGE"
              class="glass-input keywords-input"
            ></textarea>
            <div class="helper-row">
              <span class="helper">Each keyword gets its own results.</span>
              <span class="count">{{ keywordList.length }} / 100</span>
            </div>
          </div>

          <div class="section">
            <label>Max results per keyword</label>
            <input
              v-model="maxResultsInput"
              type="number"
              min="1"
              placeholder="Leave empty for no limit"
              class="glass-input max-input"
            />
            <span class="helper">Each keyword will only export this many matching rows.</span>
          </div>

          <div class="section">
            <label>Columns to Export</label>
            <div class="column-list">
              <label v-for="col in columnOptions" :key="col.value" class="column-checkbox">
                <input type="checkbox" v-model="col.selected" />
                {{ col.label }}
              </label>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-actions" v-if="!isRunning && !isDone">
        <button class="btn-secondary" @click="closeModal">Cancel</button>
        <button class="btn-primary" @click="runExport" :disabled="!canExport">
          Export {{ keywordList.length }} Keyword{{ keywordList.length === 1 ? '' : 's' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import * as XLSX from 'xlsx';

const emit = defineEmits(['close', 'toast']);

const keywordsInput = ref('');
const maxResultsInput = ref('');
const columnOptions = ref([
  { value: 'ref', label: 'Reference', selected: true },
  { value: 'designation', label: 'Designation', selected: true },
  { value: 'marque', label: 'Marque', selected: true },
  { value: 'prix', label: 'Prix', selected: true },
  { value: 'date', label: 'Date', selected: true },
  { value: 'fournisseur', label: 'Fournisseur', selected: true },
]);

const keywordList = computed(() => {
  return keywordsInput.value
    .split('\n')
    .map(k => k.trim())
    .filter(Boolean)
    .slice(0, 100);
});

const selectedColumns = computed(() => {
  return columnOptions.value.filter(c => c.selected).map(c => c.value);
});

const selectedLabels = computed(() => {
  return columnOptions.value.filter(c => c.selected).map(c => c.label);
});

const canExport = computed(() => {
  return keywordList.value.length > 0 && selectedColumns.value.length > 0;
});

// Local progress state — the whole export runs on the user's device.
const status = ref('idle'); // idle | running | completed | failed
const keywordIndex = ref(0);
const keywordCount = ref(0);
const currentKeyword = ref('');
const matchedRows = ref(0);
const errorMsg = ref('');
const maxResults = ref(null);

const isRunning = computed(() => status.value === 'running');
const isDone = computed(() => status.value === 'completed' || status.value === 'failed');
const completed = computed(() => status.value === 'completed');
const failed = computed(() => status.value === 'failed');

const progressPercent = computed(() => {
  if (!keywordCount.value) return 0;
  return Math.min(100, Math.round((keywordIndex.value / keywordCount.value) * 100));
});

const formatNumber = (n) => (n ?? 0).toLocaleString();

const showToast = (type, title, message) => {
  emit('toast', { type, title, message });
};

const API_BASE = 'https://api.gda.sarlpro.com/api';

const runExport = async () => {
  if (!canExport.value || isRunning.value) return;

  const keywords = keywordList.value;
  const columns = selectedColumns.value;
  const cap = maxResultsInput.value ? parseInt(maxResultsInput.value, 10) : null;

  status.value = 'running';
  keywordCount.value = keywords.length;
  keywordIndex.value = 0;
  currentKeyword.value = '';
  matchedRows.value = 0;
  errorMsg.value = '';
  maxResults.value = cap;

  const allRows = [];

  try {
    showToast('info', 'Export Started', 'Searching and building the file on your device...');

    for (let i = 0; i < keywords.length; i++) {
      keywordIndex.value = i + 1;
      currentKeyword.value = keywords[i];

      const { data } = await axios.post(`${API_BASE}/products/multi-search-rows`, {
        keyword: keywords[i],
        columns,
        max_results: cap,
      });

      allRows.push(...data.rows);
      matchedRows.value += data.total;
    }

    if (allRows.length === 0) {
      throw new Error('No results matched any keyword.');
    }

    const headers = ['Search Value', ...selectedLabels.value];
    const worksheet = XLSX.utils.aoa_to_sheet([headers, ...allRows]);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Search Results');

    const dateStamp = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');
    const filename = `multi_search_export_${dateStamp}.xlsx`;
    XLSX.writeFile(workbook, filename);

    status.value = 'completed';
    showToast('success', 'Export Complete', `Downloaded ${matchedRows.value.toLocaleString()} rows across ${keywords.length} keyword(s).`);
  } catch (error) {
    console.error('Multi-search export failed:', error);
    errorMsg.value = error?.response?.data?.message
      || error?.message
      || 'Something went wrong fetching rows.';
    status.value = 'failed';
    showToast('error', 'Export Failed', errorMsg.value);
  }
};

const closeModal = () => {
  if (isRunning.value) {
    showToast('info', 'Export in progress', 'Keep this window open until the file is downloaded.');
    return;
  }
  emit('close');
};
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  animation: fadeIn 0.3s ease;
  backdrop-filter: blur(4px);
}

.modal {
  max-width: 720px;
  width: 95%;
  max-height: 92vh;
  overflow-y: auto;
  border-radius: 24px;
  border: 1px solid var(--glass-border, #d1d5db);
  background: var(--glass-bg, #ffffff);
  box-shadow: 0 32px 64px -16px rgba(0, 0, 0, 0.15);
}

.modal-header {
  padding: 1.75rem;
  border-bottom: 1px solid var(--glass-border, #d1d5db);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--text-primary, #111827);
}

.close-btn {
  background: none;
  border: none;
  width: 40px;
  height: 40px;
  border-radius: 12px;
  cursor: pointer;
  color: var(--text-secondary, #4b5563);
  transition: all 0.3s;
}

.close-btn:hover {
  background: rgba(255, 0, 0, 0.1);
  color: #ef4444;
}

.modal-body {
  padding: 1.75rem;
}

.progress-view {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding: 3rem 1rem;
  text-align: center;
}

.progress-view h3 {
  margin: 0;
  color: var(--text-primary, #111827);
  font-size: 1.1rem;
}

.current-keyword {
  margin: 0;
  color: var(--text-secondary, #4b5563);
  font-size: 0.95rem;
}

.error-text {
  margin: 0;
  color: #ef4444;
  font-size: 0.9rem;
}

.hint {
  margin: 0;
  color: var(--text-secondary, #4b5563);
  font-size: 0.85rem;
  max-width: 360px;
}

.progress-track {
  width: 100%;
  max-width: 420px;
  height: 10px;
  border-radius: 999px;
  background: rgba(59, 130, 246, 0.15);
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #2563eb, #1d4ed8);
  border-radius: 999px;
  transition: width 0.5s ease;
}

.progress-stats {
  display: flex;
  gap: 2rem;
  justify-content: center;
  flex-wrap: wrap;
}

.stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.stat-label {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-secondary, #4b5563);
}

.stat-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-primary, #111827);
}

.done-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: rgba(16, 185, 129, 0.12);
  color: #10b981;
  display: flex;
  align-items: center;
  justify-content: center;
}

.done-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
}

.max-input {
  max-width: 240px;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid rgba(59, 130, 246, 0.2);
  border-top: 4px solid #2563eb;
  border-radius: 50%;
  animation: spin 0.9s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.section {
  margin-bottom: 1.75rem;
}

.section > label {
  display: block;
  margin-bottom: 0.75rem;
  font-weight: 600;
  color: var(--text-primary, #111827);
}

.keywords-input {
  width: 100%;
  resize: vertical;
  min-height: 160px;
}

.helper-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 0.5rem;
}

.helper {
  font-size: 0.8rem;
  color: var(--text-secondary, #4b5563);
}

.count {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--text-secondary, #4b5563);
}

.column-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 0.6rem;
}

.column-checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  color: var(--text-primary, #111827);
  font-size: 0.875rem;
}

.modal-actions {
  padding: 1.5rem 1.75rem;
  border-top: 1px solid var(--glass-border, #d1d5db);
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

.btn-secondary {
  background: var(--glass-bg, #ffffff);
  border: 1px solid var(--glass-border, #d1d5db);
  color: var(--text-primary, #111827);
  padding: 0.75rem 1.5rem;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s;
  font-weight: 500;
}

.btn-secondary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: white;
  border: none;
  padding: 0.75rem 1.75rem;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(59, 130, 246, 0.5);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.glass-input {
  background: var(--glass-bg, #ffffff);
  border: 2px solid var(--glass-border, #d1d5db);
  border-radius: 16px;
  padding: 0.9rem 1.1rem;
  font-size: 1rem;
  transition: all 0.3s ease;
  color: var(--text-primary, #111827);
  font-family: inherit;
}

.glass-input::placeholder {
  color: var(--text-secondary, #4b5563);
}

.glass-input:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes pop-in {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.animate-pop-in {
  animation: pop-in 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
</style>
