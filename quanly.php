<?php
// ============================================================
//  IC3 CLASSROOM MANAGER - dashboard.php
// ============================================================
$tenLop = file_exists('active_class.txt') ? trim(file_get_contents('active_class.txt')) : '';
if (empty($tenLop)) {
  header("Location: index.php");
  exit;
}

// Trích xuất khối lớp (6, 7, 8) từ tên lớp (VD: "7.6" -> 7)
$khoiLop = 0;
if (preg_match('/\d/', $tenLop, $m)) {
  $khoiLop = (int)$m[0];
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>IC3 Manager – <?= htmlspecialchars($tenLop) ?></title>
  <style>
    /* ── RESET & BASE ── */
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    :root {
      --blue: #1a73e8;
      --blue-dk: #1557b0;
      --blue-lt: #e8f0fe;
      --blue-mid: #4285f4;
      --green: #1e8e3e;
      --green-lt: #e6f4ea;
      --red: #d93025;
      --red-lt: #fce8e6;
      --amber: #e37400;
      --amber-lt: #fef7e0;
      --gray-50: #f8f9fa;
      --gray-100: #f1f3f4;
      --gray-200: #e8eaed;
      --gray-300: #dadce0;
      --gray-500: #9aa0a6;
      --gray-600: #80868b;
      --gray-700: #5f6368;
      --gray-900: #202124;
      --white: #ffffff;

      /* TĂNG KÍCH THƯỚC GIAO DIỆN TỔNG THỂ */
      --header-h: 64px;
      --sidebar-w: 290px;
      --panel-w: 280px;
      --r: 8px;
      --r-lg: 12px;
      --shadow: 0 1px 4px rgba(0, 0, 0, .14);
    }

    html,
    body {
      height: 100%;
      font-family: 'Segoe UI', system-ui, sans-serif;
      background: var(--gray-100);
      color: var(--gray-900);
      font-size: 15px;
      line-height: 1.5;
    }

    /* ── TOPBAR ── */
    .topbar {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 200;
      height: var(--header-h);
      background: var(--white);
      border-bottom: 1px solid var(--gray-200);
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 0 16px;
    }

    .logo-badge {
      background: var(--blue);
      color: #fff;
      font-weight: 800;
      font-size: 14px;
      padding: 6px 12px;
      border-radius: 6px;
      letter-spacing: .5px;
      flex-shrink: 0;
    }

    .class-pill {
      background: var(--blue-lt);
      color: var(--blue);
      font-weight: 700;
      font-size: 15px;
      padding: 6px 16px;
      border-radius: 20px;
      flex-shrink: 0;
    }

    .sep {
      width: 1px;
      height: 32px;
      background: var(--gray-200);
      flex-shrink: 0;
      margin: 0 4px;
    }

    .spacer {
      flex: 1;
    }

    /* ── BUTTONS ── */
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      padding: 8px 16px;
      border-radius: 8px;
      border: 1px solid var(--gray-300);
      background: var(--white);
      color: var(--gray-700);
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all .15s ease;
      white-space: nowrap;
      flex-shrink: 0;
      line-height: 1.2;
    }

    .btn svg {
      width: 18px;
      height: 18px;
      flex-shrink: 0;
      display: block;
      margin-top: -1px;
    }

    .btn:hover {
      background: var(--gray-50);
      border-color: var(--gray-500);
    }

    .btn.active {
      background: var(--blue-lt);
      color: var(--blue);
      border-color: var(--blue);
    }

    .btn-primary {
      background: var(--blue);
      color: #fff;
      border-color: var(--blue);
    }

    .btn-primary:hover {
      background: var(--blue-dk);
      border-color: var(--blue-dk);
    }

    .btn-green {
      background: var(--green-lt);
      color: var(--green);
      border-color: #b7dfbe;
    }

    .btn-green:hover {
      background: #c8e6c9;
    }

    .btn-red {
      background: var(--red-lt);
      color: var(--red);
      border-color: #fad2cf;
    }

    .btn-red:hover {
      background: #fad2cf;
    }

    .btn-amber {
      background: var(--amber-lt);
      color: var(--amber);
      border-color: #fde7a3;
    }

    .btn-amber:hover {
      background: #fde7a3;
    }

    .btn:disabled {
      opacity: .5;
      cursor: not-allowed;
    }

    a.btn {
      text-decoration: none;
    }

    /* ── LAYOUT & SIDEBAR ── */
    .layout {
      display: flex;
      padding-top: var(--header-h);
      height: 100vh;
      overflow: hidden;
    }

    .sidebar {
      width: var(--sidebar-w);
      min-width: var(--sidebar-w);
      background: var(--white);
      border-right: 1px solid var(--gray-200);
      display: flex;
      flex-direction: column;
      overflow-y: auto;
      overflow-x: hidden;
    }

    .sb-section {
      padding: 16px;
      border-bottom: 1px solid var(--gray-200);
    }

    .sb-title {
      font-size: 12px;
      font-weight: 800;
      color: var(--gray-700);
      text-transform: uppercase;
      letter-spacing: .8px;
      margin-bottom: 12px;
    }

    /* Stat cards */
    .stat-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
    }

    .stat-card {
      background: var(--gray-50);
      border: 1px solid var(--gray-200);
      border-radius: 8px;
      padding: 12px 10px;
    }

    .stat-val {
      font-size: 26px;
      font-weight: 700;
      color: var(--blue);
    }

    .stat-lbl {
      font-size: 13px;
      color: var(--gray-600);
      margin-top: 2px;
    }

    .stat-val.green {
      color: var(--green);
    }

    .stat-val.amber {
      color: var(--amber);
    }

    .stat-val.red {
      color: var(--red);
    }

    /* ── PHỔ ĐIỂM & VINH DANH ── */
    .dist-row {
      display: flex;
      align-items: center;
      margin-bottom: 6px;
      gap: 8px;
      font-size: 13px;
      font-weight: 600;
    }

    .dist-lbl {
      width: 30px;
      color: var(--gray-700);
    }

    .dist-val {
      width: 24px;
      text-align: right;
      color: var(--gray-900);
    }

    .prog-wrap {
      background: var(--gray-200);
      border-radius: 99px;
      height: 8px;
      overflow: hidden;
    }

    .prog-bar {
      height: 100%;
      border-radius: 99px;
      background: var(--blue);
      transition: width .5s;
    }

    .top-item {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 8px 10px;
      border-radius: 8px;
      background: var(--gray-50);
      margin-bottom: 6px;
      border: 1px solid var(--gray-200);
      cursor: pointer;
      transition: background .1s;
    }

    .top-item:hover {
      background: #e8f0fe;
    }

    .top-rank {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      font-weight: 800;
      color: #fff;
    }

    .rank-1 {
      background: #f1c40f;
    }

    .rank-2 {
      background: #95a5a6;
    }

    .rank-3 {
      background: #d35400;
    }

    .rank-n {
      background: var(--blue-mid);
    }

    .top-name {
      flex: 1;
      font-weight: 600;
      font-size: 14px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      color: var(--gray-900);
    }

    .top-score {
      font-weight: 800;
      color: var(--green);
      font-size: 14px;
    }

    /* Inputs */
    .inp,
    .sel,
    .textarea {
      width: 100%;
      border: 1px solid var(--gray-300);
      border-radius: 6px;
      padding: 8px 12px;
      font-size: 14px;
      color: var(--gray-900);
      background: var(--white);
      outline: none;
      transition: border-color .15s, box-shadow .15s;
    }

    .inp:focus,
    .sel:focus,
    .textarea:focus {
      border-color: var(--blue);
      box-shadow: 0 0 0 2px rgba(26, 115, 232, .14);
    }

    .inp-row {
      display: flex;
      gap: 8px;
      margin-bottom: 8px;
      align-items: center;
    }

    .inp-lbl {
      font-size: 13px;
      color: var(--gray-600);
      white-space: nowrap;
      min-width: 35px;
      font-weight: 600;
    }

    .textarea {
      resize: vertical;
      min-height: 120px;
      font-family: inherit;
      font-size: 15px;
    }

    /* ── BỘ LỌC CHÚ THÍCH (MỚI) ── */
    .legend {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .leg-filter {
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      padding: 8px 10px;
      border-radius: 6px;
      transition: all 0.2s;
      user-select: none;
      margin: 0 -8px;
      font-size: 14px;
      color: var(--gray-700);
      font-weight: 500;
    }

    .leg-filter:hover {
      background: var(--gray-100);
    }

    .leg-filter.active {
      background: var(--blue-lt);
      font-weight: 700;
      color: var(--blue-dk);
    }

    .leg-count {
      font-size: 14px;
      color: var(--gray-500);
      margin-left: auto;
      font-weight: 700;
    }

    .leg-filter.active .leg-count {
      color: var(--blue);
    }

    .ldot {
      width: 14px;
      height: 14px;
      border-radius: 50%;
      flex-shrink: 0;
    }

    /* ── TABLE CONTENT ── */
    .content {
      flex: 1;
      overflow-y: auto;
      padding: 16px;
      min-width: 0;
    }

    .table-card {
      background: var(--white);
      border-radius: var(--r-lg);
      border: 1px solid var(--gray-200);
      overflow: hidden;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    /* SIZE TIÊU ĐỀ TĂNG LÊN 13px */
    thead th {
      background: var(--gray-50);
      padding: 12px 10px;
      text-align: center !important;
      font-size: 13px;
      font-weight: 700;
      color: var(--gray-600);
      text-transform: uppercase;
      letter-spacing: .6px;
      border-bottom: 1px solid var(--gray-200);
      white-space: nowrap;
      position: sticky;
      top: 0;
      z-index: 5;
    }

    tbody tr {
      border-bottom: 1px solid var(--gray-100);
      cursor: pointer;
      transition: background .1s;
    }

    tbody tr:last-child {
      border-bottom: none;
    }

    tbody tr:hover {
      background: #f0f4ff;
    }

    tbody tr.sel-row {
      background: #d2e3fc !important;
    }

    /* SIZE DATA TĂNG LÊN 15px */
    td {
      padding: 10px 10px;
      font-size: 15px;
      color: var(--gray-900);
      vertical-align: middle;
    }

    /* PC Badge to hơn xíu */
    .pc-badge {
      width: 42px;
      height: 28px;
      border-radius: 6px;
      background: var(--blue);
      color: #fff;
      font-weight: 700;
      font-size: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto;
    }

    .pc-badge.empty {
      background: var(--gray-200);
      color: var(--gray-600);
    }

    /* Cột Tên - Tăng font, không bao giờ bị cắt chữ */
    .name-cell {
      font-weight: normal;
      font-size: 15px;
      color: var(--gray-900);
      white-space: nowrap;
      text-align: left;
      padding-left: 15px;
    }

    .no-name {
      color: var(--gray-500);
      font-style: italic;
      font-weight: normal;
    }

    .sbadge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 46px;
      height: 26px;
      border-radius: 13px;
      font-weight: 800;
      font-size: 14px;
      padding: 0 10px;
      white-space: nowrap;
    }

    .sbadge-na {
      background: var(--gray-100);
      color: var(--gray-600);
    }

    .sbadge-low {
      background: var(--red-lt);
      color: var(--red);
    }

    .sbadge-mid {
      background: var(--amber-lt);
      color: var(--amber);
    }

    .sbadge-high {
      background: var(--green-lt);
      color: var(--green);
    }

    tbody tr.row-high {
      background: #f0faf3;
    }

    tbody tr.row-high:hover {
      background: #dff5e7;
    }

    /* Inputs trong bảng to hơn */
    .tgt-inp {
      border: 1px solid var(--gray-300);
      border-radius: 5px;
      padding: 5px 8px;
      font-size: 14px;
      width: 65px;
      text-align: center;
      font-weight: 600;
      transition: border-color .15s;
    }

    .tgt-inp:focus {
      border-color: var(--blue);
      outline: none;
    }

    .ot-sel {
      border: 1px solid var(--gray-300);
      border-radius: 5px;
      padding: 5px 8px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      width: 120px;
      background: var(--white);
      color: var(--gray-800);
      display: inline-block;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      line-height: 1.2;
    }

    .ot-sel:hover {
      border-color: var(--blue);
    }

    .ot-warn {
      border-color: var(--red) !important;
      background: var(--red-lt) !important;
      color: var(--red) !important;
    }

    .ot-match {
      color: var(--green);
      font-weight: 700;
      font-size: 14px;
    }

    .ot-mismatch {
      color: var(--red);
      font-weight: 700;
      font-size: 14px;
    }

    .ot-none {
      color: var(--gray-500);
      font-size: 14px;
      font-weight: 500;
    }

    .time-cell {
      color: var(--gray-600);
      font-size: 14px;
      font-weight: 500;
    }

    /* ── DETAIL PANEL ── */
    .detail-panel {
      width: var(--panel-w);
      min-width: var(--panel-w);
      background: var(--white);
      border-left: 1px solid var(--gray-200);
      display: flex;
      flex-direction: column;
      overflow-y: auto;
    }

    .dp-section {
      padding: 16px;
      border-bottom: 1px solid var(--gray-200);
    }

    .dp-title {
      font-size: 12px;
      font-weight: 700;
      color: var(--gray-600);
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 12px;
    }

    .avatar {
      width: 56px;
      height: 56px;
      border-radius: 50%;
      background: var(--blue-lt);
      color: var(--blue);
      font-weight: 800;
      font-size: 22px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 10px;
    }

    .dp-name {
      font-size: 18px;
      font-weight: 800;
      text-align: center;
      color: var(--gray-900);
    }

    .dp-sub {
      font-size: 13px;
      color: var(--gray-600);
      text-align: center;
      margin-top: 4px;
      font-weight: 500;
    }

    .big-score {
      font-size: 46px;
      font-weight: 900;
      text-align: center;
      padding: 10px 0 4px;
    }

    .big-max {
      font-size: 14px;
      color: var(--gray-600);
      text-align: center;
      margin-bottom: 8px;
      font-weight: 600;
    }

    .dp-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
      font-size: 14px;
    }

    .dp-lbl {
      color: var(--gray-600);
    }

    .dp-val {
      font-weight: 600;
      color: var(--gray-800);
    }

    /* ── MODALS (Tăng size Box) ── */
    .modal-bg {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .5);
      z-index: 900;
      align-items: center;
      justify-content: center;
    }

    .modal-bg.open {
      display: flex;
    }

    .modal {
      background: var(--white);
      border-radius: var(--r-lg);
      padding: 24px;
      width: 400px;
      max-width: 95vw;
      box-shadow: 0 10px 40px rgba(0, 0, 0, .2);
      animation: mIn .2s ease;
    }

    @keyframes mIn {
      from {
        opacity: 0;
        transform: scale(.95) translateY(10px);
      }

      to {
        opacity: 1;
        transform: none;
      }
    }

    .modal-title {
      font-size: 18px;
      font-weight: 800;
      margin-bottom: 16px;
      color: var(--blue-dk);
    }

    .modal-foot {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      margin-top: 20px;
    }

    /* Checkbox Multiple */
    .cb-label {
      display: flex;
      align-items: center;
      gap: 8px;
      width: 45%;
      cursor: pointer;
      font-size: 15px;
      font-weight: 600;
      color: var(--gray-800);
    }

    .cb-label input {
      width: 18px;
      height: 18px;
      cursor: pointer;
    }

    /* ── TOAST ── */
    .toast {
      position: fixed;
      bottom: 30px;
      left: 50%;
      transform: translateX(-50%);
      background: #202124;
      color: #fff;
      padding: 12px 24px;
      border-radius: 30px;
      font-size: 15px;
      font-weight: 600;
      z-index: 9999;
      opacity: 0;
      transition: opacity .3s;
      pointer-events: none;
      white-space: nowrap;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .toast.show {
      opacity: 1;
    }

    /* ── CHIP ── */
    .chip {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 4px 10px;
      border-radius: 14px;
      font-size: 12px;
      font-weight: 700;
    }

    .chip-blue {
      background: var(--blue-lt);
      color: var(--blue);
    }

    .chip-green {
      background: var(--green-lt);
      color: var(--green);
    }

    .chip-red {
      background: var(--red-lt);
      color: var(--red);
    }

    .chip-gray {
      background: var(--gray-100);
      color: var(--gray-600);
    }

    /* ── SCROLLBAR ── */
    ::-webkit-scrollbar {
      width: 8px;
      height: 8px;
    }

    ::-webkit-scrollbar-track {
      background: transparent;
    }

    ::-webkit-scrollbar-thumb {
      background: var(--gray-300);
      border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: var(--gray-500);
    }
  </style>
</head>

<body>

  <div class="topbar">
    <div class="logo-badge">IC3</div>
    <div class="class-pill" id="classLabel"><?= htmlspecialchars($tenLop) ?></div>
    <div class="sep"></div>
    <a href="index.php" class="btn"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M13 4L7 10l6 6" stroke-linecap="round" stroke-linejoin="round" />
      </svg> Đổi lớp</a>
    <button class="btn btn-green" onclick="doRefresh()" id="refreshBtn"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M4 10a6 6 0 1 0 1.6-4" stroke-linecap="round" />
        <path d="M4 4v3h3" stroke-linecap="round" stroke-linejoin="round" />
      </svg> Làm mới</button>
    <div class="sep"></div>
    <button class="btn btn-amber" onclick="openBulkTarget()"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="10" cy="10" r="7" />
        <circle cx="10" cy="10" r="3" />
      </svg> Chỉ tiêu</button>
    <button class="btn" onclick="openBulkOT()"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="6" height="6" rx="1" />
        <rect x="11" y="3" width="6" height="6" rx="1" />
        <rect x="3" y="11" width="6" height="6" rx="1" />
        <rect x="11" y="11" width="6" height="6" rx="1" />
      </svg> Giao bài</button>
    <button class="btn" onclick="toggleListModal()"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M4 6h12M4 10h12M4 14h7" stroke-linecap="round" />
      </svg> Danh sách HS</button>
    <a href="export_excel.php" class="btn btn-green" target="_blank"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M14 3v4a1 1 0 0 0 1 1h4" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M17 21H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h8l9 9v9a2 2 0 0 1-2 2z" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M9 13l-4 4m0-4l4 4" stroke-linecap="round" stroke-linejoin="round" />
      </svg> Xuất Excel</a>
    <div class="spacer"></div>
    <span id="lastUpdate" style="font-size:13px; color:var(--gray-500); font-weight:600;"></span>
    <div class="sep"></div>
    <button class="btn btn-red" onclick="doDeleteScores()"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M6 4h8M5 4v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M8 9v5M12 9v5" stroke-linecap="round" />
      </svg> Xóa điểm</button>
  </div>

  <div class="layout">
    <div class="sidebar">

      <div class="sb-section">
        <div class="sb-title">Thống kê nhanh</div>
        <div class="stat-grid">
          <div class="stat-card">
            <div class="stat-val" id="stScored">0</div>
            <div class="stat-lbl">Có điểm</div>
          </div>
          <div class="stat-card">
            <div class="stat-val" id="stTotal">0</div>
            <div class="stat-lbl">Học sinh</div>
          </div>
          <div class="stat-card">
            <div class="stat-val green" id="stAvg">—</div>
            <div class="stat-lbl">Điểm TB</div>
          </div>
          <div class="stat-card">
            <div class="stat-val amber" id="stPass">0</div>
            <div class="stat-lbl">Đạt chỉ tiêu</div>
          </div>
          <div class="stat-card">
            <div class="stat-val red" id="stFail">0</div>
            <div class="stat-lbl">Chưa đạt</div>
          </div>
          <div class="stat-card">
            <div class="stat-val red" id="stOTFail">0</div>
            <div class="stat-lbl">Sai bài thi</div>
          </div>
        </div>
      </div>

      <div class="sb-section">
        <div class="sb-title">Sắp Xếp Dữ Liệu</div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
          <button class="btn" id="sort_score_desc" onclick="setSort('score_desc')" style="padding:8px 4px; font-size:13px; gap:4px;">🔽 Điểm Cao</button>
          <button class="btn" id="sort_score_asc" onclick="setSort('score_asc')" style="padding:8px 4px; font-size:13px; gap:4px;">🔼 Điểm Thấp</button>
          <button class="btn" id="sort_time_asc" onclick="setSort('time_asc')" style="padding:8px 4px; font-size:13px; gap:4px;">⚡ Nhanh nhất</button>
          <button class="btn active" id="sort_pc_asc" onclick="setSort('pc_asc')" style="padding:8px 4px; font-size:13px; gap:4px;">💻 Mặc định</button>
        </div>
      </div>

      <div class="sb-section">
        <div class="sb-title">Biểu đồ Phổ Điểm</div>
        <div class="dist-row">
          <div class="dist-lbl">Giỏi</div>
          <div class="prog-wrap" style="flex:1;">
            <div class="prog-bar" id="distG" style="background:#1e8e3e; width:0%"></div>
          </div>
          <div class="dist-val" id="valG">0</div>
        </div>
        <div class="dist-row">
          <div class="dist-lbl">Khá</div>
          <div class="prog-wrap" style="flex:1;">
            <div class="prog-bar" id="distK" style="background:#f1c40f; width:0%"></div>
          </div>
          <div class="dist-val" id="valK">0</div>
        </div>
        <div class="dist-row">
          <div class="dist-lbl">TB</div>
          <div class="prog-wrap" style="flex:1;">
            <div class="prog-bar" id="distTB" style="background:#e67e22; width:0%"></div>
          </div>
          <div class="dist-val" id="valTB">0</div>
        </div>
        <div class="dist-row">
          <div class="dist-lbl">Yếu</div>
          <div class="prog-wrap" style="flex:1;">
            <div class="prog-bar" id="distY" style="background:#d93025; width:0%"></div>
          </div>
          <div class="dist-val" id="valY">0</div>
        </div>
      </div>

      <div class="sb-section">
        <div class="sb-title">🏆 Bảng Vinh Danh (Top 5)</div>
        <div id="topStudentsList">
          <div style="text-align:center; color:var(--gray-500); font-size:13px; font-style:italic; padding:10px 0;">Chưa có học sinh nộp bài</div>
        </div>
      </div>

      <div class="sb-section">
        <div class="sb-title">Tìm kiếm</div>
        <div class="inp-row">
          <input class="inp" id="searchInp" placeholder="Nhập tên / số máy..." oninput="filterTable()" onfocus="isTyping=true" onblur="isTyping=false" />
        </div>
      </div>

      <div class="sb-section" style="flex:1;">
        <div class="sb-title">Chú thích & Lọc Nhanh</div>
        <div class="legend" style="font-style: italic; font-size: 12px; margin-bottom:8px; color:var(--gray-500);">* Bấm vào từng mục để lọc danh sách</div>
        <div class="legend">
          <div class="leg-filter" id="leg_pass" onclick="setLegendFilter('pass')">
            <div class="ldot" style="background:#e6f4ea; border:2px solid #1e8e3e;"></div>
            Đạt chỉ tiêu / Tốt <span class="leg-count" id="c_pass">(0)</span>
          </div>
          <div class="leg-filter" id="leg_mid" onclick="setLegendFilter('mid')">
            <div class="ldot" style="background:#fef7e0; border:2px solid #e37400;"></div>
            Gần đạt (60-79%) <span class="leg-count" id="c_mid">(0)</span>
          </div>
          <div class="leg-filter" id="leg_low" onclick="setLegendFilter('low')">
            <div class="ldot" style="background:#fce8e6; border:2px solid #d93025;"></div>
            Chưa đạt (&lt;60%) <span class="leg-count" id="c_low">(0)</span>
          </div>
          <div class="leg-filter" id="leg_none" onclick="setLegendFilter('none')">
            <div class="ldot" style="background:#f1f3f4; border:2px solid #dadce0;"></div>
            Chưa làm bài <span class="leg-count" id="c_none">(0)</span>
          </div>
          <div class="leg-filter" id="leg_otfail" onclick="setLegendFilter('otfail')">
            <div class="ldot" style="background:#fce8e6; border:2px dashed #d93025;"></div>
            Thi sai bài giao <span class="leg-count" id="c_otfail">(0)</span>
          </div>
        </div>
      </div>

    </div>
    <div class="content">
      <div class="table-card">
        <table>
          <thead>
            <tr>
              <th style="width:60px;">Máy</th>
              <th>Họ và tên</th>
              <th style="width:150px;">Điểm Đã Thi</th>
              <th style="width:80px;">MAX</th>
              <th style="width:80px;">Lần thi</th>
              <th style="width:90px;">Chỉ tiêu</th>
              <th style="width:130px;">Bài giao</th>
              <th style="width:100px;">Đã thi</th>
              <th style="width:100px;">Thời gian</th>
            </tr>
          </thead>
          <tbody id="tableBody">
          </tbody>
        </table>
      </div>
    </div>
    <div class="detail-panel">
      <div class="dp-section">
        <div class="dp-title">Chi tiết học sinh</div>
        <div id="detailContent">
          <div style="text-align:center; padding:20px 0; color:var(--gray-500); font-size:14px; font-style:italic;">Nhấn vào một học sinh để xem chi tiết</div>
        </div>
      </div>
      <div class="dp-section">
        <div class="dp-title">Tất cả lần thi</div>
        <div id="allScores" style="font-size:14px; color:var(--gray-600);">—</div>
      </div>
      <div class="dp-section" style="flex:1;">
        <div class="dp-title">Lịch sử thao tác</div>
        <div id="actionLog" style="font-size:13px; color:var(--gray-600); line-height:2;">Chưa có thao tác.</div>
      </div>
    </div>
  </div>
  <div class="modal-bg" id="modalBg" onclick="if(event.target===this) closeModal()">
    <div class="modal">
      <div class="modal-title" id="modalTitle">Modal</div>
      <div id="modalBody"></div>
      <div class="modal-foot">
        <button class="btn" onclick="closeModal()">Hủy</button>
        <button class="btn btn-primary" id="modalOk">Áp dụng</button>
      </div>
    </div>
  </div>

  <div class="modal-bg" id="listModalBg" onclick="if(event.target===this) toggleListModal()">
    <div class="modal" style="width: 650px; max-width: 95vw;">
      <div class="modal-title">📝 Nhập danh sách học sinh</div>
      <div style="font-size:15px; color:var(--gray-600); margin-bottom:14px; line-height:1.6;">
        Mỗi dòng 1 học sinh theo định dạng: <b>Tên Học Sinh PC1</b><br>
        <i>(Có thể dán trực tiếp 2 cột Họ và Tên từ file Excel vào đây)</i>
      </div>
      <textarea class="textarea" id="txtList" style="min-height: 450px; font-size:17px; border:2px solid var(--gray-300);" onfocus="isTyping=true" onblur="isTyping=false"><?php
                                                                                                                                                                            $pathDS = "danhsach/danhsach_$tenLop.txt";
                                                                                                                                                                            if (file_exists($pathDS)) echo htmlspecialchars(file_get_contents($pathDS));
                                                                                                                                                                            ?></textarea>
      <div class="modal-foot">
        <button class="btn" onclick="toggleListModal()">Đóng</button>
        <button class="btn btn-primary" onclick="saveList()">💾 Lưu danh sách</button>
      </div>
    </div>
  </div>

  <div class="toast" id="toast"></div>

  <script>
    // ── GLOBALS ────────────────────────────────────────────────
    const OT_LIST = ['OT1', 'OT2', 'OT3', 'OT4', 'OT5', 'GM1', 'GM2'];
    let currentData = {
      mapping: {},
      scores: {},
      targets: {},
      otConfigs: {}
    };
    let studentList = [];
    let selectedPC = null;
    let isTyping = false;
    let actionLog = [];
    let refreshTimer = null;
    let currentSort = 'pc_asc';
    let currentLegendFilter = 'all'; // Biến lưu trạng thái lọc
    const classGrade = <?= $khoiLop ?>; // Khối lớp PHP truyền xuống (6, 7 hoặc 8)

    // ── TOAST & LOG ─────────────────────────────────────────────
    function showToast(msg, dur = 2800) {
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.classList.add('show');
      clearTimeout(t._t);
      t._t = setTimeout(() => t.classList.remove('show'), dur);
    }

    function logAction(msg) {
      const now = new Date();
      const ts = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0') + ':' + now.getSeconds().toString().padStart(2, '0');
      actionLog.unshift(ts + ' — ' + msg);
      if (actionLog.length > 30) actionLog.pop();
      document.getElementById('actionLog').innerHTML = actionLog.join('<br>');
    }

    // ── MODALS ───────────────────────────────────────────────────
    function openModal(title, bodyHtml, onOk) {
      document.getElementById('modalTitle').textContent = title;
      document.getElementById('modalBody').innerHTML = bodyHtml;
      document.getElementById('modalOk').onclick = onOk;
      document.getElementById('modalBg').classList.add('open');
    }

    function closeModal() {
      document.getElementById('modalBg').classList.remove('open');
    }

    function toggleListModal() {
      const m = document.getElementById('listModalBg');
      if (m.classList.contains('open')) m.classList.remove('open');
      else m.classList.add('open');
    }

    // ── API & REFRESH ────────────────────────────────────────────
    function apiPost(body) {
      return fetch('api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: body
      });
    }

    function doRefresh() {
      const btn = document.getElementById('refreshBtn');
      btn.disabled = true;
      refreshData().finally(() => {
        btn.disabled = false;
      });
    }

    function refreshData() {
      if (isTyping) return Promise.resolve();
      return fetch('api.php?action=get_data').then(r => r.json()).then(data => {
        currentData = data;
        processData();
        document.getElementById('lastUpdate').textContent = 'Cập nhật: ' + new Date().toLocaleTimeString('vi-VN');
      }).catch(() => {});
    }

    // ── XỬ LÝ CHUẨN HÓA DỮ LIỆU & PHÂN LOẠI ĐIỂM ─────────────────
    function parseTimeSec(tStr) {
      if (!tStr || tStr === '—') return 999999;
      let m = 0,
        s = 0;
      let pM = tStr.match(/(\d+)p/);
      let pS = tStr.match(/(\d+)s/);
      if (pM) m = parseInt(pM[1]);
      if (pS) s = parseInt(pS[1]);
      return m * 60 + s;
    }

    function getGradeCategory(score, ot) {
      let s = parseFloat(score);
      if (isNaN(s)) return null;
      let g = classGrade;
      ot = ot || 'OT1';

      if (g === 6) {
        if (ot === 'OT5') {
          if (s >= 53) return 'G';
          if (s >= 41) return 'K';
          if (s >= 30) return 'TB';
          return 'Y';
        } else {
          if (s >= 41) return 'G';
          if (s >= 35) return 'K';
          if (s >= 23) return 'TB';
          return 'Y';
        }
      } else if (g === 7) {
        if (ot === 'OT5') {
          if (s >= 30) return 'G';
          if (s >= 25) return 'K';
          if (s >= 17) return 'TB';
          return 'Y';
        } else {
          if (s >= 41) return 'G';
          if (s >= 35) return 'K';
          if (s >= 23) return 'TB';
          return 'Y';
        }
      } else if (g === 8) {
        if (ot === 'OT5') {
          if (s >= 26) return 'G';
          if (s >= 22) return 'K';
          if (s >= 14) return 'TB';
          return 'Y';
        } else {
          if (s >= 36) return 'G';
          if (s >= 30) return 'K';
          if (s >= 20) return 'TB';
          return 'Y';
        }
      }
      if (s >= 41) return 'G';
      if (s >= 35) return 'K';
      if (s >= 23) return 'TB';
      return 'Y';
    }

    function processData() {
      studentList = [];
      for (let i = 1; i <= 50; i++) {
        const name = currentData.mapping[i] || '';
        const sd = currentData.scores[i] || {};
        const allScr = sd.score !== undefined && sd.score !== '' ? String(sd.score) : '';
        let bestScore = '';
        if (allScr !== '') {
          const parts = allScr.split('|').map(s => parseFloat(s.trim())).filter(n => !isNaN(n));
          bestScore = parts.length ? Math.max(...parts) : '';
        }

        studentList.push({
          pc: i,
          name: name,
          bestScore: bestScore,
          max: sd.max || '',
          time: sd.timeSpent || '',
          timeSec: parseTimeSec(sd.timeSpent),
          lastOT: sd.lastOT || '',
          target: currentData.targets[i] || '',
          assigned: currentData.otConfigs[i] || '',
          allScr: allScr,
          attempts: allScr ? allScr.split('|').length : 0
        });
      }
      renderTable();
      updateStatsAndCharts();
    }

    // ── LỌC VÀ SẮP XẾP BẢNG ─────────────────────────────────────
    function setSort(type) {
      currentSort = type;
      document.querySelectorAll('.sidebar .btn').forEach(b => b.classList.remove('active'));
      document.getElementById('sort_' + type).classList.add('active');
      renderTable();
    }

    function setLegendFilter(type) {
      if (currentLegendFilter === type) currentLegendFilter = 'all';
      else currentLegendFilter = type;

      document.querySelectorAll('.leg-filter').forEach(el => el.classList.remove('active'));
      if (currentLegendFilter !== 'all') document.getElementById('leg_' + type).classList.add('active');

      filterTable();
    }

    function getSortedStudents() {
      return [...studentList].sort((a, b) => {
        if (currentSort === 'pc_asc') return a.pc - b.pc;
        if (currentSort === 'score_desc') {
          let sa = a.bestScore !== '' ? a.bestScore : -1;
          let sb = b.bestScore !== '' ? b.bestScore : -1;
          if (sa !== sb) return sb - sa;
          return a.timeSec - b.timeSec;
        }
        if (currentSort === 'score_asc') {
          let sa = a.bestScore !== '' ? a.bestScore : 9999;
          let sb = b.bestScore !== '' ? b.bestScore : 9999;
          if (sa !== sb) return sa - sb;
          return a.timeSec - b.timeSec;
        }
        if (currentSort === 'time_asc') {
          let ta = a.bestScore !== '' ? a.timeSec : 999999;
          let tb = b.bestScore !== '' ? b.timeSec : 999999;
          return ta - tb;
        }
        return 0;
      });
    }

    function badgeClass(score, target, ot) {
      if (score === '' || score === undefined || score === null) return 'sbadge-na'; // Xám
      let s = parseFloat(score);
      let t = target !== '' ? parseFloat(target) : null;

      if (t !== null) {
        if (s >= t) return 'sbadge-high'; // Xanh
        if (s >= t * 0.60) return 'sbadge-mid'; // Cam
        return 'sbadge-low'; // Đỏ
      } else {
        let grade = getGradeCategory(s, ot);
        if (grade === 'G' || grade === 'K') return 'sbadge-high';
        if (grade === 'TB') return 'sbadge-mid';
        return 'sbadge-low';
      }
    }

    function renderTable() {
      let html = '';
      const sortedData = getSortedStudents();

      sortedData.forEach(st => {
        let assignedStr = st.assigned || '';
        let assignedArr = assignedStr ? assignedStr.split(',').map(s => s.trim()) : [];

        const bc = badgeClass(st.bestScore, st.target, st.lastOT || assignedArr[0]);

        // Màu nền hàng: Xanh nhạt nếu có tên và có điểm
        const rc = (st.name && st.bestScore !== '') ? 'row-high' : '';

        const otWarn = assignedStr && st.lastOT && st.lastOT !== 'UNKNOWN' && !assignedArr.includes(st.lastOT);
        const otClass = st.lastOT ? (assignedStr ? (assignedArr.includes(st.lastOT) ? 'ot-match' : 'ot-mismatch') : 'ot-none') : 'ot-none';

        const isSelected = selectedPC === st.pc;
        let allScrDisplay = st.allScr ? st.allScr.split('|').map(s => s.trim()).join(' | ') : '—';

        html += `<tr id="row-pc${st.pc}" class="${rc}${isSelected?' sel-row':''}" onclick="selectRow(${st.pc})">
  <td><div class="pc-badge ${!st.name?'empty':''}">${st.pc}</div></td>
  <td class="name-cell ${!st.name?'no-name':''}">${st.name || '—'}</td>
  <td style="text-align:center; color:var(--blue-dk); font-weight:600; font-size:14px;">${allScrDisplay}</td>
  <td style="text-align:center;"><span class="sbadge ${bc}">${st.bestScore!==''?st.bestScore:'—'}</span></td>
  <td style="text-align:center; color:var(--gray-600);">${st.attempts>0?st.attempts+'x':'—'}</td>
  <td onclick="event.stopPropagation()" style="text-align:center;">
    <input class="tgt-inp" type="number" value="${st.target}" placeholder="—" onfocus="isTyping=true" onblur="isTyping=false; saveTargetScore(${st.pc}, this.value)" onkeydown="if(event.key==='Enter')this.blur()" />
  </td>
  <td onclick="event.stopPropagation()" style="text-align:center;">
    <div class="ot-sel ${otWarn?'ot-warn':''}" onclick="openOTModal(${st.pc}, '${assignedStr}')">${assignedStr || '--'}</div>
  </td>
  <td class="${otClass}" style="text-align:center;">${st.lastOT||'—'}</td>
  <td class="time-cell" style="text-align:center;">${st.time||'—'}</td>
</tr>`;
      });
      document.getElementById('tableBody').innerHTML = html;
      filterTable();
    }

    // ── UPDATE STATS & BỘ ĐẾM CHÚ THÍCH ────────────────────────
    function updateStatsAndCharts() {
      let total = 0,
        scored = 0,
        sum = 0,
        pass = 0,
        fail = 0,
        otFail = 0;
      let countG = 0,
        countK = 0,
        countTB = 0,
        countY = 0;
      let cPass = 0,
        cMid = 0,
        cLow = 0,
        cNone = 0,
        cOtfail = 0;
      let topArr = [];

      studentList.forEach(st => {
        if (st.name) {
          total++;
          let assignedArr = st.assigned ? st.assigned.split(',').map(s => s.trim()) : [];
          const isOtFail = (st.assigned && st.lastOT && st.lastOT !== 'UNKNOWN' && !assignedArr.includes(st.lastOT));
          if (isOtFail) cOtfail++;

          if (st.bestScore === '') {
            cNone++;
          } else {
            scored++;
            sum += st.bestScore;
            const bc = badgeClass(st.bestScore, st.target, st.lastOT || assignedArr[0]);

            if (bc === 'sbadge-high') {
              cPass++;
              pass++;
            } else if (bc === 'sbadge-mid') {
              cMid++;
              fail++;
            } else if (bc === 'sbadge-low') {
              cLow++;
              fail++;
            }

            if (isOtFail) otFail++;

            let gradeCat = getGradeCategory(st.bestScore, st.lastOT || assignedArr[0]);
            if (gradeCat === 'G') countG++;
            if (gradeCat === 'K') countK++;
            if (gradeCat === 'TB') countTB++;
            if (gradeCat === 'Y') countY++;

            topArr.push({
              pc: st.pc,
              name: st.name,
              score: st.bestScore,
              timeSec: st.timeSec
            });
          }
        }
      });

      document.getElementById('stScored').textContent = scored;
      document.getElementById('stTotal').textContent = total;
      document.getElementById('stAvg').textContent = scored ? Math.round(sum / scored) : '—';
      document.getElementById('stPass').textContent = pass;
      document.getElementById('stFail').textContent = fail;
      document.getElementById('stOTFail').textContent = otFail;

      document.getElementById('valG').textContent = countG;
      document.getElementById('distG').style.width = scored ? (countG / scored * 100) + '%' : '0%';
      document.getElementById('valK').textContent = countK;
      document.getElementById('distK').style.width = scored ? (countK / scored * 100) + '%' : '0%';
      document.getElementById('valTB').textContent = countTB;
      document.getElementById('distTB').style.width = scored ? (countTB / scored * 100) + '%' : '0%';
      document.getElementById('valY').textContent = countY;
      document.getElementById('distY').style.width = scored ? (countY / scored * 100) + '%' : '0%';

      document.getElementById('c_pass').textContent = `(${cPass})`;
      document.getElementById('c_mid').textContent = `(${cMid})`;
      document.getElementById('c_low').textContent = `(${cLow})`;
      document.getElementById('c_none').textContent = `(${cNone})`;
      document.getElementById('c_otfail').textContent = `(${cOtfail})`;

      topArr.sort((a, b) => {
        if (b.score !== a.score) return b.score - a.score;
        return a.timeSec - b.timeSec;
      });
      let topHtml = '';
      for (let i = 0; i < Math.min(5, topArr.length); i++) {
        let rClass = i === 0 ? 'rank-1' : (i === 1 ? 'rank-2' : (i === 2 ? 'rank-3' : 'rank-n'));
        topHtml += `<div class="top-item" onclick="selectRow(${topArr[i].pc})"><span class="top-rank ${rClass}">${i+1}</span><span class="top-name">${topArr[i].name} <span style="font-size:11px; color:var(--gray-500); font-weight:normal;">(Máy ${topArr[i].pc})</span></span><span class="top-score">${topArr[i].score}</span></div>`;
      }
      if (!topHtml) topHtml = '<div style="text-align:center; color:var(--gray-500); font-size:13px; font-style:italic; padding:10px 0;">Chưa có học sinh nộp bài</div>';
      document.getElementById('topStudentsList').innerHTML = topHtml;
    }

    // ── BỘ LỌC CHÍNH ─────────────────────────
    function filterTable() {
      const search = (document.getElementById('searchInp').value || '').toLowerCase().trim();
      document.querySelectorAll('#tableBody tr').forEach(row => {
        const pc = parseInt(row.id.replace('row-pc', ''));
        const st = studentList.find(s => s.pc === pc);
        if (!st) return;

        let show = true;
        if (search && !(st.name || '').toLowerCase().includes(search) && !String(pc).includes(search)) show = false;

        if (currentLegendFilter !== 'all') {
          let assignedArr = st.assigned ? st.assigned.split(',').map(s => s.trim()) : [];
          const isOtFail = (st.assigned && st.lastOT && st.lastOT !== 'UNKNOWN' && !assignedArr.includes(st.lastOT));
          const bc = badgeClass(st.bestScore, st.target, st.lastOT || assignedArr[0]);

          if (currentLegendFilter === 'pass' && bc !== 'sbadge-high') show = false;
          if (currentLegendFilter === 'mid' && bc !== 'sbadge-mid') show = false;
          if (currentLegendFilter === 'low' && bc !== 'sbadge-low') show = false;
          if (currentLegendFilter === 'none' && st.bestScore !== '') show = false;
          if (currentLegendFilter === 'otfail' && !isOtFail) show = false;
        }

        row.style.display = show ? '' : 'none';
      });
    }

    // ── SELECT ROW & DETAIL ──────────────────────────────────────
    function selectRow(pc) {
      const old = selectedPC;
      selectedPC = (selectedPC === pc) ? null : pc;
      if (old) {
        const r = document.getElementById('row-pc' + old);
        if (r) r.classList.remove('sel-row');
      }
      if (selectedPC) {
        const r = document.getElementById('row-pc' + selectedPC);
        if (r) r.classList.add('sel-row');
      }
      showDetail(selectedPC);
    }

    function showDetail(pc) {
      const panel = document.getElementById('detailContent');
      const allPanel = document.getElementById('allScores');
      if (!pc) {
        panel.innerHTML = '<div style="text-align:center;padding:20px 0;color:var(--gray-500);font-size:14px;font-style:italic;">Nhấn vào một học sinh để xem chi tiết</div>';
        allPanel.innerHTML = '—';
        return;
      }
      const st = studentList.find(s => s.pc === pc);
      if (!st) return;

      let assignedArr = st.assigned ? st.assigned.split(',').map(s => s.trim()) : [];
      const bc = badgeClass(st.bestScore, st.target, st.lastOT || assignedArr[0]);
      let scoreColor = 'var(--gray-700)';
      if (bc === 'sbadge-high') scoreColor = 'var(--green)';
      if (bc === 'sbadge-mid') scoreColor = 'var(--amber)';
      if (bc === 'sbadge-low') scoreColor = 'var(--red)';

      const otMatchClass = (st.lastOT !== '' && st.assigned !== '') ? (assignedArr.includes(st.lastOT) ? 'chip-green' : 'chip-red') : 'chip-gray';
      const pct = (st.bestScore !== '' && st.max) ? Math.round((st.bestScore / st.max) * 100) : null;

      panel.innerHTML = `
<div style="text-align:center; padding:10px 0 12px;">
  <div class="avatar">${st.pc}</div><div class="dp-name">${st.name || '(Chưa có tên)'}</div><div class="dp-sub">Máy số ${st.pc}</div>
</div>
<div style="background:var(--gray-50); border-radius:10px; padding:14px; margin-bottom:12px; text-align:center; border: 1px solid var(--gray-200);">
  <div class="big-score" style="color:${scoreColor}">${st.bestScore !== '' ? st.bestScore : '—'}</div>
  <div class="big-max">/ ${st.max||'?'} điểm</div>
  ${pct !== null ? `<div class="prog-wrap"><div class="prog-bar" style="width:${pct}%; background:${scoreColor};"></div></div><div style="font-size:13px; font-weight:700; color:var(--gray-600); text-align:right; margin-top:4px;">${pct}%</div>` : ''}
</div>
<div class="dp-row"><span class="dp-lbl">Chỉ tiêu</span><span class="dp-val">${st.target||'—'}</span></div>
<div class="dp-row"><span class="dp-lbl">Bài được giao</span><span class="dp-val">${st.assigned||'—'}</span></div>
<div class="dp-row">
  <span class="dp-lbl">Bài đã thi</span>
  <span class="chip ${otMatchClass}" style="font-size:13px;">${st.lastOT||'—'}</span>
</div>
<div class="dp-row"><span class="dp-lbl">Thời gian</span><span class="dp-val">${st.time||'—'}</span></div>
<div class="dp-row"><span class="dp-lbl">Số lần thi</span><span class="dp-val">${st.attempts || 0} lần</span></div>
`;
      if (st.allScr) {
        // CHỈNH SỬA YÊU CẦU 4: Format hiển thị lần thi
        allPanel.innerHTML = st.allScr.split('|').map((s, idx) => `<span class="chip chip-blue" style="margin:3px; font-size:13px; padding:5px 12px;">Lần ${idx+1}: ${s.trim()}</span>`).join(' ');
      } else {
        allPanel.innerHTML = '<span style="color:var(--gray-500); font-style:italic;">Chưa có dữ liệu</span>';
      }
    }

    // ── SAVE OPERATIONS ──────────────────────────────────────────
    function saveList() {
      const data = document.getElementById('txtList').value;
      apiPost('action=save_list&data=' + encodeURIComponent(data)).then(() => {
        showToast('✅ Đã lưu danh sách học sinh!');
        logAction('Lưu danh sách học sinh');
        refreshData();
        toggleListModal();
      });
    }

    function doDeleteScores() {
      if (!confirm('Xóa toàn bộ điểm của lớp hiện tại?\nHành động này không thể hoàn tác!')) return;
      apiPost('action=delete_scores').then(() => {
        showToast('🗑️ Đã xóa toàn bộ điểm!');
        logAction('Xóa toàn bộ điểm');
        refreshData();
      });
    }

    function saveTargetScore(pc, val) {
      val = val.trim();
      apiPost('action=save_target&pc=' + pc + '&target=' + encodeURIComponent(val)).then(() => {
        currentData.targets[pc] = val;
        processData();
        if (selectedPC === pc) showDetail(pc);
        logAction(`Chỉ tiêu máy ${pc}: ${val}`);
      });
    }

    function saveOT(pc, ot) {
      apiPost('action=save_ot&pc=' + pc + '&ot=' + ot).then(() => {
        currentData.otConfigs[pc] = ot;
        processData();
        if (selectedPC === pc) showDetail(pc);
        logAction(`Giao bài máy ${pc}: ${ot||'(xóa)'}`);
      });
    }

    // ── OT MODALS ────────────────────────
    function openOTModal(pc, currentAssigned) {
      let arr = currentAssigned ? currentAssigned.split(',').map(s => s.trim()) : [];
      let html = `<div style="font-size:14px; font-weight:bold; margin-bottom:10px;">Chọn bài thi cho Máy ${pc}:</div><div style="display:flex; flex-wrap:wrap; gap:10px; background:var(--gray-50); padding:14px; border-radius:6px; border:1px solid var(--gray-200);">`;
      OT_LIST.forEach(ot => {
        let checked = arr.includes(ot) ? 'checked' : '';
        html += `<label class="cb-label"><input type="checkbox" value="${ot}" class="cb-ot-single" ${checked}> ${ot}</label>`;
      });
      html += `</div><div style="margin-top:8px; font-size:13px; color:var(--gray-500); font-style:italic;">* Bỏ chọn tất cả để gỡ bài</div>`;
      openModal(`Giao Bài Thi`, html, function() {
        let selected = Array.from(document.querySelectorAll('.cb-ot-single:checked')).map(cb => cb.value).join(',');
        saveOT(pc, selected);
        closeModal();
      });
    }

    function openBulkTarget() {
      openModal('Đặt chỉ tiêu điểm hàng loạt',
        `<div class="inp-row"><span class="inp-lbl" style="min-width:60px;">Từ máy</span><input class="inp" id="mb1" type="number" min="1" max="50" placeholder="1"/></div>
 <div class="inp-row"><span class="inp-lbl" style="min-width:60px;">Đến máy</span><input class="inp" id="mb2" type="number" min="1" max="50" placeholder="50"/></div>
 <div class="inp-row"><span class="inp-lbl" style="min-width:60px;">Chỉ tiêu</span><input class="inp" id="mb3" type="number" placeholder="VD: 800"/></div>`,
        function() {
          const s = document.getElementById('mb1').value,
            e = document.getElementById('mb2').value,
            v = document.getElementById('mb3').value;
          if (!s || !e || !v) {
            showToast('⚠️ Vui lòng điền đầy đủ!');
            return;
          }
          apiPost(`action=save_bulk_target&start=${s}&end=${e}&target=${v}`).then(() => {
            closeModal();
            refreshData();
            showToast(`✅ Đã đặt chỉ tiêu ${v} cho máy ${s}–${e}`);
            logAction(`Chỉ tiêu loạt máy ${s}–${e}: ${v}`);
          });
        }
      );
    }

    function openBulkOT() {
      let html = `<div class="inp-row"><span class="inp-lbl" style="min-width:60px;">Từ máy</span><input class="inp" id="mo1" type="number" min="1" max="50" placeholder="1"/></div>
          <div class="inp-row"><span class="inp-lbl" style="min-width:60px;">Đến máy</span><input class="inp" id="mo2" type="number" min="1" max="50" placeholder="50"/></div>
          <div style="margin-top:12px; font-size:14px; font-weight:bold; margin-bottom:8px;">Chọn bài thi (Có thể chọn nhiều):</div>
          <div style="display:flex; flex-wrap:wrap; gap:10px; background:var(--gray-50); padding:14px; border-radius:6px; border:1px solid var(--gray-200);">`;
      OT_LIST.forEach(ot => {
        html += `<label class="cb-label"><input type="checkbox" value="${ot}" class="cb-ot-bulk"> ${ot}</label>`;
      });
      html += `</div><div style="margin-top:8px; font-size:13px; color:var(--gray-500); font-style:italic;">* Bỏ chọn tất cả để HỦY giao bài</div>`;

      openModal('Giao bài thi hàng loạt', html, function() {
        const s = document.getElementById('mo1').value,
          e = document.getElementById('mo2').value;
        let selected = Array.from(document.querySelectorAll('.cb-ot-bulk:checked')).map(cb => cb.value).join(',');
        if (!s || !e) {
          showToast('⚠️ Vui lòng nhập phạm vi máy!');
          return;
        }
        apiPost(`action=save_bulk_ot&start=${s}&end=${e}&ot=${selected}`).then(() => {
          closeModal();
          refreshData();
          showToast(`✅ Đã giao bài "${selected||'(xóa)'}" cho máy ${s}–${e}`);
          logAction(`Giao bài loạt máy ${s}–${e}: ${selected||'(xóa)'}`);
        });
      });
    }

    // ── KEYBOARD NAV ─────────────────────────────────────────────
    window.addEventListener('keydown', function(e) {
      if (isTyping) return;
      if (!['ArrowUp', 'ArrowDown', 'Home', 'End', 'Escape'].includes(e.key)) return;
      e.preventDefault();
      if (e.key === 'Escape') {
        selectRow(selectedPC);
        return;
      }
      const old = selectedPC;
      if (!selectedPC) {
        selectedPC = 1;
      } else if (e.key === 'ArrowDown' && selectedPC < 50) selectedPC++;
      else if (e.key === 'ArrowUp' && selectedPC > 1) selectedPC--;
      else if (e.key === 'Home') selectedPC = 1;
      else if (e.key === 'End') selectedPC = 50;
      if (old !== selectedPC) {
        if (old) {
          const r = document.getElementById('row-pc' + old);
          if (r) r.classList.remove('sel-row');
        }
        const nr = document.getElementById('row-pc' + selectedPC);
        if (nr) {
          nr.classList.add('sel-row');
          nr.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
          });
        }
        showDetail(selectedPC);
      }
    });

    function startAutoRefresh() {
      refreshTimer = setInterval(() => {
        if (!isTyping) refreshData();
      }, 5000);
    }
    document.addEventListener('visibilitychange', function() {
      if (document.hidden) {
        clearInterval(refreshTimer);
      } else {
        refreshData();
        startAutoRefresh();
      }
    });

    refreshData();
    startAutoRefresh();
  </script>
</body>

</html>