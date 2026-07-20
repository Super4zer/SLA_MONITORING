// Modul History "Terselesaikan" - reusable, dipasang di dashboard.php maupun laporan.php.
// Syarat: ada elemen dengan id sesuai HISTORY_IDS di bawah pada halaman yang memasang script ini.
(function () {
  const HISTORY_IDS = {
    container: 'history-list',
    search: 'history-search',
    filterType: 'history-filter-type',   // select: 'all' | 'date' | 'month'
    filterDate: 'history-filter-date',   // input type=date
    filterMonth: 'history-filter-month', // select bulan 1-12
    filterYear: 'history-filter-year',   // input tahun
    btnSearch: 'history-btn-search',
    btnLoadMore: 'history-btn-more',
    btnDelete: 'history-btn-delete',
    deleteMonths: 'history-delete-months', // select 1-12
    status: 'history-status',
  };

  const el = {};
  for (const key in HISTORY_IDS) {
    el[key] = document.getElementById(HISTORY_IDS[key]);
  }

  // Kalau halaman ini tidak punya elemen history sama sekali, jangan jalankan apa-apa.
  if (!el.container) return;

  let currentPage = 1;
  let isLoading = false;
  let hasMore = true;

  function formatDuration(sec) {
    if (sec === null || sec === undefined) return '-';
    const m = Math.floor(sec / 60);
    const s = sec % 60;
    return `${m}m ${s}s`;
  }

  function buildFilterParams(page) {
    const params = { page, search: el.search ? el.search.value.trim() : '' };
    const type = el.filterType ? el.filterType.value : 'all';

    if (type === 'date' && el.filterDate && el.filterDate.value) {
      params.date = el.filterDate.value;
    } else if (type === 'month' && el.filterMonth && el.filterMonth.value) {
      params.month = el.filterMonth.value;
      if (el.filterYear && el.filterYear.value) {
        params.year = el.filterYear.value;
      }
    }
    return params;
  }

  function renderItem(item) {
    const div = document.createElement('div');
    div.className = 'history-item';
    const isLate = item.status_sla === 'MERAH';

    div.innerHTML = `
      <div class="hi-top">
        <span class="hi-group">${item.group_name || item.group_id}</span>
        <span class="status-chip ${isLate ? 'red' : 'green'}">${isLate ? 'Telat' : 'Tepat Waktu'}</span>
      </div>
      <div class="hi-phone">+${item.client_phone}</div>
      <div class="hi-msg">${item.message_content || '-'}</div>
      <div class="hi-meta">
        Agen: ${item.responded_by_name || item.responded_by || '-'} &middot;
        Durasi: ${formatDuration(item.sla_seconds)} &middot;
        ${new Date(item.time_received).toLocaleString('id-ID')}
      </div>
    `;
    return div;
  }

  async function loadHistory(reset = false) {
    if (isLoading) return;
    isLoading = true;

    if (reset) {
      currentPage = 1;
      hasMore = true;
      el.container.replaceChildren();
    }

    if (el.status) el.status.textContent = 'Memuat...';

    const res = await window.SLA_API.getHistoryResolved(buildFilterParams(currentPage));

    isLoading = false;

    if (!res || res.status !== 'success') {
      if (el.status) el.status.textContent = 'Gagal memuat data.';
      return;
    }

    const { data, pagination } = res;

    if (currentPage === 1 && data.length === 0) {
      el.container.innerHTML = '<div class="text-center text-muted py-5" style="font-size:13px">Tidak ada data terselesaikan.</div>';
      if (el.status) el.status.textContent = '';
      hasMore = false;
      if (el.btnLoadMore) el.btnLoadMore.style.display = 'none';
      return;
    }

    data.forEach(item => el.container.appendChild(renderItem(item)));

    hasMore = pagination.page < pagination.total_pages;
    currentPage = pagination.page + 1;

    if (el.status) el.status.textContent = `Menampilkan ${data.length ? ((pagination.page - 1) * pagination.per_page + 1) : 0}-${(pagination.page - 1) * pagination.per_page + data.length} dari ${pagination.total} data`;
    if (el.btnLoadMore) el.btnLoadMore.style.display = hasMore ? 'inline-block' : 'none';
  }

  // ==== Event bindings ====
  if (el.btnSearch) {
    el.btnSearch.addEventListener('click', () => loadHistory(true));
  }
  if (el.search) {
    el.search.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') loadHistory(true);
    });
  }
  if (el.filterType) {
    el.filterType.addEventListener('change', () => {
      const type = el.filterType.value;
      if (el.filterDate) el.filterDate.style.display = type === 'date' ? 'inline-block' : 'none';
      if (el.filterMonth) el.filterMonth.style.display = type === 'month' ? 'inline-block' : 'none';
      if (el.filterYear) el.filterYear.style.display = type === 'month' ? 'inline-block' : 'none';
    });
  }
  if (el.btnLoadMore) {
    el.btnLoadMore.addEventListener('click', () => loadHistory(false));
  }
  if (el.btnDelete) {
    el.btnDelete.addEventListener('click', async () => {
      const months = el.deleteMonths ? parseInt(el.deleteMonths.value, 10) : 0;
      if (!months) return;

      const confirmMsg = `Yakin hapus semua data terselesaikan yang lebih lama dari ${months} bulan? Tindakan ini tidak bisa dibatalkan.`;
      if (!confirm(confirmMsg)) return;

      const res = await window.SLA_API.deleteHistoryResolved(months);
      if (res && res.status === 'success') {
        alert(res.message);
        loadHistory(true);
      } else {
        alert('Gagal menghapus data.');
      }
    });
  }

  // Load pertama kali begitu halaman siap
  window.addEventListener('DOMContentLoaded', () => loadHistory(true));
})();