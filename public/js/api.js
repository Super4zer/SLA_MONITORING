const API_BASE = "/api/monitoring";

async function fetchJSON(url, options = {}) {
  try {
    const response = await fetch(url, options);
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    const data = await response.json();
    return data;
  } catch (error) {
    console.error("API Fetch Error:", error);
    return null;
  }
}

window.SLA_API = {
  login: (username, password) =>
    fetchJSON(`/api/login`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ username, password }),
    }),

  getWaiting: () => fetchJSON(`${API_BASE}/waiting`),
  getOverdue: () => fetchJSON(`${API_BASE}/overdue`),
  getOverdueResolved: () => fetchJSON(`${API_BASE}/overdue-resolved`),
  getCompleted: () => fetchJSON(`${API_BASE}/completed`),

  resolve: (id) =>
    fetchJSON(`${API_BASE}/${id}/resolve`, { method: "POST" }),

  escalate: (id, clientName, complaint) =>
    fetchJSON(`${API_BASE}/${id}/escalate`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ client_name: clientName, complaint: complaint }),
    }),

  // ==== History "Terselesaikan" (search, filter tanggal/bulan, delete) ====
  getHistoryResolved: (params = {}) => {
    const cleanParams = Object.fromEntries(
      Object.entries(params).filter(([, v]) => v !== null && v !== undefined && v !== '')
    );
    const query = new URLSearchParams(cleanParams).toString();
    return fetchJSON(`${API_BASE}/history-resolved${query ? '?' + query : ''}`);
  },

  deleteHistoryResolved: (months) =>
    fetchJSON(`${API_BASE}/history-resolved/delete`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ months }),
    }),
};