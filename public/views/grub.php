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

    <style>
    body {
        font-family: "Inter", sans-serif;
        background-color: #f2f3f7;
        color: #1e1e2d;
        overflow: hidden;
        height: 100vh;
    }

    /* ================= SIDEBAR (DARK THEME) ================= */
    .sidebar {
        width: 250px;
        background-color: #1c1c24;
        display: flex;
        flex-direction: column;
        z-index: 1010;
        transition: all 0.3s;
    }

    .sidebar-brand {
        height: 80px;
        display: flex;
        align-items: center;
        padding: 0 24px;
    }

    .sidebar-brand img {
        max-width: 140px;
    }

    .nav-sidebar {
        margin-top: 10px;
    }

    .nav-sidebar .nav-link {
        color: #8b8b99;
        font-weight: 500;
        padding: 12px 24px;
        display: flex;
        align-items: center;
        gap: 14px;
        font-size: 14px;
        border-left: 3px solid transparent;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .nav-sidebar .nav-link:hover {
        color: #ffffff;
        background-color: rgba(255, 255, 255, 0.03);
    }

    /* Gaya Aktif (Neon Green Accent) */
    .nav-sidebar .nav-link.active {
        color: #ffffff;
        border-left: 3px solid #ccff00;
        background: linear-gradient(90deg,
                rgba(204, 255, 0, 0.05) 0%,
                transparent 100%);
        font-weight: 600;
    }

    .nav-sidebar .nav-link.active .material-symbols-outlined {
        color: #ccff00;
    }

    /* Sidebar Footer (User Profile) */
    .sidebar-footer {
        padding: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
    }

    .sidebar-footer .avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background-color: #3b3b45;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: bold;
        font-size: 14px;
    }

    .sidebar-footer .user-info p {
        margin: 0;
        color: #ffffff;
        font-size: 13px;
        font-weight: 600;
    }

    .sidebar-footer .user-info span {
        color: #8b8b99;
        font-size: 11px;
    }

    /* ================= MAIN CONTENT ================= */
    .main-wrapper {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        height: 100vh;
        overflow: hidden;
    }

    .topbar {
        height: 80px;
        background-color: transparent;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 32px;
    }

    .main-content {
        flex-grow: 1;
        overflow-y: auto;
        padding: 0 32px 32px 32px;
    }

    /* ================= CARDS & COMPONENTS ================= */
    .dashboard-card {
        background: #ffffff;
        border: none;
        border-radius: 16px;
        box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.03);
        padding: 24px;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .card-title-custom {
        font-size: 16px;
        font-weight: 600;
        color: #1e1e2d;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Jam Digital */
    .tabular-clock {
        font-variant-numeric: tabular-nums;
        font-weight: 600;
        color: #8b8b99;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* ================= CRUD FORM STYLING ================= */
    .form-label {
        font-weight: 600;
        font-size: 13px;
        color: #1e1e2d;
        margin-bottom: 8px;
    }

    .form-control {
        background-color: #f8f9fc;
        border: 1px solid #e2e2e8;
        border-radius: 10px;
        padding: 12px 16px;
        font-size: 14px;
        color: #1e1e2d;
        transition: all 0.2s;
        box-shadow: none !important;
    }

    .form-control:focus {
        background-color: #ffffff;
        border-color: #1c1c24;
    }

    .btn-primary-custom {
        background-color: #ccff00;
        color: #1c1c24;
        font-weight: 600;
        border-radius: 10px;
        padding: 12px 20px;
        border: none;
        font-size: 14px;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary-custom:hover {
        background-color: #b8e600;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(204, 255, 0, 0.2);
    }

    .btn-light-custom {
        background-color: #f2f3f7;
        color: #1e1e2d;
        font-weight: 500;
        border-radius: 10px;
        padding: 12px 20px;
        border: none;
        font-size: 14px;
        transition: all 0.2s;
    }

    .btn-light-custom:hover {
        background-color: #e2e2e8;
    }

    /* ================= DATA TABLE STYLING ================= */
    .table-container {
        flex-grow: 1;
        overflow-y: auto;
        border-radius: 12px;
        border: 1px solid #f2f3f7;
    }

    .table-container::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    .table-container::-webkit-scrollbar-thumb {
        background: #e2e2e8;
        border-radius: 10px;
    }

    .table {
        margin-bottom: 0;
    }

    .table> :not(caption)>*>* {
        padding: 16px 20px;
        border-bottom: 1px solid #f2f3f7;
        box-shadow: none !important;
        vertical-align: middle;
        font-size: 14px;
    }

    .table thead th {
        background-color: #fafafa;
        color: #8b8b99;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #e2e2e8;
        position: sticky;
        top: 0;
        z-index: 1;
    }

    .table tbody tr {
        transition: background-color 0.2s;
    }

    .table tbody tr:hover {
        background-color: #fcfcfd;
    }

    /* Action Buttons in Table */
    .action-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: transparent;
        transition: all 0.2s;
        cursor: pointer;
    }

    .action-btn.edit {
        color: #f59e0b;
    }

    .action-btn.edit:hover {
        background-color: rgba(245, 158, 11, 0.1);
    }

    .action-btn.delete {
        color: #f43f5e;
    }

    .action-btn.delete:hover {
        background-color: rgba(244, 63, 94, 0.1);
    }

    .action-btn .material-symbols-outlined {
        font-size: 18px;
    }
    </style>
</head>

<body>
    <div class="d-flex h-100 w-100">
        <aside class="sidebar d-none d-lg-flex">
            <div class="sidebar-brand">
                <span class="fw-bold text-white fs-4 d-flex justify-content-center align-items-center">
                    <img src="/images/DSI.png" alt="DSI Logo" width="auto" height="180px" class="mt-3" />
                </span>
            </div>

            <nav class="nav-sidebar flex-grow-1">
                <a href="/dashboard" class="nav-link">
                    <span class="material-symbols-outlined fs-5">grid_view</span>
                    Dashboard
                </a>
                <a href="/grub" class="nav-link active">
                    <span class="material-symbols-outlined fs-5">confirmation_number</span>
                    Tambah Grub
                </a>
                <a href="/laporan" class="nav-link">
                    <span class="material-symbols-outlined fs-5">bar_chart</span>
                    Laporan Kinerja
                </a>

            </nav>

            <div class="sidebar-footer">
                <div class="avatar">
                    <span class="material-symbols-outlined fs-6">person</span>
                </div>
                <div class="user-info">
                    <p>Admin DSI</p>
                    <span>Administrator</span>
                </div>
                <a href="#" class="ms-auto text-secondary"><span
                        class="material-symbols-outlined fs-5">logout</span></a>
            </div>
        </aside>

        <div class="main-wrapper">
            <header class="topbar">
                <div>
                    <h4 class="m-0 fw-bold text-dark">Manajemen Grub Whitelist</h4>
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
                                <a href="/grub" class="nav-link">
                                    <span class="material-symbols-outlined fs-5">confirmation_number</span>
                                    Tambah Grub
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
                                        <span class="material-symbols-outlined fs-6">save</span>
                                        Simpan Data
                                    </button>
                                    <button type="reset" class="btn-light-custom" id="btnReset">
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
                                    Daftar Grub Terdaftar
                                </div>
                                <div class="position-relative">
                                    <input type="text" class="form-control form-control-sm ps-4"
                                        placeholder="Cari grub..." style="width: 250px; border-radius: 8px" />
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
                                            <th width="35%">ID Grub</th>
                                            <th width="40%">Nama Grub</th>
                                            <th width="20%" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="grubTableBody">
                                        <tr>
                                            <td class="fw-medium text-muted">1</td>
                                            <td class="font-monospace text-secondary">
                                                12036304123456@g.us
                                            </td>
                                            <td class="fw-medium">Tim Engineer Utama</td>
                                            <td class="text-center">
                                                <button class="action-btn edit" title="Edit Data">
                                                    <span class="material-symbols-outlined">edit_square</span>
                                                </button>
                                                <button class="action-btn delete" title="Hapus Data">
                                                    <span class="material-symbols-outlined">delete</span>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium text-muted">2</td>
                                            <td class="font-monospace text-secondary">
                                                12036304987654@g.us
                                            </td>
                                            <td class="fw-medium">Notifikasi Server Down</td>
                                            <td class="text-center">
                                                <button class="action-btn edit" title="Edit Data">
                                                    <span class="material-symbols-outlined">edit_square</span>
                                                </button>
                                                <button class="action-btn delete" title="Hapus Data">
                                                    <span class="material-symbols-outlined">delete</span>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

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

   
    </script>
</body>

</html> 