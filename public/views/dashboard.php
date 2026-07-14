<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SLA Monitoring Command Center</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="/css/style.css" />
</head>

<body>
    <div class="d-flex h-100 w-100">
        <aside class="sidebar d-none d-lg-flex">
            <div class="sidebar-brand">
                <span class="fw-bold text-white fs-4 d-flex justify-content-center align-items-center">
                    <img src="/images/DSI.png" alt="" width="auto" height="180px" class="mt-3" />
                </span>
            </div>

            <nav class="nav-sidebar flex-grow-1">
                <a href="/dashboard" class="nav-link active">
                    <span class="material-symbols-outlined fs-5">grid_view</span>
                    Dashboard
                </a>
                <a href="/grub" class="nav-link">
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
                    <h4 class="m-0 fw-bold text-dark">Dashboard</h4>
                </div>
                <div class="tabular-clock" id="live-clock">
                    <span class="material-symbols-outlined fs-6">schedule</span>
                    00:00:00
                </div>
            </header>

            <main class="main-content">
                <div class="row g-4 mb-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="dashboard-card align-items-center justify-content-center p-3">
                            <div style="position: relative; height: 130px; width: 100%">
                                <canvas id="slaChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="dashboard-card justify-content-center">
                            <div class="stat-header">
                                <h6 class="stat-title">Menunggu Balasan</h6>
                                <div class="stat-icon-small bg-warning bg-opacity-10 text-waiting">
                                    <span class="material-symbols-outlined fs-6">hourglass_empty</span>
                                </div>
                            </div>
                            <h2 class="stat-value" id="stat-waiting">0</h2>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="dashboard-card justify-content-center">
                            <div class="stat-header">
                                <h6 class="stat-title">Terlambat Respon</h6>
                                <div class="stat-icon-small bg-danger bg-opacity-10 text-overdue">
                                    <span class="material-symbols-outlined fs-6">warning</span>
                                </div>
                            </div>
                            <h2 class="stat-value" id="stat-overdue">0</h2>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="dashboard-card justify-content-center">
                            <div class="stat-header">
                                <h6 class="stat-title">Selesai Hari Ini</h6>
                                <div class="stat-icon-small bg-success bg-opacity-10 text-completed">
                                    <span class="material-symbols-outlined fs-6">check_circle</span>
                                </div>
                            </div>
                            <h2 class="stat-value" id="stat-completed">0</h2>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="dashboard-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark fs-6">
                                    <span class="dot-indicator bg-waiting"></span> Belum
                                    direspon
                                </span>
                                <span class="fw-bold text-waiting small" id="count-waiting">0</span>
                            </div>

                            <div class="list-container-scroll" id="list-waiting">
                                <!-- Cards will be injected here -->
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="dashboard-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark fs-6">
                                    <span class="dot-indicator bg-overdue"></span> Melewati
                                    Batas 3 Menit
                                </span>
                                <span class="fw-bold text-overdue small" id="count-overdue">0</span>
                            </div>

                            <div class="list-container-scroll" id="list-overdue">
                                <!-- Cards will be injected here -->
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="dashboard-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark fs-6">
                                    <span class="dot-indicator bg-completed"></span>
                                    Terselesaikan
                                </span>
                                <span class="fw-bold text-completed small" id="count-completed">0</span>
                            </div>

                            <div class="list-container-scroll" id="list-completed">
                                <!-- Cards will be injected here -->
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.3/purify.min.js"></script>

    <script>
    // 1. Clock
    if (!document.getElementById("live-clock").textContent.includes(":")) {
        setInterval(() => {
            const now = new Date();
            const time = now.toLocaleTimeString("id-ID", {
                hour12: false
            });
            document.getElementById("live-clock").innerHTML =
                `<span class="material-symbols-outlined fs-6">schedule</span> ${time}`;
        }, 1000);
    }

    // 2. Chart.js Inisialisasi (Lingkaran Sempurna)
    const ctx = document.getElementById("slaChart").getContext("2d");
    const slaChart = new Chart(ctx, {
        type: "pie", // Diubah menjadi pie untuk lingkaran sempurna
        data: {
            labels: ["Menunggu", "Terlambat", "Selesai"],
            datasets: [{
                data: [0, 0, 0],
                backgroundColor: ["#f59e0b", "#f43f5e", "#10b981"],
                borderWidth: 0, // Menghilangkan border agar tampilan sangat bersih
                hoverOffset: 6, // Memberikan sedikit efek "pop-out" saat di-hover
            }, ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: "#1c1c24",
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: false,
                },
            },
        },
    });

    // 3. Sinkronisasi Observer
    const updateAnalytics = () => {
        const w =
            parseInt(document.getElementById("count-waiting").textContent) || 0;
        const o =
            parseInt(document.getElementById("count-overdue").textContent) || 0;
        const c =
            parseInt(document.getElementById("count-completed").textContent) || 0;

        document.getElementById("stat-waiting").textContent = w;
        document.getElementById("stat-overdue").textContent = o;
        document.getElementById("stat-completed").textContent = c;

        slaChart.data.datasets[0].data = [w, o, c];
        slaChart.update();
    };

    const observer = new MutationObserver(updateAnalytics);
    const config = {
        characterData: true,
        childList: true,
        subtree: true
    };
    observer.observe(document.getElementById("count-waiting"), config);
    observer.observe(document.getElementById("count-overdue"), config);
    observer.observe(document.getElementById("count-completed"), config);
    </script>

    <script src="/js/api.js"></script>
    <script src="/js/app.js"></script>
</body>

</html>