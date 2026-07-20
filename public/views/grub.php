<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manajemen Grub - SLA Monitoring Command Center</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="/css/grub.css">

    <style>
    /* Custom Notification Panel */
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

    <div class="modal fade" id="editGrubModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-cmd-content">
                <div class="modal-header modal-cmd-header">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined text-warning">edit_square</span> Edit Data Grub
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="editGrubForm">
                    <div class="modal-body p-4">
                        <input type="hidden" id="editId" />
                        <div class="mb-3">
                            <label class="form-label" for="editGrubId">ID Grub</label>
                            <input type="text" class="form-control" id="editGrubId" required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="editGrubName">Nama Grub</label>
                            <input type="text" class="form-control" id="editGrubName" required />
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

    <div class="modal fade" id="deleteGrubModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content modal-cmd-content">
                <div class="modal-body text-center p-4">
                    <span class="material-symbols-outlined text-danger mb-2" style="font-size: 48px;">warning</span>
                    <h6 class="fw-bold text-white mb-2">Hapus Data Grub?</h6>
                    <p class="text-muted small mb-4">Tindakan ini tidak dapat dibatalkan secara permanen.</p>
                    <input type="hidden" id="deleteId" />
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-secondary btn-sm px-3" style="border-radius: 6px;"
                            data-bs-dismiss="modal">Batal</button>
                        <button type="button" id="btnConfirmDelete" class="btn btn-danger btn-sm px-3"
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
                    <h4 class="m-0 fw-bold text-dark">Manajemen Grub Whitelist</h4>
                </div>
                <div class="tabular-clock" id="live-clock"><span class="material-symbols-outlined fs-6">schedule</span>
                    00:00:00</div>
            </header>

            <main class="main-content">
                <div class="row g-4 h-100">
                    <div class="col-lg-4">
                        <div class="dashboard-card">
                            <div class="card-title-custom">
                                <a href="/grub" class="nav-link">
                                    <span class="material-symbols-outlined fs-5">confirmation_number</span> Tambah Grub
                                </a>
                            </div>
                            <form id="grubForm">
                                <input type="hidden" id="formAction" value="create" />
                                <div class="mb-4">
                                    <label class="form-label" for="grubId">ID Grub</label>
                                    <input type="text" class="form-control" id="grubId"
                                        placeholder="Contoh: 12036304XXXXX@g.us" required />
                                    <small class="text-muted mt-1 d-block" style="font-size: 12px">Masukkan ID unik grup
                                        WhatsApp/Telegram.</small>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label" for="grubName">Nama Grub</label>
                                    <input type="text" class="form-control" id="grubName"
                                        placeholder="Contoh: Tim IT Support" required />
                                </div>
                                <div class="d-flex gap-2 mt-2">
                                    <button type="submit" class="btn-primary-custom flex-grow-1 justify-content-center">
                                        <span class="material-symbols-outlined fs-6">save</span> Simpan Data
                                    </button>
                                    <button type="reset" class="btn-light-custom" id="btnReset">Batal</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="dashboard-card pb-0">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="card-title-custom mb-0"><span
                                        class="material-symbols-outlined">list_alt</span> Daftar Grub Terdaftar</div>
                                <div class="position-relative">
                                    <input type="text" class="form-control form-control-sm ps-4"
                                        placeholder="Cari grub..." style="width: 250px; border-radius: 8px" />
                                    <span class="material-symbols-outlined position-absolute"
                                        style="top: 8px; left: 10px; font-size: 18px; color: #a1a1aa;">search</span>
                                </div>
                            </div>
                            <div class="table-container mb-4">
                                <table class="table table-nowrap">
                                    <thead>
                                        <tr>
                                            <th width="5%">No</th>
                                            <th width="35%">ID Grub</th>
                                            <th width="40%">Nama Grub</th>
                                            <th width="20%" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="grubTableBody">
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

    // 3. Global State untuk Cache Data Tabel
    const API_GROUP_URL = '/api/groups';
    const tableBody = document.getElementById('grubTableBody');
    let localGroupsCache = []; // Menyimpan data sementara untuk kemudahan Edit

    // Load Data
    async function loadGroups() {
        tableBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Memuat data...</td></tr>';
        try {
            const response = await fetch(API_GROUP_URL);
            const result = await response.json();

            if (result.status === 'success' && result.data.length > 0) {
                localGroupsCache = result.data; // Simpan di cache lokal
                tableBody.innerHTML = result.data.map((group, index) => `
                    <tr>
                        <td class="fw-medium text-muted">${index + 1}</td>
                        <td class="font-monospace text-secondary">${group.group_id}</td>
                        <td class="fw-medium">${group.group_name}</td>
                        <td class="text-center">
                            <button class="action-btn edit" title="Edit Data" onclick="openEditModal(${group.id})">
                                <span class="material-symbols-outlined">edit_square</span>
                            </button>
                            <button class="action-btn delete" title="Hapus Data" onclick="openDeleteModal(${group.id})">
                                <span class="material-symbols-outlined">delete</span>
                            </button>
                        </td>
                    </tr>
                `).join('');
            } else {
                tableBody.innerHTML =
                    '<tr><td colspan="4" class="text-center text-muted">Belum ada grup yang terdaftar</td></tr>';
            }
        } catch (error) {
            tableBody.innerHTML =
                '<tr><td colspan="4" class="text-center text-danger">Gagal terhubung ke server</td></tr>';
        }
    }

    // 4. Tambah Data (Create)
    document.getElementById('grubForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btnSubmit = e.target.querySelector('button[type="submit"]');
        btnSubmit.disabled = true;

        const payload = {
            group_id: document.getElementById('grubId').value,
            group_name: document.getElementById('grubName').value
        };

        try {
            const response = await fetch(API_GROUP_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const result = await response.json();

            if (result.status === 'success') {
                showCmdNotification('Registrasi Sukses', 'Data grup telah ditambahkan ke database.',
                    'success');
                document.getElementById('grubForm').reset();
                loadGroups();
            } else {
                showCmdNotification('Gagal Menyimpan', result.message, 'error');
            }
        } catch (error) {
            showCmdNotification('Koneksi Terputus', 'Gagal menghubungi server.', 'error');
        } finally {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = `<span class="material-symbols-outlined fs-6">save</span> Simpan Data`;
        }
    });

    // ==========================================
    // 5. LOGIKA TOMBOL EDIT (FORM MODAL & PUT FETCH)
    // ==========================================
    const bsEditModal = new bootstrap.Modal(document.getElementById('editGrubModal'));

    function openEditModal(id) {
        // Cari objek grup di data lokal cache berdasarkan id database
        const targetGroup = localGroupsCache.find(g => g.id == id);
        if (targetGroup) {
            document.getElementById('editId').value = targetGroup.id;
            document.getElementById('editGrubId').value = targetGroup.group_id;
            document.getElementById('editGrubName').value = targetGroup.group_name;
            bsEditModal.show();
        }
    }

    document.getElementById('editGrubForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('editId').value;
        const payload = {
            id: id,
            group_id: document.getElementById('editGrubId').value,
            group_name: document.getElementById('editGrubName').value
        };

        try {
            const response = await fetch('/api/groups/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const result = await response.json();

            if (result.status === 'success') {
                showCmdNotification('Update Sukses', 'Perubahan data grup berhasil diperbarui.', 'success');
                bsEditModal.hide();
                loadGroups();
            } else {
                showCmdNotification('Update Gagal', result.message, 'error');
            }
        } catch (error) {
            showCmdNotification('Error Sistem', 'Gagal melakukan pembaruan.', 'error');
        }
    });

    // ==========================================
    // 6. LOGIKA TOMBOL DELETE (CONFIRM MODAL & POST FETCH)
    // ==========================================
    const bsDeleteModal = new bootstrap.Modal(document.getElementById('deleteGrubModal'));

    function openDeleteModal(id) {
        document.getElementById('deleteId').value = id;
        bsDeleteModal.show();
    }

    document.getElementById('btnConfirmDelete').addEventListener('click', async () => {
        const id = document.getElementById('deleteId').value;

        try {
            const response = await fetch('/api/groups/delete', {
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
                showCmdNotification('Data Dihapus', 'Grup berhasil dihapus dari whitelist.', 'success');
                bsDeleteModal.hide();
                loadGroups();
            } else {
                showCmdNotification('Gagal Hapus', result.message, 'error');
            }
        } catch (error) {
            showCmdNotification('Error Jaringan', 'Gagal memproses penghapusan.', 'error');
        }
    });

    // Jalankan pertama kali halaman dibuka
    document.addEventListener('DOMContentLoaded', loadGroups);
    </script>
</body>

</html>