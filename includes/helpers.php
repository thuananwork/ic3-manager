<?php
// ═══════════════════════════════════════════════════════════
//  IC3 Manager – Shared Helper Functions
//  Include this file from api.php and export_excel.php
// ═══════════════════════════════════════════════════════════

function loadJson($path) {
    return file_exists($path) ? (json_decode(file_get_contents($path), true) ?: []) : [];
}

function saveJson($path, $data) {
    file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE));
}

function validateRange($start, $end) {
    return ($start >= 1 && $end <= 50 && $start <= $end);
}

/**
 * Auto-detect server's LAN IP address
 * @return string IP address or fallback
 */
function getServerIP() {
    // Method 1: Use SERVER_ADDR (works when called via HTTP)
    if (!empty($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] !== '127.0.0.1' && $_SERVER['SERVER_ADDR'] !== '::1') {
        return $_SERVER['SERVER_ADDR'];
    }
    // Method 2: Parse ipconfig on Windows
    $output = shell_exec('ipconfig 2>&1');
    if ($output && preg_match_all('/IPv4[^\n]*:\s*([\d\.]+)/i', $output, $matches)) {
        foreach ($matches[1] as $ip) {
            if ($ip !== '127.0.0.1' && strpos($ip, '192.168.') === 0) {
                return $ip;
            }
        }
        // Return first non-loopback IP if no 192.168.x.x found
        foreach ($matches[1] as $ip) {
            if ($ip !== '127.0.0.1') return $ip;
        }
    }
    return '127.0.0.1';
}

/**
 * Parse danh sách học sinh từ file TXT (hỗ trợ nhiều format)
 * @return array [pcNum => fullName, ...]
 */
function parseDanhSach($pathDS) {
    $mapping = [];
    if (!file_exists($pathDS)) return $mapping;

    $lines = explode("\n", file_get_contents($pathDS));
    $seqPC = 0;
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        $cols = explode("\t", $line);
        $fullName = "";
        $pcNum = 0;

        // Format 1: Google Sheet chuẩn (≥9 cột, cột 8 là PC)
        if (count($cols) >= 9 && isset($cols[8]) && preg_match('/PC\d+/i', $cols[8])) {
            $hoTen = trim($cols[0]);
            $ten = trim($cols[1]);
            $viTri = trim($cols[8]);
            $pcNum = (int)preg_replace('/[^0-9]/', '', $viTri);

            $fullName = $hoTen;
            if (!empty($ten) && !preg_match("/\b" . preg_quote($ten, '/') . "\b/i", $fullName)) {
                $fullName .= " " . $ten;
            }
        } else {
            // Format 2: Tab-separated, tự tìm cột PC
            $viTriIndex = -1;
            for ($i = 0; $i < count($cols); $i++) {
                if (preg_match('/^PC\d+/i', trim($cols[$i]))) {
                    $viTriIndex = $i;
                    break;
                }
            }

            if ($viTriIndex !== -1) {
                $pcNum = (int)preg_replace('/[^0-9]/', '', $cols[$viTriIndex]);
                $nameParts = [];
                for ($i = 0; $i < $viTriIndex; $i++) {
                    $p = trim($cols[$i]);
                    if (!empty($p) && !is_numeric($p) && strlen($p) > 1) {
                        $nameParts[] = $p;
                    }
                }
                if (empty($nameParts) && $viTriIndex > 0) {
                    $nameParts[] = trim($cols[0]);
                }
                $fullName = implode(" ", $nameParts);
            } else {
                // Format 3: Space-separated (Tên PC)
                $parts = preg_split('/\s+/', $line);
                if (count($parts) >= 2) {
                    $lastPart = "";
                    foreach ($parts as $p) {
                        if (preg_match('/^PC\d+/i', $p)) {
                            $lastPart = $p;
                            break;
                        }
                    }
                    if (empty($lastPart)) {
                        $lastPart = array_pop($parts);
                    } else {
                        $parts = array_filter($parts, function ($p) use ($lastPart) { return $p !== $lastPart; });
                    }

                    $pcNum = (int)preg_replace('/[^0-9]/', '', $lastPart);
                    $nameParts = [];
                    foreach ($parts as $p) {
                        if (preg_match('/^(OT|GM)\d?$/i', $p) || is_numeric($p) || $p === '---' || $p === 'Hoàn thành') continue;
                        $nameParts[] = $p;
                    }
                    $fullName = implode(" ", $nameParts);
                }

                // Format 4: Partial sheet 16 cols (Họ và tên, Tên, XL, OT1-5, Yếu nhất, Mục tiêu, ...)
                // No PC column → assign sequential PCs
                if ($pcNum === 0 && count($cols) >= 14 && count($cols) <= 20) {
                    $firstCol = trim($cols[0]);
                    // Skip header row
                    if (mb_strtolower($firstCol) === 'họ và tên' || $firstCol === 'STT' || $firstCol === '#') continue;

                    $potentialOT = trim($cols[8] ?? '');
                    if (preg_match('/^(OT\d|GM\d|Hoàn thành|Kiểm tra)/iu', $potentialOT) ||
                        (is_numeric(trim($cols[3] ?? '')) && is_numeric(trim($cols[4] ?? '')))) {
                        $seqPC++;
                        $pcNum = $seqPC;
                        $fullName = trim($cols[0]);
                        $ten = trim($cols[1] ?? '');
                        if (!empty($ten)) {
                            $fullName .= " " . $ten;
                        }
                    }
                }
            }
        }

        if ($pcNum > 0 && $pcNum <= 50 && !empty($fullName)) {
            $mapping[$pcNum] = rtrim($fullName, " -");
        }
    }
    return $mapping;
}

/**
 * Parse điểm từ thư mục HTML files
 * @return array [pcNum => ['score'=>..., 'max'=>..., 'timeSpent'=>..., 'lastOT'=>...], ...]
 */
function parseScores($tenLop) {
    $diemDaThu = [];
    if (!$tenLop || !is_dir($tenLop)) return $diemDaThu;

    $files = glob($tenLop . "/*.html");
    if (!$files) return $diemDaThu;

    usort($files, function ($a, $b) {
        return filemtime($a) - filemtime($b);
    });

    foreach ($files as $file) {
        $filename = basename($file);
        if (strpos($filename, '._') === 0) continue;

        $pcNum = 0;
        $subedOT = "UNKNOWN";
        $score = "0";
        $t = [];

        // Format cũ: PC01_Diem_100_00-05-30
        if (preg_match('/PC(\d+)_Diem_(\d+)_([\d\-]+)/i', $filename, $matches)) {
            $pcNum = (int)$matches[1];
            $subedOT = "OT1";
            $score = $matches[2];
            $t = explode('-', $matches[3]);
        }
        // Format mới: PC01_OT1_Diem_100_00-05-30
        elseif (preg_match('/PC(\d+)_([^_]+)_Diem_(\d+)_([\d\-]+)/i', $filename, $matches)) {
            $pcNum = (int)$matches[1];
            $subedOT = strtoupper($matches[2]);
            $score = $matches[3];
            $t = explode('-', $matches[4]);
        }

        if ($pcNum >= 1 && $pcNum <= 50) {
            $timeDisplay = ((int)($t[1] ?? 0) > 0 ? (int)$t[1] . "p" : "") . (int)($t[2] ?? 0) . "s";
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
    return $diemDaThu;
}
