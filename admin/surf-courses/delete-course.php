<?php
$host = 'localhost';
$dbname = 'taghazout';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header('Location: courses.php?error=خطأ في الاتصال بقاعدة البيانات');
    exit;
}

// Get course ID
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: courses.php?error=معرف الدورة غير صحيح');
    exit;
}

// Check if course exists
$stmt = $pdo->prepare("SELECT * FROM surf_courses WHERE id = ?");
$stmt->execute([$id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    header('Location: courses.php?error=الدورة غير موجودة');
    exit;
}

// Handle confirmed deletion
if (isset($_POST['confirm_delete'])) {
    // Delete image file if exists
    if (!empty($course['image'])) {
        $img_path = '../../uploads/surf/' . $course['image'];
        if (file_exists($img_path)) unlink($img_path);
    }

    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM surf_courses WHERE id = ?");
    $stmt->execute([$id]);

    header('Location: courses.php?success=تم حذف الدورة بنجاح');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حذف الدورة</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0a0a0f;
            --surface: #111118;
            --card: #16161f;
            --border: #1e1e2e;
            --accent: #6c63ff;
            --text: #e8e8f0;
            --muted: #6b6b80;
            --danger: #ff4d6d;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Cairo', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .delete-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 3rem;
            max-width: 500px;
            width: 90%;
            text-align: center;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .danger-icon {
            width: 80px; height: 80px;
            background: rgba(255,77,109,0.1);
            border: 2px solid rgba(255,77,109,0.3);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.5rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(255,77,109,0.3); }
            50% { box-shadow: 0 0 0 10px rgba(255,77,109,0); }
        }

        h1 {
            font-size: 1.6rem;
            font-weight: 900;
            margin-bottom: 0.75rem;
            color: var(--danger);
        }

        .course-name {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1rem 1.5rem;
            margin: 1.5rem 0;
            font-weight: 700;
            font-size: 1.05rem;
        }

        .warning-text {
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .warning-text strong { color: var(--danger); }

        .actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .btn {
            padding: 0.8rem 2rem;
            border-radius: 10px;
            border: none;
            font-family: 'Cairo', sans-serif;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }
        .btn-danger:hover { background: #e03050; transform: translateY(-1px); }

        .btn-ghost {
            background: transparent;
            color: var(--muted);
            border: 1px solid var(--border);
        }
        .btn-ghost:hover { color: var(--text); border-color: var(--text); }

        .course-meta {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            color: var(--muted);
        }
    </style>
</head>
<body>

<div class="delete-card">
    <div class="danger-icon">⚠️</div>

    <h1>تأكيد الحذف</h1>

    <p style="color:var(--muted); margin-bottom:0.5rem">هل تريد حذف الدورة التالية؟</p>

    <div class="course-name">
        📖 <?= htmlspecialchars($course['title']) ?>
    </div>

    <div class="course-meta">
        <span>💰 <?= number_format($course['price'] ?? 0, 2) ?> د.م</span>
        <span>📁 <?= htmlspecialchars($course['category'] ?? 'غير محدد') ?></span>
    </div>

    <p class="warning-text">
        هذا الإجراء <strong>لا يمكن التراجع عنه</strong>.<br>
        سيتم حذف الدورة وجميع بياناتها بشكل نهائي.
    </p>

    <div class="actions">
        <a href="courses.php" class="btn btn-ghost">❌ إلغاء</a>
        <form method="POST" style="display:inline">
            <button type="submit" name="confirm_delete" class="btn btn-danger">
                🗑️ نعم، احذف الدورة
            </button>
        </form>
    </div>
</div>

</body>
</html>