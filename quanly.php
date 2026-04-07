<?php
// ═══════════════════════════════════════════════════════════
//  IC3 CLASSROOM MANAGER – Dashboard Template
// ═══════════════════════════════════════════════════════════
$tenLop = file_exists('active_class.txt') ? trim(file_get_contents('active_class.txt')) : '';
if (empty($tenLop)) {
  header("Location: index.php");
  exit;
}

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
  <link rel="stylesheet" href="css/quanly.css">
</head>

<body>

  <!-- ── TOPBAR ─────────────────────────────────────────── -->
  <div class="topbar">
    <div class="logo-badge">IC3</div>
    <div class="class-pill" id="classLabel"><?= htmlspecialchars($tenLop) ?></div>
    <div class="sep"></div>

    <a href="index.php" class="btn btn-sm">
      <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 4L7 10l6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Đổi lớp
    </a>
    <button class="btn btn-green btn-sm" onclick="doRefresh()" id="refreshBtn">
      <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 10a6 6 0 1 0 1.6-4" stroke-linecap="round"/><path d="M4 4v3h3" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Làm mới
    </button>

    <div class="sep"></div>

    <button class="btn btn-sm" onclick="toggleListModal()">
      <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h12M4 10h12M4 14h7" stroke-linecap="round"/></svg>
      Danh sách HS
    </button>
    <a href="chongoi.php" class="btn btn-sm" target="_blank">
      <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="14" height="14" rx="2" stroke-linecap="round"/><path d="M7 7h6M7 11h6" stroke-linecap="round"/></svg>
      Sơ đồ chỗ ngồi
    </a>
    <button class="btn btn-blue btn-sm" onclick="toggleIPModal()">
      <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16z" stroke-linecap="round"/><path d="M2 10h16M10 2c2.5 2.5 4 5.5 4 8s-1.5 5.5-4 8c-2.5-2.5-4-5.5-4-8s1.5-5.5 4-8z" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Quản lý IP
    </button>

    <div class="sep"></div>

    <!-- ── DROPDOWN: Công cụ ── -->
    <div class="toolbar-dropdown" id="toolsDropdown">
      <button class="btn btn-sm" onclick="toggleToolsMenu()">
        ⚙️ Công cụ ▾
      </button>
      <div class="toolbar-dropdown-menu" id="toolsMenu">
        <button class="dd-item" onclick="openBulkTarget(); closeToolsMenu();">🎯 Đặt mục tiêu hàng loạt</button>
        <button class="dd-item dd-danger" onclick="openClearBulkTarget(); closeToolsMenu();">🚫 Xoá mục tiêu hàng loạt</button>
        <div class="dd-sep"></div>
        <button class="dd-item" onclick="openBulkOT(); closeToolsMenu();">📋 Giao bài hàng loạt</button>
        <button class="dd-item dd-danger" onclick="openClearBulkOT(); closeToolsMenu();">🚫 Huỷ giao bài hàng loạt</button>
        <div class="dd-sep"></div>
        <a class="dd-item" href="export_excel.php" target="_blank" onclick="closeToolsMenu();">📊 Xuất Excel</a>
        <div class="dd-sep"></div>
        <button class="dd-item dd-danger" onclick="doDeleteScores(); closeToolsMenu();">🗑️ Xóa toàn bộ điểm</button>
      </div>
    </div>

    <div class="spacer"></div>
    <span id="lastUpdate" style="font-size:12px; color:var(--gray-500); font-weight:600;"></span>
  </div>

  <!-- ── LAYOUT ─────────────────────────────────────────── -->
  <div class="layout">

    <!-- SIDEBAR -->
    <div class="sidebar">

      <div class="sb-section">
        <div class="sb-title">Thống kê nhanh</div>
        <div class="stat-grid">
          <div class="stat-card"><div class="stat-val" id="stScored">0</div><div class="stat-lbl">Có điểm</div></div>
          <div class="stat-card"><div class="stat-val" id="stTotal">0</div><div class="stat-lbl">Học sinh</div></div>
          <div class="stat-card"><div class="stat-val green" id="stAvg">—</div><div class="stat-lbl">Điểm TB</div></div>
          <div class="stat-card"><div class="stat-val amber" id="stPass">0</div><div class="stat-lbl">Đạt mục tiêu</div></div>
          <div class="stat-card"><div class="stat-val red" id="stFail">0</div><div class="stat-lbl">Chưa đạt</div></div>
          <div class="stat-card"><div class="stat-val red" id="stOTFail">0</div><div class="stat-lbl">Sai bài thi</div></div>
        </div>
      </div>

      <div class="sb-section">
        <div class="sb-title">Sắp Xếp Dữ Liệu</div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
          <button class="btn" id="sort_score_desc" onclick="setSort('score_desc')" style="padding:8px 4px; font-size:13px; gap:4px;">🔽 Điểm Cao</button>
          <button class="btn" id="sort_score_asc"  onclick="setSort('score_asc')"  style="padding:8px 4px; font-size:13px; gap:4px;">🔼 Điểm Thấp</button>
          <button class="btn" id="sort_time_asc"   onclick="setSort('time_asc')"   style="padding:8px 4px; font-size:13px; gap:4px;">⚡ Nhanh nhất</button>
          <button class="btn active" id="sort_pc_asc" onclick="setSort('pc_asc')"  style="padding:8px 4px; font-size:13px; gap:4px;">💻 Mặc định</button>
        </div>
      </div>

      <div class="sb-section">
        <div class="sb-title">Biểu đồ Phổ Điểm</div>
        <div class="dist-row"><div class="dist-lbl">Giỏi</div><div class="prog-wrap" style="flex:1;"><div class="prog-bar" id="distG" style="background:#1e8e3e; width:0%"></div></div><div class="dist-val" id="valG">0</div></div>
        <div class="dist-row"><div class="dist-lbl">Khá</div><div class="prog-wrap" style="flex:1;"><div class="prog-bar" id="distK" style="background:#f1c40f; width:0%"></div></div><div class="dist-val" id="valK">0</div></div>
        <div class="dist-row"><div class="dist-lbl">TB</div><div class="prog-wrap" style="flex:1;"><div class="prog-bar" id="distTB" style="background:#e67e22; width:0%"></div></div><div class="dist-val" id="valTB">0</div></div>
        <div class="dist-row"><div class="dist-lbl">Yếu</div><div class="prog-wrap" style="flex:1;"><div class="prog-bar" id="distY" style="background:#d93025; width:0%"></div></div><div class="dist-val" id="valY">0</div></div>
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
        <div class="legend" style="font-style:italic; font-size:12px; margin-bottom:8px; color:var(--gray-500);">* Bấm vào từng mục để lọc danh sách</div>
        <div class="legend">
          <div class="leg-filter" id="leg_pass" onclick="setLegendFilter('pass')">
            <div class="ldot" style="background:#e6f4ea; border:2px solid #1e8e3e;"></div>
            Đạt mục tiêu / Tốt <span class="leg-count" id="c_pass">(0)</span>
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

    <!-- MAIN TABLE -->
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
              <th style="width:90px;">Mục tiêu</th>
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

    <!-- DETAIL PANEL -->
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

  <!-- ── MODAL: Generic ────────────────────────────────── -->
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

  <!-- ── MODAL: Student List (Spreadsheet Style) ──────── -->
  <div class="modal-bg" id="listModalBg" onclick="if(event.target===this) toggleListModal()">
    <div class="modal" style="width: 95vw; max-width: 1400px; max-height: 95vh; display: flex; flex-direction: column;">
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
        <div class="modal-title" style="margin-bottom:0;">📊 Danh sách học sinh</div>
        <div style="display:flex; gap:8px; align-items:center;">
          <span id="sheetInfo" style="font-size:13px; color:var(--gray-500); font-weight:600;"></span>
          <button class="btn btn-sm" onclick="clearSheetData()" style="color:var(--red);">🗑️ Xoá hết</button>
        </div>
      </div>

      <div style="font-size:13px; color:var(--gray-500); margin-bottom:10px; line-height:1.5;">
        💡 <b>Copy từ Google Sheet</b> → click vào ô đầu tiên → <b>Ctrl+V</b> để dán. Hệ thống tự nhận diện cột Họ tên, PC, Bài giao, Mục tiêu.
      </div>

      <!-- Spreadsheet Grid -->
      <div id="sheetContainer" style="flex:1; overflow:auto; border:2px solid var(--gray-300); border-radius:8px; background:#fff; min-height:400px; position:relative;">
        <table id="sheetTable" class="sheet-table">
          <thead id="sheetHead"></thead>
          <tbody id="sheetBody"></tbody>
        </table>
      </div>

      <!-- Hidden raw data storage -->
      <textarea id="txtList" style="display:none;" onfocus="isTyping=true" onblur="isTyping=false"><?php
        $pathDS = "danhsach/danhsach_$tenLop.txt";
        if (file_exists($pathDS)) echo htmlspecialchars(file_get_contents($pathDS));
      ?></textarea>

      <div class="modal-foot" style="margin-top:12px;">
        <button class="btn" onclick="toggleListModal()">Đóng</button>
        <button class="btn btn-primary" onclick="saveList()">💾 Lưu danh sách</button>
      </div>
    </div>
  </div>

  <!-- ── MODAL: IP Management ─────────────────────────── -->
  <div class="modal-bg" id="ipModalBg" onclick="if(event.target===this) toggleIPModal()">
    <div class="modal" style="width: 90vw; max-width: 1100px; max-height: 95vh; display: flex; flex-direction: column;">
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
        <div class="modal-title" style="margin-bottom:0;">🌐 Quản lý IP Máy Học Sinh</div>
        <div style="display:flex; gap:8px; align-items:center;">
          <span id="ipCount" style="font-size:13px; color:var(--gray-500); font-weight:600;"></span>
          <span id="ipLastUpdate" style="font-size:12px; color:var(--gray-400);"></span>
        </div>
      </div>

      <!-- Server IP & BAT generation -->
      <div style="background: linear-gradient(135deg, #e8f5e9, #f1f8e9); border-radius:10px; padding:16px; margin-bottom:14px; border:1px solid #c8e6c9;">
        <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
          <div style="flex:1; min-width:200px;">
            <div style="font-size:13px; font-weight:700; color:#2e7d32; margin-bottom:6px;">📡 IP Máy Chủ (Server)</div>
            <div style="display:flex; gap:8px; align-items:center;">
              <input class="inp" id="serverIPInput" type="text" placeholder="Đang phát hiện..." style="max-width:200px; font-size:15px; font-weight:700; text-align:center;" />
              <button class="btn btn-green btn-sm" onclick="generateBat()" style="white-space:nowrap;">⚡ Tạo file lay_ip.bat</button>
            </div>
          </div>
          <div style="font-size:12px; color:#558b2f; line-height:1.6; max-width:400px;">
            💡 <b>Quy trình:</b> Tạo file → Gửi qua NetSupport → Học sinh chạy → IP tự cập nhật<br>
            📁 File sẽ được lưu tại <code>ic3-manager/lay_ip.bat</code>
          </div>
        </div>
      </div>

      <!-- Action buttons -->
      <div style="display:flex; gap:8px; margin-bottom:12px; flex-wrap:wrap;">
        <button class="btn btn-green" onclick="loadIPMapping()">🔄 Làm mới danh sách IP</button>
        <button class="btn btn-red btn-sm" onclick="clearIPMapping()">🗑️ Xoá tất cả IP</button>
        <div class="spacer"></div>
        <a href="lay_ip.bat" download class="btn btn-sm" style="text-decoration:none;">📥 Tải lay_ip.bat</a>
      </div>

      <!-- IP Table -->
      <div style="flex:1; overflow:auto; border:2px solid var(--gray-300); border-radius:8px; background:#fff; min-height:300px;">
        <table class="sheet-table">
          <thead>
            <tr>
              <th style="width:60px;">#</th>
              <th style="width:100px;">Tên máy</th>
              <th style="width:80px;">Số PC</th>
              <th>Địa chỉ IP</th>
              <th style="width:200px;">Học sinh (nếu có)</th>
              <th style="width:80px;">Trạng thái</th>
            </tr>
          </thead>
          <tbody id="ipTableBody">
            <tr><td colspan="6" style="text-align:center; padding:40px; color:var(--gray-500); font-style:italic;">Nhấn "Làm mới" để tải dữ liệu IP</td></tr>
          </tbody>
        </table>
      </div>

      <div class="modal-foot" style="margin-top:12px;">
        <button class="btn" onclick="toggleIPModal()">Đóng</button>
      </div>
    </div>
  </div>

  <div class="toast" id="toast"></div>

  <!-- ── SCRIPTS ───────────────────────────────────────── -->
  <script>
    const CONFIG = {
      khoiLop: <?= $khoiLop ?>,
      tenLop: '<?= addslashes($tenLop) ?>'
    };
  </script>
  <script src="js/quanly.js"></script>
</body>

</html>