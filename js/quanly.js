// ═══════════════════════════════════════════════════════════
//  IC3 CLASSROOM MANAGER – Dashboard JavaScript
//  Requires CONFIG.khoiLop injected from PHP
// ═══════════════════════════════════════════════════════════

// ── GLOBALS ──────────────────────────────────────────────
const OT_LIST = ['OT1', 'OT2', 'OT3', 'OT4', 'OT5', 'GM1', 'GM2'];
let currentData = { mapping: {}, scores: {}, targets: {}, otConfigs: {} };
let studentList = [];
let selectedPC = null;
let isTyping = false;
let actionLog = [];
let refreshTimer = null;
let currentSort = 'pc_asc';
let currentLegendFilter = 'all';
const classGrade = (typeof CONFIG !== 'undefined') ? CONFIG.khoiLop : 0;

// ── TOAST & LOG ──────────────────────────────────────────
function showToast(msg, dur = 2800) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(t._t);
  t._t = setTimeout(() => t.classList.remove('show'), dur);
}

function logAction(msg) {
  const now = new Date();
  const ts = now.getHours().toString().padStart(2, '0') + ':' +
             now.getMinutes().toString().padStart(2, '0') + ':' +
             now.getSeconds().toString().padStart(2, '0');
  actionLog.unshift(ts + ' — ' + msg);
  if (actionLog.length > 30) actionLog.pop();
  document.getElementById('actionLog').innerHTML = actionLog.join('<br>');
}

// ── MODALS ───────────────────────────────────────────────
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
  if (m.classList.contains('open')) {
    m.classList.remove('open');
  } else {
    m.classList.add('open');
    renderSheetFromTextarea();
  }
}

// ── API & REFRESH ────────────────────────────────────────
function apiPost(body) {
  return fetch('api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: body
  });
}

function doRefresh() {
  const btn = document.getElementById('refreshBtn');
  btn.disabled = true;
  refreshData().finally(() => { btn.disabled = false; });
}

function refreshData() {
  if (isTyping) return Promise.resolve();
  return fetch('api.php?action=get_data&t=' + new Date().getTime()).then(r => r.json()).then(data => {
    currentData = data;
    processData();
    document.getElementById('lastUpdate').textContent =
      'Cập nhật: ' + new Date().toLocaleTimeString('vi-VN');
  }).catch(() => {});
}

// ── DATA PROCESSING ──────────────────────────────────────
function parseTimeSec(tStr) {
  if (!tStr || tStr === '—') return 999999;
  let m = 0, s = 0;
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
      if (s >= 53) return 'G'; if (s >= 41) return 'K'; if (s >= 30) return 'TB'; return 'Y';
    } else {
      if (s >= 41) return 'G'; if (s >= 35) return 'K'; if (s >= 23) return 'TB'; return 'Y';
    }
  } else if (g === 7) {
    if (ot === 'OT5') {
      if (s >= 30) return 'G'; if (s >= 25) return 'K'; if (s >= 17) return 'TB'; return 'Y';
    } else {
      if (s >= 41) return 'G'; if (s >= 35) return 'K'; if (s >= 23) return 'TB'; return 'Y';
    }
  } else if (g === 8) {
    if (ot === 'OT5') {
      if (s >= 26) return 'G'; if (s >= 22) return 'K'; if (s >= 14) return 'TB'; return 'Y';
    } else {
      if (s >= 36) return 'G'; if (s >= 30) return 'K'; if (s >= 20) return 'TB'; return 'Y';
    }
  }
  if (s >= 41) return 'G'; if (s >= 35) return 'K'; if (s >= 23) return 'TB'; return 'Y';
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

// ── SORT & FILTER ────────────────────────────────────────
function setSort(type) {
  currentSort = type;
  document.querySelectorAll('.sidebar .btn[id^="sort_"]').forEach(b => b.classList.remove('active'));
  const el = document.getElementById('sort_' + type);
  if (el) el.classList.add('active');
  renderTable();
}

function setLegendFilter(type) {
  if (currentLegendFilter === type) currentLegendFilter = 'all';
  else currentLegendFilter = type;

  document.querySelectorAll('.leg-filter').forEach(el => el.classList.remove('active'));
  if (currentLegendFilter !== 'all') {
    const el = document.getElementById('leg_' + type);
    if (el) el.classList.add('active');
  }
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
  if (score === '' || score === undefined || score === null) return 'sbadge-na';
  let s = parseFloat(score);
  let t = target !== '' ? parseFloat(target) : null;

  if (t !== null) {
    if (s >= t) return 'sbadge-high';
    if (s >= t * 0.60) return 'sbadge-mid';
    return 'sbadge-low';
  } else {
    let grade = getGradeCategory(s, ot);
    if (grade === 'G' || grade === 'K') return 'sbadge-high';
    if (grade === 'TB') return 'sbadge-mid';
    return 'sbadge-low';
  }
}

// ── TABLE RENDERING ──────────────────────────────────────
function renderTable() {
  let html = '';
  const sortedData = getSortedStudents();

  sortedData.forEach(st => {
    let assignedStr = st.assigned || '';
    let assignedArr = assignedStr ? assignedStr.split(',').map(s => s.trim()) : [];
    const bc = badgeClass(st.bestScore, st.target, st.lastOT || assignedArr[0]);
    const rc = (st.name && st.bestScore !== '') ? 'row-high' : '';
    const isCompleted = assignedStr === 'Hoàn thành';
    const otWarn = !isCompleted && assignedStr && st.lastOT && st.lastOT !== 'UNKNOWN' && !assignedArr.includes(st.lastOT);
    const otClass = st.lastOT ? (assignedStr ? (isCompleted || assignedArr.includes(st.lastOT) ? 'ot-match' : 'ot-mismatch') : 'ot-none') : 'ot-none';
    const isSelected = selectedPC === st.pc;
    let allScrDisplay = st.allScr ? st.allScr.split('|').map(s => s.trim()).join(' | ') : '—';

    html += `<tr id="row-pc${st.pc}" class="${rc}${isSelected ? ' sel-row' : ''}" onclick="selectRow(${st.pc})">
  <td><div class="pc-badge ${!st.name ? 'empty' : ''}">${st.pc}</div></td>
  <td class="name-cell ${!st.name ? 'no-name' : ''}">${st.name || '—'}</td>
  <td style="text-align:center; color:var(--blue-dk); font-weight:600; font-size:14px;">${allScrDisplay}</td>
  <td style="text-align:center;"><span class="sbadge ${bc}">${st.bestScore !== '' ? st.bestScore : '—'}</span></td>
  <td style="text-align:center; color:var(--gray-600);">${st.attempts > 0 ? st.attempts + 'x' : '—'}</td>
  <td onclick="event.stopPropagation()" style="text-align:center;">
    <input class="tgt-inp" type="number" min="0" value="${st.target}" placeholder="—" onfocus="isTyping=true" onblur="isTyping=false; saveTargetScore(${st.pc}, this.value)" onkeydown="if(event.key==='Enter')this.blur()" />
  </td>
  <td onclick="event.stopPropagation()" style="text-align:center;">
    <div class="ot-sel ${otWarn ? 'ot-warn' : ''} ${isCompleted ? 'ot-done' : ''}" onclick="openOTModal(${st.pc}, '${assignedStr}')">${assignedStr || '--'}</div>
  </td>
  <td class="${otClass}" style="text-align:center;">${st.lastOT || '—'}</td>
  <td class="time-cell" style="text-align:center;">${st.time || '—'}</td>
</tr>`;
  });
  document.getElementById('tableBody').innerHTML = html;
  filterTable();
}

// ── STATS & CHARTS ───────────────────────────────────────
function updateStatsAndCharts() {
  let total = 0, scored = 0, sum = 0, pass = 0, fail = 0, otFail = 0;
  let countG = 0, countK = 0, countTB = 0, countY = 0;
  let cPass = 0, cMid = 0, cLow = 0, cNone = 0, cOtfail = 0;
  let topArr = [];

  studentList.forEach(st => {
    if (st.name) {
      total++;
      let assignedArr = st.assigned ? st.assigned.split(',').map(s => s.trim()) : [];
      const isOtFail = (st.assigned && st.assigned !== 'Hoàn thành' && st.lastOT && st.lastOT !== 'UNKNOWN' && !assignedArr.includes(st.lastOT));
      if (isOtFail) cOtfail++;

      if (st.bestScore === '') {
        cNone++;
      } else {
        scored++;
        sum += st.bestScore;
        const bc = badgeClass(st.bestScore, st.target, st.lastOT || assignedArr[0]);

        if (bc === 'sbadge-high') { cPass++; pass++; }
        else if (bc === 'sbadge-mid') { cMid++; fail++; }
        else if (bc === 'sbadge-low') { cLow++; fail++; }

        if (isOtFail) otFail++;

        let gradeCat = getGradeCategory(st.bestScore, st.lastOT || assignedArr[0]);
        if (gradeCat === 'G') countG++;
        if (gradeCat === 'K') countK++;
        if (gradeCat === 'TB') countTB++;
        if (gradeCat === 'Y') countY++;

        topArr.push({ pc: st.pc, name: st.name, score: st.bestScore, timeSec: st.timeSec });
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
    topHtml += `<div class="top-item" onclick="selectRow(${topArr[i].pc})"><span class="top-rank ${rClass}">${i + 1}</span><span class="top-name">${topArr[i].name} <span style="font-size:11px; color:var(--gray-500); font-weight:normal;">(Máy ${topArr[i].pc})</span></span><span class="top-score">${topArr[i].score}</span></div>`;
  }
  if (!topHtml) topHtml = '<div style="text-align:center; color:var(--gray-500); font-size:13px; font-style:italic; padding:10px 0;">Chưa có học sinh nộp bài</div>';
  document.getElementById('topStudentsList').innerHTML = topHtml;
}

// ── FILTER ───────────────────────────────────────────────
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
      const isOtFail = (st.assigned && st.assigned !== 'Hoàn thành' && st.lastOT && st.lastOT !== 'UNKNOWN' && !assignedArr.includes(st.lastOT));
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

// ── ROW SELECTION & DETAIL ───────────────────────────────
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
  <div class="big-max">/ ${st.max || '?'} điểm</div>
  ${pct !== null ? `<div class="prog-wrap"><div class="prog-bar" style="width:${pct}%; background:${scoreColor};"></div></div><div style="font-size:13px; font-weight:700; color:var(--gray-600); text-align:right; margin-top:4px;">${pct}%</div>` : ''}
</div>
<div class="dp-row"><span class="dp-lbl">Mục tiêu</span><span class="dp-val">${st.target || '—'}</span></div>
<div class="dp-row"><span class="dp-lbl">Bài được giao</span><span class="dp-val">${st.assigned || '—'}</span></div>
<div class="dp-row">
  <span class="dp-lbl">Bài đã thi</span>
  <span class="chip ${otMatchClass}" style="font-size:13px;">${st.lastOT || '—'}</span>
</div>
<div class="dp-row"><span class="dp-lbl">Thời gian</span><span class="dp-val">${st.time || '—'}</span></div>
<div class="dp-row"><span class="dp-lbl">Số lần thi</span><span class="dp-val">${st.attempts || 0} lần</span></div>`;

  if (st.allScr) {
    allPanel.innerHTML = st.allScr.split('|').map((s, idx) =>
      `<span class="chip chip-blue" style="margin:3px; font-size:13px; padding:5px 12px;">Lần ${idx + 1}: ${s.trim()}</span>`
    ).join(' ');
  } else {
    allPanel.innerHTML = '<span style="color:var(--gray-500); font-style:italic;">Chưa có dữ liệu</span>';
  }
}

// ── SAVE OPERATIONS ──────────────────────────────────────
function saveList() {
  const data = document.getElementById('txtList').value;
  apiPost('action=save_list&data=' + encodeURIComponent(data)).then(() => {
    toggleListModal();
    refreshData().then(() => {
      const count = Object.keys(currentData.mapping).length;
      showToast(`✅ Đã lưu danh sách! (${count} học sinh)`);
      logAction(`Lưu danh sách: ${count} học sinh`);
    });
  });
}

// ── SPREADSHEET VIEW ─────────────────────────────────────
// Full sheet (34 cols) → extract relevant columns by index
const FULL_COLS = [
  {label:'Vị trí',   src:4,  cls:'col-pc', hdr:''},
  {label:'Họ và tên', src:18, cls:'col-name', hdr:''},
  {label:'Tên',      src:19, cls:'', hdr:''},
  {label:'XL',       src:20, cls:'col-score', hdr:''},
  {label:'OT1',      src:21, cls:'col-score', hdr:'sheet-ot-hdr'},
  {label:'OT2',      src:22, cls:'col-score', hdr:'sheet-ot-hdr'},
  {label:'OT3',      src:23, cls:'col-score', hdr:'sheet-ot-hdr'},
  {label:'OT4',      src:24, cls:'col-score', hdr:'sheet-ot-hdr'},
  {label:'OT5',      src:25, cls:'col-score', hdr:'sheet-ot-hdr'},
  {label:'Yếu nhất', src:26, cls:'col-weak', hdr:'sheet-key-hdr'},
  {label:'Mục tiêu', src:27, cls:'col-target', hdr:'sheet-key-hdr'},
  {label:'Số HT',    src:28, cls:'col-center', hdr:''},
  {label:'M.OT1',    src:29, cls:'col-center', hdr:''},
  {label:'M.OT2',    src:30, cls:'col-center', hdr:''},
  {label:'M.OT3',    src:31, cls:'col-center', hdr:''},
  {label:'M.OT4',    src:32, cls:'col-center', hdr:''},
  {label:'M.OT5',    src:33, cls:'col-center', hdr:''},
];

// Partial sheet (17 cols): Họ và tên, Tên, XL, OT1-5, Vị trí, Yếu nhất, Mục tiêu, Số HT, Mức OT1-5
const PARTIAL_COLS = [
  {label:'Họ và tên', src:0, cls:'col-name', hdr:''},
  {label:'Tên',      src:1, cls:'', hdr:''},
  {label:'XL',       src:2, cls:'col-score', hdr:''},
  {label:'OT1',      src:3, cls:'col-score', hdr:'sheet-ot-hdr'},
  {label:'OT2',      src:4, cls:'col-score', hdr:'sheet-ot-hdr'},
  {label:'OT3',      src:5, cls:'col-score', hdr:'sheet-ot-hdr'},
  {label:'OT4',      src:6, cls:'col-score', hdr:'sheet-ot-hdr'},
  {label:'OT5',      src:7, cls:'col-score', hdr:'sheet-ot-hdr'},
  {label:'Vị trí',   src:8, cls:'col-pc', hdr:'sheet-key-hdr'},
  {label:'Yếu nhất', src:9, cls:'col-weak', hdr:'sheet-key-hdr'},
  {label:'Mục tiêu', src:10, cls:'col-target', hdr:'sheet-key-hdr'},
  {label:'Số HT',    src:11, cls:'col-center', hdr:''},
  {label:'M.OT1',    src:12, cls:'col-center', hdr:''},
  {label:'M.OT2',    src:13, cls:'col-center', hdr:''},
  {label:'M.OT3',    src:14, cls:'col-center', hdr:''},
  {label:'M.OT4',    src:15, cls:'col-center', hdr:''},
  {label:'M.OT5',    src:16, cls:'col-center', hdr:''},
];

function isHeaderRow(cols) {
  const f = cols[0].trim().toLowerCase();
  return f === 'họ và tên' || f === 'stt' || f === '#' || f === 'họ' || f === 'họ và tên ';
}

function detectSheetFormat(lines) {
  if (!lines.length) return null;
  const firstCols = lines[0].split('\t');
  if (firstCols.length >= 28) return 'full';
  if (firstCols.length >= 14 && firstCols.length <= 20) return 'partial';
  return 'raw';
}

function detectSimpleHeaders(sampleCols) {
  // For simple format (2-5 cols), detect which is name and which is PC
  const count = sampleCols.length;
  if (count < 1 || count > 8) return null;

  // Find which column has PC pattern
  let pcIdx = -1;
  for (let i = 0; i < count; i++) {
    if (/^PC\d+/i.test(sampleCols[i].trim())) { pcIdx = i; break; }
  }

  const headers = [];
  for (let i = 0; i < count; i++) {
    if (i === pcIdx) {
      headers.push({label: 'Vị trí (PC)', hdr: 'sheet-key-hdr'});
    } else if (i === 0) {
      headers.push({label: 'Họ và tên', hdr: ''});
    } else if (sampleCols[i].trim() === '' && i < pcIdx) {
      // Empty column between name parts – could be tab padding
      headers.push({label: '', hdr: ''});
    } else if (/^(OT|GM)/i.test(sampleCols[i].trim()) || sampleCols[i].trim() === 'Hoàn thành') {
      headers.push({label: 'Bài giao', hdr: 'sheet-ot-hdr'});
    } else if (/^\d+$/.test(sampleCols[i].trim())) {
      headers.push({label: 'Mục tiêu', hdr: 'sheet-key-hdr'});
    } else {
      headers.push({label: `Cột ${i+1}`, hdr: ''});
    }
  }
  return headers;
}

function renderSheetFromTextarea() {
  const raw = document.getElementById('txtList').value.trim();
  const thead = document.getElementById('sheetHead');
  const tbody = document.getElementById('sheetBody');
  const info = document.getElementById('sheetInfo');

  if (!raw) {
    thead.innerHTML = '';
    tbody.innerHTML = '<tr><td colspan="18" style="text-align:center; padding:40px; color:var(--gray-500); font-style:italic;">Chưa có dữ liệu. Copy từ Google Sheet rồi Ctrl+V vào đây.</td></tr>';
    info.textContent = '0 học sinh';
    return;
  }

  let lines = raw.split('\n').filter(l => l.trim() !== '');

  // Skip header row if detected
  if (lines.length > 0 && isHeaderRow(lines[0].split('\t'))) {
    lines = lines.slice(1);
  }
  // Update textarea without header (for backend parsing)
  if (lines.length === 0) {
    thead.innerHTML = '';
    tbody.innerHTML = '<tr><td colspan="18" style="text-align:center; padding:40px; color:var(--gray-500);">Không có dữ liệu học sinh.</td></tr>';
    info.textContent = '0 học sinh';
    return;
  }

  const format = detectSheetFormat(lines);
  const colDefs = format === 'full' ? FULL_COLS : (format === 'partial' ? PARTIAL_COLS : null);

  // Generate header
  let hdrHtml = '<tr><th class="sheet-row-num">#</th>';
  if (colDefs) {
    colDefs.forEach(def => {
      hdrHtml += `<th class="${def.hdr}">${def.label}</th>`;
    });
  } else {
    // Simple format (Name + PC): detect columns smartly
    const sampleCols = lines[0].split('\t');
    const simpleHeaders = detectSimpleHeaders(sampleCols);
    if (simpleHeaders) {
      simpleHeaders.forEach(h => { hdrHtml += `<th class="${h.hdr || ''}">${h.label}</th>`; });
    } else {
      const numCols = Math.min(sampleCols.length, 20);
      for (let c = 0; c < numCols; c++) hdrHtml += `<th>Cột ${c+1}</th>`;
    }
  }
  hdrHtml += '</tr>';
  thead.innerHTML = hdrHtml;

  // Generate body
  let bodyHtml = '';
  lines.forEach((line, idx) => {
    const cols = line.split('\t');
    bodyHtml += `<tr><td>${idx + 1}</td>`;

    if (colDefs) {
      colDefs.forEach(def => {
        const val = (cols[def.src] !== undefined) ? cols[def.src].trim() : '';
        let cls = def.cls || '';
        if (def.label === 'Yếu nhất' && val === 'Hoàn thành') cls += ' col-done';
        bodyHtml += `<td class="${cls}">${escHtml(val)}</td>`;
      });
    } else {
      const numCols = Math.min(cols.length, 20);
      for (let c = 0; c < numCols; c++) {
        const val = cols[c]?.trim() || '';
        let cls = /^PC\d+/i.test(val) ? 'col-pc' : '';
        if (!cls && c === 0) cls = 'col-name';
        bodyHtml += `<td class="${cls}">${escHtml(val)}</td>`;
      }
    }

    bodyHtml += '</tr>';
  });

  tbody.innerHTML = bodyHtml;
  info.textContent = `${lines.length} học sinh`;
}

function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}

function clearSheetData() {
  if (!confirm('Xoá toàn bộ dữ liệu danh sách?')) return;
  document.getElementById('txtList').value = '';
  renderSheetFromTextarea();
}

// Handle paste into sheet container
document.addEventListener('DOMContentLoaded', function() {
  const container = document.getElementById('sheetContainer');
  if (!container) return;

  // Make container focusable for paste
  container.setAttribute('tabindex', '0');

  container.addEventListener('paste', function(e) {
    e.preventDefault();
    const pastedText = (e.clipboardData || window.clipboardData).getData('text');
    if (!pastedText.trim()) return;

    const textarea = document.getElementById('txtList');
    const existing = textarea.value.trim();

    // If existing data, ask to replace or append
    if (existing) {
      const choice = confirm('Đã có dữ liệu.\nBấm OK để THAY THẾ, bấm Cancel để THÊM VÀO.');
      if (choice) {
        textarea.value = pastedText;
      } else {
        textarea.value = existing + '\n' + pastedText;
      }
    } else {
      textarea.value = pastedText;
    }

    renderSheetFromTextarea();
    showToast('📋 Đã dán dữ liệu từ clipboard!');
  });

  container.addEventListener('click', function() {
    this.focus();
  });
});

function doDeleteScores() {
  const className = CONFIG.tenLop || '';
  if (!confirm(`⚠️ XÓA TOÀN BỘ ĐIỂM lớp "${className}"?\n\nHành động này sẽ xóa tất cả file bài thi đã nộp.\nKhông thể hoàn tác!\n\nBấm OK để xác nhận xóa.`)) return;
  apiPost('action=delete_scores').then(() => {
    showToast('🗑️ Đã xóa toàn bộ điểm!');
    logAction('Xóa toàn bộ điểm');
    refreshData();
  });
}

function saveTargetScore(pc, val) {
  val = val.trim();
  apiPost('action=save_target&pc=' + pc + '&target=' + encodeURIComponent(val)).then(() => {
    if (val === '') {
      delete currentData.targets[pc];
    } else {
      currentData.targets[pc] = val;
    }
    processData();
    if (selectedPC === pc) showDetail(pc);
    logAction(`Mục tiêu máy ${pc}: ${val || '(xóa)'}`);
  });
}

function saveOT(pc, ot) {
  apiPost('action=save_ot&pc=' + pc + '&ot=' + ot).then(() => {
    if (ot === '') {
      delete currentData.otConfigs[pc];
    } else {
      currentData.otConfigs[pc] = ot;
    }
    processData();
    if (selectedPC === pc) showDetail(pc);
    logAction(`Giao bài máy ${pc}: ${ot || '(xóa)'}`);
  });
}

// ── OT MODAL (đơn lẻ) ───────────────────────────────────
function openOTModal(pc, currentAssigned) {
  let arr = currentAssigned ? currentAssigned.split(',').map(s => s.trim()) : [];
  let html = `<div style="font-size:14px; font-weight:bold; margin-bottom:10px;">Chọn bài thi cho Máy ${pc}:</div>
<div style="display:flex; flex-wrap:wrap; gap:10px; background:var(--gray-50); padding:14px; border-radius:6px; border:1px solid var(--gray-200);">`;
  OT_LIST.forEach(ot => {
    let checked = arr.includes(ot) ? 'checked' : '';
    html += `<label class="cb-label"><input type="checkbox" value="${ot}" class="cb-ot-single" ${checked}> ${ot}</label>`;
  });
  html += `</div><div style="margin-top:8px; font-size:13px; color:var(--gray-500); font-style:italic;">* Bỏ chọn tất cả để gỡ bài</div>`;
  openModal('Giao Bài Thi', html, function () {
    let selected = Array.from(document.querySelectorAll('.cb-ot-single:checked')).map(cb => cb.value).join(',');
    saveOT(pc, selected);
    closeModal();
  });
}

// ── BULK OPERATIONS ──────────────────────────────────────
function validateBulkRange(startId, endId) {
  const s = parseInt(document.getElementById(startId).value);
  const e = parseInt(document.getElementById(endId).value);
  if (isNaN(s) || isNaN(e)) {
    showToast('⚠️ Vui lòng nhập phạm vi máy!');
    return null;
  }
  if (s < 1 || e > 50) {
    showToast('⚠️ Phạm vi máy phải từ 1 đến 50!');
    return null;
  }
  if (s > e) {
    showToast('⚠️ Máy bắt đầu phải nhỏ hơn hoặc bằng máy kết thúc!');
    return null;
  }
  return { start: s, end: e };
}

function openBulkTarget() {
  openModal('Đặt mục tiêu điểm hàng loạt',
    `<div class="inp-row"><span class="inp-lbl" style="min-width:60px;">Từ máy</span><input class="inp" id="mb1" type="number" min="1" max="50" placeholder="1"/></div>
     <div class="inp-row"><span class="inp-lbl" style="min-width:60px;">Đến máy</span><input class="inp" id="mb2" type="number" min="1" max="50" placeholder="50"/></div>
     <div class="inp-row"><span class="inp-lbl" style="min-width:60px;">Mục tiêu</span><input class="inp" id="mb3" type="number" min="0" placeholder="VD: 800"/></div>`,
    function () {
      const range = validateBulkRange('mb1', 'mb2');
      if (!range) return;
      const v = document.getElementById('mb3').value;
      if (!v) { showToast('⚠️ Vui lòng nhập điểm mục tiêu!'); return; }
      apiPost(`action=save_bulk_target&start=${range.start}&end=${range.end}&target=${v}`).then(() => {
        closeModal();
        refreshData();
        showToast(`✅ Đã đặt mục tiêu ${v} cho máy ${range.start}–${range.end}`);
        logAction(`Mục tiêu loạt máy ${range.start}–${range.end}: ${v}`);
      });
    }
  );
}

function openClearBulkTarget() {
  openModal('🚫 Xoá mục tiêu hàng loạt',
    `<div style="color:var(--red); font-weight:600; margin-bottom:14px;">Xoá mục tiêu điểm cho các máy trong phạm vi:</div>
     <div class="inp-row"><span class="inp-lbl" style="min-width:60px;">Từ máy</span><input class="inp" id="ct1" type="number" min="1" max="50" placeholder="1"/></div>
     <div class="inp-row"><span class="inp-lbl" style="min-width:60px;">Đến máy</span><input class="inp" id="ct2" type="number" min="1" max="50" placeholder="50"/></div>`,
    function () {
      const range = validateBulkRange('ct1', 'ct2');
      if (!range) return;
      apiPost(`action=save_bulk_target&start=${range.start}&end=${range.end}&target=`).then(() => {
        closeModal();
        refreshData();
        showToast(`🗑️ Đã xoá mục tiêu máy ${range.start}–${range.end}`);
        logAction(`Xoá mục tiêu loạt máy ${range.start}–${range.end}`);
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
  html += `</div><div style="margin-top:8px; font-size:13px; color:var(--gray-500); font-style:italic;">* Bỏ chọn tất cả = không giao bài</div>`;

  openModal('Giao bài thi hàng loạt', html, function () {
    const range = validateBulkRange('mo1', 'mo2');
    if (!range) return;
    let selected = Array.from(document.querySelectorAll('.cb-ot-bulk:checked')).map(cb => cb.value).join(',');
    if (!selected) {
      if (!confirm('Bạn chưa chọn bài nào.\nBấm OK để HUỶ giao bài cho phạm vi máy này.')) return;
    }
    apiPost(`action=save_bulk_ot&start=${range.start}&end=${range.end}&ot=${selected}`).then(() => {
      closeModal();
      refreshData();
      showToast(`✅ Đã giao bài "${selected || '(xóa)'}" cho máy ${range.start}–${range.end}`);
      logAction(`Giao bài loạt máy ${range.start}–${range.end}: ${selected || '(xóa)'}`);
    });
  });
}

function openClearBulkOT() {
  openModal('🚫 Huỷ giao bài hàng loạt',
    `<div style="color:var(--red); font-weight:600; margin-bottom:14px;">Huỷ giao bài thi cho các máy trong phạm vi:</div>
     <div class="inp-row"><span class="inp-lbl" style="min-width:60px;">Từ máy</span><input class="inp" id="co1" type="number" min="1" max="50" placeholder="1"/></div>
     <div class="inp-row"><span class="inp-lbl" style="min-width:60px;">Đến máy</span><input class="inp" id="co2" type="number" min="1" max="50" placeholder="50"/></div>`,
    function () {
      const range = validateBulkRange('co1', 'co2');
      if (!range) return;
      apiPost(`action=save_bulk_ot&start=${range.start}&end=${range.end}&ot=`).then(() => {
        closeModal();
        refreshData();
        showToast(`🗑️ Đã huỷ giao bài máy ${range.start}–${range.end}`);
        logAction(`Huỷ giao bài loạt máy ${range.start}–${range.end}`);
      });
    }
  );
}

// ── KEYBOARD NAVIGATION ─────────────────────────────────
window.addEventListener('keydown', function (e) {
  if (isTyping) return;
  if (!['ArrowUp', 'ArrowDown', 'Home', 'End', 'Escape'].includes(e.key)) return;
  e.preventDefault();

  if (e.key === 'Escape') {
    selectRow(selectedPC);
    return;
  }

  const sorted = getSortedStudents();
  const old = selectedPC;

  if (!selectedPC) {
    selectedPC = sorted.length ? sorted[0].pc : 1;
  } else {
    const currentIndex = sorted.findIndex(s => s.pc === selectedPC);
    if (currentIndex === -1) {
      selectedPC = sorted.length ? sorted[0].pc : 1;
    } else if (e.key === 'ArrowDown' && currentIndex < sorted.length - 1) {
      selectedPC = sorted[currentIndex + 1].pc;
    } else if (e.key === 'ArrowUp' && currentIndex > 0) {
      selectedPC = sorted[currentIndex - 1].pc;
    } else if (e.key === 'Home') {
      selectedPC = sorted[0].pc;
    } else if (e.key === 'End') {
      selectedPC = sorted[sorted.length - 1].pc;
    }
  }

  if (old !== selectedPC) {
    if (old) {
      const r = document.getElementById('row-pc' + old);
      if (r) r.classList.remove('sel-row');
    }
    const nr = document.getElementById('row-pc' + selectedPC);
    if (nr) {
      nr.classList.add('sel-row');
      nr.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    showDetail(selectedPC);
  }
});

// ── TOOLBAR DROPDOWN ─────────────────────────────────────
function toggleToolsMenu() {
  const menu = document.getElementById('toolsMenu');
  menu.classList.toggle('open');
}

function closeToolsMenu() {
  document.getElementById('toolsMenu').classList.remove('open');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
  const dd = document.getElementById('toolsDropdown');
  if (dd && !dd.contains(e.target)) {
    closeToolsMenu();
  }
});

// ── IP MANAGEMENT ────────────────────────────────────────
let ipRefreshTimer = null;

function toggleIPModal() {
  const m = document.getElementById('ipModalBg');
  if (m.classList.contains('open')) {
    m.classList.remove('open');
    // Stop auto-refresh when modal closes
    if (ipRefreshTimer) { clearInterval(ipRefreshTimer); ipRefreshTimer = null; }
  } else {
    m.classList.add('open');
    loadIPMapping();
    // Auto-refresh IP data every 3 seconds while modal is open
    ipRefreshTimer = setInterval(loadIPMapping, 3000);
  }
}

function loadIPMapping() {
  fetch('api.php?action=get_ip_mapping&t=' + new Date().getTime()).then(r => r.json()).then(data => {
    const tbody = document.getElementById('ipTableBody');
    const countEl = document.getElementById('ipCount');
    const lastEl = document.getElementById('ipLastUpdate');
    const ipInput = document.getElementById('serverIPInput');

    // Only set server IP if field is empty (don't overwrite user edits)
    if (data.serverIP && !ipInput.value.trim()) ipInput.value = data.serverIP;
    countEl.textContent = `${data.totalPCs} máy có IP`;
    lastEl.textContent = data.lastModified ? `Cập nhật: ${data.lastModified}` : '';

    if (!data.ipMapping || data.ipMapping.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:40px; color:var(--gray-500); font-style:italic;">Chưa có máy nào gửi IP.<br>Chạy lay_ip.bat từ NetSupport để thu thập IP.</td></tr>';
      return;
    }

    // Sort by pcNum
    data.ipMapping.sort((a, b) => a.pcNum - b.pcNum);

    let html = '';
    data.ipMapping.forEach((item, idx) => {
      const studentName = currentData.mapping[item.pcNum] || '';
      const isTeacher = item.pcName.toLowerCase().includes('thuan') || item.pcName.toLowerCase().includes('gv');

      let statusClass, statusText;
      if (isTeacher) {
        statusClass = 'chip-blue';
        statusText = '👨‍🏫 GV';
      } else if (studentName) {
        statusClass = 'chip-green';
        statusText = '📋 Có HS';
      } else {
        statusClass = 'chip-gray';
        statusText = '📡 Có IP';
      }

      html += `<tr>
        <td>${idx + 1}</td>
        <td style="font-weight:700; color:var(--blue);">${escHtml(item.pcName)}</td>
        <td style="text-align:center;"><div class="pc-badge${item.pcNum === 0 ? ' empty' : ''}" style="width:36px; height:24px; font-size:12px;">${item.pcNum || '—'}</div></td>
        <td style="font-family:monospace; font-size:13px; color:var(--gray-700);">${item.ip}</td>
        <td style="font-weight:${studentName ? '600' : '400'}; color:${studentName ? 'var(--gray-900)' : 'var(--gray-400)'}; font-style:${studentName ? 'normal' : 'italic'};">${studentName || '(chưa gán)'}</td>
        <td style="text-align:center;"><span class="chip ${statusClass}">${statusText}</span></td>
      </tr>`;
    });

    tbody.innerHTML = html;
  }).catch(() => {
    showToast('⚠️ Không thể tải dữ liệu IP');
  });
}

function clearIPMapping() {
  if (!confirm('⚠️ Xoá toàn bộ dữ liệu IP mapping?\n\nSau khi xoá, cần chạy lại lay_ip.bat trên tất cả máy học sinh.\n\nBấm OK để xác nhận.')) return;
  apiPost('action=clear_ip_mapping').then(() => {
    showToast('🗑️ Đã xoá toàn bộ IP mapping!');
    logAction('Xoá toàn bộ IP mapping');
    loadIPMapping();
  });
}

function generateBat() {
  const serverIP = document.getElementById('serverIPInput').value.trim();
  if (!serverIP) {
    showToast('⚠️ Vui lòng nhập IP máy chủ!');
    return;
  }
  apiPost('action=generate_bat&server_ip=' + encodeURIComponent(serverIP)).then(r => r.json()).then(data => {
    if (data.ok) {
      showToast(`✅ Đã tạo lay_ip.bat (Server: ${data.serverIP})`);
      logAction(`Tạo lay_ip.bat với IP: ${data.serverIP}`);
    }
  }).catch(() => {
    showToast('⚠️ Lỗi khi tạo file bat!');
  });
}

// ── AUTO REFRESH & INIT ─────────────────────────────────
function startAutoRefresh() {
  refreshTimer = setInterval(() => {
    if (!isTyping) refreshData();
  }, 5000);
}

document.addEventListener('visibilitychange', function () {
  if (document.hidden) {
    clearInterval(refreshTimer);
  } else {
    refreshData();
    startAutoRefresh();
  }
});

// Boot
refreshData();
startAutoRefresh();

// Auto-clear IP mapping at 23:59:59 mỗi ngày
(function scheduleMidnightClear() {
  const now = new Date();
  const midnight = new Date(now);
  midnight.setHours(23, 59, 59, 0);
  let ms = midnight.getTime() - now.getTime();
  if (ms < 0) ms += 24 * 60 * 60 * 1000; // already past → schedule for tomorrow
  setTimeout(function() {
    apiPost('action=clear_ip_mapping').then(() => {
      logAction('🕛 Tự động xoá IP mapping (cuối ngày)');
      if (document.getElementById('ipModalBg').classList.contains('open')) {
        loadIPMapping();
      }
    });
    // Schedule again for next day
    scheduleMidnightClear();
  }, ms);
})();
