<?php
require_once '../includes/db.php';

// Set timezone (change to your local timezone if needed)
date_default_timezone_set('UTC');

$action = $_GET['action'] ?? '';

// 🔹 API: Load attendance data for last 7 days
if ($action === 'load') {
    header('Content-Type: application/json');

    $dates = [];
    for ($i = 6; $i >= 0; $i--) {
        $dates[] = date('Y-m-d', strtotime("-$i days"));
    }

    $stmt = $pdo->prepare("
        SELECT s.id, s.name, a.date, a.status
        FROM students s
        LEFT JOIN attendance a ON s.id = a.student_id AND a.date BETWEEN ? AND ?
        ORDER BY s.id ASC, a.date ASC
    ");
    $stmt->execute([$dates[0], $dates[6]]);
    $rows = $stmt->fetchAll();

    // Structure data: group by student
    $students = [];
    foreach ($rows as $row) {
        if (!isset($students[$row['id']])) {
            $students[$row['id']] = ['id' => $row['id'], 'name' => $row['name'], 'attendance' => []];
        }
        if ($row['date']) {
            $students[$row['id']]['attendance'][$row['date']] = $row['status'];
        }
    }

    echo json_encode([
        'dates' => $dates,
        'students' => array_values($students)
    ]);
    exit;
}

// 🔹 API: Save single attendance record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['student_id'], $input['date'], $input['status'])) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO attendance (student_id, date, status) 
            VALUES (?, ?, ?)
            ON CONFLICT(student_id, date) DO UPDATE SET status = ?, updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$input['student_id'], $input['date'], $input['status'], $input['status']]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 🔹 API: Export to CSV
if ($action === 'export') {
    $dates = [];
    for ($i = 6; $i >= 0; $i--) $dates[] = date('Y-m-d', strtotime("-$i days"));

    $stmt = $pdo->prepare("SELECT s.id, s.name, a.date, a.status FROM students s LEFT JOIN attendance a ON s.id = a.student_id AND a.date BETWEEN ? AND ? ORDER BY s.id ASC");
    $stmt->execute([$dates[0], $dates[6]]);
    $rows = $stmt->fetchAll();

    // Group for CSV
    $students = [];
    foreach ($rows as $row) {
        if (!isset($students[$row['id']])) {
            $students[$row['id']] = ['name' => $row['name'], 'data' => array_fill_keys($dates, 'Unmarked')];
        }
        if ($row['date']) {
            $students[$row['id']]['data'][$row['date']] = $row['status'];
        }
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="attendance_' . date('Y-m-d') . '.csv"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Student Name', ...array_map(fn($d) => date('D d/m', strtotime($d)), $dates)]);
    foreach ($students as $s) {
        fputcsv($out, [$s['name'], ...$s['data']]);
    }
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Attendance Log</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #10b981;
            --success-light: #dcfce7;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            --warning: #f59e0b;
            --bg: #f8fafc;
            --surface: #ffffff;
            --text: #0f172a;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
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
            max-width: 1400px;
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
            max-width: 1400px;
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
            background: var(--surface);
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
        }

        .toolbar-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .toolbar-info strong {
            font-weight: 600;
            color: var(--text);
        }

        .date-range {
            color: var(--text-secondary);
            font-size: 0.95rem;
            background: var(--bg);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
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

        .btn-outline {
            background: var(--surface);
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
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

        .table-scroll {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        th,
        td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        th {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
        }

        th:first-child,
        td:first-child {
            width: 60px;
            text-align: center;
        }

        th:nth-child(2),
        td:nth-child(2) {
            width: 200px;
            white-space: normal;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tbody tr {
            transition: all 0.2s ease;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        select {
            width: 100%;
            padding: 0.6rem 0.8rem;
            border: 2px solid var(--border);
            border-radius: 0.5rem;
            font-size: 0.85rem;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            font-weight: 500;
        }

        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .status-present {
            background: var(--success-light);
            color: #047857;
            border-color: var(--success);
        }

        .status-absent {
            background: var(--danger-light);
            color: #991b1b;
            border-color: var(--danger);
        }

        .status-unmarked {
            background: white;
            color: var(--text);
            border-color: var(--border);
        }

        .saving {
            opacity: 0.6;
            pointer-events: none;
        }

        .empty {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty p {
            font-size: 0.95rem;
        }

        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--success);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
            pointer-events: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            z-index: 200;
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .stat-card {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0.5rem 0;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
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

            .toolbar-info {
                flex-direction: column;
                gap: 0.5rem;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            th,
            td {
                padding: 0.75rem 0.5rem;
                font-size: 0.8rem;
            }

            th:nth-child(2),
            td:nth-child(2) {
                width: 150px;
            }

            select {
                padding: 0.5rem;
                font-size: 0.75rem;
            }

            .stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-brand">
                <i class="fas fa-calendar-check"></i> Attendance System
            </div>
            <div class="navbar-nav">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="students.php"><i class="fas fa-users"></i> Students</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <header>
            <h1><i class="fas fa-calendar-days"></i> Daily Attendance Log</h1>
            <div class="nav">
                <a href="index.php"><i class="fas fa-arrow-left"></i> Back Home</a>
            </div>
        </header>

        <div class="toolbar">
            <div class="toolbar-info">
                <strong><i class="fas fa-clock"></i> Last 7 Days:</strong>
                <span class="date-range" id="dateRange">Loading...</span>
            </div>
            <button class="btn btn-outline" onclick="exportCSV()"><i class="fas fa-download"></i> Export to CSV</button>
        </div>

        <div class="table-wrapper">
            <div class="table-scroll">
                <table id="attendanceTable">
                    <thead id="tableHead"></thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>
            <div id="loadingState" class="empty">
                <div class="empty-icon"><i class="fas fa-spinner fa-spin"></i></div>
                <p>Loading attendance records...</p>
            </div>
        </div>
    </div>

    <div id="toast" class="toast">
        <i class="fas fa-check-circle"></i> <span id="toastMsg">Saved successfully</span>
    </div>

    <script>
        let dates = [];
        let isSaving = false;
        let stats = {
            total: 0,
            present: 0,
            absent: 0
        };

        async function loadAttendance() {
            try {
                const res = await fetch('?action=load');
                const data = await res.json();
                dates = data.dates;

                // Render headers
                const thead = document.getElementById('tableHead');
                thead.innerHTML = '<tr><th>#</th><th>Student Name</th>' +
                    dates.map(d => `<th title="${new Date(d).toDateString()}">${formatDateHeader(d)}</th>`).join('') + '</tr>';

                document.getElementById('dateRange').textContent = `${formatDateHeader(dates[0])} → ${formatDateHeader(dates[6])}`;

                // Render rows
                const tbody = document.getElementById('tableBody');
                tbody.innerHTML = '';

                if (data.students.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="${dates.length + 2}" class="empty">
                        <div class="empty-icon"><i class="fas fa-inbox"></i></div>
                        <p><strong>No students found.</strong> Add students first to start tracking attendance.</p>
                    </td></tr>`;
                    document.getElementById('loadingState').style.display = 'none';
                    return;
                }

                stats.total = data.students.length * dates.length;

                data.students.forEach((s, idx) => {
                    const tr = document.createElement('tr');
                    let rowHtml = `<td style="text-align:center; font-weight:600; color:var(--text-secondary);">${idx + 1}</td><td style="font-weight:500; color:var(--text);">${escapeHtml(s.name)}</td>`;

                    dates.forEach(d => {
                        const status = s.attendance[d] || '';
                        rowHtml += `<td data-student="${s.id}" data-date="${d}">
                            <select onchange="autoSave(this, '${s.id}', '${d}')" class="status-${status.toLowerCase() || 'unmarked'}">
                                <option value="">Unmarked</option>
                                <option value="Present" ${status === 'Present' ? 'selected' : ''}>✓ Present</option>
                                <option value="Absent" ${status === 'Absent' ? 'selected' : ''}>✗ Absent</option>
                            </select>
                        </td>`;
                    });

                    tr.innerHTML = rowHtml;
                    tbody.appendChild(tr);
                });

                document.getElementById('loadingState').style.display = 'none';
            } catch (err) {
                document.getElementById('loadingState').innerHTML = `
                    <div class="empty">
                        <div class="empty-icon"><i class="fas fa-exclamation-circle"></i></div>
                        <p><strong>Failed to load attendance data.</strong></p>
                        <p style="font-size:0.9rem; margin-top:0.5rem;">Check your connection and try again.</p>
                    </div>
                `;
                console.error(err);
            }
        }

        async function autoSave(select, studentId, date) {
            if (isSaving) return;
            isSaving = true;
            select.classList.add('saving');

            try {
                const res = await fetch('?action=save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        student_id: studentId,
                        date: date,
                        status: select.value
                    })
                });
                const result = await res.json();
                if (result.success) {
                    select.className = `status-${(select.value || 'unmarked').toLowerCase()}`;
                    showToast('✓ Attendance saved successfully');
                } else {
                    showToast('✗ Save failed', 'error');
                    console.error('Save failed:', result.error);
                }
            } catch (err) {
                showToast('✗ Network error', 'error');
                console.error(err);
            } finally {
                select.classList.remove('saving');
                isSaving = false;
            }
        }

        function exportCSV() {
            const btn = event.target.closest('.btn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
            window.location.href = '?action=export';
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-download"></i> Export to CSV';
            }, 2000);
        }

        function formatDateHeader(dateStr) {
            const d = new Date(dateStr + 'T00:00:00Z');
            return `${['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][d.getUTCDay()]} ${d.getUTCDate()}/${String(d.getUTCMonth()+1).padStart(2,'0')}`;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showToast(msg, type = 'success') {
            const t = document.getElementById('toast');
            const msgElem = document.getElementById('toastMsg');
            msgElem.textContent = msg;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 3000);
        }

        document.addEventListener('DOMContentLoaded', loadAttendance);

        // Keyboard shortcut: Ctrl+E to export
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                exportCSV();
            }
        });
    </script>
</body>

</html>