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

  echo json_encode(['error' => 'Aksi tidak dikenal']);
  exit;
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
      flex-shrink: 0;
      /* 🔒 kunci lebar sidebar, jangan pernah menyusut */
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
      min-width: 0;

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
      flex-shrink: 0;
    }

    .main-content {
      flex-grow: 1;
      overflow-y: auto;
      scrollbar-gutter: stable;
      padding: 0 32px 32px 32px;

    }

    .tabular-clock {
      font-variant-numeric: tabular-nums;
      font-weight: 600;
      color: #8b8b99;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* ================= CARDS ================= */
    .dashboard-card {
      background: #ffffff;
      border: none;
      border-radius: 16px;
      box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.03);
      padding: 24px;
      height: 100%;
    }

    .stat-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
    }

    .stat-title {
      color: #8b8b99;
      font-size: 13px;
      font-weight: 500;
      margin: 0;
    }

    .stat-icon-small {
      width: 24px;
      height: 24px;
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .stat-value {
      font-size: 30px;
      font-weight: 700;
      color: #1e1e2d;
      margin: 0;
      line-height: 1;
    }

    .stat-sub {
      font-size: 12px;
      color: #a1a1aa;
      margin-top: 4px;
    }

    .bg-waiting {
      background-color: #f59e0b;
    }

    .bg-overdue {
      background-color: #f43f5e;
    }

    .bg-completed {
      background-color: #10b981;
    }

    .text-waiting {
      color: #f59e0b;
    }

    .text-overdue {
      color: #f43f5e;
    }

    .text-completed {
      color: #10b981;
    }

    .dot-indicator {
      display: inline-block;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      margin-right: 8px;
    }

    /* ============ VIEW SWITCH ============ */
    .view-switch {
      display: inline-flex;
      background: #ffffff;
      border-radius: 12px;
      padding: 4px;
      box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.03);
      gap: 4px;
    }

    .view-switch button {
      border: none;
      background: transparent;
      color: #8b8b99;
      font-weight: 600;
      font-size: 13px;
      padding: 8px 18px;
      border-radius: 8px;
      transition: all 0.2s ease;
      cursor: pointer;
    }

    .view-switch button.active {
      background-color: #1c1c24;
      color: #ccff00;
    }

    .view-switch button:hover:not(.active) {
      color: #1e1e2d;
    }

    .range-nav {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .range-nav button {
      width: 34px;
      height: 34px;
      border-radius: 10px;
      border: 1px solid #e2e2e8;
      background: #ffffff;
      color: #1e1e2d;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s;
    }

    .range-nav button:hover {
      background-color: #1c1c24;
      color: #ccff00;
      border-color: #1c1c24;
    }

    .range-label {
      font-weight: 700;
      font-size: 15px;
      color: #1e1e2d;
      min-width: 170px;
      text-align: center;
    }

    .btn-today {
      border: 1px solid #e2e2e8;
      background: #ffffff;
      color: #1e1e2d;
      font-size: 12.5px;
      font-weight: 600;
      border-radius: 10px;
      padding: 8px 14px;
      transition: all 0.2s;
    }

    .btn-today:hover {
      border-color: #ccff00;
      background-color: #fbffe6;
    }

    .legend-item {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 12.5px;
      color: #646470;
      font-weight: 500;
    }

    /* ============ CALENDAR: MONTH VIEW ============ */
    .cal-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 8px;
    }

    .cal-weekday {
      text-align: center;
      font-size: 11px;
      font-weight: 700;
      color: #a1a1aa;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding-bottom: 8px;
    }

    .cal-cell {
      border: 1px solid #f2f3f7;
      border-radius: 12px;
      min-height: 92px;
      padding: 8px 10px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      cursor: pointer;
      transition: all 0.15s ease;
      background: #ffffff;
    }

    .cal-cell:hover {
      border-color: #ccff00;
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
      transform: translateY(-1px);
    }

    .cal-cell.outside {
      opacity: 0.35;
      cursor: default;
    }

    .cal-cell.outside:hover {
      border-color: #f2f3f7;
      box-shadow: none;
      transform: none;
    }

    .cal-cell.is-today {
      border-color: #1c1c24;
    }

    .cal-cell.selected {
      border-color: #ccff00;
      background: #fbffe6;
    }

    .cal-date-num {
      font-size: 13px;
      font-weight: 600;
      color: #1e1e2d;
    }

    .cal-badges {
      display: flex;
      gap: 4px;
      flex-wrap: wrap;
    }

    .cal-badge {
      font-size: 11px;
      font-weight: 700;
      border-radius: 6px;
      padding: 2px 6px;
      display: inline-flex;
      align-items: center;
      gap: 3px;
      line-height: 1.4;
    }

    .cal-badge.red {
      background: rgba(244, 63, 94, 0.1);
      color: #f43f5e;
    }

    .cal-badge.green {
      background: rgba(16, 185, 129, 0.1);
      color: #10b981;
    }

    .cal-empty {
      font-size: 11px;
      color: #d4d4d8;
    }

    /* ============ WEEK VIEW ============ */
    .week-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 12px;
    }

    .week-cell {
      border: 1px solid #f2f3f7;
      border-radius: 14px;
      padding: 16px 10px;
      text-align: center;
      cursor: pointer;
      transition: all 0.15s ease;
    }

    .week-cell:hover {
      border-color: #ccff00;
      transform: translateY(-2px);
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
    }

    .week-cell.selected {
      border-color: #ccff00;
      background: #fbffe6;
    }

    .week-cell .wc-day {
      font-size: 11px;
      font-weight: 700;
      color: #a1a1aa;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .week-cell .wc-date {
      font-size: 22px;
      font-weight: 700;
      color: #1e1e2d;
      margin: 6px 0 12px 0;
    }

    .week-cell .wc-badges {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    /* ============ YEAR VIEW ============ */
    .year-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px;
    }

    .year-cell {
      border: 1px solid #f2f3f7;
      border-radius: 14px;
      padding: 16px;
      cursor: pointer;
      transition: all 0.15s ease;
    }

    .year-cell:hover {
      border-color: #ccff00;
      transform: translateY(-2px);
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
    }

    .year-cell .yc-month {
      font-weight: 700;
      font-size: 14px;
      color: #1e1e2d;
      margin-bottom: 10px;
    }

    .yc-bar {
      height: 8px;
      border-radius: 4px;
      background: #f2f3f7;
      overflow: hidden;
      display: flex;
      margin-bottom: 10px;
    }

    .yc-bar .seg-green {
      background: #10b981;
      height: 100%;
    }

    .yc-bar .seg-red {
      background: #f43f5e;
      height: 100%;
    }

    .yc-stats {
      display: flex;
      justify-content: space-between;
      font-size: 12px;
      font-weight: 600;
    }

    /* ============ DETAIL PANEL ============ */
    .detail-empty {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100%;
      color: #a1a1aa;
      text-align: center;
      padding: 40px 20px;
    }

    .detail-empty .material-symbols-outlined {
      font-size: 42px;
      margin-bottom: 10px;
      color: #d4d4d8;
    }

    .detail-list {
      max-height: 480px;
      overflow-y: auto;
    }

    .detail-item {
      border-bottom: 1px solid #f2f3f7;
      padding: 12px 2px;
    }

    .detail-item:last-child {
      border-bottom: none;
    }

    .detail-item .di-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 4px;
    }

    .detail-item .di-phone {
      font-weight: 700;
      font-size: 13px;
      color: #1e1e2d;
    }

    .detail-item .di-msg {
      font-size: 12.5px;
      color: #646470;
      margin-bottom: 4px;
    }

    .detail-item .di-meta {
      font-size: 11px;
      color: #a1a1aa;
    }

    .status-chip {
      font-size: 10.5px;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 20px;
    }

    .status-chip.red {
      background: rgba(244, 63, 94, 0.1);
      color: #f43f5e;
    }

    .status-chip.green {
      background: rgba(16, 185, 129, 0.1);
      color: #10b981;
    }

    .status-chip.yellow {
      background: rgba(245, 158, 11, 0.1);
      color: #f59e0b;
    }

    ::-webkit-scrollbar {
      width: 5px;
    }

    ::-webkit-scrollbar-track {
      background: transparent;
    }

    ::-webkit-scrollbar-thumb {
      background: #e2e2e8;
      border-radius: 10px;
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
        <a href="/grub" class="nav-link">
          <span class="material-symbols-outlined fs-5">confirmation_number</span>
          Tambah Grub
        </a>
        <a href="/laporan" class="nav-link active">
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
        <a href="#" class="ms-auto text-secondary"><span class="material-symbols-outlined fs-5">logout</span></a>
      </div>
    </aside>

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
              <div class="legend-item"><span class="dot-indicator bg-overdue"></span> Terlambat &gt;3 menit</div>
            </div>
            <div class="range-nav">
              <button id="btn-prev"><span class="material-symbols-outlined fs-6">chevron_left</span></button>
              <span class="range-label" id="range-label">-</span>
              <button id="btn-next"><span class="material-symbols-outlined fs-6">chevron_right</span></button>
            </div>
            <button class="btn-today" id="btn-today">Hari ini</button>
          </div>
        </div>

        <!-- CALENDAR + DETAIL -->
        <div class="row g-4">
          <div class="col-lg-8">
            <div class="dashboard-card">
              <div id="calendar-area">
                <div class="text-center text-secondary py-5">Memuat data...</div>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="dashboard-card">
              <h6 class="fw-bold mb-3" id="detail-title">Detail Hari</h6>
              <div id="detail-area">
                <div class="detail-empty">
                  <span class="material-symbols-outlined">touch_app</span>
                  <div style="font-size: 13px">Pilih salah satu tanggal untuk melihat detail log chat.</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script>
    /* ========================================================
      1. CLOCK
    ======================================================== */
    setInterval(() => {
      const now = new Date();
      const time = now.toLocaleTimeString("id-ID", { hour12: false });
      document.getElementById("live-clock").innerHTML =
        `<span class="material-symbols-outlined fs-6">schedule</span> ${time}`;
    }, 1000);

    /* ========================================================
      2. KONSTANTA & HELPER
    ======================================================== */
    const DAY_NAMES = ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];
    const MONTH_NAMES = [
      "Januari", "Februari", "Maret", "April", "Mei", "Juni",
      "Juli", "Agustus", "September", "Oktober", "November", "Desember"
    ];

    function pad(n) { return n.toString().padStart(2, "0"); }
    function dateKey(d) { return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`; }
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
      return rangeCache[dateStr] || { red: 0, green: 0, total: 0 };
    }

    function renderStats(entries, subLabel) {
      let total = 0, green = 0, red = 0;
      entries.forEach(e => { total += e.total; green += e.green; red += e.red; });
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
        html += `
              <div class="week-cell ${isSelected ? 'selected' : ''}" onclick="selectDay('${key}')">
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
        html += `
              <div class="cal-cell ${outside ? 'outside' : ''} ${isToday ? 'is-today' : ''} ${isSelected ? 'selected' : ''}"
                  ${outside ? '' : `onclick="selectDay('${key}')"`}>
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

      let yearTotalRed = 0, yearTotalGreen = 0;
      let html = '<div class="year-grid">';

      for (let m = 0; m < 12; m++) {
        const daysInMonth = new Date(year, m + 1, 0).getDate();
        let red = 0, green = 0;
        for (let dnum = 1; dnum <= daysInMonth; dnum++) {
          const data = getDay(dateKey(new Date(year, m, dnum)));
          red += data.red;
          green += data.green;
        }
        yearTotalRed += red;
        yearTotalGreen += green;
        const total = red + green;
        const redPct = total ? Math.round((red / total) * 100) : 0;
        const greenPct = 100 - redPct;

        html += `
              <div class="year-cell" onclick="jumpToMonth(${year}, ${m})">
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
          const label = it.status === 'red' ? 'Terlambat' : (it.status === 'green' ? 'Tepat waktu' : 'Menunggu');
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

    // Render awal
    render();
  </script>
</body>

</html>