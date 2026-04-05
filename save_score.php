<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/plain");

$tenLop = file_exists('active_class.txt') ? trim(file_get_contents('active_class.txt')) : "Lop_Chua_Phan_Loai";
$ip_hs = $_SERVER['REMOTE_ADDR'];
$rawData = file_get_contents('php://input');

if ($rawData) {
    $data = urldecode($rawData);
    if (!file_exists($tenLop)) { mkdir($tenLop, 0777, true); }

    // 2. Lấy điểm
    $diem = "0";
    if (preg_match('/[?&]sp=(\d+)/i', '&' . $data, $m)) { $diem = $m[1]; }

    // 3. Lấy thời gian
    $totalSeconds = 0;
    if (preg_match('/[?&]ut=(\d+)/i', '&' . $data, $m)) { $totalSeconds = (int)$m[1]; }
    $safeDuration = sprintf('%02d-%02d-%02d', floor($totalSeconds / 3600), floor(($totalSeconds % 3600) / 60), $totalSeconds % 60);

    // --- MỚI: Lấy tên bài OT (Tìm trong dữ liệu iSpring gửi về) ---
    $otType = "Unknown";
    // iSpring thường gửi tên bài qua tham số 'lt' hoặc 'USER_NAME' nếu thầy đặt tên file tinh tế
    if (preg_match('/[?&]lt=([^&]*)/i', '&' . $data, $m)) {
        $otType = strtoupper(trim($m[1]));
    }
    // Nếu không thấy, thử tìm trong USER_NAME (nếu học sinh nhập kèm)
    if ($otType == "Unknown" && preg_match('/(OT\d|GM\d)/i', $data, $m)) {
        $otType = strtoupper($m[1]);
    }

    // 4. Xác định PC
    $pcFinal = 0;
    if (file_exists("ip_mapping.txt")) {
        $lines = file("ip_mapping.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 2 && $parts[0] == $ip_hs) {
                $pcFinal = (int)preg_replace('/[^0-9]/', '', $parts[1]);
                break;
            }
        }
    }
    if ($pcFinal == 0 && preg_match('/USER_NAME=([^&]*)/i', $data, $m)) {
        $pcFinal = (int)preg_replace('/[^0-9]/', '', $m[1]);
    }

    // 5. Lưu file (Thêm $otType vào tên file)
    $prefix = ($pcFinal >= 1 && $pcFinal <= 50) ? "PC" . str_pad($pcFinal, 2, "0", STR_PAD_LEFT) : "Khach_" . str_replace('.', '_', $ip_hs);
    // Định dạng mới: PC01_OT1_Diem_100_...html
    $filename = $tenLop . "/" . $prefix . "_" . $otType . "_Diem_" . $diem . "_" . $safeDuration . "_" . time() . ".html";

    if (file_put_contents($filename, $rawData)) { echo "OK"; } else { echo "Error"; }
}
?>