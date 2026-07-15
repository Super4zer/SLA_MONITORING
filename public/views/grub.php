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