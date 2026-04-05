<?php
error_reporting(0);
$listClasses = [];
foreach (glob("danhsach/danhsach_*.txt") as $filename) {
    $listClasses[] = str_replace(['danhsach/danhsach_', '.txt'], '', $filename);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_data'])) {
    $tenLop = $_POST['lop'];
    $data = json_decode($_POST['save_data'], true);
    if (!empty($data)) {
        if (!is_dir($tenLop)) mkdir($tenLop, 0777, true);
        file_put_contents("$tenLop/diem_tong.json", json_encode($data, JSON_UNESCAPED_UNICODE));
        echo "<script>alert('✅ Đã lưu sổ điểm lớp $tenLop!');</script>";
    }
}

$headers_data = ["STT", "HỌ", "TÊN", "LỚP", "VỊ TRÍ", "CẤM", "Ghi chú buổi học", "OT1", "OT2", "OT3", "OT4", "OT5", "SL HT TH", "FLA GM1", "FLA GM2", "GMT1", "GMT2", "TRẠNG THÁI"];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Excel Mode - Sổ Điểm</title>
    <style>
        :root { --ex-green: #217346; --ex-border: #bbb; --ex-bg: #f3f3f3; --ex-select: rgba(33, 115, 70, 0.2); }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #eef2f7; margin: 0; padding: 15px; overflow: hidden; }
        .container { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); height: 95vh; display: flex; flex-direction: column; }
        
        .controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-shrink: 0; }
        .btn-save { background: var(--ex-green); color: white; border: none; padding: 12px 30px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 16px; }
        
        .table-area { overflow: auto; border: 1px solid var(--ex-border); background: #fff; flex-grow: 1; position: relative; }
        table { border-collapse: collapse; table-layout: fixed; min-width: 1950px; }
        
        /* Headers A, B, C... (Tăng size) */
        th.col-header { background: var(--ex-bg); border: 1px solid var(--ex-border); font-size: 13px; color: #666; height: 26px; position: sticky; top: 0; z-index: 10; font-weight: normal; cursor: pointer; }
        
        /* Cột số thứ tự (Tăng size) */
        .row-header { position: sticky; left: 0; background: var(--ex-bg) !important; z-index: 11; border: 1px solid var(--ex-border); font-weight: bold; text-align: center; width: 50px; cursor: pointer; color: #666; font-size: 14px; }

        /* Ô nhập liệu (Tăng size chủ lực) */
        td.input-cell { border: 1px solid #ddd; padding: 0; }
        input.cell { width: 100%; height: 38px; border: none; outline: none; padding: 0 10px; font-size: 15px; box-sizing: border-box; background: transparent; display: block; cursor: cell; }
        
        /* Hàng tiêu đề số 1 (Tăng size) */
        .header-row-1 { background: #f2f2f2; }
        .header-row-1 input { font-weight: bold; text-align: center; color: #000; font-size: 14px; }

        input.cell:focus { box-shadow: inset 0 0 0 2px var(--ex-green); background: #fff; z-index: 5; position: relative; }
        input.selected-visual { background: var(--ex-select) !important; }

        /* Độ rộng cột */
        .w-ho { width: 200px; } .w-ten { width: 110px; }
        .w-ot { width: 80px; } .w-cam { width: 70px; }
        .w-status { width: 160px; }
    </style>
</head>
<body>

<div class="container">
    <div class="controls">
        <h2 style="margin:0; color:var(--ex-green); font-size: 1.4rem;">Excel Online: Nhập Điểm Thủ Công</h2>
        <form id="mainForm" method="POST">
            <span style="font-size:16px; font-weight:bold;">Lớp:</span> 
            <select name="lop" style="padding:8px; border-radius:4px; border:1px solid #ccc; font-weight:bold; font-size:15px;">
                <?php foreach($listClasses as $c) echo "<option value='$c'>$c</option>"; ?>
            </select>
            <input type="hidden" name="save_data" id="save_data">
            <button type="button" class="btn-save" onclick="collectAndSave()">💾 LƯU SỔ ĐIỂM</button>
        </form>
    </div>

    <div class="table-area">
        <table id="excelTable">
            <thead>
                <tr>
                    <th class="row-header" style="z-index:15; top:0;"></th>
                    <?php $cols = range('A', 'R'); foreach($cols as $index => $c) echo "<th class='col-header' onclick='selectColumn($index)'>$c</th>"; ?>
                </tr>
            </thead>
            <tbody>
                <?php for($i=1; $i<=60; $i++): 
                    $rowClass = ($i == 1) ? "header-row-1" : "";
                ?>
                <tr class="<?php echo $rowClass; ?>">
                    <td class="row-header" onclick="selectRow(<?php echo $i; ?>)"><?php echo $i; ?></td>
                    <?php for($j=0; $j<18; $j++): 
                        $val = ($i == 1) ? $headers_data[$j] : "";
                        $colClass = "";
                        if($j==1) $colClass="w-ho"; if($j==2) $colClass="w-ten";
                        if($j>=7 && $j<=11) $colClass="w-ot"; if($j==5) $colClass="w-cam";
                        if($j==17) $colClass="w-status";
                    ?>
                    <td class="input-cell <?php echo $colClass; ?>">
                        <input type="text" class="cell" data-row="<?php echo $i; ?>" data-col="<?php echo $j; ?>" value="<?php echo $val; ?>" spellcheck="false" autocomplete="off">
                    </td>
                    <?php endfor; ?>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const allCells = document.querySelectorAll('.cell');
let isAllSelected = false;

document.addEventListener('keydown', function(e) {
    const active = document.activeElement;
    if (!active || !active.classList.contains('cell')) return;
    const r = parseInt(active.getAttribute('data-row'));
    const c = parseInt(active.getAttribute('data-col'));

    if (e.ctrlKey && (e.key === 'a' || e.key === 'A')) {
        e.preventDefault();
        isAllSelected = true;
        allCells.forEach(cell => cell.classList.add('selected-visual'));
        return;
    }

    if (e.key === 'Delete') {
        const selected = document.querySelectorAll('.selected-visual');
        if (selected.length > 0) {
            selected.forEach(cell => { if (cell.getAttribute('data-row') != "1") cell.value = ''; });
        } else if (r != 1) { active.value = ''; }
    }

    let target = null;
    if (e.key === 'ArrowRight') target = document.querySelector(`.cell[data-row="${r}"][data-col="${c+1}"]`);
    if (e.key === 'ArrowLeft')  target = document.querySelector(`.cell[data-row="${r}"][data-col="${c-1}"]`);
    if (e.key === 'ArrowDown')  target = document.querySelector(`.cell[data-row="${r+1}"][data-col="${c}"]`);
    if (e.key === 'ArrowUp')    target = document.querySelector(`.cell[data-row="${r-1}"][data-col="${c}"]`);
    if (target) { e.preventDefault(); clearSelection(); target.focus(); }
});

function clearSelection() { isAllSelected = false; allCells.forEach(c => c.classList.remove('selected-visual')); }
function selectRow(rowNum) { clearSelection(); document.querySelectorAll(`.cell[data-row="${rowNum}"]`).forEach(c => c.classList.add('selected-visual')); }
function selectColumn(colIdx) { clearSelection(); document.querySelectorAll(`.cell[data-col="${colIdx}"]`).forEach(c => c.classList.add('selected-visual')); }
allCells.forEach(cell => { cell.addEventListener('mousedown', clearSelection); });

document.addEventListener('paste', function(e) {
    const active = document.activeElement;
    if (!active || !active.classList.contains('cell')) return;
    e.preventDefault();
    const text = (e.clipboardData || window.clipboardData).getData('text');
    const lines = text.split(/\r?\n/);
    const startR = parseInt(active.getAttribute('data-row'));
    const startC = parseInt(active.getAttribute('data-col'));
    lines.forEach((line, i) => {
        if (!line.trim()) return;
        const columns = line.split('\t');
        columns.forEach((val, j) => {
            const target = document.querySelector(`.cell[data-row="${startR + i}"][data-col="${startC + j}"]`);
            if (target) target.value = val.trim();
        });
    });
});

function collectAndSave() {
    const data = [];
    for (let i = 2; i <= 60; i++) {
        const ho = document.querySelector(`.cell[data-row="${i}"][data-col="1"]`).value;
        const ten = document.querySelector(`.cell[data-row="${i}"][data-col="2"]`).value;
        if (ho || ten) {
            data.push({
                name: (ho + " " + ten).trim(),
                OT1: document.querySelector(`.cell[data-row="${i}"][data-col="7"]`).value || 0,
                OT2: document.querySelector(`.cell[data-row="${i}"][data-col="8"]`).value || 0,
                OT3: document.querySelector(`.cell[data-row="${i}"][data-col="9"]`).value || 0,
                OT4: document.querySelector(`.cell[data-row="${i}"][data-col="10"]`).value || 0,
                OT5: document.querySelector(`.cell[data-row="${i}"][data-col="11"]`).value || 0
            });
        }
    }
    if (data.length === 0) { alert("Dữ liệu trống!"); return; }
    document.getElementById('save_data').value = JSON.stringify(data);
    document.getElementById('mainForm').submit();
}
</script>
</body>
</html>