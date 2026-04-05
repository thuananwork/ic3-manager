<?php
// Cho phép nhận diện IP
$ip_moi = $_SERVER['REMOTE_ADDR'];
$pc_name = isset($_GET['pc']) ? trim($_GET['pc']) : "Unknown";

if ($pc_name !== "Unknown") {
    $mappingFile = "ip_mapping.txt";
    $lines = [];

    // 1. Đọc dữ liệu cũ nếu file đã tồn tại
    if (file_exists($mappingFile)) {
        $lines = file($mappingFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    $new_mapping = [];
    $found = false;

    // 2. Lọc bỏ các dòng cũ của chính IP này hoặc Tên máy này để tránh trùng lặp
    foreach ($lines as $line) {
        $parts = preg_split('/\s+/', trim($line));
        if (count($parts) >= 2) {
            // Nếu dòng này không phải của IP mới và cũng không phải của PC này thì giữ lại
            if ($parts[0] !== $ip_moi && strtolower($parts[1]) !== strtolower($pc_name)) {
                $new_mapping[] = trim($line);
            }
        }
    }

    // 3. Thêm dòng mapping mới nhất vào danh sách
    $new_mapping[] = "$ip_moi $pc_name";

    // 4. Sắp xếp lại cho đẹp (theo số máy nếu có thể)
    sort($new_mapping);

    // 5. Ghi lại vào file ip_mapping.txt
    file_put_contents($mappingFile, implode("\n", $new_mapping));

    echo "OK - Da cap nhat: $pc_name co IP la $ip_moi";
} else {
    echo "Loi: Khong nhan duoc ten may (pc).";
}
