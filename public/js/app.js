(function() {
  console.log("SLA Dashboard App Loaded");

  // DOM Elements
  const clockEl = document.getElementById('live-clock');
  const listWaiting = document.getElementById('list-waiting');
  const listOverdue = document.getElementById('list-overdue');
  const listCompleted = document.getElementById('list-completed');

  const countWaiting = document.getElementById('count-waiting');
  const countOverdue = document.getElementById('count-overdue');
  const countCompleted = document.getElementById('count-completed');

  // Update Clock
  function updateClock() {
    if (clockEl) {
      const now = new Date();
      clockEl.textContent = now.toLocaleTimeString('id-ID');
    }
  }
  setInterval(updateClock, 1000);
  updateClock();

  function formatElapsedTime(dateString) {
    const received = new Date(dateString);
    const now = new Date();
    const diffSeconds = Math.max(0, Math.floor((now - received) / 1000));

    const minutes = Math.floor(diffSeconds / 60);
    const seconds = diffSeconds % 60;

    return `${minutes}m ${seconds}s`;
  }

  function createTicketCard(ticket, type) {
    const card = document.createElement('div');
    card.className = `ticket-card ${type}`;

    // Header: Client Phone & Time
    const header = document.createElement('div');
    header.className = 'ticket-header';
    
    const client = document.createElement('div');
    client.className = 'ticket-client';
    client.innerHTML = `<span class="material-symbols-outlined" style="font-size: 14px">person</span> +${ticket.client_phone}`;
    
    const time = document.createElement('div');
    time.className = 'ticket-time';
    time.textContent = new Date(ticket.time_received).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    
    header.appendChild(client);
    header.appendChild(time);

    // Body: Message
    const message = document.createElement('div');
    message.className = 'ticket-message';
    message.textContent = ticket.message_content;

    // Footer: Duration & Actions
    const footer = document.createElement('div');
    footer.className = 'ticket-footer';
    
    const duration = document.createElement('div');
    duration.className = `ticket-duration duration-${type}`;
    
    if (type === 'completed') {
      duration.innerHTML = `<span class="material-symbols-outlined" style="font-size: 14px">check_circle</span> SLA: ${ticket.sla_seconds}s`;
    } else {
      duration.innerHTML = `<span class="material-symbols-outlined" style="font-size: 14px">schedule</span> ${formatElapsedTime(ticket.time_received)}`;
    }
    
    footer.appendChild(duration);

    if (type !== 'completed') {
      const actions = document.createElement('div');
      actions.className = 'ticket-actions';
      
      const btnResolve = document.createElement('button');
      btnResolve.className = 'btn-action btn-resolve';
      btnResolve.textContent = 'Resolve';
      btnResolve.onclick = () => handleResolve(ticket.id_monitoring);
      
      const btnEscalate = document.createElement('button');
      btnEscalate.className = 'btn-action btn-escalate';
      btnEscalate.textContent = 'Escalate';
      btnEscalate.onclick = () => handleEscalate(ticket.id_monitoring, ticket.client_phone, ticket.message_content);
      
      actions.appendChild(btnResolve);
      actions.appendChild(btnEscalate);
      footer.appendChild(actions);
    }

    card.appendChild(header);
    card.appendChild(message);
    card.appendChild(footer);
    
    return card;
  }

  async function handleResolve(id) {
    if (!window.SLA_API) return;
    const res = await window.SLA_API.resolve(id);
    if (res && res.status === 'success') {
      refreshDashboard();
    } else {
      alert('Gagal resolve komplain');
    }
  }

  async function handleEscalate(id, phone, msg) {
    if (!window.SLA_API) return;
    const res = await window.SLA_API.escalate(id, `Client ${phone}`, msg);
    if (res && res.status === 'success') {
      alert(`Berhasil dieskalasi dengan Log ID: ${res.log_klikdsi_id}`);
      refreshDashboard();
    } else {
      alert('Gagal eskalasi komplain');
    }
  }

  function renderList(container, data, type) {
    if (!container) return;
    container.replaceChildren();

    if (!data || data.length === 0) {
      const empty = document.createElement('div');
      empty.className = 'text-center text-muted py-5';
      empty.style.fontSize = '13px';
      empty.textContent = 'Tidak ada data...';
      container.appendChild(empty);
      return;
    }

    data.forEach(ticket => {
      container.appendChild(createTicketCard(ticket, type));
    });
  }

  async function refreshDashboard() {
    if (!window.SLA_API) {
      console.error("SLA_API not found!");
      return;
    }

    try {
      const [waitingRes, overdueRes, completedRes] = await Promise.all([
        window.SLA_API.getWaiting(),
        window.SLA_API.getOverdue(),
        window.SLA_API.getCompleted()
      ]);

      if (waitingRes?.data) {
        if (countWaiting) countWaiting.textContent = waitingRes.data.length;
        renderList(listWaiting, waitingRes.data, 'waiting');
      }

      if (overdueRes?.data) {
        if (countOverdue) countOverdue.textContent = overdueRes.data.length;
        renderList(listOverdue, overdueRes.data, 'overdue');
      }

      if (completedRes?.data) {
        if (countCompleted) countCompleted.textContent = completedRes.data.length;
        renderList(listCompleted, completedRes.data, 'completed');
      }
    } catch (e) {
      console.error("Refresh failed:", e);
    }
  }

  window.addEventListener('DOMContentLoaded', () => {
    refreshDashboard();
    setInterval(refreshDashboard, 10000);
  });
})();
