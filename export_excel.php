<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . '/includes/helpers.php';

$tenLop = isset($_GET['lop']) ? $_GET['lop'] : (file_exists('active_class.txt') ? trim(file_get_contents('active_class.txt')) : "");
if (empty($tenLop)) {
    die("Khong tim thay lop.");
}

// 1. Cấu hình để trình duyệt nhận diện là file Excel
$filename = "Bang_Diem_" . $tenLop . "_" . date("H-i_d-m-Y") . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

// 2. Lấy dữ liệu (dùng chung hàm parse từ helpers.php)
$pathDS = "danhsach/danhsach_$tenLop.txt";
$danhSachHS = parseDanhSach($pathDS);
$targets = loadJson("danhsach/target_$tenLop.json");
$otConfigs = loadJson("danhsach/ot_config_$tenLop.json");

// 3. Lấy dữ liệu điểm (format 'all' array cho Excel)
$diemDaThu = [];
if (is_dir($tenLop)) {
    $files = glob($tenLop . "/*.html");
    if ($files) {
        usort($files, function ($a, $b) { return filemtime($a) - filemtime($b); });
        foreach ($files as $file) {
            $fname = basename($file);
            if (strpos($fname, '._') === 0) continue;

            $pcNum = 0;
            $score = "0";
            $timeDisplay = "0s";
            $subedOT = "UNKNOWN";

            // Format mới: PC01_OT1_Diem_100_...
            if (preg_match('/PC(\d+)_([^_]+)_Diem_(\d+)_([\d\-]+)/i', $fname, $matches)) {
                $pcNum = (int)$matches[1];
                $subedOT = strtoupper($matches[2]);
                $score = $matches[3];
                $t = explode('-', $matches[4]);
                $timeDisplay = ((int)($t[1] ?? 0) > 0 ? (int)$t[1] . "p" : "") . (int)($t[2] ?? 0) . "s";
            }
            // Format cũ: PC01_Diem_100_...
            elseif (preg_match('/PC(\d+)_Diem_(\d+)_([\d\-]+)/i', $fname, $matches)) {
                $pcNum = (int)$matches[1];
                $subedOT = "OT1";
                $score = $matches[2];
                $t = explode('-', $matches[3]);
                $timeDisplay = ((int)($t[1] ?? 0) > 0 ? (int)$t[1] . "p" : "") . (int)($t[2] ?? 0) . "s";
            }

            if ($pcNum >= 1 && $pcNum <= 50) {
                if (!isset($diemDaThu[$pcNum])) {
                    $diemDaThu[$pcNum] = ['all' => [$score], 'max' => (int)$score, 'time' => $timeDisplay, 'lastOT' => $subedOT];
                } else {
                    $diemDaThu[$pcNum]['all'][] = $score;
                    if ((int)$score >= $diemDaThu[$pcNum]['max']) {
                        $diemDaThu[$pcNum]['max'] = (int)$score;
                        $diemDaThu[$pcNum]['time'] = $timeDisplay;
                        $diemDaThu[$pcNum]['lastOT'] = $subedOT;
                    }
                }
            }
        }
    }
}
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        table { border-collapse: collapse; }
        td, th { border: 1px solid #ccc; padding: 5px; color: #000; font-size: 13pt; }
        .header { background-color: #CCFFCC; text-align: center; font-weight: bold; }
        .center { text-align: center; }
    </style>
</head>

<body>
    <table>
        <thead>
            <tr>
                <th class="header" style="width: 50px;">STT</th>
                <th class="header" style="width: 250px;">Họ và tên</th>
                <th class="header" style="width: 80px;">Số máy</th>
                <th class="header" style="width: 180px;">Các lần nộp</th>
                <th class="header" style="width: 120px;">ĐIỂM CẦN ĐẠT</th>
                <th class="header" style="width: 120px;">ĐIỂM CAO NHẤT</th>
                <th class="header" style="width: 120px;">Bài giao</th>
                <th class="header" style="width: 120px;">Bài đã thi</th>
                <th class="header" style="width: 120px;">Thời gian làm</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stt = 1;
            ksort($danhSachHS);
            foreach ($danhSachHS as $pc => $ten) {
                $lanNop = isset($diemDaThu[$pc]) ? implode(" | ", $diemDaThu[$pc]['all']) : "";
                $diemCao = isset($diemDaThu[$pc]) ? $diemDaThu[$pc]['max'] : "";
                $tg = isset($diemDaThu[$pc]) ? $diemDaThu[$pc]['time'] : "";
                $targetScore = isset($targets[$pc]) ? $targets[$pc] : "";
                $baiGiao = isset($otConfigs[$pc]) ? $otConfigs[$pc] : "";
                $baiDaThi = isset($diemDaThu[$pc]) ? $diemDaThu[$pc]['lastOT'] : "";

                // Bôi đỏ nếu Điểm Max < Điểm Cần Đạt
                $maxScoreStyle = "font-weight:bold;";
                if ($targetScore !== "" && $diemCao !== "") {
                    if ((int)$diemCao < (int)$targetScore) {
                        $maxScoreStyle .= " background-color: #ffcccc; color: #cc0000;";
                    }
                }

                // Bôi đỏ nếu thi sai bài giao
                $otStyle = "";
                if ($baiGiao !== "" && $baiDaThi !== "" && $baiDaThi !== "UNKNOWN") {
                    $assignedArr = array_map('trim', explode(',', $baiGiao));
                    if (!in_array($baiDaThi, $assignedArr)) {
                        $otStyle = "background-color: #ffcccc; color: #cc0000; font-weight:bold;";
                    }
                }

                echo "<tr>";
                echo "<td class='center'>$stt</td>";
                echo "<td>$ten</td>";
                echo "<td class='center'>PC" . str_pad($pc, 2, "0", STR_PAD_LEFT) . "</td>";
                echo "<td class='center'>$lanNop</td>";
                echo "<td class='center'>$targetScore</td>";
                echo "<td class='center' style='$maxScoreStyle'>$diemCao</td>";
                echo "<td class='center'>$baiGiao</td>";
                echo "<td class='center' style='$otStyle'>$baiDaThi</td>";
                echo "<td class='center'>$tg</td>";
                echo "</tr>";
                $stt++;
            }
            ?>
        </tbody>
    </table>
</body>

</html>