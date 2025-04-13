<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Tambah tugas baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['task'])) {
    $task = trim($_POST['task']);
    $stmt = $pdo->prepare("INSERT INTO tasks (user_id, task, status) VALUES (?, ?, 0)");
    $stmt->execute([$user_id, $task]);
}

// Update status tugas
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['task_id']) && isset($_POST['status'])) {
    $task_id = (int)$_POST['task_id'];
    $status = $_POST['status'] ? 1 : 0;
    $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$status, $task_id, $user_id]);
}

// Menghapus tugas
if (isset($_GET['delete_task'])) {
    $task_id = (int)$_GET['delete_task'];
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->execute([$task_id, $user_id]);
    header("Location: index.php");
    exit();
}

// Mengambil semua tugas
$tasks = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ?");
$tasks->execute([$user_id]);
$tasks = $tasks->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>To-Do List</title>
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
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        h2 {
            color: white;
        }
        .logout-button {
            background: red;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: bold;
            text-decoration: none;
            transition: background 0.3s;
        }
        .logout-button:hover {
            background: darkred;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 20px;
        }
        input, button {
            padding: 10px;
            border: none;
            border-radius: 5px;
            width: 100%;
        }
        button {
            background-color: #8B4513;
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
            background: #8B4513;
            color: white;
        }
        .task-completed {
            text-decoration: line-through;
            color: gray;
        }
        .task-checkbox {
            transform: scale(1.2);
            cursor: pointer;
        }
        .task-link {
            color: white;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s;
        }
        .task-link:hover {
            color: #FFD700;
            text-decoration: underline;
        }
    </style>
    <script>
        function toggleTaskStatus(taskId, checkbox) {
            fetch("update_task_status.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "task_id=" + taskId + "&status=" + (checkbox.checked ? 1 : 0)
            }).then(response => response.text()).then(() => {
                checkbox.closest("tr").classList.toggle("task-completed", checkbox.checked);
                const editButton = checkbox.closest("tr").querySelector(".edit-btn");
                if (checkbox.checked) {
                    editButton.style.display = "none";
                    checkbox.disabled = true;
                } else {
                    editButton.style.display = "inline-block";
                    checkbox.disabled = false;
                }
            });
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>To-Do List</h2>
            <a href="logout.php" class="logout-button" onclick="return confirm('Apakah Anda yakin ingin logout?');">Logout</a>
        </div>

        <form method="POST">
            <input type="text" name="task" placeholder="Tambahkan tugas baru" required>
            <button type="submit">➕ Tambah Tugas</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Tugas</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task): ?>
                    <tr class="<?= $task['status'] ? 'task-completed' : '' ?>">
                        <td>
                            <input type="checkbox" class="task-checkbox" 
                                onchange="toggleTaskStatus(<?= $task['id'] ?>, this)" 
                                <?= $task['status'] ? 'checked disabled' : '' ?>>
                        </td>
                        <td>
                            <a href="subtasks.php?task_id=<?= $task['id'] ?>" class="task-link">
                                <?= htmlspecialchars($task['task']) ?>
                            </a>
                        </td>
                        <td class="action-buttons">
                            <?php if (!$task['status']): ?>
                                <a href="edit_task.php?task_id=<?= $task['id'] ?>" class="edit-btn">✏️ Edit</a>
                            <?php endif; ?>
                            <a href="?delete_task=<?= $task['id'] ?>" class="delete-btn" onclick="return confirm('Hapus tugas ini?');">🗑️ Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>