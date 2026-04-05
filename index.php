<?php
// File lưu số lượng lớp của mỗi khối
$classFile = 'classes.json';
// Mặc định mỗi khối có 8 lớp nếu chưa có file
$classCounts = ['6' => 8, '7' => 8, '8' => 8];

if (file_exists($classFile)) {
    $classCounts = json_decode(file_get_contents($classFile), true);
} else {
    file_put_contents($classFile, json_encode($classCounts));
}

// Xử lý khi bấm nút "Thêm lớp"
if (isset($_POST['add_class'])) {
    $grade = $_POST['add_class'];
    if (isset($classCounts[$grade])) {
        $classCounts[$grade]++;
        file_put_contents($classFile, json_encode($classCounts));
    }
    // Tránh bị lỗi gửi lại form khi F5
    header("Location: index.php");
    exit;
}

// Xử lý khi bấm chọn 1 lớp để vào quản lý
if (isset($_POST['class'])) {
    file_put_contents('active_class.txt', $_POST['class']);
    header("Location: quanly.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IC3 Manager - Chọn Lớp</title>
    <style>
        /* ── RESET & BASE ── */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --blue: #1a73e8;
            --blue-dk: #1557b0;
            --blue-lt: #e8f0fe;
            --gray-50: #f8f9fa;
            --gray-100: #f1f3f4;
            --gray-200: #e8eaed;
            --gray-300: #dadce0;
            --gray-500: #9aa0a6;
            --gray-600: #80868b;
            --gray-700: #5f6368;
            --gray-900: #202124;
            --white: #ffffff;
            --shadow-sm: 0 2px 6px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 12px 32px rgba(26, 115, 232, 0.15);
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-900);
            padding: 20px 0;
            /* Cho phép scroll nếu màn hình nhỏ */
        }

        .container {
            max-width: 1100px;
            width: 100%;
            padding: 20px;
        }

        /* ── HEADER ── */
        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 32px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            color: var(--gray-900);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .logo-badge {
            background: var(--blue);
            color: var(--white);
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 20px;
            letter-spacing: 1px;
            box-shadow: var(--shadow-sm);
        }

        /* ── GRID LAYOUT ── */
        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        /* ── CARDS ── */
        .card {
            background: var(--white);
            padding: 30px 25px;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--white);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
            border-color: var(--blue-lt);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px dashed var(--gray-200);
        }

        .card-header h3 {
            font-size: 22px;
            font-weight: 800;
            color: var(--blue-dk);
            letter-spacing: 1px;
        }

        .card-icon {
            background: var(--blue-lt);
            color: var(--blue);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-icon svg {
            width: 20px;
            height: 20px;
        }

        /* ── BUTTONS ── */
        .btn-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        button {
            width: 100%;
            padding: 12px 10px;
            background: var(--gray-50);
            border: 1.5px solid var(--gray-300);
            color: var(--gray-700);
            font-size: 15px;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        button:hover {
            background: var(--blue);
            border-color: var(--blue);
            color: var(--white);
            transform: scale(1.03);
            box-shadow: 0 4px 10px rgba(26, 115, 232, 0.3);
        }

        /* Nút thêm lớp đặc biệt */
        .btn-add {
            background: transparent;
            border: 2px dashed var(--gray-300);
            color: var(--gray-500);
            font-size: 14px;
        }

        .btn-add:hover {
            border-color: var(--green);
            background: var(--green-lt);
            color: var(--green);
            box-shadow: none;
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 900px) {
            .grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header">
            <h1><span class="logo-badge">IC3</span> CHỌN LỚP ĐANG DẠY</h1>
            <p style="color: var(--gray-600); margin-top: 10px; font-size: 15px;">Hệ thống quản lý điểm thực hành phòng máy</p>
        </div>

        <form method="POST" class="grid">

            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        </svg>
                    </div>
                    <h3>KHỐI 6</h3>
                </div>
                <div class="btn-grid">
                    <?php for ($i = 1; $i <= $classCounts['6']; $i++) echo "<button name='class' value='6.$i'>Lớp 6.$i</button>"; ?>
                    <button class="btn-add" name="add_class" value="6" title="Bấm để thêm lớp 6.<?= $classCounts['6'] + 1 ?>">+ Thêm</button>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        </svg>
                    </div>
                    <h3>KHỐI 7</h3>
                </div>
                <div class="btn-grid">
                    <?php for ($i = 1; $i <= $classCounts['7']; $i++) echo "<button name='class' value='7.$i'>Lớp 7.$i</button>"; ?>
                    <button class="btn-add" name="add_class" value="7" title="Bấm để thêm lớp 7.<?= $classCounts['7'] + 1 ?>">+ Thêm</button>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        </svg>
                    </div>
                    <h3>KHỐI 8</h3>
                </div>
                <div class="btn-grid">
                    <?php for ($i = 1; $i <= $classCounts['8']; $i++) echo "<button name='class' value='8.$i'>Lớp 8.$i</button>"; ?>
                    <button class="btn-add" name="add_class" value="8" title="Bấm để thêm lớp 8.<?= $classCounts['8'] + 1 ?>">+ Thêm</button>
                </div>
            </div>

        </form>
    </div>

</body>

</html>