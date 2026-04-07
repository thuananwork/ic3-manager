<?php
error_reporting(0);
require_once __DIR__ . '/includes/helpers.php';

$tenLop = file_exists('active_class.txt') ? trim(file_get_contents('active_class.txt')) : "";
if (empty($tenLop)) {
    die("Vui lòng chọn lớp trước (tại trang index)!");
}
$folderDS = "danhsach";

$pathDS = "$folderDS/danhsach_$tenLop.txt";
$mapping = parseDanhSach($pathDS); // returns [pcNum => fullName]

// Prepare JSON for initial state
$mappingJson = json_encode($mapping, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sơ đồ chỗ ngồi - Lớp <?= htmlspecialchars($tenLop) ?></title>
    <link rel="stylesheet" href="css/quanly.css">
    <style>
        body {
            background-color: #f1f3f4;
            padding: 20px;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-sizing: border-box;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .header h2 { 
            margin: 0; 
            font-size: 28px; 
            font-weight: 800;
            background: linear-gradient(90deg, #1557b0, #4285f4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0px 2px 4px rgba(0,0,0,0.1);
        }
        .sub { font-size: 15px; color: var(--gray-500); font-style: italic; margin-top: 4px; }
        
        .main-container {
            display: flex;
            flex: 1;
            gap: 20px;
            overflow: hidden;
        }
        
        .left-panel {
            width: 380px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            border: 1px solid var(--gray-300);
            overflow: hidden;
        }
        
        .panel-header {
            background: #f8f9fa;
            color: #333;
            padding: 15px;
            font-weight: 700;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--gray-300);
            font-size: 16px;
        }
        
        .list-container {
            flex: 1;
            overflow-y: auto;
            padding: 0;
        }
        
        .edit-row {
            display: flex;
            border-bottom: 1px solid var(--gray-200);
            padding: 10px 15px;
            align-items: center;
            font-size: 14px;
        }
        .edit-row:hover { background: var(--gray-50); }
        .er-name {
            flex: 1;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-right: 10px;
        }
        .er-pc {
            width: 70px;
        }
        .er-pc input {
            width: 100%;
            padding: 6px;
            border: 1px solid var(--gray-400);
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
            color: var(--blue-dk);
            font-size: 14px;
        }
        
        /* Right Panel */
        .right-panel {
            flex: 1;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            padding: 20px;
            border: 1px solid var(--gray-300);
            overflow: auto;
        }
        
        /* Sơ Đồ Table */
        table.sodo-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 18px; /* Phóng to chữ ra xíu */
            table-layout: fixed;
        }
        table.sodo-table th, table.sodo-table td {
            border: 1px solid var(--gray-300);
            padding: 12px 6px;
            text-align: center;
            vertical-align: middle;
        }
        table.sodo-table th {
            background-color: #d1e7dd; /* Tô màu cột tiêu đề tươi hơn (xanh lá nhạt) */
            font-weight: 700;
            color: #0f5132;
            position: static !important; /* KHÔNG cố định khi lướt xuống */
            border-bottom: 2px solid #badbcc;
        }
        
        .col-ho { width: 14%; }
        .col-ten { width: 8%; }
        .col-pc { width: 7%; font-weight: bold; }
        .col-empty { width: 3%; border-top: none; border-bottom: none; background: #fff !important; }
        
        .dp-ho { text-align: left; padding-left: 8px; font-weight: 400; color: #333; }
        .dp-ten { font-weight: 500; color: #333; }
        .dp-pc { font-weight: 500; color: #333; }
        
        .btn-ix { padding: 4px 8px; background: none; border: none; color: var(--gray-500); cursor: pointer; transition: 0.2s; font-weight: bold; font-size: 16px; }
        .btn-ix:hover { color: var(--red); }
        
        /* Toast style (Dialog giữa màn hình) */
        .toast {
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.9); background: #fff;
            padding: 20px 30px; border-radius: 12px;
            opacity: 0; pointer-events: none; transition: 0.3s; z-index: 9999;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2); border: 1px solid var(--gray-200);
            text-align: center; min-width: 320px;
        }
        .toast.show { opacity: 1; transform: translate(-50%, -50%) scale(1); pointer-events: auto; }
        .toast-title { font-size: 18px; color: var(--green); font-weight: 800; margin-bottom: 10px; }
        .toast-body { font-size: 16px; color: #333; font-weight: normal; }
        /* Presentation Mode */
        body.presenting .header, body.presenting .left-panel { display: none !important; }
        body.presenting { padding: 5px; background: #fff; }
        body.presenting .right-panel { border: none; box-shadow: none; padding: 5px; border-radius: 0; }
        body.presenting table.sodo-table { font-size: 26px; height: 100%; border: 2px solid var(--gray-400); }
        body.presenting table.sodo-table td { padding: 15px 8px; border: 1px solid var(--gray-400); }
        body.presenting table.sodo-table th { display: none; }
        body.presenting .col-ho { width: 18%; font-size: 28px; font-weight: bold; }
        body.presenting .col-ten { width: 9%; font-size: 28px; font-weight: bold; }
        body.presenting .col-pc { width: 6%; font-size: 20px; font-weight: normal; color: #666; }
    </style>
</head>
<body>

<div class="header">
    <div>
        <h2>🪑 SƠ ĐỒ CHỖ NGỒI - LỚP <?= htmlspecialchars($tenLop) ?></h2>
    </div>
    <div style="display: flex; gap: 12px;">
        <a href="index.php" class="btn" style="padding: 12px 20px; font-size: 16px;">🔄 Đổi lớp</a>
        <a href="quanly.php" class="btn" style="padding: 12px 20px; font-size: 16px;">⬅️ Quay lại quản lý</a>
        <button class="btn btn-green" onclick="togglePresent()" style="padding: 12px 20px; font-size: 16px;">📺 Trình chiếu</button>
        <button class="btn btn-primary" id="btnSave" onclick="saveChanges()" disabled style="padding: 12px 20px; font-size: 16px;">💾 Lưu thay đổi</button>
    </div>
</div>

<div class="main-container">
    <!-- Trái: Sửa vị trí -->
    <div class="left-panel">
        <div class="panel-header">
            <span>Chỉnh sửa vị trí</span>
            <div>
                <button class="btn btn-sm btn-green" onclick="openPasteModal()" style="font-size: 12px; padding: 4px 8px;">📋 Dán từ Excel</button>
                <span id="stCount" style="font-size: 13px; font-weight: normal; opacity: 0.8; margin-left: 8px;"></span>
            </div>
        </div>
        <div class="list-container" id="editorList">
            <!-- Rendered by JS -->
        </div>
    </div>
    
    <!-- Phải: Giao diện Sơ đồ Excel -->
    <div class="right-panel">
        <table class="sodo-table" id="sodoTable">
            <thead>
                <tr>
                    <th class="col-ho">Họ và chữ đệm</th>
                    <th class="col-ten">Tên</th>
                    <th class="col-pc">Vị trí</th>
                    <th class="col-empty" style="border:none;"></th>
                    <th class="col-ho">Họ và chữ đệm</th>
                    <th class="col-ten">Tên</th>
                    <th class="col-pc">Vị trí</th>
                    <th class="col-empty" style="border:none;"></th>
                    <th class="col-ho">Họ và chữ đệm</th>
                    <th class="col-ten">Tên</th>
                    <th class="col-pc">Vị trí</th>
                </tr>
            </thead>
            <tbody id="sodoBody">
                <!-- Rendered by JS -->
            </tbody>
        </table>
    </div>
</div>

<!-- Toast Message Centered -->
<div class="toast" id="toastMsg">
    <div class="toast-title">✅ THÀNH CÔNG</div>
    <div class="toast-body" id="toastContent"></div>
</div>

<!-- Modal background for confirm swap -->
<div class="modal-bg" id="swapModalBg">
    <div class="modal">
        <div class="modal-title">⚠️ Có sự trùng lặp máy!</div>
        <div id="swapModalBody" style="font-size: 15px; line-height: 1.5; margin-bottom: 20px;"></div>
        <div class="modal-foot">
            <button class="btn" onclick="cancelSwap()">Hủy bỏ</button>
            <button class="btn btn-primary" onclick="confirmSwap()">Đồng ý Đổi Chỗ</button>
        </div>
    </div>
</div>

<!-- Modal Paste -->
<div class="modal-bg" id="pasteModalBg">
    <div class="modal" style="width: 500px;">
        <div class="modal-title">Dán dữ liệu từ Excel</div>
        <div style="font-size: 14px; margin-bottom: 10px; color: var(--gray-700);">
            Dán bảng dữ liệu (từ Google Sheet hoặc Excel) chứa cột Tên Học sinh và cột Số máy. Hệ thống sẽ tự động ghép nối.
        </div>
        <textarea id="pasteData" class="textarea" placeholder="Nguyễn Văn A    PC05&#10;..."></textarea>
        <div class="modal-foot" style="margin-top: 15px;">
            <button class="btn" onclick="closePasteModal()">Hủy</button>
            <button class="btn btn-primary" onclick="confirmPaste()">Cập nhật vị trí</button>
        </div>
    </div>
</div>

<div id="presentExitBtn" style="display:none; position:fixed; right: 20px; bottom: 20px; z-index: 10000; box-shadow: 0 4px 10px rgba(0,0,0,0.3); border-radius: 8px;">
    <button class="btn btn-primary" onclick="togglePresent()" style="font-size: 14px; padding: 10px 16px;">❌ Thoát Trình Chiếu</button>
</div>

<script>
let currentMapping = <?= $mappingJson ?>;
let originalMapping = JSON.parse(JSON.stringify(currentMapping));
let pendingChanges = {}; // Keeps track of changes made

// Internal state: array of {id, ho, ten, originalPc, currentPc, deleted}
let students = [];

function parseName(fullName) {
    let parts = fullName.trim().split(" ");
    if (parts.length > 1) {
        let ten = parts.pop();
        let ho = parts.join(" ");
        return {ho, ten};
    }
    return {ho: fullName, ten: ""};
}

function initData() {
    students = [];
    for (let pc in originalMapping) {
        let name = originalMapping[pc];
        let pName = parseName(name);
        students.push({
            id: pc,
            ho: pName.ho,
            ten: pName.ten,
            fullName: name,
            originalPc: parseInt(pc),
            currentPc: parseInt(pc),
            deleted: false
        });
    }
    document.getElementById('stCount').textContent = students.length + " học sinh";
    renderEditor();
    renderSodo();
}

function renderEditor() {
    // Sort by current pc for consistent ordering 
    // Actually, user might prefer it sorted by STT or Name, but let's keep it sorted by current PC so it matches left-to-right logic.
    students.sort((a,b) => a.currentPc - b.currentPc);
    
    let html = '';
    students.forEach((st) => {
        if (st.deleted) return;
        let isChanged = st.currentPc !== st.originalPc;
        let bgStyle = isChanged ? "background: #fff3e0;" : ""; // highlight changed rows
        html += `
        <div class="edit-row" style="${bgStyle}">
            <div class="er-name" title="${st.fullName}">${st.fullName}</div>
            <div class="er-pc">
                <input type="number" min="1" max="50" data-id="${st.id}" value="${st.currentPc}" onchange="handlePCChange(this)">
            </div>
            <button class="btn-ix" title="Xóa học sinh này" onclick="deleteStudent('${st.id}')">✕</button>
        </div>`;
    });
    document.getElementById('editorList').innerHTML = html;
}

let swapCandidate = null;

function handlePCChange(inputElem) {
    let stId = inputElem.getAttribute("data-id");
    let student = students.find(s => s.id === stId);
    let oldPc = student.currentPc;
    let newPc = parseInt(inputElem.value);
    
    if (isNaN(newPc) || newPc < 1 || newPc > 50) {
        showToast("Vui lòng nhập Vị trí hợp lệ (1-50)");
        inputElem.value = oldPc;
        return;
    }
    
    if (oldPc === newPc) return; // No change
    
    // Check collision
    let collision = students.find(s => s.currentPc === newPc && s.id !== stId);
    
    if (collision) {
        // Warning logic
        swapCandidate = {
            mover: student,
            target: collision,
            oldPc: oldPc,
            newPc: newPc,
            inputElem: inputElem
        };
        
        let msg = `Máy <b>PC${newPc.toString().padStart(2, '0')}</b> hiện tại đang có học sinh <b>${collision.fullName}</b> ngồi.<br><br>
                   Thầy có muốn <b>Giao hoán (Swap)</b> chỗ của <b>${student.fullName}</b> và <b>${collision.fullName}</b> không?`;
        
        document.getElementById('swapModalBody').innerHTML = msg;
        document.getElementById('swapModalBg').classList.add('open');
    } else {
        // Safe to move
        student.currentPc = newPc;
        updateSaveButtonState();
        renderEditor();
        renderSodo();
    }
}

function deleteStudent(id) {
    let st = students.find(s => s.id === id);
    if (!st) return;
    if (confirm("Chắc chắn bạn muốn xóa học sinh " + st.fullName + " khỏi danh sách?")) {
        st.deleted = true;
        updateSaveButtonState();
        renderEditor();
        renderSodo();
    }
}

function openPasteModal() {
    document.getElementById('pasteData').value = '';
    document.getElementById('pasteModalBg').classList.add('open');
}

function closePasteModal() {
    document.getElementById('pasteModalBg').classList.remove('open');
}

function confirmPaste() {
    let raw = document.getElementById('pasteData').value.trim();
    if (!raw) {
        closePasteModal();
        return;
    }
    
    let cells = raw.replace(/\r/g, '').split(/[\t\n]+/);
    let currentNameBuffer = [];
    let mappings = [];
    
    for (let c of cells) {
        c = c.trim();
        if (!c) continue;
        let pcMatch = c.match(/^PC\s*0*(\d+)$/i);
        if (pcMatch) {
            mappings.push({
                str: currentNameBuffer.join(" ").toLowerCase().replace(/\s+/g, ' '),
                pc: parseInt(pcMatch[1], 10)
            });
            currentNameBuffer = []; // reset for next student
        } else {
            currentNameBuffer.push(c.toLowerCase());
        }
    }
    
    let mapCount = 0;
    for (let m of mappings) {
        if (!m.str) continue;
        let bestStudent = null;
        for (let st of students) {
            if (st.deleted) continue;
            let fName = st.fullName.toLowerCase().replace(/\s+/g, ' ');
            let ho = st.ho ? st.ho.toLowerCase().replace(/\s+/g, ' ') : '';
            let ten = st.ten ? st.ten.toLowerCase().replace(/\s+/g, ' ') : '';
            
            if (m.str.includes(fName) || fName.includes(m.str)) {
                bestStudent = st; break;
            } else if (ten && ho && m.str.includes(ten) && m.str.includes(ho)) {
                bestStudent = st; break;
            }
        }
        if (bestStudent && bestStudent.currentPc !== m.pc) {
            bestStudent.currentPc = m.pc;
            mapCount++;
        }
    }
    
    closePasteModal();
    if (mapCount > 0) {
        document.querySelector('#toastMsg .toast-title').innerHTML = "✅ THÀNH CÔNG";
        document.querySelector('#toastMsg .toast-title').style.color = "var(--green)";
        showToast(`Đã nhận diện và cập nhật vị trí cho <b>${mapCount}</b> học sinh!`);
        updateSaveButtonState();
        renderEditor();
        renderSodo();
    } else {
        document.querySelector('#toastMsg .toast-title').innerHTML = "⚠️ LỖI GHÉP NỐI";
        document.querySelector('#toastMsg .toast-title').style.color = "var(--red)";
        showToast("Không ghép nối được học sinh nào. Hãy kiểm tra lại cột Tên và Số máy.");
    }
}

function confirmSwap() {
    if (!swapCandidate) return;
    
    // Mover goes to new PC
    swapCandidate.mover.currentPc = swapCandidate.newPc;
    // Target goes to Mover's old PC
    swapCandidate.target.currentPc = swapCandidate.oldPc;
    
    document.getElementById('swapModalBg').classList.remove('open');
    swapCandidate = null;
    
    updateSaveButtonState();
    renderEditor();
    renderSodo();
    document.querySelector('#toastMsg .toast-title').innerHTML = "✅ THÀNH CÔNG";
    document.querySelector('#toastMsg .toast-title').style.color = "var(--green)";
    showToast("Đã đổi chỗ thành công!");
}

function cancelSwap() {
    if (swapCandidate) {
        swapCandidate.inputElem.value = swapCandidate.oldPc; // revert input
    }
    document.getElementById('swapModalBg').classList.remove('open');
    swapCandidate = null;
}

function togglePresent() {
    document.body.classList.toggle('presenting');
    let exitBtn = document.getElementById('presentExitBtn');
    if (document.body.classList.contains('presenting')) {
        exitBtn.style.display = 'block';
        if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen().catch(err => {
                console.log("Fullscreen API error:", err);
            });
        }
    } else {
        exitBtn.style.display = 'none';
        if (document.fullscreenElement) {
            document.exitFullscreen().catch(e => {});
        }
    }
}

function updateSaveButtonState() {
    let hasChanges = students.some(s => s.currentPc !== s.originalPc || s.deleted);
    const btn = document.getElementById('btnSave');
    btn.disabled = !hasChanges;
    if (hasChanges) {
        btn.innerHTML = "💾 Lưu thay đổi (Có sửa đổi!)";
        btn.style.animation = "pulse 1.5s infinite alternate";
    } else {
        btn.innerHTML = "💾 Lưu sơ đồ";
        btn.style.animation = "none";
    }
}

function renderSodo() {
    let html = '';
    
    // Helper to get student by CURRENT PC, ignoring deleted
    let getByPc = (pcNum) => {
        return students.find(s => s.currentPc === pcNum && !s.deleted) || null;
    };
    
    for (let row = 0; row < 20; row++) {
        let pc1 = row + 1;       // 1 - 20
        let pc2 = row + 21;      // 21 - 40
        let pc3 = row + 41;      // 41 - 60
        
        let st1 = getByPc(pc1);
        let st2 = getByPc(pc2);
        let st3 = getByPc(pc3);
        
        html += `<tr>`;
        
        // Col 1 (A-C)
        html += `
            <td class="dp-ho">${st1 ? st1.ho : ''}</td>
            <td class="dp-ten">${st1 ? st1.ten : ''}</td>
            <td class="col-pc">PC${pc1.toString().padStart(2, '0')}</td>
            <td class="col-empty"></td>
        `;
        
        // Col 2 (E-G)
        html += `
            <td class="dp-ho">${st2 ? st2.ho : ''}</td>
            <td class="dp-ten">${st2 ? st2.ten : ''}</td>
            <td class="col-pc">PC${pc2.toString().padStart(2, '0')}</td>
            <td class="col-empty"></td>
        `;
        
        // Col 3 (I-K) - only render up to PC 50 basically, but structure allows 60
        let showPc3 = pc3 <= 50;
        html += `
            <td class="dp-ho" style="border-right: ${!showPc3?'none':''}">${st3 && showPc3 ? st3.ho : ''}</td>
            <td class="dp-ten" style="border-right: ${!showPc3?'none':''}">${st3 && showPc3 ? st3.ten : ''}</td>
            <td class="${showPc3 ? 'col-pc' : 'col-empty'}" style="border-right: ${!showPc3?'none':''}">${showPc3 ? 'PC'+pc3.toString().padStart(2, '0') : ''}</td>
        `;
        
        html += `</tr>`;
    }
    
    document.getElementById('sodoBody').innerHTML = html;
}

function showToast(msg) {
    const t = document.getElementById('toastMsg');
    document.getElementById('toastContent').innerHTML = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}

function saveChanges() {
    let btn = document.getElementById('btnSave');
    btn.disabled = true;
    btn.innerHTML = "⏳ Đang lưu...";
    
    let changeMap = {};
    let deletes = [];
    students.forEach(st => {
        if (st.deleted) {
            deletes.push(st.originalPc);
        } else if (st.currentPc !== st.originalPc) {
            changeMap[st.originalPc] = st.currentPc;
        }
    });
    
    let formData = new URLSearchParams();
    formData.append('action', 'update_pc_mapping');
    formData.append('changes', JSON.stringify(changeMap));
    formData.append('deletes', JSON.stringify(deletes));
    
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(r => r.text())
    .then(res => {
        if (res.trim() === 'OK') {
            document.querySelector('#toastMsg .toast-title').innerHTML = "✅ LƯU THÀNH CÔNG";
            document.querySelector('#toastMsg .toast-title').style.color = "var(--green)";
            showToast("Đã lưu danh sách & cập nhật mọi chỉ tiêu bài thi!");
            // Commit changes
            students = students.filter(st => !st.deleted);
            students.forEach(st => {
                st.originalPc = st.currentPc;
            });
            updateSaveButtonState();
            renderEditor();
        } else {
            showToast("❌ Lỗi: " + res);
            btn.disabled = false;
        }
    })
    .catch(err => {
        showToast("❌ Lỗi kết nối!");
        btn.disabled = false;
    });
}

initData();
</script>

<style>
@keyframes pulse {
    0% { background-color: #f57f17; }
    100% { background-color: #ffb300; }
}
/* Base modal styles if quanly.css missing it */
.modal-bg { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 100; }
.modal-bg.open { display: flex; }
.modal { background: #fff; border-radius: 8px; width: 400px; max-width: 90%; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
.modal-title { font-size: 18px; font-weight: bold; margin-bottom: 15px; color: #d32f2f; }
.modal-foot { display: flex; justify-content: flex-end; gap: 10px; }
</style>

</body>
</html>
