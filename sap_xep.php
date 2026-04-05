<?php
$file = "danhsach/ip_mapping.txt";
if (file_exists($file)) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    // Sắp xếp theo số máy (PC-01, PC-02...)
    usort($lines, function($a, $b) {
        preg_match('/PC-(\d+)/', $a, $mA);
        preg_match('/PC-(\d+)/', $b, $mB);
        return (int)($mA[1] ?? 0) <=> (int)($mB[1] ?? 0);
    });
    
    // Ghi lại vào file (Bỏ chữ PC- để save_score.php đọc chuẩn hơn)
    $cleanLines = [];
    foreach($lines as $l) {
        $p = explode(" ", trim($l));
        if(count($p) >= 2) {
            $num = preg_replace('/[^0-9]/', '', $p[1]);
            $cleanLines[] = $p[0] . " " . str_pad($num, 2, "0", STR_PAD_LEFT);
        }
    }
    
    file_put_contents($file, implode(PHP_EOL, $cleanLines) . PHP_EOL);
    echo "<h3>🎉 Đã sắp xếp xong 48 máy theo thứ tự từ 01 đến 50!</h3>";
    echo "Thầy có thể mở file ip_mapping.txt để kiểm tra sự ngăn nắp.";
}
?>