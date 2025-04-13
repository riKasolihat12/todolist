<?php
session_start();
include 'db.php';

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$task_id = $_GET['task_id'] ?? null;

if (!$task_id) {
    header("Location: index.php");
    exit();
}

// Mengambil informasi tugas utama
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
$stmt->execute([$task_id, $user_id]);
$task = $stmt->fetch();

if (!$task) {
    header("Location: index.php");
    exit();
}

// Menambahkan subtugas
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['subtask'])) {
    $subtask = $_POST['subtask'];
    $deadline = $_POST['deadline'];
    $priority = $_POST['priority'];

    // Proses penyimpanan subtugas ke dalam database
    $stmt = $pdo->prepare("INSERT INTO subtasks (task_id, subtask, deadline, priority, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$task_id, $subtask, $deadline, $priority, 0]); // status = 0 (belum selesai)
    header("Location: subtasks.php?task_id=$task_id");
    exit();
}

// Mengambil semua subtugas
$stmt = $pdo->prepare("SELECT * FROM subtasks WHERE task_id = ?");
$stmt->execute([$task_id]);
$subtasks = $stmt->fetchAll();


// Menghapus subtugas
if (isset($_GET['delete_subtask'])) {
    $subtask_id = $_GET['delete_subtask'];
    
    // Pastikan hanya pengguna yang terkait dengan tugas yang bisa menghapus subtugas
    $stmt = $pdo->prepare("DELETE FROM subtasks WHERE id = ? AND task_id IN (SELECT id FROM tasks WHERE user_id = ?)");
    $stmt->execute([$subtask_id, $user_id]);
    
    header("Location: subtasks.php?task_id=$task_id");
    exit();
}

// Menandai subtugas sebagai selesai atau belum selesai
if (isset($_POST['subtask_status'])) {
    $subtask_id = $_POST['subtask_id'];
    $status = $_POST['status'];  // status 1 untuk selesai, 0 untuk belum selesai

    // Update status subtugas
    $stmt = $pdo->prepare("UPDATE subtasks SET status = ? WHERE id = ? AND task_id = ?");
    $stmt->execute([$status, $subtask_id, $task_id]);

    header("Location: subtasks.php?task_id=$task_id");
    exit();
}

// Menandai semua subtugas selesai dan mengubah status tugas utama menjadi selesai
if (isset($_POST['mark_all_completed'])) {
    // Update semua subtugas menjadi selesai (status = 1)
    $stmt = $pdo->prepare("UPDATE subtasks SET status = 1 WHERE task_id = ?");
    $stmt->execute([$task_id]);

    // Cek apakah semua subtugas sudah selesai, jika ya update tugas utama menjadi selesai
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subtasks WHERE task_id = ? AND status = 0");
    $stmt->execute([$task_id]);
    $unfinished_subtasks = $stmt->fetchColumn();

    if ($unfinished_subtasks == 0) {
        // Update status tugas utama menjadi selesai
        $stmt = $pdo->prepare("UPDATE tasks SET status = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$task_id, $user_id]);
    }

    header("Location: subtasks.php?task_id=$task_id");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subtugas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to right, #8B4513, #8B4513);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            width: 90%;
            max-width: 700px;
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            text-align: center;
        }
        h2 {
            color: white;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 10px;
            margin-top: 10px;
            color: white;
            text-decoration: none;
            font-size: 14px;
            transition: 0.3s;
        }
        .back-link:hover {
            color: #8B4513;
            text-decoration: underline;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 20px;
        }
        input, select, button {
            padding: 10px;
            border: none;
            border-radius: 5px;
            width: 100%;
        }
        button {
            background-color: rgb(253, 218, 185);
            color: white;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }
        button:hover {
            background-color: #8B4513;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background: rgb(253, 218, 185);
            color: white;
        }
        .task-completed {
            text-decoration: line-through;
            color: gray;
        }
        .checkbox {
            transform: scale(1.2);
            cursor: pointer;
        }
        .action-buttons a {
            text-decoration: none;
            padding: 6px 10px;
            margin: 2px;
            border-radius: 5px;
            font-weight: bold;
            display: inline-block;
        }
        .edit-btn {
            background: #facc15;
            color: black;
        }
        .delete-btn {
            background: red;
            color: white;
        }
        .edit-btn:hover {
            background: #eab308;
        }
        .delete-btn:hover {
            background: darkred;
        }
    </style>
</head>
<body>
<div class="container">
        <h2>📝 Subtugas untuk: <br><?= htmlspecialchars($task['task']) ?></h2>
        <a href="index.php?task_id=<?= $task_id ?>" class="back-link">⬅ Kembali ke daftar tugas </a>

        <!-- Tombol "Semua Selesai" -->
        <form method="POST" style="margin-top: 10px;">
            <button type="submit" name="mark_all_completed" style="button {
            background-color: rgb(253, 218, 185);
            color: white;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }
        button:hover {
            background-color: #8B4513;
        }">
                ✅ Semua Selesai
            </button>
        </form>

        <!-- Form Tambah Subtugas -->
        <form method="POST">
            <input type="text" name="subtask" placeholder="Tambahkan subtugas baru" required>
            <input type="date" name="deadline" required min="<?= date('Y-m-d') ?>">
            <select name="priority" required>
                <option value="Tinggi">Tinggi</option>
                <option value="Sedang">Sedang</option>
                <option value="Rendah">Rendah</option>
            </select>
            <button type="submit">➕ Tambah Subtugas</button>
        </form>

        <!-- Tabel Daftar Subtugas -->
        <table>
            <thead>
                <tr>
                    <th>Subtugas</th>
                    <th>Tenggat</th>
                    <th>Prioritas</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subtasks as $subtask): ?>
                    <tr>
                        <td><?= htmlspecialchars($subtask['subtask']) ?></td>
                        <td><?= htmlspecialchars($subtask['deadline']) ?></td>
                        <td><strong><?= htmlspecialchars($subtask['priority']) ?></strong></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="checkbox" name="subtask_status" value="1" class="checkbox" <?= $subtask['status'] == 1 ? 'checked' : ''; ?>
                                       onclick="this.form.submit();">
                                <input type="hidden" name="subtask_id" value="<?= $subtask['id'] ?>">
                                <input type="hidden" name="status" value="<?= $subtask['status'] == 1 ? 0 : 1 ?>"> <!-- Toggle status -->
                            </form>
                        </td>
                        <td class="action-buttons">
                            <a href="edit_subtask.php?subtask_id=<?= $subtask['id'] ?>" class="edit-btn">✏️ Edit</a>
                            <a href="?delete_task=<?= $task['id'] ?>" class="delete-btn" onclick="return confirm('Hapus tugas ini?');">🗑️ Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>