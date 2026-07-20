(function() {
  console.log("SLA Dashboard App Loaded");

  // ==== CONFIG ====
  const SLA_THRESHOLD_SECONDS = 180; // 3 menit
  const REFRESH_INTERVAL_MS = 10000; // ambil data baru dari server tiap 10 detik
  const TICK_INTERVAL_MS = 1000;     // update angka jam berjalan tiap 1 detik

  // ==== DOM Elements ====
  const clockEl = document.getElementById('live-clock');

  const listUnresolved = document.getElementById('list-waiting');       // "Belum Direspon"
  const listResolved = document.getElementById('list-completed');       // "Terselesaikan"

  const countUnresolved = document.getElementById('count-waiting');
  const countResolved = document.getElementById('count-completed');

  const statWaiting = document.getElementById('stat-waiting');     // unresolved & masih on-time
  const statOverdue = document.getElementById('stat-overdue');     // unresolved & sudah lewat SLA
  const statCompleted = document.getElementById('stat-completed'); // total sudah terselesaikan

  // Menyimpan ticket unresolved yang sedang tampil, supaya timer bisa jalan
  // tiap detik tanpa perlu render ulang / fetch ulang ke server.
  let liveUnresolvedTickets = [];

  // ==== Clock ====
  function updateClock() {
    if (clockEl) {
      const now = new Date();
      const time = now.toLocaleTimeString('id-ID', { hour12: false });
      clockEl.innerHTML = `<span class="material-symbols-outlined fs-6">schedule</span> ${time}`;
    }
  }
  setInterval(updateClock, 1000);
  updateClock();

  // ==== Helpers ====
  function getElapsedSeconds(dateString) {
    const received = new Date(dateString);
    const now = new Date();
    return Math.max(0, Math.floor((now - received) / 1000));
  }

  function formatDuration(totalSeconds) {
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    return `${minutes}m ${seconds}s`;
  }

  // Warna & style konsisten: hijau = tepat waktu, merah = lewat SLA
  function applySlaStyle(durationEl, cardEl, isLate) {
    if (isLate) {
      durationEl.style.color = '#f43f5e';
      cardEl.style.borderLeftColor = '#f43f5e';
    } else {
      durationEl.style.color = '#10b981';
      cardEl.style.borderLeftColor = '#10b981';
    }
  }

  function createTicketCard(ticket, isResolved) {
    const card = document.createElement('div');
    card.className = `ticket-card ${isResolved ? 'completed' : 'waiting'}`;
    card.dataset.id = ticket.id_monitoring;

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

    // Message
    const message = document.createElement('div');
    message.className = 'ticket-message';
    message.textContent = ticket.message_content; // textContent is safe from XSS

    // Footer: Duration & Actions
    const footer = document.createElement('div');
    footer.className = 'ticket-footer';

    const duration = document.createElement('div');
    duration.className = 'ticket-duration';
    duration.dataset.role = 'duration';

    if (isResolved) {
      // Sudah dijawab CS -> timer berhenti, warna final tergantung tepat waktu / telat
      const finalSeconds = ticket.sla_seconds != null ? ticket.sla_seconds : getElapsedSeconds(ticket.time_received);
      const isLate = finalSeconds >= SLA_THRESHOLD_SECONDS;
      const icon = isLate ? 'task_alt' : 'check_circle';
      duration.innerHTML = `<span class="material-symbols-outlined" style="font-size: 14px">${icon}</span> SLA: ${formatDuration(finalSeconds)}`;
      applySlaStyle(duration, card, isLate);
    } else {
      // Belum dijawab -> timer jalan tiap detik, warna berubah sendiri saat lewat 3 menit
      const elapsed = getElapsedSeconds(ticket.time_received);
      const isLate = elapsed >= SLA_THRESHOLD_SECONDS;
      duration.innerHTML = `<span class="material-symbols-outlined" style="font-size: 14px">schedule</span> ${formatDuration(elapsed)}`;
      applySlaStyle(duration, card, isLate);
    }

    footer.appendChild(duration);

    // Kartu yang sudah terselesaikan tidak butuh tombol aksi lagi.
    // Kartu yang belum terselesaikan hanya punya tombol Escalate
    // (tidak ada tombol Resolve lagi -- perpindahan ke "Terselesaikan"
    // terjadi otomatis begitu CS membalas, terdeteksi dari data server).
    if (!isResolved) {
      const actions = document.createElement('div');
      actions.className = 'ticket-actions';

      const btnEscalate = document.createElement('button');
      btnEscalate.className = 'btn-action btn-escalate';
      btnEscalate.textContent = 'Escalate';
      btnEscalate.onclick = () => handleEscalate(ticket.id_monitoring, ticket.client_phone, ticket.message_content);

      actions.appendChild(btnEscalate);
      footer.appendChild(actions);
    } else {
      const actions = document.createElement('div');
      actions.className = 'ticket-actions';

      const btnDetail = document.createElement('button');
      btnDetail.className = 'btn-action btn-detail';
      btnDetail.textContent = 'Detail';
      btnDetail.onclick = () => showDetailModal(ticket);

      actions.appendChild(btnDetail);
      footer.appendChild(actions);
    }

    card.appendChild(header);
    card.appendChild(message);
    card.appendChild(footer);

    return card;
  }

  // ==== Modal Detail (Terselesaikan) ====
  function showDetailModal(ticket) {
    const overlay = document.getElementById('detail-modal-overlay');
    if (!overlay) return;

    const finalSeconds = ticket.sla_seconds != null ? ticket.sla_seconds : getElapsedSeconds(ticket.time_received);
    const isLate = finalSeconds >= SLA_THRESHOLD_SECONDS;

    document.getElementById('detail-client').textContent = `+${ticket.client_phone}`;
    document.getElementById('detail-received').textContent = new Date(ticket.time_received).toLocaleString('id-ID');
    document.getElementById('detail-message').textContent = ticket.message_content || '-';

    document.getElementById('detail-responder').textContent = ticket.responded_by_name
      ? ticket.responded_by_name
      : (ticket.responded_by ? `+${ticket.responded_by}` : '-');
    document.getElementById('detail-responded-time').textContent = ticket.time_responded
      ? new Date(ticket.time_responded).toLocaleString('id-ID')
      : '-';
    document.getElementById('detail-response').textContent = ticket.response_content || '(Tidak ada catatan balasan)';

    const durationEl = document.getElementById('detail-duration');
    durationEl.textContent = `${formatDuration(finalSeconds)} ${isLate ? '(Telat)' : '(Tepat Waktu)'}`;
    durationEl.style.color = isLate ? '#f43f5e' : '#10b981';

    overlay.style.display = 'flex';
  }

  function hideDetailModal() {
    const overlay = document.getElementById('detail-modal-overlay');
    if (overlay) overlay.style.display = 'none';
  }
  window.hideDetailModal = hideDetailModal;

  async function handleEscalate(id, phone, msg) {
    if (!window.SLA_API) return;
    const res = await window.SLA_API.escalate(id, `Client ${phone}`, msg);
    if (res && res.status === 'success') {
      alert('Berhasil eskalasi ke grup Telegram!');
    } else {
      alert('Gagal eskalasi komplain');
    }
  }

  function renderList(container, tickets, isResolved) {
    if (!container) return;
    container.replaceChildren();

    if (!tickets || tickets.length === 0) {
      const empty = document.createElement('div');
      empty.className = 'text-center text-muted py-5';
      empty.style.fontSize = '13px';
      empty.textContent = 'Tidak ada data.';
      container.appendChild(empty);
      return;
    }

    tickets.forEach(ticket => {
      container.appendChild(createTicketCard(ticket, isResolved));
    });
  }

  // Update tampilan angka jam berjalan tiap detik, tanpa fetch ulang / render ulang DOM.
  function tickLiveTimers() {
    let lateCount = 0;
    let onTimeCount = 0;

    liveUnresolvedTickets.forEach(ticket => {
      const cardEl = listUnresolved.querySelector(`.ticket-card[data-id="${ticket.id_monitoring}"]`);
      if (!cardEl) return;
      const durationEl = cardEl.querySelector('[data-role="duration"]');
      if (!durationEl) return;

      const elapsed = getElapsedSeconds(ticket.time_received);
      const isLate = elapsed >= SLA_THRESHOLD_SECONDS;
      if (isLate) lateCount++; else onTimeCount++;

      durationEl.innerHTML = `<span class="material-symbols-outlined" style="font-size: 14px">schedule</span> ${formatDuration(elapsed)}`;
      applySlaStyle(durationEl, cardEl, isLate);
    });

    if (statWaiting) statWaiting.textContent = onTimeCount;
    if (statOverdue) statOverdue.textContent = lateCount;

    if (typeof window.updateSlaChart === 'function') {
      window.updateSlaChart(onTimeCount, lateCount, liveResolvedCount);
    }
  }

  let liveResolvedCount = 0;

  async function refreshDashboard() {
    if (!window.SLA_API) {
      console.error("SLA_API not found!");
      return;
    }

    try {
      const [waitingRes, overdueRes, overdueResolvedRes, completedRes] = await Promise.all([
        window.SLA_API.getWaiting(),
        window.SLA_API.getOverdue(),
        window.SLA_API.getOverdueResolved(),
        window.SLA_API.getCompleted()
      ]);

      const resolvedRaw = [
        ...(completedRes?.data || []),
        ...(overdueResolvedRes?.data || [])
      ];

      // ID yang sudah pasti selesai, dipakai untuk membuang tiket "hantu"
      // yang mungkin masih nyangkut di daftar waiting/overdue dari server.
      const resolvedIds = new Set(resolvedRaw.map(t => t.id_monitoring));

      const unresolvedRaw = [
        ...(waitingRes?.data || []),
        ...(overdueRes?.data || [])
      ].filter(t => !resolvedIds.has(t.id_monitoring));

      // Buang duplikat id kalau ada, biar tidak double-render.
      const seenUnresolved = new Set();
      const unresolvedTickets = unresolvedRaw.filter(t => {
        if (seenUnresolved.has(t.id_monitoring)) return false;
        seenUnresolved.add(t.id_monitoring);
        return true;
      });

      const seenResolved = new Set();
      const resolvedTickets = resolvedRaw.filter(t => {
        if (seenResolved.has(t.id_monitoring)) return false;
        seenResolved.add(t.id_monitoring);
        return true;
      });

      // Urutkan: yang paling lama nunggu di atas
      unresolvedTickets.sort((a, b) => new Date(a.time_received) - new Date(b.time_received));
      resolvedTickets.sort((a, b) => new Date(b.time_received) - new Date(a.time_received));

      liveUnresolvedTickets = unresolvedTickets;
      liveResolvedCount = resolvedTickets.length;

      if (countUnresolved) countUnresolved.textContent = unresolvedTickets.length;
      if (countResolved) countResolved.textContent = resolvedTickets.length;
      if (statCompleted) statCompleted.textContent = resolvedTickets.length;

      renderList(listUnresolved, unresolvedTickets, false);
      renderList(listResolved, resolvedTickets, true);

      // Langsung hitung status on-time/late begitu render selesai
      tickLiveTimers();
    } catch (e) {
      console.error("Refresh failed:", e);
    }
  }

  window.addEventListener('DOMContentLoaded', () => {
    refreshDashboard();
    setInterval(refreshDashboard, REFRESH_INTERVAL_MS);
    setInterval(tickLiveTimers, TICK_INTERVAL_MS);
  });
})();