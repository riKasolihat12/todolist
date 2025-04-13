<?php
session_start();
include 'db.php';

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$subtask_id = $_GET['subtask_id'] ?? null;

if (!$subtask_id) {
    die("Subtugas tidak ditemukan!");
}

// Ambil subtugas berdasarkan ID
$stmt = $pdo->prepare("SELECT * FROM subtasks WHERE id = ? AND task_id IN (SELECT id FROM tasks WHERE user_id = ?)");
$stmt->execute([$subtask_id, $user_id]);
$subtask = $stmt->fetch();

if (!$subtask) {
    die("Subtugas tidak ditemukan atau bukan milik Anda!");
}

// Update subtugas jika form dikirim
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subtask_name = $_POST['subtask'];
    $deadline = $_POST['deadline'];
    $priority = $_POST['priority'];

    $stmt = $pdo->prepare("UPDATE subtasks SET subtask = ?, deadline = ?, priority = ? WHERE id = ?");
    $stmt->execute([$subtask_name, $deadline, $priority, $subtask_id]);

    // Set session variable for success message
    $_SESSION['subtask_updated'] = true;

    header("Location: subtasks.php?task_id=" . $subtask['task_id']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Subtugas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(to right, #8B4513, #8B4513);
            color: white;
        }
        .container {
            background: rgba(255, 255, 255, 0.2);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            width: 350px;
            text-align: center;
        }
        h2 {
            margin-bottom: 20px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        input, select, button {
            padding: 12px;
            border: none;
            border-radius: 5px;
            width: 100%;
            font-size: 16px;
            text-align: center;
        }
        input, select {
            background: rgba(255, 255, 255, 0.8);
        }
        button {
            background-color: rgb(253, 218, 185);
            color: black;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background-color: rgb(253, 190, 150);
        }
        .back-link {
            display: block;
            margin-top: 15px;
            color: white;
            text-decoration: none;
            font-size: 14px;
            transition: 0.3s;
        }
        .back-link:hover {
            color: rgb(253, 218, 185);
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>📝 Edit Subtugas</h2>
        <form method="POST">
            <input type="hidden" name="subtask_id" value="<?= $subtask['id'] ?>">
            <input type="text" name="subtask" value="<?= htmlspecialchars($subtask['subtask']) ?>" required>
            <input type="date" name="deadline" value="<?= $subtask['deadline'] ?>" required min="<?= date('Y-m-d') ?>">
            <select name="priority" required>
                <option value="Tinggi" <?= $subtask['priority'] == 'Tinggi' ? 'selected' : '' ?>>🔥 Tinggi</option>
                <option value="Sedang" <?= $subtask['priority'] == 'Sedang' ? 'selected' : '' ?>>⚡ Sedang</option>
                <option value="Rendah" <?= $subtask['priority'] == 'Rendah' ? 'selected' : '' ?>>🟢 Rendah</option>
            </select>
            <button type="submit">✔ Simpan Perubahan</button>
        </form>
        <a href="subtasks.php?task_id=<?= $subtask['task_id'] ?>" class="back-link">⬅ Kembali ke daftar</a>
    </div>

    <?php if (isset($_SESSION['subtask_updated'])): ?>
        <script>
            alert("Subtugas berhasil diubah!");
            <?php unset($_SESSION['subtask_updated']); ?> // Clear the session variable after the alert
        </script>
    <?php endif; ?>
</body>
</html>
