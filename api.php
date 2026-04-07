<?php
error_reporting(0);
require_once __DIR__ . '/includes/helpers.php';

$tenLop = file_exists('active_class.txt') ? trim(file_get_contents('active_class.txt')) : "";
$folderDS = "danhsach";

// ═══════════════════════════════════════════════════════════
//  GET DATA
// ═══════════════════════════════════════════════════════════
if (isset($_GET['action']) && $_GET['action'] == 'get_data') {
    $pathDS = "$folderDS/danhsach_$tenLop.txt";
    $danhSachMapping = parseDanhSach($pathDS);
    $targets = loadJson("$folderDS/target_$tenLop.json");
    $otConfigs = loadJson("$folderDS/ot_config_$tenLop.json");
    $diemDaThu = parseScores($tenLop);

    echo json_encode([
        'mapping'   => (object)$danhSachMapping,
        'scores'    => (object)$diemDaThu,
        'targets'   => (object)$targets,
        'otConfigs' => (object)$otConfigs
    ]);
    exit;
}

// ═══════════════════════════════════════════════════════════
//  GET IP MAPPING
// ═══════════════════════════════════════════════════════════
if (isset($_GET['action']) && $_GET['action'] == 'get_ip_mapping') {
    $mappingFile = 'ip_mapping.txt';
    $ipData = [];
    if (file_exists($mappingFile)) {
        $lines = file($mappingFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 2) {
                $ip = $parts[0];
                $pcName = $parts[1];
                $pcNum = (int)preg_replace('/[^0-9]/', '', $pcName);
                $ipData[] = [
                    'ip' => $ip,
                    'pcName' => $pcName,
                    'pcNum' => $pcNum
                ];
            }
        }
    }
    // Auto-detect server IP
    $serverIP = getServerIP();
    echo json_encode([
        'ipMapping' => $ipData,
        'serverIP' => $serverIP,
        'totalPCs' => count($ipData),
        'lastModified' => file_exists($mappingFile) ? date('H:i:s d/m/Y', filemtime($mappingFile)) : null
    ]);
    exit;
}

// ═══════════════════════════════════════════════════════════
//  POST ACTIONS – Guard: require valid class
// ═══════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($tenLop)) {
    http_response_code(400);
    echo "ERR_NO_CLASS";
    exit;
}

// ── SAVE LIST ─────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] == 'save_list') {
    if (!is_dir($folderDS)) mkdir($folderDS, 0777, true);

    $data = $_POST['data'];
    $lines = explode("\n", $data);
    $newTargets = [];
    $newOTConfigs = [];
    $clearTargets = [];
    $isSheetFormat = false;
    $seqPC = 0;

    foreach ($lines as $line) {
        if (empty(trim($line))) continue;

        $cols = array_map('trim', explode("\t", $line));

        // Skip header row
        $firstCol = mb_strtolower(trim($cols[0]));
        if ($firstCol === 'họ và tên' || $firstCol === 'stt' || $firstCol === '#' || $firstCol === 'họ') continue;

        // Tìm cột PC
        $viTriIndex = -1;
        for ($i = 0; $i < count($cols); $i++) {
            if (preg_match('/^PC\d+/i', $cols[$i])) {
                $viTriIndex = $i;
                break;
            }
        }

        $pcNum = 0;
        $baiGiao = '';
        $mucTieu = '';
        $detected = false;

        if ($viTriIndex !== -1) {
            $pcNum = (int)preg_replace('/[^0-9]/', '', $cols[$viTriIndex]);
            if ($pcNum > 0 && $pcNum <= 50) {

                // Format A: Full Google Sheet (PC tại cột 4, ≥28 cột)
                if ($viTriIndex == 4 && count($cols) >= 28) {
                    $detected = true;
                    $baiGiao = $cols[26];
                    $mucTieu = $cols[27];
                }
                // Format B: Compact Sheet (PC tại cột 8, ≥11 cột)
                else if ($viTriIndex == 8 && count($cols) >= 11) {
                    $detected = true;
                    $baiGiao = $cols[9];
                    $mucTieu = $cols[10];
                }
                // Format C: Simple (Tên PC BàiGiao MụcTiêu)
                else if (count($cols) > $viTriIndex + 1) {
                    $nextCol = $cols[$viTriIndex + 1];
                    if (preg_match('/^(OT|GM|Hoàn thành)/i', $nextCol)) {
                        $detected = true;
                        $baiGiao = $nextCol;
                        $mucTieu = isset($cols[$viTriIndex + 2]) ? $cols[$viTriIndex + 2] : '';
                    }
                }
            }
        }
        // Format D: Partial sheet (16 cols, no PC column)
        // Cols: Họ và tên, Tên, XL, OT1-5, Yếu nhất, Mục tiêu, Số HT, Mức OT1-5
        else if (count($cols) >= 14 && count($cols) <= 20) {
            $potentialOT = trim($cols[8] ?? '');
            if (preg_match('/^(OT\d|GM\d|Hoàn thành|Kiểm tra)/iu', $potentialOT) ||
                (is_numeric(trim($cols[3] ?? '')) && is_numeric(trim($cols[4] ?? '')))) {
                $seqPC++;
                $pcNum = $seqPC;
                $detected = true;
                $baiGiao = $potentialOT;
                $mucTieu = trim($cols[9] ?? '');
            }
        }

        if ($detected && $pcNum > 0 && $pcNum <= 50) {
            $isSheetFormat = true;

            // Bài giao (OT/GM/Hoàn thành)
            if ($baiGiao !== '' && preg_match('/^(OT\d|GM\d|Hoàn thành)/i', $baiGiao)) {
                $newOTConfigs[$pcNum] = $baiGiao;
            }

            // Mục tiêu
            if ($mucTieu === '---') {
                $clearTargets[] = $pcNum;
            } else if ($mucTieu !== '' && is_numeric($mucTieu)) {
                $newTargets[$pcNum] = $mucTieu;
            }
        }
    }

    if ($isSheetFormat) {
        $targets = loadJson("$folderDS/target_$tenLop.json");
        $otConfigs = loadJson("$folderDS/ot_config_$tenLop.json");

        foreach ($newTargets as $k => $v) $targets[$k] = $v;
        foreach ($newOTConfigs as $k => $v) $otConfigs[$k] = $v;
        foreach ($clearTargets as $k) unset($targets[$k]);

        saveJson("$folderDS/target_$tenLop.json", $targets);
        saveJson("$folderDS/ot_config_$tenLop.json", $otConfigs);
    }

    file_put_contents("$folderDS/danhsach_$tenLop.txt", $data);
    echo "OK";
    exit;
}

// ── DELETE SCORES ─────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] == 'delete_scores') {
    if ($tenLop && is_dir($tenLop)) {
        foreach (glob($tenLop . "/*.html") as $file) {
            if (is_file($file)) unlink($file);
        }
    }
    echo "OK";
    exit;
}

// ── CLEAR IP MAPPING ─────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] == 'clear_ip_mapping') {
    file_put_contents('ip_mapping.txt', '');
    echo "OK";
    exit;
}

// ── GENERATE BAT FILE ────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] == 'generate_bat') {
    $serverIP = getServerIP();
    $customIP = isset($_POST['server_ip']) ? trim($_POST['server_ip']) : '';
    if (!empty($customIP)) $serverIP = $customIP;

    $batContent = "@echo off\r\n";
    $batContent .= "title CONG CU LAY IP TU DONG - THAY AN\r\n";
    $batContent .= "color 0a\r\n";
    $batContent .= "cls\r\n";
    $batContent .= "\r\n";
    $batContent .= "echo ==========================================\r\n";
    $batContent .= "echo        DANG KET NOI VOI MAY THAY...\r\n";
    $batContent .= "echo ==========================================\r\n";
    $batContent .= "\r\n";
    $batContent .= ":: Lay ten may tinh hien tai\r\n";
    $batContent .= "set pcname=%COMPUTERNAME%\r\n";
    $batContent .= "\r\n";
    $batContent .= ":: Tu dong tim Desktop\r\n";
    $batContent .= "set DESKTOP_PATH=%USERPROFILE%\\Desktop\r\n";
    $batContent .= "if not exist \"%DESKTOP_PATH%\" set DESKTOP_PATH=%USERPROFILE%\\OneDrive\\Desktop\r\n";
    $batContent .= "\r\n";
    $batContent .= "echo May hien tai: %pcname%\r\n";
    $batContent .= "echo User: %USERNAME%\r\n";
    $batContent .= "\r\n";
    $batContent .= ":: Gui IP ve may thay (IP: $serverIP)\r\n";
    $batContent .= "curl -s \"http://$serverIP/ic3-manager/get_ip.php?pc=%pcname%\"\r\n";
    $batContent .= "\r\n";
    $batContent .= "echo.\r\n";
    $batContent .= "echo ------------------------------------------\r\n";
    $batContent .= "echo THANH CONG! IP cua ban da duoc gui ve.\r\n";
    $batContent .= "echo ------------------------------------------\r\n";
    $batContent .= "timeout /t 3\r\n";
    $batContent .= "exit\r\n";

    file_put_contents('lay_ip.bat', $batContent);
    echo json_encode(['ok' => true, 'serverIP' => $serverIP]);
    exit;
}

// ── SAVE TARGET (đơn lẻ) ─────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] == 'save_target') {
    $pc = $_POST['pc'];
    $target = trim($_POST['target']);
    $targets = loadJson("$folderDS/target_$tenLop.json");

    if ($target === '' || $target === null) {
        unset($targets[$pc]);
    } else {
        $targets[$pc] = $target;
    }

    saveJson("$folderDS/target_$tenLop.json", $targets);
    echo "OK";
    exit;
}

// ── SAVE OT (đơn lẻ) ─────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] == 'save_ot') {
    $pc = $_POST['pc'];
    $ot = trim($_POST['ot']);
    $otConfigs = loadJson("$folderDS/ot_config_$tenLop.json");

    if ($ot === '' || $ot === null) {
        unset($otConfigs[$pc]);
    } else {
        $otConfigs[$pc] = $ot;
    }

    saveJson("$folderDS/ot_config_$tenLop.json", $otConfigs);
    echo "OK";
    exit;
}

// ── SAVE BULK TARGET ──────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] == 'save_bulk_target') {
    $start = (int)$_POST['start'];
    $end = (int)$_POST['end'];
    $target = trim($_POST['target']);

    if (!validateRange($start, $end)) {
        http_response_code(400);
        echo "ERR_INVALID_RANGE";
        exit;
    }

    $targets = loadJson("$folderDS/target_$tenLop.json");
    for ($i = $start; $i <= $end; $i++) {
        if ($target === '' || $target === null) {
            unset($targets[$i]);
        } else {
            $targets[$i] = $target;
        }
    }
    saveJson("$folderDS/target_$tenLop.json", $targets);
    echo "OK";
    exit;
}

// ── SAVE BULK OT ──────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] == 'save_bulk_ot') {
    $start = (int)$_POST['start'];
    $end = (int)$_POST['end'];
    $ot = trim($_POST['ot']);

    if (!validateRange($start, $end)) {
        http_response_code(400);
        echo "ERR_INVALID_RANGE";
        exit;
    }

    $otConfigs = loadJson("$folderDS/ot_config_$tenLop.json");
    for ($i = $start; $i <= $end; $i++) {
        if ($ot === '' || $ot === null) {
            unset($otConfigs[$i]);
        } else {
            $otConfigs[$i] = $ot;
        }
    }
    saveJson("$folderDS/ot_config_$tenLop.json", $otConfigs);
    echo "OK";
    exit;
}

// ── UPDATE PC MAPPING (Từ Sơ đồ chỗ ngồi) ────────────────
if (isset($_POST['action']) && $_POST['action'] == 'update_pc_mapping') {
    $changes = json_decode($_POST['changes'], true) ?: []; 
    $deletes = json_decode($_POST['deletes'] ?? '[]', true) ?: [];
    // changes: [ oldPc => newPc ], deletes: [ pc1, pc2 ]
    
    if (empty($changes) && empty($deletes)) {
        echo "OK";
        exit;
    }

    $pathDS = "$folderDS/danhsach_$tenLop.txt";
    if (file_exists($pathDS)) {
        $lines = explode("\n", file_get_contents($pathDS));
        $newLines = [];
        $seqPC = 0;
        
        foreach ($lines as $line) {
            $lineTrimmed = rtrim($line, "\r\n");
            if (empty(trim($lineTrimmed))) {
                $newLines[] = $lineTrimmed;
                continue;
            }
            
            $cols = explode("\t", $lineTrimmed);
            $pcNum = 0;
            $viTriIndex = -1;
            
            // Format 1: Google Sheet chuẩn
            if (count($cols) >= 9 && isset($cols[8]) && preg_match('/^PC\d+/i', $cols[8])) {
                $pcNum = (int)preg_replace('/[^0-9]/', '', $cols[8]);
                $viTriIndex = 8;
            } else {
                // Auto find PC col
                for ($i = 0; $i < count($cols); $i++) {
                    if (preg_match('/^PC\d+/i', trim($cols[$i]))) {
                        $viTriIndex = $i;
                        $pcNum = (int)preg_replace('/[^0-9]/', '', $cols[$i]);
                        break;
                    }
                }
            }
            
            // Format 3: Space separated
            if ($pcNum === 0 && count($cols) == 1) {
                $parts = preg_split('/\s+/', $lineTrimmed);
                foreach ($parts as $i => $p) {
                    if (preg_match('/^PC\d+/i', $p)) {
                        $pcNum = (int)preg_replace('/[^0-9]/', '', $p);
                        if (isset($changes[$pcNum])) {
                            $parts[$i] = sprintf("PC%02d", $changes[$pcNum]);
                            $lineTrimmed = implode(" ", $parts);
                        }
                        break;
                    }
                }
            } 
            
            // For format 1 & 2
            if ($viTriIndex !== -1 && $pcNum > 0) {
                if (in_array($pcNum, $deletes)) {
                    continue; // Skip adding to newLines
                }
                if (isset($changes[$pcNum])) {
                    $cols[$viTriIndex] = sprintf("PC%02d", $changes[$pcNum]);
                    $lineTrimmed = implode("\t", $cols);
                }
            }
            
            $newLines[] = $lineTrimmed;
        }
        
        file_put_contents($pathDS, implode("\n", $newLines));
        
        // Update target and otConfigs safely
        $targets = loadJson("$folderDS/target_$tenLop.json");
        $otConfigs = loadJson("$folderDS/ot_config_$tenLop.json");
        
        $newTargets = $targets;
        $newOTs = $otConfigs;
        
        // Remove deleted items
        foreach ($deletes as $delPc) {
            unset($newTargets[$delPc]);
            unset($newOTs[$delPc]);
        }
        
        foreach ($changes as $oldPc => $newPc) {
            unset($newTargets[$oldPc]);
            unset($newOTs[$oldPc]);
        }
        
        foreach ($changes as $oldPc => $newPc) {
            if (isset($targets[$oldPc])) $newTargets[$newPc] = $targets[$oldPc];
            if (isset($otConfigs[$oldPc])) $newOTs[$newPc] = $otConfigs[$oldPc];
        }
        
        saveJson("$folderDS/target_$tenLop.json", $newTargets);
        saveJson("$folderDS/ot_config_$tenLop.json", $newOTs);
    }
    
    echo "OK";
    exit;
}
