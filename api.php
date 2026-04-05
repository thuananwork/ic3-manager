<?php
error_reporting(0); // Chặn cảnh báo của XAMPP làm hỏng chuẩn JSON
$tenLop = file_exists('active_class.txt') ? trim(file_get_contents('active_class.txt')) : "";
$folderDS = "danhsach";

if (isset($_GET['action']) && $_GET['action'] == 'get_data') {
    $danhSachMapping = [];
    $pathDS = "$folderDS/danhsach_$tenLop.txt";

    $targets = file_exists("$folderDS/target_$tenLop.json") ? json_decode(file_get_contents("$folderDS/target_$tenLop.json"), true) : [];
    $otConfigs = file_exists("$folderDS/ot_config_$tenLop.json") ? json_decode(file_get_contents("$folderDS/ot_config_$tenLop.json"), true) : [];

    if ($tenLop && file_exists($pathDS)) {
        $lines = explode("\n", file_get_contents($pathDS));
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            $parts = preg_split('/\s+/', $line);
            if (count($parts) >= 2) {
                $lastPart = array_pop($parts);
                $pcNum = (int)preg_replace('/[^0-9]/', '', $lastPart);
                $name = implode(" ", $parts);
                if ($pcNum > 0 && $pcNum <= 50) $danhSachMapping[$pcNum] = rtrim($name, " -");
            }
        }
    }

    $diemDaThu = [];
    if ($tenLop && is_dir($tenLop)) {
        $files = glob($tenLop . "/*.html");
        if ($files) {
            usort($files, function ($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            foreach ($files as $file) {
                $filename = basename($file);

                // BỎ QUA FILE RÁC CỦA MACBOOK (Bắt đầu bằng dấu ._)
                if (strpos($filename, '._') === 0) continue;

                $pcNum = 0;
                $subedOT = "UNKNOWN";
                $score = "0";
                $t = [];
                // File cũ
                if (preg_match('/PC(\d+)_Diem_(\d+)_([\d\-]+)/i', $filename, $matches)) {
                    $pcNum = (int)$matches[1];
                    $subedOT = "OT1";
                    $score = $matches[2];
                    $t = explode('-', $matches[3]);
                }
                // File mới
                elseif (preg_match('/PC(\d+)_([^_]+)_Diem_(\d+)_([\d\-]+)/i', $filename, $matches)) {
                    $pcNum = (int)$matches[1];
                    $subedOT = strtoupper($matches[2]);
                    $score = $matches[3];
                    $t = explode('-', $matches[4]);
                }

                if ($pcNum >= 1 && $pcNum <= 50) {
                    $timeDisplay = ((int)$t[1] > 0 ? (int)$t[1] . "p" : "") . (int)($t[2] ?? 0) . "s";
                    if (!isset($diemDaThu[$pcNum])) {
                        $diemDaThu[$pcNum] = ['score' => $score, 'max' => (int)$score, 'timeSpent' => $timeDisplay, 'lastOT' => $subedOT];
                    } else {
                        $diemDaThu[$pcNum]['score'] .= " | " . $score;
                        if ((int)$score >= $diemDaThu[$pcNum]['max']) {
                            $diemDaThu[$pcNum]['max'] = (int)$score;
                            $diemDaThu[$pcNum]['timeSpent'] = $timeDisplay;
                            $diemDaThu[$pcNum]['lastOT'] = $subedOT;
                        }
                    }
                }
            }
        }
    }
    // Trả về Object JSON an toàn tuyệt đối
    echo json_encode([
        'mapping' => (object)$danhSachMapping,
        'scores' => (object)$diemDaThu,
        'targets' => (object)$targets,
        'otConfigs' => (object)$otConfigs
    ]);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] == 'save_list') {
    if (!is_dir($folderDS)) mkdir($folderDS, 0777, true);
    file_put_contents("$folderDS/danhsach_$tenLop.txt", $_POST['data']);
    echo "OK";
    exit;
}
if (isset($_POST['action']) && $_POST['action'] == 'delete_scores') {
    if ($tenLop && is_dir($tenLop)) {
        foreach (glob($tenLop . "/*.html") as $file) {
            if (is_file($file)) unlink($file);
        }
    }
    echo "OK";
    exit;
}
if (isset($_POST['action']) && $_POST['action'] == 'save_target') {
    $pc = $_POST['pc'];
    $target = trim($_POST['target']);
    $targets = file_exists("$folderDS/target_$tenLop.json") ? json_decode(file_get_contents("$folderDS/target_$tenLop.json"), true) : [];
    $targets[$pc] = $target;
    file_put_contents("$folderDS/target_$tenLop.json", json_encode($targets));
    echo "OK";
    exit;
}
if (isset($_POST['action']) && $_POST['action'] == 'save_ot') {
    $pc = $_POST['pc'];
    $ot = $_POST['ot'];
    $otConfigs = file_exists("$folderDS/ot_config_$tenLop.json") ? json_decode(file_get_contents("$folderDS/ot_config_$tenLop.json"), true) : [];
    $otConfigs[$pc] = $ot;
    file_put_contents("$folderDS/ot_config_$tenLop.json", json_encode($otConfigs));
    echo "OK";
    exit;
}
if (isset($_POST['action']) && $_POST['action'] == 'save_bulk_target') {
    $start = (int)$_POST['start'];
    $end = (int)$_POST['end'];
    $target = trim($_POST['target']);
    $targets = file_exists("$folderDS/target_$tenLop.json") ? json_decode(file_get_contents("$folderDS/target_$tenLop.json"), true) : [];
    for ($i = $start; $i <= $end; $i++) {
        $targets[$i] = $target;
    }
    file_put_contents("$folderDS/target_$tenLop.json", json_encode($targets));
    echo "OK";
    exit;
}
if (isset($_POST['action']) && $_POST['action'] == 'save_bulk_ot') {
    $start = (int)$_POST['start'];
    $end = (int)$_POST['end'];
    $ot = $_POST['ot'];
    $otConfigs = file_exists("$folderDS/ot_config_$tenLop.json") ? json_decode(file_get_contents("$folderDS/ot_config_$tenLop.json"), true) : [];
    for ($i = $start; $i <= $end; $i++) {
        $otConfigs[$i] = $ot;
    }
    file_put_contents("$folderDS/ot_config_$tenLop.json", json_encode($otConfigs));
    echo "OK";
    exit;
}
