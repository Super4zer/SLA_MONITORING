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
                    <div class="col-xl-6 col-lg-6">
                        <div class="dashboard-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark fs-6">
                                    <span class="dot-indicator bg-waiting"></span> Belum
                                    Direspon
                                </span>
                                <span class="fw-bold text-waiting small" id="count-waiting">0</span>
                            </div>

                            <div class="list-container-scroll" id="list-waiting">
                                <!-- Cards will be injected here -->
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-6 col-lg-6">
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

    <!-- Modal Detail balasan CS (vanilla, tidak butuh Bootstrap JS) -->
    <div id="detail-modal-overlay"
        style="display:none; position:fixed; inset:0; background:rgba(15,15,20,0.55); z-index:1050; align-items:center; justify-content:center;">
        <div
            style="background:#fff; border-radius:14px; width:100%; max-width:480px; margin:16px; padding:24px; box-shadow:0 20px 50px rgba(0,0,0,0.25);">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="m-0 fw-bold text-dark">Detail Komplain</h5>
                <button onclick="hideDetailModal()"
                    style="border:none; background:none; font-size:20px; line-height:1; cursor:pointer;">&times;</button>
            </div>

            <div class="mb-3">
                <div class="text-muted small mb-1">Client</div>
                <div class="fw-semibold" id="detail-client">-</div>
                <div class="text-muted small" id="detail-received">-</div>
            </div>

            <div class="mb-3">
                <div class="text-muted small mb-1">Isi Komplain</div>
                <div id="detail-message" style="white-space:pre-wrap;">-</div>
            </div>

            <hr />

            <div class="mb-3">
                <div class="text-muted small mb-1">Dijawab oleh</div>
                <div class="fw-semibold" id="detail-responder">-</div>
                <div class="text-muted small" id="detail-responded-time">-</div>
            </div>

            <div class="mb-3">
                <div class="text-muted small mb-1">Isi Balasan CS</div>
                <div id="detail-response" style="white-space:pre-wrap;">-</div>
            </div>

            <div>
                <div class="text-muted small mb-1">Durasi SLA</div>
                <div class="fw-bold" id="detail-duration">-</div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.3/purify.min.js"></script>

    <script>
    // 1. Clock (backup, app.js juga mengelola ini)
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
        type: "pie",
        data: {
            labels: ["Menunggu", "Terlambat", "Selesai"],
            datasets: [{
                data: [0, 0, 0],
                backgroundColor: ["#10b981", "#f43f5e", "#10b981"],
                borderWidth: 0,
                hoverOffset: 6,
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

    // 3. Dipanggil langsung dari app.js tiap kali data / timer berubah,
    // jadi tidak perlu lagi MutationObserver.
    window.updateSlaChart = function(onTime, late, completed) {
        slaChart.data.datasets[0].data = [onTime, late, completed];
        slaChart.update();
    };
    </script>

    <script src="/js/api.js"></script>
    <script src="/js/app.js"></script>
</body>

</html>