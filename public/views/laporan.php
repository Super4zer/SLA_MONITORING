<?php
if (getenv('DB_HOST') === false) {
    $envPath = dirname(__DIR__, 2) . '/.env';
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
                continue;
            }
            list($key, $value) = array_map('trim', explode('=', $line, 2));
            if (getenv($key) === false) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
            }
        }
    }
}

$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_PORT = getenv('DB_PORT') ?: '3306';
$DB_NAME = getenv('DB_NAME') ?: 'sla_monitoring';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    if (isset($_GET['action'])) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Koneksi database gagal: ' . $e->getMessage()]);
        exit;
    }
    die('Koneksi database gagal: ' . $e->getMessage());
}


if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    $action = $_GET['action'];

    if ($action === 'range') {
        $view = $_GET['view'] ?? 'month';
        $refDate = new DateTime($_GET['date'] ?? 'now');

        if ($view === 'week') {
            $start = clone $refDate;
            $start->modify('monday this week');
            $end = clone $start;
            $end->modify('+6 days');
        } elseif ($view === 'year') {
            $start = new DateTime($refDate->format('Y') . '-01-01');
            $end = new DateTime($refDate->format('Y') . '-12-31');
        } else { // month
            $start = new DateTime($refDate->format('Y-m-01'));
            $end = clone $start;
            $end->modify('last day of this month');
        }

        $sql = "SELECT
                      DATE(time_received) AS tgl,
                      SUM(CASE WHEN status_sla = 'MERAH' THEN 1 ELSE 0 END) AS red,
                      SUM(CASE WHEN status_sla = 'HIJAU' THEN 1 ELSE 0 END) AS green
                  FROM ts_sla_monitoring
                  WHERE DATE(time_received) BETWEEN :start AND :end
                  GROUP BY DATE(time_received)
                  ORDER BY tgl ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':start' => $start->format('Y-m-d'),
            ':end' => $end->format('Y-m-d'),
        ]);

        $rows = $stmt->fetchAll();
        $data = [];
        foreach ($rows as $r) {
            $data[$r['tgl']] = [
                'red' => (int) $r['red'],
                'green' => (int) $r['green'],
                'total' => (int) $r['red'] + (int) $r['green'],
            ];
        }

        echo json_encode([
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'data' => $data,
        ]);
        exit;
    }

    if ($action === 'detail') {
        $date = $_GET['date'] ?? date('Y-m-d');

        $sql = "SELECT
                      m.client_phone,
                      m.message_content,
                      m.time_received,
                      m.time_responded,
                      m.sla_seconds,
                      m.status_sla,
                      m.responded_by,
                      s.staff_name
                  FROM ts_sla_monitoring m
                  LEFT JOIN cs_staff_whitelist s ON s.phone_number = m.responded_by
                  WHERE DATE(m.time_received) = :date
                  ORDER BY m.time_received ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':date' => $date]);
        $rows = $stmt->fetchAll();

        $items = array_map(function ($r) {
            return [
                'phone' => $r['client_phone'],
                'msg' => $r['message_content'] !== '' ? $r['message_content'] : '(pesan kosong)',
                'received' => $r['time_received'],
                'responded' => $r['time_responded'],
                'seconds' => $r['sla_seconds'] !== null ? (int) $r['sla_seconds'] : null,
                'status' => $r['status_sla'] === 'MERAH' ? 'red' : ($r['status_sla'] === 'HIJAU' ? 'green' : 'yellow'),
                'staff' => $r['staff_name'] ?? '-',
            ];
        }, $rows);

        echo json_encode(['date' => $date, 'items' => $items]);
        exit;
    }

  if ($action === 'delete') {
        // Mengambil parameter dari URL (GET), bukan dari payload JSON (POST)
        $months = isset($_GET['months']) ? (int) $_GET['months'] : 0;

        if ($months < 1 || $months > 12) {
            echo json_encode(['error' => 'Pilih rentang 1-12 bulan']);
            exit;
        }

        try {
            // Kueri ini akan mengeksekusi penghapusan seluruh data
            // yang waktu masuknya (time_received) lebih lama dari X bulan ke belakang.
            $stmt = $pdo->prepare("
                DELETE FROM ts_sla_monitoring
                WHERE time_received < (NOW() - INTERVAL :months MONTH)
            ");
            $stmt->bindValue(':months', $months, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode(['deleted' => $stmt->rowCount()]);
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Gagal menghapus data: ' . $e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['error' => 'Aksi tidak dikenal']);
    exit;
}

// Aksi hapus log (mutasi data) hanya boleh lewat POST, terpisah dari
// pembacaan data lewat GET di atas supaya tidak bisa dipicu cuma dengan
// membuka URL. Mendukung banyak tanggal/bulan sekaligus (multi-select
// langsung dari kalender di frontend).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  header('Content-Type: application/json');

  $period = $_POST['period'] ?? '';
  $valuesRaw = $_POST['values'] ?? '';
  $values = array_values(array_filter(array_map('trim', explode(',', $valuesRaw)), fn($v) => $v !== ''));

  $where = buildDeleteWhereClause($period, $values);
  if ($where === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Periode atau nilai tidak valid']);
    exit;
  }

  $stmt = $pdo->prepare("DELETE FROM ts_sla_monitoring WHERE {$where['sql']}");
  $stmt->execute($where['params']);

  echo json_encode([
    'success' => true,
    'deleted' => $stmt->rowCount(),
  ]);
  exit;
}

/**
 * Menyusun klausa WHERE (beserta parameter binding) untuk hapus log
 * berdasarkan periode (day/month/year) dan daftar nilainya (bisa lebih dari
 * satu, karena user bisa centang beberapa hari/bulan sekaligus di
 * kalender). Validasi format ketat supaya tidak ada input asal-asalan yang
 * lolos ke query.
 */
function buildDeleteWhereClause(string $period, array $values): ?array
{
  if (empty($values)) {
    return null;
  }

  [$column, $pattern] = match ($period) {
    'day' => ['DATE(time_received)', '/^\d{4}-\d{2}-\d{2}$/'],
    'month' => ["DATE_FORMAT(time_received, '%Y-%m')", '/^\d{4}-\d{2}$/'],
    'year' => ['YEAR(time_received)', '/^\d{4}$/'],
    default => [null, null],
  };

  if ($column === null) {
    return null;
  }

  $placeholders = [];
  $params = [];
  foreach ($values as $i => $value) {
    if (!preg_match($pattern, $value)) {
      return null;
    }
    $ph = ":val{$i}";
    $placeholders[] = $ph;
    $params[$ph] = $value;
  }

  return [
    'sql' => "{$column} IN (" . implode(', ', $placeholders) . ")",
    'params' => $params,
  ];
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Log Audit - SLA Monitoring Command Center</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="/css/laporan.css" />

    <style>
    /* Custom Notification Panel (disamakan dengan grub.php) */
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

    /* ==========================================================
       RAPIKAN LAYOUT (tidak mengubah warna, hanya susunan/spacing)
       ========================================================== */

    /* Header halaman */
    .topbar .m-0.text-secondary {
        margin-top: 2px !important;
    }

    /* Bar kontrol: dijadikan satu "toolbar card" biar rapi & tidak nempel */
    .toolbar-card {
        background: #ffffff;
        border-radius: 14px;
        padding: 14px 18px;
        margin-bottom: 24px;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
    }

    .toolbar-left {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 14px;
    }

    .toolbar-right {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 14px;
        margin-left: auto;
    }

    /* Legend dirapikan jadi satu grup dengan pemisah tipis */
    .legend-group {
        display: flex;
        align-items: center;
        gap: 16px;
        padding-right: 14px;
        border-right: 1px solid #ecebf1;
    }

    @media (max-width: 991.98px) {
        .legend-group {
            border-right: none;
            padding-right: 0;
        }
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12.5px;
        color: #6b6b78;
        white-space: nowrap;
    }

    /* Zona berbahaya (hapus data lama) dipisahkan visual agar tidak tertukar aksi biasa */
    .danger-zone {
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(244, 63, 94, 0.06);
        border: 1px solid rgba(244, 63, 94, 0.18);
        padding: 6px 10px;
        border-radius: 10px;
    }

    .danger-zone-label {
        font-size: 11.5px;
        font-weight: 600;
        color: #f43f5e;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        white-space: nowrap;
        padding-left: 2px;
    }

    .danger-zone #delete-months {
        border-radius: 8px;
        font-size: 13px;
    }

    .danger-zone #btn-delete-old {
        font-weight: 600;
        border: 1px solid rgba(244, 63, 94, 0.35) !important;
    }

    /* Kartu kalender & panel detail: judul section lebih tegas */
    .section-card-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #ecebf1;
    }

    .section-card-title h6 {
        margin: 0;
        font-weight: 700;
        font-size: 15px;
        color: #1c1c24;
    }

    /* Panel detail: beri tinggi konsisten & scroll agar tidak "mendorong" layout */
    #detail-area {
        max-height: 560px;
        overflow-y: auto;
        padding-right: 4px;
    }

    #detail-area::-webkit-scrollbar {
        width: 6px;
    }

    #detail-area::-webkit-scrollbar-thumb {
        background: #e2e2ea;
        border-radius: 6px;
    }

    /* Responsif: di layar kecil, toolbar kanan full width & rapi ke bawah */
    @media (max-width: 767.98px) {
        .toolbar-card {
            flex-direction: column;
            align-items: stretch;
        }

        .toolbar-left,
        .toolbar-right {
            width: 100%;
            justify-content: flex-start;
        }

        .danger-zone {
            width: 100%;
            justify-content: space-between;
        }
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

    <div class="d-flex h-100 w-100">
        <div class="main-wrapper">
            <header class="topbar">
                <div>
                    <h4 class="m-0 fw-bold text-dark">Log Audit Chat</h4>
                    <p class="m-0 text-secondary" style="font-size: 13px">
                        Rekap kepatuhan SLA respon chat CS (live dari database)
                    </p>
                </div>
                <div class="tabular-clock" id="live-clock">
                    <span class="material-symbols-outlined fs-6">schedule</span>
                    00:00:00
                </div>
            </header>

            <main class="main-content">
                <!-- STAT CARDS -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="dashboard-card">
                            <div class="stat-header">
                                <h6 class="stat-title">Total Chat</h6>
                                <div class="stat-icon-small" style="background: rgba(28,28,36,0.06); color:#1c1c24;">
                                    <span class="material-symbols-outlined fs-6">forum</span>
                                </div>
                            </div>
                            <h2 class="stat-value" id="stat-total">0</h2>
                            <div class="stat-sub" id="stat-total-sub">pada periode terpilih</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="dashboard-card">
                            <div class="stat-header">
                                <h6 class="stat-title">Tepat Waktu</h6>
                                <div class="stat-icon-small bg-success bg-opacity-10 text-completed">
                                    <span class="material-symbols-outlined fs-6">check_circle</span>
                                </div>
                            </div>
                            <h2 class="stat-value text-completed" id="stat-green">0</h2>
                            <div class="stat-sub">respon &le; 3 menit</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="dashboard-card">
                            <div class="stat-header">
                                <h6 class="stat-title">Terlambat</h6>
                                <div class="stat-icon-small bg-danger bg-opacity-10 text-overdue">
                                    <span class="material-symbols-outlined fs-6">warning</span>
                                </div>
                            </div>
                            <h2 class="stat-value text-overdue" id="stat-red">0</h2>
                            <div class="stat-sub">respon &gt; 3 menit</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="dashboard-card">
                            <div class="stat-header">
                                <h6 class="stat-title">SLA Compliance</h6>
                                <div class="stat-icon-small" style="background: rgba(204,255,0,0.15); color:#8a9c00;">
                                    <span class="material-symbols-outlined fs-6">verified</span>
                                </div>
                            </div>
                            <h2 class="stat-value" id="stat-compliance">0%</h2>
                            <div class="stat-sub">tepat waktu / total chat</div>
                        </div>
                    </div>
                </div>

                <!-- CONTROL BAR -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                    <div class="view-switch">
                        <button data-view="week" class="active">Minggu</button>
                        <button data-view="month">Bulan</button>
                        <button data-view="year">Tahun</button>
                    </div>

                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div class="d-flex gap-3">
                            <div class="legend-item"><span class="dot-indicator bg-completed"></span> Tepat waktu</div>
                            <div class="legend-item"><span class="dot-indicator bg-overdue"></span> Terlambat</div>

                        </div>

                        <div class="range-nav">
                            <button id="btn-prev"><span
                                    class="material-symbols-outlined fs-6">chevron_left</span></button>
                            <span class="range-label" id="range-label">-</span>
                            <button id="btn-next"><span
                                    class="material-symbols-outlined fs-6">chevron_right</span></button>
                        </div>

                        <button class="btn-today" id="btn-today">Hari ini</button>
                        <button class="btn-hapus-chat" id="btn-toggle-delete">
                            <span class="material-symbols-outlined fs-6">delete</span>
                            Hapus Chat
                        </button>
                    </div>
                </div>

                <!-- BAR SELEKSI HAPUS (muncul hanya saat mode hapus aktif) -->
                <div class="selection-bar d-none" id="selection-bar">
                    <div class="d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined fs-6">touch_app</span>
                        <span id="selection-count">Pilih hari/bulan pada kalender untuk dihapus</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn-selection-cancel" id="btn-selection-cancel">Batal</button>
                        <button type="button" class="btn-selection-delete" id="btn-selection-delete" disabled>
                            <span class="material-symbols-outlined fs-6">delete_forever</span>
                            Hapus
                        </button>
                    </div>

                    <div class="toolbar-right">
                        <div class="legend-group">
                            <div class="legend-item"><span class="dot-indicator bg-completed"></span> Tepat waktu
                            </div>
                            <div class="legend-item"><span class="dot-indicator bg-overdue"></span> Terlambat &gt;3
                                menit</div>
                        </div>

                        <div class="danger-zone">
                            <span class="danger-zone-label">Hapus data</span>
                            <select id="delete-months" class="form-select form-select-sm" style="width: auto;">
                                <option value="1">&gt; 1 bulan</option>
                                <option value="3">&gt; 3 bulan</option>
                                <option value="6">&gt; 6 bulan</option>
                                <option value="12">&gt; 12 bulan</option>
                            </select>
                            <button id="btn-delete-old" class="btn-today" style="color:#f43f5e;">Hapus</button>
                        </div>
                    </div>
                </div>

                <!-- CALENDAR + DETAIL -->
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="dashboard-card">
                            <div class="section-card-title">
                                <h6>Kalender SLA</h6>
                            </div>
                            <div id="calendar-area">
                                <div class="text-center text-secondary py-5">Memuat data...</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="dashboard-card">
                            <div class="section-card-title">
                                <h6 id="detail-title">Detail Hari</h6>
                            </div>
                            <div id="detail-area">
                                <div class="detail-empty">
                                    <span class="material-symbols-outlined">touch_app</span>
                                    <div style="font-size: 13px">Pilih salah satu tanggal untuk melihat detail log chat.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- MODAL KONFIRMASI HAPUS -->
    <div class="modal-overlay d-none" id="delete-confirm-modal">
        <div class="modal-box">
            <div class="modal-icon">
                <span class="material-symbols-outlined">warning</span>
            </div>
            <h6 class="fw-bold mb-2">Hapus Log Chat?</h6>
            <p id="delete-confirm-text" class="mb-4"></p>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn-selection-cancel" id="delete-confirm-no">Batal</button>
                <button type="button" class="btn-selection-delete" id="delete-confirm-yes">
                    <span class="material-symbols-outlined fs-6">delete_forever</span>
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>

    <script>
    /* ========================================================
      1. CLOCK
    ======================================================== */
    setInterval(() => {
        const now = new Date();
        const time = now.toLocaleTimeString("id-ID", {
            hour12: false
        });
        document.getElementById("live-clock").innerHTML =
            `<span class="material-symbols-outlined fs-6">schedule</span> ${time}`;
    }, 1000);

    /* ========================================================
      1b. CUSTOM NOTIFICATION (disamakan dengan grub.php)
    ======================================================== */
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

    /* ========================================================
      2. KONSTANTA & HELPER
    ======================================================== */
    const DAY_NAMES = ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];
    const MONTH_NAMES = [
        "Januari", "Februari", "Maret", "April", "Mei", "Juni",
        "Juli", "Agustus", "September", "Oktober", "November", "Desember"
    ];

    function pad(n) {
        return n.toString().padStart(2, "0");
    }

    function dateKey(d) {
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    }

    function startOfWeek(d) {
        const copy = new Date(d);
        const day = (copy.getDay() + 6) % 7; // Senin = 0
        copy.setDate(copy.getDate() - day);
        copy.setHours(0, 0, 0, 0);
        return copy;
    }

    function formatDuration(sec) {
        if (sec === null) return "-";
        if (sec < 60) return `${sec} detik`;
        const m = Math.floor(sec / 60);
        const s = sec % 60;
        return `${m} menit ${s} detik`;
    }

    /* ========================================================
      3. STATE
    ======================================================== */
    let currentView = "week"; // week | month | year
    let refDate = new Date();
    let selectedDate = null;
    let rangeCache = {}; // key tanggal -> {red, green, total}

    /* ========================================================
      4. FETCH DATA DARI SERVER (endpoint action=range)
    ======================================================== */
    async function fetchRange(view, date) {
        const res = await fetch(`?action=range&view=${view}&date=${dateKey(date)}`);
        const json = await res.json();
        if (json.error) {
            console.error(json.error);
            return {};
        }
        return json.data || {};
    }

    async function fetchDetail(date) {
        const res = await fetch(`?action=detail&date=${date}`);
        return await res.json();
    }

    function getDay(dateStr) {
        return rangeCache[dateStr] || {
            red: 0,
            green: 0,
            total: 0
        };
    }

    function renderStats(entries, subLabel) {
        let total = 0,
            green = 0,
            red = 0;
        entries.forEach(e => {
            total += e.total;
            green += e.green;
            red += e.red;
        });
        const compliance = total ? Math.round((green / total) * 100) : 0;
        document.getElementById("stat-total").textContent = total;
        document.getElementById("stat-green").textContent = green;
        document.getElementById("stat-red").textContent = red;
        document.getElementById("stat-compliance").textContent = compliance + "%";
        document.getElementById("stat-total-sub").textContent = subLabel;
    }

    /* ========================================================
      5. RENDER: WEEK VIEW
    ======================================================== */
    async function renderWeek() {
        const start = startOfWeek(refDate);
        const days = [];
        for (let i = 0; i < 7; i++) {
            const d = new Date(start);
            d.setDate(start.getDate() + i);
            days.push(d);
        }
        const end = days[6];
        document.getElementById("range-label").textContent =
            `${start.getDate()} - ${end.getDate()} ${MONTH_NAMES[end.getMonth()]} ${end.getFullYear()}`;

        rangeCache = await fetchRange("week", start);
        const entries = days.map(d => getDay(dateKey(d)));
        renderStats(entries, "pada minggu ini");

        const today = dateKey(new Date());
        let html = '<div class="week-grid">';
        days.forEach(d => {
            const key = dateKey(d);
            const data = getDay(key);
            const isSelected = selectedDate === key;
            const isPicked = selectionMode && selectedKeys.has(key);
            html += `
              <div class="week-cell ${isSelected ? 'selected' : ''} ${selectionMode ? 'selecting' : ''} ${isPicked ? 'picked' : ''}" onclick="handleDayClick('${key}', this)">
                ${selectionMode ? `<span class="pick-check"></span>` : ''}
                <div class="wc-day">${DAY_NAMES[d.getDay()]}${key === today ? ' &middot; hari ini' : ''}</div>
                <div class="wc-date">${d.getDate()}</div>
                <div class="wc-badges">
                  ${data.total === 0
            ? '<span class="cal-empty">Tidak ada chat</span>'
            : `
                      ${data.red > 0 ? `<span class="cal-badge red mx-auto"><span class="material-symbols-outlined" style="font-size:12px">warning</span> ${data.red}</span>` : ''}
                      ${data.green > 0 ? `<span class="cal-badge green mx-auto"><span class="material-symbols-outlined" style="font-size:12px">check</span> ${data.green}</span>` : ''}
                    `
          }
                </div>
              </div>`;
        });
        html += '</div>';
        document.getElementById("calendar-area").innerHTML = html;
    }

    /* ========================================================
      6. RENDER: MONTH VIEW
    ======================================================== */
    async function renderMonth() {
        const year = refDate.getFullYear();
        const month = refDate.getMonth();
        document.getElementById("range-label").textContent = `${MONTH_NAMES[month]} ${year}`;

        const firstOfMonth = new Date(year, month, 1);
        const gridStart = startOfWeek(firstOfMonth);
        const cells = [];
        for (let i = 0; i < 42; i++) {
            const d = new Date(gridStart);
            d.setDate(gridStart.getDate() + i);
            cells.push(d);
        }

        rangeCache = await fetchRange("month", firstOfMonth);
        const monthEntries = cells.filter(d => d.getMonth() === month).map(d => getDay(dateKey(d)));
        renderStats(monthEntries, `pada ${MONTH_NAMES[month]} ${year}`);

        const today = dateKey(new Date());
        let html = '<div class="cal-grid mb-1">';
        ["Sen", "Sel", "Rab", "Kam", "Jum", "Sab", "Min"].forEach(d => {
            html += `<div class="cal-weekday">${d}</div>`;
        });
        html += '</div><div class="cal-grid">';

        cells.forEach(d => {
            const outside = d.getMonth() !== month;
            const key = dateKey(d);
            const data = getDay(key);
            const isToday = key === today;
            const isSelected = selectedDate === key;
            const isPicked = !outside && selectionMode && selectedKeys.has(key);
            html += `
              <div class="cal-cell ${outside ? 'outside' : ''} ${isToday ? 'is-today' : ''} ${isSelected ? 'selected' : ''} ${!outside && selectionMode ? 'selecting' : ''} ${isPicked ? 'picked' : ''}"
                  ${outside ? '' : `onclick="handleDayClick('${key}', this)"`}>
                ${!outside && selectionMode ? `<span class="pick-check"></span>` : ''}
                <div class="cal-date-num">${d.getDate()}</div>
                <div class="cal-badges">
                  ${outside ? '' : (data.total === 0
            ? '<span class="cal-empty">-</span>'
            : `
                      ${data.red > 0 ? `<span class="cal-badge red">${data.red}</span>` : ''}
                      ${data.green > 0 ? `<span class="cal-badge green">${data.green}</span>` : ''}
                    `)}
                </div>
              </div>`;
        });
        html += '</div>';
        document.getElementById("calendar-area").innerHTML = html;
    }

    /* ========================================================
      7. RENDER: YEAR VIEW
    ======================================================== */
    async function renderYear() {
        const year = refDate.getFullYear();
        document.getElementById("range-label").textContent = `${year}`;

        rangeCache = await fetchRange("year", new Date(year, 0, 1));

        let yearTotalRed = 0,
            yearTotalGreen = 0;
        yearMonthTotals = {};
        let html = '<div class="year-grid">';

        for (let m = 0; m < 12; m++) {
            const daysInMonth = new Date(year, m + 1, 0).getDate();
            let red = 0,
                green = 0;
            for (let dnum = 1; dnum <= daysInMonth; dnum++) {
                const data = getDay(dateKey(new Date(year, m, dnum)));
                red += data.red;
                green += data.green;
            }
            yearTotalRed += red;
            yearTotalGreen += green;
            const total = red + green;
            const monthKey = `${year}-${pad(m + 1)}`;
            yearMonthTotals[monthKey] = total;
            const redPct = total ? Math.round((red / total) * 100) : 0;
            const greenPct = 100 - redPct;
            const isPicked = selectionMode && selectedKeys.has(monthKey);

            html += `
              <div class="year-cell ${selectionMode ? 'selecting' : ''} ${isPicked ? 'picked' : ''}" onclick="handleMonthCellClick(${year}, ${m}, this)">
                ${selectionMode ? `<span class="pick-check"></span>` : ''}
                <div class="yc-month">${MONTH_NAMES[m]}</div>
                <div class="yc-bar">
                  ${total ? `<div class="seg-green" style="width:${greenPct}%"></div><div class="seg-red" style="width:${redPct}%"></div>` : ''}
                </div>
                <div class="yc-stats">
                  <span class="text-completed">${green} tepat</span>
                  <span class="text-overdue">${red} telat</span>
                </div>
              </div>`;
        }
        html += '</div>';
        document.getElementById("calendar-area").innerHTML = html;

        const totalAll = yearTotalRed + yearTotalGreen;
        document.getElementById("stat-total").textContent = totalAll;
        document.getElementById("stat-green").textContent = yearTotalGreen;
        document.getElementById("stat-red").textContent = yearTotalRed;
        document.getElementById("stat-compliance").textContent =
            (totalAll ? Math.round((yearTotalGreen / totalAll) * 100) : 0) + "%";
        document.getElementById("stat-total-sub").textContent = `sepanjang tahun ${year}`;
    }

    function jumpToMonth(year, month) {
        refDate = new Date(year, month, 1);
        currentView = "month";
        document.querySelectorAll(".view-switch button").forEach(b => {
            b.classList.toggle("active", b.dataset.view === "month");
        });
        render();
    }

    /* ========================================================
      8. DETAIL PANEL (ambil data asli per chat dari server)
    ======================================================== */
    async function selectDay(key) {
        selectedDate = key;
        const [y, m, d] = key.split("-").map(Number);

        document.getElementById("detail-title").textContent =
            `Detail - ${d} ${MONTH_NAMES[m - 1]} ${y}`;
        document.getElementById("detail-area").innerHTML =
            '<div class="text-center text-secondary py-4" style="font-size:13px">Memuat detail...</div>';

        const result = await fetchDetail(key);
        const items = result.items || [];

        if (items.length === 0) {
            document.getElementById("detail-area").innerHTML = `
              <div class="detail-empty">
                <span class="material-symbols-outlined">inbox</span>
                <div style="font-size: 13px">Tidak ada chat masuk pada tanggal ini.</div>
              </div>`;
        } else {
            let html = '<div class="detail-list">';
            items.forEach(it => {
                const label = it.status === 'red' ? 'Terlambat' : (it.status === 'green' ? 'Tepat waktu' :
                    'Menunggu');
                html += `
                <div class="detail-item">
                  <div class="di-top">
                    <span class="di-phone">${it.phone}</span>
                    <span class="status-chip ${it.status}">${label}</span>
                  </div>
                  <div class="di-msg">${it.msg}</div>
                  <div class="di-meta">Agen: ${it.staff} &middot; Waktu respon: ${formatDuration(it.seconds)}</div>
                </div>`;
            });
            html += '</div>';
            document.getElementById("detail-area").innerHTML = html;
        }

        if (currentView === "week") renderWeek();
        if (currentView === "month") renderMonth();
    }

    /* ========================================================
      9. NAVIGATION
    ======================================================== */
    function render() {
        if (currentView === "week") renderWeek();
        else if (currentView === "month") renderMonth();
        else renderYear();
    }

    document.querySelectorAll(".view-switch button").forEach(btn => {
        btn.addEventListener("click", () => {
            currentView = btn.dataset.view;
            document.querySelectorAll(".view-switch button").forEach(b => b.classList.remove("active"));
            btn.classList.add("active");
            render();
        });
    });

    document.getElementById("btn-prev").addEventListener("click", () => {
        if (currentView === "week") refDate.setDate(refDate.getDate() - 7);
        else if (currentView === "month") refDate.setMonth(refDate.getMonth() - 1);
        else refDate.setFullYear(refDate.getFullYear() - 1);
        render();
    });
    document.getElementById("btn-next").addEventListener("click", () => {
        if (currentView === "week") refDate.setDate(refDate.getDate() + 7);
        else if (currentView === "month") refDate.setMonth(refDate.getMonth() + 1);
        else refDate.setFullYear(refDate.getFullYear() + 1);
        render();
    });
    document.getElementById("btn-today").addEventListener("click", () => {
        refDate = new Date();
        render();
    });
    /* ========================================================
              10. DELETE DATA LAMA
            ======================================================== */
    document.getElementById("btn-delete-old").addEventListener("click", async () => {
        const months = document.getElementById("delete-months").value;
        const confirmMsg =
            `Peringatan: Kamu akan menghapus SELURUH histori chat yang lebih lama dari ${months} bulan. Tindakan ini tidak bisa dibatalkan. Lanjutkan?`;

        if (!confirm(confirmMsg)) return;

        try {
            // Mengubah fetch ke metode GET dan menyisipkan parameter 'months' ke dalam URL
            const res = await fetch(`?action=delete&months=${months}`);

            // Pengecekan jika response dari server bukan JSON (misal error HTML)
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }

            const json = await res.json();

            if (json.error) {
                showCmdNotification('Gagal Hapus', json.error, 'error');
                return;
            }

            showCmdNotification('Data Dihapus', `${json.deleted} data berhasil dihapus.`, 'success');
            render(); // Memuat ulang kalender dan statistik secara otomatis
        } catch (err) {
            showCmdNotification('Error Jaringan', 'Terjadi kesalahan saat menghubungi server: ' + err
                .message, 'error');
            console.error(err);
        }
    });

    // Render awal
    render();

    /* ========================================================
      10. MODE HAPUS CHAT (multi-select langsung di kalender)
    ======================================================== */
    let selectionMode = false;
    let selectedKeys = new Set(); // "YYYY-MM-DD" (week/month) atau "YYYY-MM" (year)
    let yearMonthTotals = {}; // cache total log per bulan saat di year view, key "YYYY-MM"

    const btnToggleDelete = document.getElementById("btn-toggle-delete");
    const selectionBar = document.getElementById("selection-bar");
    const selectionCount = document.getElementById("selection-count");
    const btnSelectionCancel = document.getElementById("btn-selection-cancel");
    const btnSelectionDelete = document.getElementById("btn-selection-delete");

    function getSelectionUnit() {
        return currentView === "year" ? "month" : "day";
    }

    function selectedTotal() {
        let total = 0;
        selectedKeys.forEach(key => {
            if (getSelectionUnit() === "day") {
                total += getDay(key).total;
            } else {
                total += yearMonthTotals[key] || 0;
            }
        });
        return total;
    }

    function updateSelectionBar() {
        const n = selectedKeys.size;
        if (n === 0) {
            selectionCount.textContent = "Pilih hari/bulan pada kalender untuk dihapus";
            btnSelectionDelete.disabled = true;
        } else {
            const unit = getSelectionUnit() === "day" ? "hari" : "bulan";
            selectionCount.textContent = `${n} ${unit} dipilih \u00b7 ${selectedTotal()} log chat`;
            btnSelectionDelete.disabled = selectedTotal() === 0;
        }
    }

    function toggleSelectionMode(forceOff = false) {
        selectionMode = forceOff ? false : !selectionMode;
        selectedKeys.clear();
        btnToggleDelete.classList.toggle("active", selectionMode);
        btnToggleDelete.innerHTML = selectionMode ?
            '<span class="material-symbols-outlined fs-6">close</span> Batal Pilih' :
            '<span class="material-symbols-outlined fs-6">delete</span> Hapus Chat';
        selectionBar.classList.toggle("d-none", !selectionMode);
        updateSelectionBar();
        render();
    }

    function toggleKeySelection(key, el) {
        const picked = !selectedKeys.has(key);
        if (picked) selectedKeys.add(key);
        else selectedKeys.delete(key);
        updateSelectionBar();

        // Update tampilan cell langsung tanpa re-fetch/re-render seluruh kalender
        if (el) el.classList.toggle("picked", picked);
    }

    function handleDayClick(key, el) {
        if (selectionMode) toggleKeySelection(key, el);
        else selectDay(key);
    }

    function handleMonthCellClick(year, month, el) {
        const key = `${year}-${pad(month + 1)}`;
        if (selectionMode) toggleKeySelection(key, el);
        else jumpToMonth(year, month);
    }

    btnToggleDelete.addEventListener("click", () => toggleSelectionMode());
    btnSelectionCancel.addEventListener("click", () => toggleSelectionMode(true));

    // Selection dibersihkan tiap kali pindah periode/navigasi supaya tidak nyangkut
    document.getElementById("btn-prev").addEventListener("click", () => {
        selectedKeys.clear();
        updateSelectionBar();
    });
    document.getElementById("btn-next").addEventListener("click", () => {
        selectedKeys.clear();
        updateSelectionBar();
    });
    document.getElementById("btn-today").addEventListener("click", () => {
        selectedKeys.clear();
        updateSelectionBar();
    });
    document.querySelectorAll(".view-switch button").forEach(btn => {
        btn.addEventListener("click", () => {
            selectedKeys.clear();
            updateSelectionBar();
        });
    });

    /* ---- Modal konfirmasi ---- */
    const modalOverlay = document.getElementById("delete-confirm-modal");
    const modalText = document.getElementById("delete-confirm-text");
    const modalConfirm = document.getElementById("delete-confirm-yes");
    const modalCancel = document.getElementById("delete-confirm-no");

    function openConfirmModal(text) {
        modalText.textContent = text;
        modalOverlay.classList.remove("d-none");
    }

    function closeConfirmModal() {
        modalOverlay.classList.add("d-none");
    }

    modalCancel.addEventListener("click", closeConfirmModal);
    modalOverlay.addEventListener("click", (e) => {
        if (e.target === modalOverlay) closeConfirmModal();
    });

    btnSelectionDelete.addEventListener("click", () => {
        if (selectedKeys.size === 0) return;
        const unit = getSelectionUnit() === "day" ? "hari" : "bulan";
        openConfirmModal(
            `Yakin ingin menghapus log chat untuk ${selectedKeys.size} ${unit} yang dipilih (total ${selectedTotal()} log)? Tindakan ini tidak dapat dibatalkan.`
        );
    });

    modalConfirm.addEventListener("click", async () => {
        closeConfirmModal();
        const period = getSelectionUnit();
        const values = Array.from(selectedKeys).join(",");

        btnSelectionDelete.disabled = true;
        selectionCount.textContent = "Menghapus log...";

        try {
            const res = await fetch("", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: `action=delete&period=${period}&values=${encodeURIComponent(values)}`,
            });
            const json = await res.json();

            if (json.error) {
                selectionCount.textContent = json.error;
                btnSelectionDelete.disabled = false;
                return;
            }

            toggleSelectionMode(true);
            if (selectedDate) selectDay(selectedDate);
        } catch (err) {
            selectionCount.textContent = "Gagal menghapus log. Coba lagi.";
            btnSelectionDelete.disabled = false;
        }
    });
    </script>
</body>

</html>