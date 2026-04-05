<?php
// nhan_ip.php
$ip_hs = $_SERVER['REMOTE_ADDR'];
$computer_name = isset($_GET['name']) ? $_GET['name'] : "Unknown";

$folder = __DIR__ . "/danhsach";
$file = $folder . "/ip_mapping.txt";

// Tự tạo thư mục nếu chưa có
if (!is_dir($folder)) {
    mkdir($folder, 0777, true);
}

if ($ip_hs) {
    // Lọc lấy số máy
    $pcNum = (int)filter_var($computer_name, FILTER_SANITIZE_NUMBER_INT);
    $danhTinh = ($pcNum > 0) ? str_pad($pcNum, 2, "0", STR_PAD_LEFT) : $computer_name;
    
    $line = "$ip_hs $danhTinh" . PHP_EOL;
    
    // Ghi file - dùng FILE_APPEND để nối đuôi danh sách
    file_put_contents($file, $line, FILE_APPEND);
    
    // Tạo thêm 1 file test ở ngay ngoài để xem PHP có chạy không
    file_put_contents("test_chay.txt", "May $computer_name da goi luc " . date("H:i:s"));
}
?>