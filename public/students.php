<?php
require_once '../includes/db.php';

// 🔹 API HANDLER (JSON responses)
if (isset($_GET['action']) && $_GET['action'] === 'list') {
    header('Content-Type: application/json');
    $stmt = $pdo->query("SELECT * FROM students ORDER BY name ASC");
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO students (name, birthday, subject, grade) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['name'], $_POST['birthday'], $_POST['subject'], $_POST['grade']]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        } elseif ($action === 'update') {
            $stmt = $pdo->prepare("UPDATE students SET name=?, birthday=?, subject=?, grade=? WHERE id=?");
            $stmt->execute([$_POST['name'], $_POST['birthday'], $_POST['subject'], $_POST['grade'], $_POST['id']]);
            echo json_encode(['success' => true]);
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM students WHERE id=?");
            $stmt->execute([$_POST['id']]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Records</title>
    <style>
        :root {
            --primary: #0f766e;
            --danger: #dc2626;
            --bg: #f8fafc;
            --text: #1e293b;
            --border: #e2e8f0;
            --white: #fff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
            padding-bottom: 40px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 1.5rem 1rem;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        h1 {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .nav {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
        }

        .nav a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .nav a:hover {
            text-decoration: underline;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: 0.2s;
        }

        .btn:hover {
            background: #0d9488;
        }

        .btn-danger {
            background: var(--danger);
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            margin-right: 0.5rem;
        }

        table {
            width: 100%;
            background: var(--white);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border-collapse: collapse;
        }

        th,
        td {
            padding: 0.8rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        th {
            background: #f1f5f9;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .empty {
            text-align: center;
            padding: 2rem;
            color: #94a3b8;
        }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            align-items: center;
            justify-content: center;
            z-index: 100;
            backdrop-filter: blur(2px);
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            animation: slideIn 0.2s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(10px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .modal-header h2 {
            font-size: 1.2rem;
        }

        .close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #64748b;
            line-height: 1;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.6rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 0.95rem;
            transition: 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(15, 118, 110, 0.1);
        }

        .form-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }

        @media (max-width: 650px) {
            table {
                font-size: 0.85rem;
                display: block;
                overflow-x: auto;
            }

            th,
            td {
                white-space: nowrap;
                padding: 0.6rem;
            }

            .btn-sm {
                padding: 0.3rem 0.6rem;
                font-size: 0.8rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <h1>👨‍🎓 Student Records</h1>
            <div class="nav">
                <a href="index.php">← Home</a>
                <a href="attendance.php">Attendance Log</a>
            </div>
        </header>

        <div class="toolbar">
            <h2 style="font-size:1.1rem; color:#475569;">Manage Students</h2>
            <button class="btn" onclick="openModal()">+ Add Student</button>
        </div>

        <div id="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width:50px">No</th>
                        <th>Student Name</th>
                        <th>Birthday</th>
                        <th>Subject / Faculty</th>
                        <th>Grade</th>
                        <th style="width:140px">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr>
                        <td colspan="6" class="empty">Loading records...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form -->
    <div id="studentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Student</h2>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <form id="studentForm">
                <input type="hidden" id="studentId" name="id">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="name" name="name" placeholder="e.g. John Doe" required>
                </div>
                <div class="form-group">
                    <label>Birthday</label>
                    <input type="date" id="birthday" name="birthday" required>
                </div>
                <div class="form-group">
                    <label>Subject / Faculty</label>
                    <input type="text" id="subject" name="subject" placeholder="e.g. Computer Science" required>
                </div>
                <div class="form-group">
                    <label>Grade</label>
                    <input type="text" id="grade" name="grade" placeholder="e.g. 10th" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-danger btn-sm" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn">Save Record</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let studentsCache = [];
        let isEditing = false;

        async function loadStudents() {
            try {
                const res = await fetch('?action=list');
                const data = await res.json();
                studentsCache = data;
                const tbody = document.getElementById('tableBody');
                tbody.innerHTML = '';

                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="empty">No students found. Add one to get started.</td></tr>';
                    return;
                }

                data.forEach((s, index) => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${escapeHtml(s.name)}</td>
                            <td>${s.birthday}</td>
                            <td>${escapeHtml(s.subject)}</td>
                            <td>${escapeHtml(s.grade)}</td>
                            <td>
                                <button class="btn btn-sm" onclick="openEditModal(${s.id})">Edit</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteStudent(${s.id})">Delete</button>
                            </td>
                        </tr>
                    `;
                });
            } catch (err) {
                document.getElementById('tableBody').innerHTML = `<tr><td colspan="6" class="empty">Failed to load data. Check console.</td></tr>`;
                console.error(err);
            }
        }

        function openModal() {
            document.getElementById('studentModal').classList.add('active');
            isEditing = false;
            document.getElementById('modalTitle').textContent = 'Add Student';
            document.getElementById('studentForm').reset();
            document.getElementById('studentId').value = '';
        }

        function openEditModal(id) {
            const s = studentsCache.find(x => x.id === id);
            if (!s) return;
            document.getElementById('studentModal').classList.add('active');
            isEditing = true;
            document.getElementById('modalTitle').textContent = 'Edit Student';
            document.getElementById('studentId').value = s.id;
            document.getElementById('name').value = s.name;
            document.getElementById('birthday').value = s.birthday;
            document.getElementById('subject').value = s.subject;
            document.getElementById('grade').value = s.grade;
        }

        function closeModal() {
            document.getElementById('studentModal').classList.remove('active');
        }

        document.getElementById('studentForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', isEditing ? 'update' : 'add');

            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.textContent = 'Saving...';
            submitBtn.disabled = true;

            try {
                const res = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();
                if (result.success) {
                    closeModal();
                    loadStudents();
                } else {
                    alert('Error: ' + (result.error || 'Unknown'));
                }
            } catch (err) {
                alert('Network error');
            } finally {
                submitBtn.textContent = 'Save Record';
                submitBtn.disabled = false;
            }
        });

        async function deleteStudent(id) {
            if (!confirm('Are you sure? This will permanently remove the student and their attendance history.')) return;
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                const res = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();
                if (result.success) loadStudents();
                else alert('Delete failed');
            } catch (err) {
                alert('Network error');
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        document.addEventListener('DOMContentLoaded', loadStudents);
        document.getElementById('studentModal').addEventListener('click', (e) => {
            if (e.target.id === 'studentModal') closeModal();
        });
    </script>
</body>

</html>