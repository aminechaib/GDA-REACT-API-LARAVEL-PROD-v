import { reactive } from 'vue'
import axios from 'axios'

const state = reactive({
  exportId: null,
  status: null,
  keywordIndex: 0,
  keywordCount: 0,
  currentKeyword: '',
  matchedRows: 0,
  maxResults: null,
  error: null,
  filename: null,
  downloadUrl: null,
  visible: false,
  polledAt: null,
})

let pollTimer = null
let onStatus = null
let consecutiveErrors = 0

const API_BASE = 'https://api.gda.sarlpro.com/api'

export function startExport({ keywords, columns, maxResults }) {
  return axios.post(`${API_BASE}/products/multi-search-export`, { keywords, columns, maxResults })
}

export function monitorExport(exportId, { onComplete, onFail } = {}) {
  stopPolling()
  consecutiveErrors = 0
  state.exportId = exportId
  state.status = 'pending'
  state.visible = true
  state.error = null
  state.filename = null
  state.downloadUrl = null

  persistActiveExport()

  onStatus = { onComplete, onFail }
  pollTimer = setInterval(pollStatus, 1500)
  pollStatus()
}

export function resumeMonitoring(exportId) {
  if (!exportId || state.exportId) return
  stopPolling()
  consecutiveErrors = 0
  state.exportId = exportId
  state.status = 'pending'
  state.visible = false
  onStatus = null
  pollTimer = setInterval(pollStatus, 1500)
  pollStatus()
}

export function stopMonitoring() {
  stopPolling()
  state.exportId = null
  state.status = null
  state.keywordIndex = 0
  state.keywordCount = 0
  state.currentKeyword = ''
  state.matchedRows = 0
  state.maxResults = null
  state.error = null
  state.filename = null
  state.downloadUrl = null
  state.visible = false
  onStatus = null
  consecutiveErrors = 0
  clearPersistedExport()
}

export function getActiveExportId() {
  return localStorage.getItem('active_multi_export') || null
}

function persistActiveExport() {
  if (state.exportId) {
    localStorage.setItem('active_multi_export', String(state.exportId))
  }
}

function clearPersistedExport() {
  localStorage.removeItem('active_multi_export')
}

export function hideMonitor() {
  state.visible = false
}

export function showMonitor() {
  if (state.exportId) state.visible = true
}

function stopPolling() {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

async function pollStatus() {
  if (!state.exportId) return
  state.polledAt = new Date()

  try {
    const { data } = await axios.get(`${API_BASE}/products/multi-search-export/${state.exportId}`)
    consecutiveErrors = 0
    state.status = data.status
    state.keywordIndex = data.keyword_index || 0
    state.keywordCount = data.keyword_count || 0
    state.currentKeyword = data.current_keyword || ''
    state.matchedRows = data.matched_rows || 0
    state.maxResults = data.max_results
    state.error = data.error
    state.filename = data.filename
    state.downloadUrl = data.download_url

    if (data.status === 'completed' || data.status === 'failed') {
      stopPolling()
      if (data.status === 'completed') {
        onStatus?.onComplete?.(data)
      } else {
        onStatus?.onFail?.(data)
      }
    }
  } catch (error) {
    consecutiveErrors++
    const isGone = error?.response?.status === 404
    if (isGone) {
      console.warn('Export no longer exists on the server, clearing monitor state:', state.exportId)
      stopPolling()
      state.exportId = null
      state.status = 'failed'
      state.error = 'Export was not found on the server.'
      onStatus?.onFail?.({
        status: 'failed',
        export_id: null,
        error: 'Export was not found on the server.',
      })
      clearPersistedExport()
      return
    }

    if (consecutiveErrors >= 5) {
      console.error('Giving up monitoring export after repeated failures:', error)
      stopPolling()
      state.status = 'failed'
      state.error = 'Lost connection to the export monitor.'
      onStatus?.onFail?.({
        status: 'failed',
        export_id: state.exportId,
        error: 'Lost connection to the export monitor.',
      })
      return
    }

    console.error('Failed to poll export status:', error)
  }
}

export default state