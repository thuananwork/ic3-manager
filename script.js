// ── GLOBALS ────────────────────────────────────────────────
const OT_LIST   = ['OT1','OT2','OT3','OT4','OT5','GM1','GM2'];
let currentData = { mapping:{}, scores:{}, targets:{}, otConfigs:{} };
let selectedPC  = null;
let isTyping    = false;
let actionLog   = [];
let refreshTimer = null;

// ── TOAST ───────────────────────────────────────────────────
function showToast(msg, dur=2800) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(t._t);
  t._t = setTimeout(() => t.classList.remove('show'), dur);
}

// ── ACTION LOG ───────────────────────────────────────────────
function logAction(msg) {
  const now = new Date();
  const ts  = now.getHours().toString().padStart(2,'0') + ':' +
              now.getMinutes().toString().padStart(2,'0') + ':' +
              now.getSeconds().toString().padStart(2,'0');
  actionLog.unshift(ts + ' — ' + msg);
  if (actionLog.length > 30) actionLog.pop();
  document.getElementById('actionLog').innerHTML = actionLog.join('<br>');
}

// ── MODAL ───────────────────────────────────────────────────
function openModal(title, bodyHtml, onOk) {
  document.getElementById('modalTitle').textContent = title;
  document.getElementById('modalBody').innerHTML    = bodyHtml;
  document.getElementById('modalOk').onclick        = onOk;
  document.getElementById('modalBg').classList.add('open');
}
function closeModal() {
  document.getElementById('modalBg').classList.remove('open');
}

// ── API HELPERS ─────────────────────────────────────────────
function apiPost(body) {
  return fetch('api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: body
  });
}

// ── REFRESH ─────────────────────────────────────────────────
function doRefresh() {
  const btn = document.getElementById('refreshBtn');
  btn.disabled = true;
  btn.textContent = ' Đang tải...';
  refreshData().finally(() => {
    btn.disabled = false;
    btn.innerHTML = `<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M4 10a6 6 0 1 0 1.6-4" stroke-linecap="round"/>
      <path d="M4 4v3h3" stroke-linecap="round" stroke-linejoin="round"/></svg> Làm mới`;
  });
}

function refreshData() {
  if (isTyping) return Promise.resolve();
  return fetch('api.php?action=get_data')
    .then(r => r.json())
    .then(data => {
      currentData = data;
      renderTable(data);
      updateStats();
      document.getElementById('lastUpdate').textContent =
        'Cập nhật: ' + new Date().toLocaleTimeString('vi-VN');
    })
    .catch(() => {});
}

// ── SCORE BADGE CLASS ────────────────────────────────────────
function badgeClass(score, max, target) {
  if (score === '' || score === undefined || score === null) return 'sbadge-na';
  const pct = max ? (score / max) * 100 : 0;
  if (target !== '' && target !== undefined && parseFloat(score) >= parseFloat(target)) return 'sbadge-high';
  if (pct >= 80) return 'sbadge-high';
  if (pct >= 60) return 'sbadge-mid';
  return 'sbadge-low';
}
function rowClass(bc) {
  if (bc === 'sbadge-high') return 'row-high';
  if (bc === 'sbadge-mid')  return 'row-mid';
  if (bc === 'sbadge-low')  return 'row-low';
  return '';
}

// ── RENDER TABLE ─────────────────────────────────────────────
function renderTable(data) {
  let html = '';
  for (let i = 1; i <= 50; i++) {
    const name     = data.mapping[i] || '';
    const sd       = data.scores[i] || {};
    const allScr   = sd.score !== undefined && sd.score !== '' ? String(sd.score) : '';
    let bestScore  = '';
    if (allScr !== '') {
      const parts = allScr.split('|').map(s => parseFloat(s.trim())).filter(n => !isNaN(n));
      bestScore   = parts.length ? Math.max(...parts) : '';
    }
    const max      = sd.max || '';
    const time     = sd.timeSpent || '';
    const lastOT   = sd.lastOT || '';
    const target   = data.targets   && data.targets[i]   ? data.targets[i]   : '';
    const assigned = data.otConfigs && data.otConfigs[i] ? data.otConfigs[i] : '';

    const bc = badgeClass(bestScore, max, target);
    const rc = name ? rowClass(bc) : '';

    const otWarn   = assigned && lastOT && lastOT !== 'UNKNOWN' && lastOT !== assigned;
    const otClass  = lastOT
      ? (assigned ? (lastOT === assigned ? 'ot-match' : 'ot-mismatch') : 'ot-none')
      : 'ot-none';

    const attempts = allScr ? allScr.split('|').length : 0;

    let otOpts = `<option value="" ${!assigned?'selected':''}>--</option>`;
    OT_LIST.forEach(ot => {
      otOpts += `<option value="${ot}" ${ot===assigned?'selected':''}>${ot}</option>`;
    });

    const isSelected = selectedPC === i;

    html += `<tr id="row-pc${i}" class="${rc}${isSelected?' sel-row':''}" onclick="selectRow(${i})">
      <td><div class="pc-badge ${!name?'empty':''}">${i}</div></td>
      <td class="name-cell ${!name?'no-name':''}">${name || '—'}</td>
      <td><span class="sbadge ${bc}">${bestScore!==''?bestScore:'—'}</span></td>
      <td style="color:var(--gray-600);font-size:12px;">${max||'—'}</td>
      <td style="font-size:12px; color:var(--gray-600);">${attempts>0?attempts+'x':'—'}</td>
      <td onclick="event.stopPropagation()">
        <input class="tgt-inp" type="number" value="${target}" placeholder="—"
          onfocus="isTyping=true" onblur="isTyping=false; saveTargetScore(${i}, this.value)"
          onkeydown="if(event.key==='Enter')this.blur()" />
      </td>
      <td onclick="event.stopPropagation()">
        <select class="ot-sel ${otWarn?'ot-warn':''}" onchange="saveOT(${i}, this.value)">${otOpts}</select>
      </td>
      <td class="${otClass}">${lastOT||'—'}</td>
      <td class="time-cell">${time||'—'}</td>
    </tr>`;
  }
  document.getElementById('tableBody').innerHTML = html;
  filterTable(); 
}

// ── UPDATE STATS ─────────────────────────────────────────────
function updateStats() {
  const d = currentData;
  let total=0, scored=0, sum=0, pass=0, fail=0, otFail=0;
  for (let i=1; i<=50; i++) {
    if (d.mapping[i]) total++;
    const sd  = d.scores[i] || {};
    const allScr = sd.score !== undefined && sd.score !== '' ? String(sd.score) : '';
    if (allScr === '') continue;
    scored++;
    const parts = allScr.split('|').map(s=>parseFloat(s.trim())).filter(n=>!isNaN(n));
    const best  = parts.length ? Math.max(...parts) : 0;
    sum += best;
    const target   = d.targets   && d.targets[i]   ? parseFloat(d.targets[i])   : null;
    const assigned = d.otConfigs && d.otConfigs[i] ? d.otConfigs[i] : null;
    if (target !== null && best >= target) pass++;
    else if (target !== null) fail++;
    if (assigned && sd.lastOT && sd.lastOT !== 'UNKNOWN' && sd.lastOT !== assigned) otFail++;
  }
  document.getElementById('stScored').textContent = scored;
  document.getElementById('stTotal').textContent  = total;
  document.getElementById('stAvg').textContent    = scored ? Math.round(sum/scored) : '—';
  document.getElementById('stPass').textContent   = pass;
  document.getElementById('stFail').textContent   = fail;
  document.getElementById('stOTFail').textContent = otFail;

  const pct = total ? Math.round((scored/total)*100) : 0;
  document.getElementById('progPct').textContent = pct + '%';
  document.getElementById('progBar').style.width  = pct + '%';
}

// ── FILTER TABLE ─────────────────────────────────────────────
function filterTable() {
  const search = (document.getElementById('searchInp').value || '').toLowerCase().trim();
  const status = document.getElementById('filterSel').value;
  document.querySelectorAll('#tableBody tr').forEach(row => {
    const pc  = parseInt(row.id.replace('row-pc',''));
    const name= (currentData.mapping[pc]||'').toLowerCase();
    const sd  = currentData.scores[pc]||{};
    const allScr = sd.score !== undefined && sd.score !== '' ? String(sd.score) : '';
    const parts  = allScr.split('|').map(s=>parseFloat(s.trim())).filter(n=>!isNaN(n));
    const best   = parts.length ? Math.max(...parts) : null;
    const target   = currentData.targets   && currentData.targets[pc]   ? parseFloat(currentData.targets[pc]) : null;
    const assigned = currentData.otConfigs && currentData.otConfigs[pc] ? currentData.otConfigs[pc] : null;
    const lastOT   = sd.lastOT || null;

    let show = true;
    if (search && !name.includes(search) && !String(pc).includes(search)) show = false;
    if (status === 'scored'   && allScr === '') show = false;
    if (status === 'noscored' && allScr !== '') show = false;
    if (status === 'pass'     && !(best !== null && target !== null && best >= target)) show = false;
    if (status === 'fail'     && !(best !== null && target !== null && best < target))  show = false;
    if (status === 'otfail'   && !(assigned && lastOT && lastOT !== 'UNKNOWN' && lastOT !== assigned)) show = false;

    row.style.display = show ? '' : 'none';
  });
}

// ── SELECT ROW ───────────────────────────────────────────────
function selectRow(pc) {
  const old = selectedPC;
  selectedPC = (selectedPC === pc) ? null : pc;
  if (old)         { const r=document.getElementById('row-pc'+old); if(r) r.classList.remove('sel-row'); }
  if (selectedPC)  { const r=document.getElementById('row-pc'+selectedPC); if(r) r.classList.add('sel-row'); }
  showDetail(selectedPC);
}

// ── SHOW DETAIL ───────────────────────────────────────────────
function showDetail(pc) {
  const panel    = document.getElementById('detailContent');
  const allPanel = document.getElementById('allScores');
  if (!pc) {
    panel.innerHTML    = '<div style="text-align:center;padding:16px 0;color:var(--gray-500);font-size:12px;">Nhấn vào một học sinh để xem chi tiết</div>';
    allPanel.innerHTML = '—';
    return;
  }
  const name     = currentData.mapping[pc]  || '(Chưa có tên)';
  const sd       = currentData.scores[pc]   || {};
  const allScr   = sd.score !== undefined && sd.score !== '' ? String(sd.score) : '';
  const parts    = allScr.split('|').map(s=>parseFloat(s.trim())).filter(n=>!isNaN(n));
  const best     = parts.length ? Math.max(...parts) : null;
  const max      = sd.max      || null;
  const time     = sd.timeSpent|| '—';
  const lastOT   = sd.lastOT   || '—';
  const target   = (currentData.targets   && currentData.targets[pc])   ? currentData.targets[pc]   : '—';
  const assigned = (currentData.otConfigs && currentData.otConfigs[pc]) ? currentData.otConfigs[pc] : '—';

  const pct   = (best !== null && max) ? Math.round((best/max)*100) : null;
  let scoreColor = 'var(--gray-700)';
  if (pct !== null) {
    if (pct >= 80 || (target !== '—' && best >= parseFloat(target))) scoreColor = 'var(--green)';
    else if (pct >= 60) scoreColor = 'var(--amber)';
    else scoreColor = 'var(--red)';
  }
  const otMatchClass = (lastOT !== '—' && assigned !== '—') ? (lastOT === assigned ? 'chip-green' : 'chip-red') : 'chip-gray';

  panel.innerHTML = `
    <div style="text-align:center; padding:8px 0 10px;">
      <div class="avatar">${pc}</div>
      <div class="dp-name">${name}</div>
      <div class="dp-sub">Máy số ${pc}</div>
    </div>
    <div style="background:var(--gray-50); border-radius:8px; padding:10px; margin-bottom:8px; text-align:center;">
      <div class="big-score" style="color:${scoreColor}">${best !== null ? best : '—'}</div>
      <div class="big-max">/ ${max||'?'} điểm</div>
      ${pct !== null ? `
        <div class="prog-wrap"><div class="prog-bar" style="width:${pct}%; background:${scoreColor};"></div></div>
        <div style="font-size:11px; color:var(--gray-600); text-align:right; margin-top:3px;">${pct}%</div>
      ` : ''}
    </div>
    <div class="dp-row"><span class="dp-lbl">Chỉ tiêu</span><span class="dp-val">${target}</span></div>
    <div class="dp-row"><span class="dp-lbl">Bài được giao</span><span class="dp-val">${assigned}</span></div>
    <div class="dp-row">
      <span class="dp-lbl">Bài đã thi</span>
      <span class="chip ${otMatchClass}" style="font-size:11px;">${lastOT}</span>
    </div>
    <div class="dp-row"><span class="dp-lbl">Thời gian</span><span class="dp-val">${time}</span></div>
    <div class="dp-row"><span class="dp-lbl">Số lần thi</span><span class="dp-val">${parts.length || 0} lần</span></div>
  `;

  if (allScr) {
    allPanel.innerHTML = parts.map((s,idx) => `<span class="chip chip-blue" style="margin:2px;">${idx+1}. ${s}</span>`).join(' ');
  } else {
    allPanel.innerHTML = '<span style="color:var(--gray-500);">Chưa có dữ liệu</span>';
  }
}

// ── SAVE OPERATIONS ──────────────────────────────────────────
function saveList() {
  const data = document.getElementById('txtList').value;
  apiPost('action=save_list&data='+encodeURIComponent(data))
    .then(() => { showToast('✅ Đã lưu danh sách học sinh!'); logAction('Lưu danh sách học sinh'); refreshData(); });
}

function doDeleteScores() {
  if (!confirm('Xóa toàn bộ điểm của lớp hiện tại?\nHành động này không thể hoàn tác!')) return;
  apiPost('action=delete_scores')
    .then(() => { showToast('🗑️ Đã xóa toàn bộ điểm!'); logAction('Xóa toàn bộ điểm'); refreshData(); });
}

function saveTargetScore(pc, val) {
  val = val.trim();
  apiPost('action=save_target&pc='+pc+'&target='+encodeURIComponent(val))
    .then(() => {
      currentData.targets = currentData.targets || {};
      currentData.targets[pc] = val;
      updateStats();
      if (selectedPC === pc) showDetail(pc);
      logAction(`Chỉ tiêu máy ${pc}: ${val}`);
    });
}

function saveOT(pc, ot) {
  apiPost('action=save_ot&pc='+pc+'&ot='+ot)
    .then(() => {
      currentData.otConfigs = currentData.otConfigs || {};
      currentData.otConfigs[pc] = ot;
      if (selectedPC === pc) showDetail(pc);
      logAction(`Giao bài máy ${pc}: ${ot||'(xóa)'}`);
    });
}

// ── BULK MODAL OPENERS ────────────────────────────────────────
function openBulkTarget() {
  openModal('Đặt chỉ tiêu điểm hàng loạt',
    `<div class="inp-row"><span class="inp-lbl">Từ máy</span><input class="inp" id="mb1" type="number" min="1" max="50" placeholder="1"/></div>
     <div class="inp-row"><span class="inp-lbl">Đến máy</span><input class="inp" id="mb2" type="number" min="1" max="50" placeholder="50"/></div>
     <div class="inp-row"><span class="inp-lbl">Chỉ tiêu</span><input class="inp" id="mb3" type="number" placeholder="VD: 800"/></div>`,
    function() {
      const s=document.getElementById('mb1').value, e=document.getElementById('mb2').value, v=document.getElementById('mb3').value;
      if (!s||!e||!v) { showToast('⚠️ Vui lòng điền đầy đủ!'); return; }
      apiPost(`action=save_bulk_target&start=${s}&end=${e}&target=${v}`)
        .then(() => {
          closeModal(); refreshData();
          showToast(`✅ Đã đặt chỉ tiêu ${v} cho máy ${s}–${e}`);
          logAction(`Chỉ tiêu loạt máy ${s}–${e}: ${v}`);
        });
    }
  );
}

function openBulkOT() {
  let opts = `<option value="">-- Không giao --</option>` + OT_LIST.map(o=>`<option value="${o}">${o}</option>`).join('');
  openModal('Giao bài thi hàng loạt',
    `<div class="inp-row"><span class="inp-lbl">Từ máy</span><input class="inp" id="mo1" type="number" min="1" max="50" placeholder="1"/></div>
     <div class="inp-row"><span class="inp-lbl">Đến máy</span><input class="inp" id="mo2" type="number" min="1" max="50" placeholder="50"/></div>
     <div class="inp-row"><span class="inp-lbl">Bài thi</span><select class="sel" id="mo3">${opts}</select></div>`,
    function() {
      const s=document.getElementById('mo1').value, e=document.getElementById('mo2').value, v=document.getElementById('mo3').value;
      if (!s||!e) { showToast('⚠️ Vui lòng nhập phạm vi máy!'); return; }
      apiPost(`action=save_bulk_ot&start=${s}&end=${e}&ot=${v}`)
        .then(() => {
          closeModal(); refreshData();
          showToast(`✅ Đã giao bài "${v||'(xóa)'}" cho máy ${s}–${e}`);
          logAction(`Giao bài loạt máy ${s}–${e}: ${v||'(xóa)'}`);
        });
    }
  );
}

// ── TOGGLE LIST SECTION ───────────────────────────────────────
function toggleList() {
  const s = document.getElementById('listSection');
  s.style.display = s.style.display === 'block' ? 'none' : 'block';
}

// ── KEYBOARD NAV ─────────────────────────────────────────────
window.addEventListener('keydown', function(e) {
  if (isTyping) return;
  if (!['ArrowUp','ArrowDown','Home','End','Escape'].includes(e.key)) return;
  e.preventDefault();
  if (e.key === 'Escape') { selectRow(selectedPC); return; }
  const old = selectedPC;
  if (!selectedPC) { selectedPC = 1; }
  else if (e.key === 'ArrowDown' && selectedPC < 50) selectedPC++;
  else if (e.key === 'ArrowUp'   && selectedPC > 1)  selectedPC--;
  else if (e.key === 'Home')  selectedPC = 1;
  else if (e.key === 'End')   selectedPC = 50;
  if (old !== selectedPC) {
    if (old) { const r=document.getElementById('row-pc'+old); if(r) r.classList.remove('sel-row'); }
    const nr = document.getElementById('row-pc'+selectedPC);
    if (nr) { nr.classList.add('sel-row'); nr.scrollIntoView({behavior:'smooth',block:'center'}); }
    showDetail(selectedPC);
  }
});

// ── AUTO REFRESH ─────────────────────────────────────────────
function startAutoRefresh() {
  refreshTimer = setInterval(() => { if (!isTyping) refreshData(); }, 5000);
}
document.addEventListener('visibilitychange', function() {
  if (document.hidden) { clearInterval(refreshTimer); } 
  else { refreshData(); startAutoRefresh(); }
});

// ── BOOT ─────────────────────────────────────────────────────
refreshData();
startAutoRefresh();
</script>