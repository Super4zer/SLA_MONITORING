<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Agent CS - SLA Monitoring Command Center</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="/css/grub.css">

    <style>
    .cmd-notification {
        position: fixed;
        top: 30px;
        right: -400px;
        width: 320px;
        background-color: #1c1c24;
        border-radius: 12px;
        padding: 16px 20px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        display: flex;
        align-items: center;
        gap: 16px;
        z-index: 9999;
        transition: right 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border-left: 4px solid transparent;
    }

    .cmd-notification.show {
        right: 30px;
    }

    .cmd-notification.success {
        border-left-color: #ccff00;
    }

    .cmd-notification.error {
        border-left-color: #f43f5e;
    }

    .cmd-notif-icon {
        font-size: 28px;
    }

    .cmd-notification.success .cmd-notif-icon {
        color: #ccff00;
    }

    .cmd-notification.error .cmd-notif-icon {
        color: #f43f5e;
    }

    .cmd-notif-content {
        display: flex;
        flex-direction: column;
    }

    .cmd-notif-title {
        color: #ffffff;
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 2px;
    }

    .cmd-notif-msg {
        color: #8b8b99;
        font-size: 12px;
        line-height: 1.4;
    }

    /* Custom Dark Modal Command Center */
    .modal-cmd-content {
        background-color: #1c1c24;
        color: #ffffff;
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .modal-cmd-header {
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        padding: 20px;
    }

    .modal-cmd-footer {
        border-top: 1px solid rgba(255, 255, 255, 0.05);
        padding: 20px;
    }

    .modal-cmd-content .form-control {
        background-color: #282833;
        border: 1px solid #3b3b4d;
        color: #ffffff;
    }

    .modal-cmd-content .form-control:focus {
        background-color: #282833;
        border-color: #ccff00;
        color: #ffffff;
        box-shadow: none;
    }

    .modal-cmd-content .form-label {
        color: #8b8b99;
        font-weight: 500;
    }

    /* Status Pill (Aktif / Nonaktif) */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .status-pill.active {
        background-color: rgba(204, 255, 0, 0.12);
        color: #7a9600;
    }

    .status-pill.inactive {
        background-color: rgba(139, 139, 153, 0.15);
        color: #8b8b99;
    }

    .status-pill .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background-color: currentColor;
    }

    .action-btn.toggle {
        color: #3b82f6;
    }

    .action-btn.toggle:hover {
        background-color: rgba(59, 130, 246, 0.1);
    }

    /* Avatar inisial pada tabel staff */
    .staff-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background-color: #1c1c24;
        color: #ccff00;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 13px;
        flex-shrink: 0;
    }
    </style>
</head>

<body>
    <div id="cmdNotification" class="cmd-notification">
        <span class="material-symbols-outlined cmd-notif-icon" id="cmdNotifIcon">check_circle</span>
        <div class="cmd-notif-content">
            <span class="cmd-notif-title" id="cmdNotifTitle">Berhasil</span>
            <span class="cmd-notif-msg" id="cmdNotifMsg">Pesan di sini.</span>
        </div>
    </div>

    <!-- Modal Edit Staff -->
    <div class="modal fade" id="editStaffModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-cmd-content">
                <div class="modal-header modal-cmd-header">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined text-warning">edit_square</span> Edit Data Agent CS
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="editStaffForm">
                    <div class="modal-body p-4">
                        <input type="hidden" id="editStaffId" />
                        <div class="mb-3">
                            <label class="form-label" for="editStaffPhone">Nomor HP (WhatsApp)</label>
                            <input type="text" class="form-control" id="editStaffPhone" required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="editStaffName">Nama Agent CS</label>
                            <input type="text" class="form-control" id="editStaffName" required />
                        </div>
                    </div>
                    <div class="modal-footer modal-cmd-footer">
                        <button type="button" class="btn btn-secondary px-3" style="border-radius: 8px;"
                            data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4"
                            style="background-color: #ccff00; color: #1c1c24; border: none; font-weight: 600; border-radius: 8px;">Simpan
                            Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade" id="deleteStaffModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content modal-cmd-content">
                <div class="modal-body text-center p-4">
                    <span class="material-symbols-outlined text-danger mb-2" style="font-size: 48px;">warning</span>
                    <h6 class="fw-bold text-white mb-2">Hapus Agent CS?</h6>
                    <p class="text-muted small mb-4">Tindakan ini tidak dapat dibatalkan secara permanen.</p>
                    <input type="hidden" id="deleteStaffId" />
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-secondary btn-sm px-3" style="border-radius: 6px;"
                            data-bs-dismiss="modal">Batal</button>
                        <button type="button" id="btnConfirmDeleteStaff" class="btn btn-danger btn-sm px-3"
                            style="border-radius: 6px; background-color: #f43f5e;">Ya, Hapus</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex h-100 w-100">


        <div class="main-wrapper">
            <header class="topbar">
                <div>
                    <h4 class="m-0 fw-bold text-dark">Manajemen Agent CS</h4>
                </div>
                <div class="tabular-clock" id="live-clock">
                    <span class="material-symbols-outlined fs-6">schedule</span>
                    00:00:00
                </div>
            </header>

            <main class="main-content">
                <div class="row g-4 h-100">
                    <div class="col-lg-4">
                        <div class="dashboard-card">
                            <div class="card-title-custom">
                                <span class="material-symbols-outlined fs-5">support_agent</span>
                                Tambah Agent CS
                            </div>

                            <form id="staffForm">
                                <div class="mb-4">
                                    <label class="form-label" for="staffPhone">Nomor HP (WhatsApp)</label>
                                    <input type="text" class="form-control" id="staffPhone"
                                        placeholder="Contoh: 6281234567890" required />
                                    <small class="text-muted mt-1 d-block" style="font-size: 12px">Gunakan format
                                        62xxxxxxxxxx, tanpa spasi atau tanda +.</small>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label" for="staffName">Nama Agent CS</label>
                                    <input type="text" class="form-control" id="staffName"
                                        placeholder="Contoh: CS Ridho" required />
                                </div>

                                <div class="d-flex gap-2 mt-2">
                                    <button type="submit" class="btn-primary-custom flex-grow-1 justify-content-center">
                                        <span class="material-symbols-outlined fs-6">save</span>
                                        Simpan Data
                                    </button>
                                    <button type="reset" class="btn-light-custom" id="btnResetStaff">
                                        Batal
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="dashboard-card pb-0">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="card-title-custom mb-0">
                                    <span class="material-symbols-outlined">list_alt</span>
                                    Daftar Agent CS Terdaftar
                                </div>
                                <div class="position-relative">
                                    <input type="text" id="staffSearchInput" class="form-control form-control-sm ps-4"
                                        placeholder="Cari nama atau nomor HP..."
                                        style="width: 250px; border-radius: 8px" />
                                    <span class="material-symbols-outlined position-absolute" style="
                        top: 8px;
                        left: 10px;
                        font-size: 18px;
                        color: #a1a1aa;
                      ">search</span>
                                </div>
                            </div>

                            <div class="table-container mb-4">
                                <table class="table table-nowrap">
                                    <thead>
                                        <tr>
                                            <th width="5%">No</th>
                                            <th width="35%">Agent CS</th>
                                            <th width="25%">Nomor HP</th>
                                            <th width="15%">Status</th>
                                            <th width="20%" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="staffTableBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // 1. Live Clock
    setInterval(() => {
        const now = new Date();
        const time = now.toLocaleTimeString("id-ID", {
            hour12: false
        });
        document.getElementById("live-clock").innerHTML =
            `<span class="material-symbols-outlined fs-6">schedule</span> ${time}`;
    }, 1000);

    // 2. Custom Notification
    let notifTimeout;

    function showCmdNotification(title, message, type = 'success') {
        const notifBox = document.getElementById('cmdNotification');
        const notifIcon = document.getElementById('cmdNotifIcon');
        const notifTitle = document.getElementById('cmdNotifTitle');
        const notifMsg = document.getElementById('cmdNotifMsg');

        notifBox.className = 'cmd-notification';
        clearTimeout(notifTimeout);

        if (type === 'success') {
            notifBox.classList.add('success');
            notifIcon.textContent = 'check_circle';
        } else {
            notifBox.classList.add('error');
            notifIcon.textContent = 'error';
        }

        notifTitle.textContent = title;
        notifMsg.textContent = message;

        setTimeout(() => {
            notifBox.classList.add('show');
        }, 50);
        notifTimeout = setTimeout(() => {
            notifBox.classList.remove('show');
        }, 3500);
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    function getInitials(name) {
        if (!name) return '?';
        const parts = name.trim().split(/\s+/);
        return (parts[0]?. [0] || '') + (parts.length > 1 ? parts[parts.length - 1][0] : '');
    }

    // 3. Global State
    const API_STAFF_URL = '/api/staff';
    const tableBody = document.getElementById('staffTableBody');
    let localStaffCache = []; // cache lokal untuk edit/delete tanpa fetch ulang

    function renderStaffRows(staffList) {
        if (!staffList || staffList.length === 0) {
            tableBody.innerHTML =
                '<tr><td colspan="5" class="text-center text-muted">Tidak ada agent CS yang cocok</td></tr>';
            return;
        }

        tableBody.innerHTML = staffList.map((staff, index) => {
            const isActive = Number(staff.is_active) === 1;
            return `
                <tr>
                    <td class="fw-medium text-muted">${index + 1}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="staff-avatar">${escapeHtml(getInitials(staff.staff_name)).toUpperCase()}</span>
                            <span class="fw-medium">${escapeHtml(staff.staff_name)}</span>
                        </div>
                    </td>
                    <td class="font-monospace text-secondary">${escapeHtml(staff.phone_number)}</td>
                    <td>
                        <span class="status-pill ${isActive ? 'active' : 'inactive'}">
                            <span class="dot"></span> ${isActive ? 'Aktif' : 'Nonaktif'}
                        </span>
                    </td>
                    <td class="text-center">
                        <button class="action-btn toggle" title="${isActive ? 'Nonaktifkan' : 'Aktifkan'}" onclick="toggleStaffStatus(${staff.id}, ${isActive ? 0 : 1})">
                            <span class="material-symbols-outlined">${isActive ? 'toggle_on' : 'toggle_off'}</span>
                        </button>
                        <button class="action-btn edit" title="Edit Data" onclick="openEditStaffModal(${staff.id})">
                            <span class="material-symbols-outlined">edit_square</span>
                        </button>
                        <button class="action-btn delete" title="Hapus Data" onclick="openDeleteStaffModal(${staff.id})">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // Load Data
    async function loadStaff(keyword = '') {
        tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Memuat data...</td></tr>';
        try {
            const url = keyword ? `${API_STAFF_URL}?q=${encodeURIComponent(keyword)}` : API_STAFF_URL;
            const response = await fetch(url);
            const result = await response.json();

            if (result.status === 'success') {
                if (!keyword) {
                    localStaffCache = result.data; // simpan cache lengkap hanya saat tanpa filter
                }
                renderStaffRows(result.data);
            } else {
                tableBody.innerHTML =
                    '<tr><td colspan="5" class="text-center text-danger">Gagal memuat data</td></tr>';
            }
        } catch (error) {
            tableBody.innerHTML =
                '<tr><td colspan="5" class="text-center text-danger">Gagal terhubung ke server</td></tr>';
        }
    }

    // 4. Fitur Pencarian Agent CS (client-side, langsung ke API agar konsisten dgn data terbaru)
    let searchDebounce;
    document.getElementById('staffSearchInput').addEventListener('input', (e) => {
        clearTimeout(searchDebounce);
        const keyword = e.target.value.trim();
        searchDebounce = setTimeout(() => loadStaff(keyword), 300);
    });

    // 5. Tambah Data (Create)
    document.getElementById('staffForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btnSubmit = e.target.querySelector('button[type="submit"]');
        btnSubmit.disabled = true;

        const payload = {
            phone_number: document.getElementById('staffPhone').value,
            staff_name: document.getElementById('staffName').value
        };

        try {
            const response = await fetch(API_STAFF_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const result = await response.json();

            if (result.status === 'success') {
                showCmdNotification('Registrasi Sukses', 'Agent CS telah ditambahkan ke database.',
                    'success');
                document.getElementById('staffForm').reset();
                document.getElementById('staffSearchInput').value = '';
                loadStaff();
            } else {
                showCmdNotification('Gagal Menyimpan', result.message, 'error');
            }
        } catch (error) {
            showCmdNotification('Koneksi Terputus', 'Gagal menghubungi server.', 'error');
        } finally {
            btnSubmit.disabled = false;
        }
    });

    // ==========================================
    // 6. LOGIKA TOMBOL EDIT
    // ==========================================
    const bsEditStaffModal = new bootstrap.Modal(document.getElementById('editStaffModal'));

    function openEditStaffModal(id) {
        const target = localStaffCache.find(s => s.id == id);
        if (target) {
            document.getElementById('editStaffId').value = target.id;
            document.getElementById('editStaffPhone').value = target.phone_number;
            document.getElementById('editStaffName').value = target.staff_name;
            bsEditStaffModal.show();
        }
    }

    document.getElementById('editStaffForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('editStaffId').value;
        const payload = {
            id: id,
            phone_number: document.getElementById('editStaffPhone').value,
            staff_name: document.getElementById('editStaffName').value
        };

        try {
            const response = await fetch('/api/staff/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const result = await response.json();

            if (result.status === 'success') {
                showCmdNotification('Update Sukses', 'Data agent CS berhasil diperbarui.', 'success');
                bsEditStaffModal.hide();
                loadStaff(document.getElementById('staffSearchInput').value.trim());
            } else {
                showCmdNotification('Update Gagal', result.message, 'error');
            }
        } catch (error) {
            showCmdNotification('Error Sistem', 'Gagal melakukan pembaruan.', 'error');
        }
    });

    // ==========================================
    // 7. LOGIKA TOGGLE AKTIF / NONAKTIF
    // ==========================================
    async function toggleStaffStatus(id, nextState) {
        try {
            const response = await fetch('/api/staff/toggle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: id,
                    is_active: nextState
                })
            });
            const result = await response.json();

            if (result.status === 'success') {
                showCmdNotification('Status Diperbarui', result.message, 'success');
                loadStaff(document.getElementById('staffSearchInput').value.trim());
            } else {
                showCmdNotification('Gagal Mengubah Status', result.message, 'error');
            }
        } catch (error) {
            showCmdNotification('Error Jaringan', 'Gagal memproses perubahan status.', 'error');
        }
    }

    // ==========================================
    // 8. LOGIKA TOMBOL DELETE
    // ==========================================
    const bsDeleteStaffModal = new bootstrap.Modal(document.getElementById('deleteStaffModal'));

    function openDeleteStaffModal(id) {
        document.getElementById('deleteStaffId').value = id;
        bsDeleteStaffModal.show();
    }

    document.getElementById('btnConfirmDeleteStaff').addEventListener('click', async () => {
        const id = document.getElementById('deleteStaffId').value;

        try {
            const response = await fetch('/api/staff/delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: id
                })
            });
            const result = await response.json();

            if (result.status === 'success') {
                showCmdNotification('Data Dihapus', 'Agent CS berhasil dihapus dari whitelist.', 'success');
                bsDeleteStaffModal.hide();
                loadStaff(document.getElementById('staffSearchInput').value.trim());
            } else {
                showCmdNotification('Gagal Hapus', result.message, 'error');
            }
        } catch (error) {
            showCmdNotification('Error Jaringan', 'Gagal memproses penghapusan.', 'error');
        }
    });

    // Jalankan pertama kali halaman dibuka
    document.addEventListener('DOMContentLoaded', () => loadStaff());
    </script>
</body>

</html>