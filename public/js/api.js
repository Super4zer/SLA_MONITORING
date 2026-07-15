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
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ username, password }),
    }),

  getWaiting: () => fetchJSON(`${API_BASE}/waiting`),
  getOverdue: () => fetchJSON(`${API_BASE}/overdue`),
  getOverdueResolved: () => fetchJSON(`${API_BASE}/overdue-resolved`),
  getCompleted: () => fetchJSON(`${API_BASE}/completed`),

  resolve: (id) =>
    fetchJSON(`${API_BASE}/${id}/resolve`, {
      method: "POST",
    }),

  escalate: (id, clientName, complaint) =>
    fetchJSON(`${API_BASE}/${id}/escalate`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        client_name: clientName,
        complaint: complaint,
      }),
    }),
};