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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --danger-dark: #dc2626;
            --bg: #f8fafc;
            --surface: #ffffff;
            --text: #0f172a;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            background: linear-gradient(135deg, var(--bg) 0%, #f1f5f9 100%);
            color: var(--text);
            line-height: 1.6;
            padding-bottom: 40px;
        }

        .navbar {
            background: var(--surface);
            box-shadow: var(--shadow);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .navbar-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-nav {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .navbar-nav a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            font-size: 0.95rem;
        }

        .navbar-nav a:hover {
            color: var(--primary);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        h1 {
            font-size: 1.75rem;
            color: var(--text);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .nav {
            display: flex;
            gap: 1.5rem;
            font-size: 0.95rem;
        }

        .nav a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .nav a:hover {
            color: var(--primary);
            transform: translateX(-2px);
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .toolbar-title {
            font-size: 1.1rem;
            color: var(--text-secondary);
            font-weight: 600;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.7rem 1.5rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 0.75rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-danger {
            background: var(--danger);
        }

        .btn-danger:hover {
            background: var(--danger-dark);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            margin-right: 0.4rem;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .table-wrapper {
            background: var(--surface);
            border-radius: 1rem;
            box-shadow: var(--shadow);
            overflow: hidden;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        th {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tbody tr {
            transition: all 0.2s ease;
            border-bottom: 1px solid var(--border);
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        .row-number {
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .student-name {
            font-weight: 600;
            color: var(--text);
        }

        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .empty {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-secondary);
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 100;
            backdrop-filter: blur(4px);
        }

        .modal.active {
            display: flex;
            animation: fadeInModal 0.2s ease;
        }

        @keyframes fadeInModal {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-content {
            background: var(--surface);
            padding: 2rem;
            border-radius: 1.5rem;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            animation: slideInUp 0.3s ease;
        }

        @keyframes slideInUp {
            from {
                transform: translateY(40px);
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
            margin-bottom: 1.5rem;
        }

        .modal-header h2 {
            font-size: 1.5rem;
            color: var(--text);
            font-weight: 700;
        }

        .close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-secondary);
            line-height: 1;
            transition: all 0.2s ease;
        }

        .close:hover {
            color: var(--danger);
            transform: scale(1.2);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text);
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border);
            border-radius: 0.75rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            background: linear-gradient(to right, #ffffff, #f8fafc);
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .form-actions .btn {
            flex: 1;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem 0.75rem;
            }

            header {
                flex-direction: column;
                align-items: flex-start;
            }

            .toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .toolbar-title,
            .btn {
                width: 100%;
                text-align: center;
            }

            th,
            td {
                padding: 0.75rem 0.5rem;
                font-size: 0.85rem;
            }

            .btn-sm {
                padding: 0.4rem 0.75rem;
            }

            .actions {
                flex-direction: column;
            }

            .modal-content {
                margin: 1rem;
                max-width: calc(100% - 2rem);
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .form-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-brand">
                <i class="fas fa-graduation-cap"></i> Student Records
            </div>
            <div class="navbar-nav">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <header>
            <h1><i class="fas fa-users"></i> Student Records</h1>
            <div class="nav">
                <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
            </div>
        </header>

        <div class="toolbar">
            <h2 class="toolbar-title">Manage all student records</h2>
            <button class="btn" onclick="openModal()"><i class="fas fa-user-plus"></i> Add Student</button>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th style="width:60px">#</th>
                        <th>Student Name</th>
                        <th>Birthday</th>
                        <th>Subject / Faculty</th>
                        <th>Grade</th>
                        <th style="width:180px">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr>
                        <td colspan="6" class="empty">
                            <div class="empty-icon"><i class="fas fa-inbox"></i></div>
                            <p>Loading records...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form -->
    <div id="studentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"><i class="fas fa-user"></i> Add Student</h2>
                <button class="close" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>
            <form id="studentForm">
                <input type="hidden" id="studentId" name="id">
                <div class="form-group">
                    <label for="name"><i class="fas fa-user"></i> Full Name *</label>
                    <input type="text" id="name" name="name" placeholder="e.g. John Doe" required>
                </div>
                <div class="form-group">
                    <label for="birthday"><i class="fas fa-birthday-cake"></i> Birthday *</label>
                    <input type="date" id="birthday" name="birthday" required>
                </div>
                <div class="form-group">
                    <label for="subject"><i class="fas fa-book"></i> Subject / Faculty *</label>
                    <input type="text" id="subject" name="subject" placeholder="e.g. Computer Science" required>
                </div>
                <div class="form-group">
                    <label for="grade"><i class="fas fa-graduation-cap"></i> Grade *</label>
                    <input type="text" id="grade" name="grade" placeholder="e.g. 10th Grade" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-danger" onclick="closeModal()"><i class="fas fa-times"></i> Cancel</button>
                    <button type="submit" class="btn"><i class="fas fa-save"></i> Save Record</button>
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
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" class="empty">
                                <div class="empty-icon"><i class="fas fa-inbox"></i></div>
                                <p><strong>No students found yet.</strong></p>
                                <p style="font-size:0.9rem; margin-top:0.5rem;">Click "Add Student" to create your first record.</p>
                            </td>
                        </tr>
                    `;
                    return;
                }

                data.forEach((s, index) => {
                    tbody.innerHTML += `
                        <tr>
                            <td class="row-number">${index + 1}</td>
                            <td class="student-name">${escapeHtml(s.name)}</td>
                            <td>${s.birthday || 'N/A'}</td>
                            <td>${escapeHtml(s.subject)}</td>
                            <td>${escapeHtml(s.grade)}</td>
                            <td>
                                <div class="actions">
                                    <button class="btn btn-sm" onclick="openEditModal(${s.id})">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteStudent(${s.id})">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
            } catch (err) {
                document.getElementById('tableBody').innerHTML = `
                    <tr>
                        <td colspan="6" class="empty">
                            <div class="empty-icon"><i class="fas fa-exclamation-circle"></i></div>
                            <p><strong>Failed to load data.</strong></p>
                            <p style="font-size:0.9rem; margin-top:0.5rem;">Please check the console for details.</p>
                        </td>
                    </tr>
                `;
                console.error(err);
            }
        }

        function openModal() {
            document.getElementById('studentModal').classList.add('active');
            isEditing = false;
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user"></i> Add Student';
            document.getElementById('studentForm').reset();
            document.getElementById('studentId').value = '';
        }

        function openEditModal(id) {
            const s = studentsCache.find(x => x.id === id);
            if (!s) return;
            document.getElementById('studentModal').classList.add('active');
            isEditing = true;
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit"></i> Edit Student';
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
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
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
                    alert('Error: ' + (result.error || 'Unknown error occurred'));
                }
            } catch (err) {
                alert('Network error: ' + err.message);
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });

        async function deleteStudent(id) {
            if (!confirm('Are you sure? This will permanently delete the student and all their attendance records.')) return;
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                const res = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();
                if (result.success) {
                    loadStudents();
                } else {
                    alert('Delete failed: ' + (result.error || 'Unknown error'));
                }
            } catch (err) {
                alert('Network error: ' + err.message);
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

        // Close modal with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && document.getElementById('studentModal').classList.contains('active')) {
                closeModal();
            }
        });
    </script>
</body>

</html>