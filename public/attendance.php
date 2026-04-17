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
    header('Content-Disposition: attachment; filename="attendance_'.date('Y-m-d').'.csv"');
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
    <style>
        :root { --primary: #0f766e; --bg: #f8fafc; --text: #1e293b; --border: #e2e8f0; --white: #fff; --present: #dcfce7; --absent: #fee2e2; --unmarked: #fff; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; background: var(--bg); color: var(--text); line-height: 1.5; padding-bottom: 40px; }
        .container { max-width: 1100px; margin: 0 auto; padding: 1.5rem 1rem; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
        h1 { font-size: 1.5rem; color: var(--primary); }
        .nav { display: flex; gap: 1rem; font-size: 0.9rem; }
        .nav a { color: var(--primary); text-decoration: none; font-weight: 500; }
        .nav a:hover { text-decoration: underline; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem; }
        .btn { padding: 0.6rem 1.2rem; background: var(--primary); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; transition: 0.2s; }
        .btn:hover { background: #0d9488; }
        .btn-outline { background: transparent; border: 1px solid var(--primary); color: var(--primary); }
        .btn-outline:hover { background: var(--primary); color: white; }
        .table-wrapper { overflow-x: auto; background: var(--white); border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th, td { padding: 0.8rem 1rem; text-align: left; border-bottom: 1px solid var(--border); white-space: nowrap; }
        th { background: #f1f5f9; font-weight: 600; font-size: 0.85rem; color: #64748b; }
        th:first-child, td:first-child { width: 50px; text-align: center; }
        tr:last-child td { border-bottom: none; }
        select { padding: 0.4rem 0.6rem; border: 1px solid var(--border); border-radius: 6px; font-size: 0.9rem; background: white; cursor: pointer; transition: 0.2s; }
        select:focus { outline: none; border-color: var(--primary); }
        .status-present { background: var(--present); }
        .status-absent { background: var(--absent); }
        .status-unmarked { background: var(--unmarked); }
        .saving { opacity: 0.6; pointer-events: none; }
        .empty { text-align: center; padding: 3rem; color: #94a3b8; }
        .toast { position: fixed; bottom: 20px; right: 20px; background: #10b981; color: white; padding: 0.8rem 1.2rem; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); opacity: 0; transform: translateY(10px); transition: 0.3s; pointer-events: none; }
        .toast.show { opacity: 1; transform: translateY(0); }
        @media (max-width: 768px) {
            .toolbar { flex-direction: column; align-items: flex-start; }
            th, td { padding: 0.6rem 0.5rem; font-size: 0.85rem; }
            select { padding: 0.3rem; font-size: 0.8rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📅 Daily Attendance Log</h1>
            <div class="nav">
                <a href="index.php">← Home</a>
                <a href="students.php">Student Records</a>
            </div>
        </header>

        <div class="toolbar">
            <div>
                <strong>Last 7 Days:</strong> <span id="dateRange" style="color:#64748b; font-size:0.9rem;"></span>
            </div>
            <button class="btn btn-outline" onclick="exportCSV()">📥 Export CSV</button>
        </div>

        <div class="table-wrapper">
            <table id="attendanceTable">
                <thead id="tableHead"></thead>
                <tbody id="tableBody"></tbody>
            </table>
            <div id="loadingState" class="empty">Loading attendance records...</div>
        </div>
    </div>

    <div id="toast" class="toast">✅ Saved successfully</div>

    <script>
        let dates = [];
        let isSaving = false;

        async function loadAttendance() {
            const res = await fetch('?action=load');
            const data = await res.json();
            dates = data.dates;
            
            // Render headers
            const thead = document.getElementById('tableHead');
            thead.innerHTML = '<tr><th>No</th><th>Student</th>' + 
                dates.map(d => `<th>${formatDateHeader(d)}</th>`).join('') + '</tr>';
            
            document.getElementById('dateRange').textContent = `${formatDateHeader(dates[0])} → ${formatDateHeader(dates[6])}`;
            
            // Render rows
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';
            
            if (data.students.length === 0) {
                tbody.innerHTML = '<tr><td colspan="' + (dates.length + 2) + '" class="empty">No students found. Add students first.</td></tr>';
                document.getElementById('loadingState').style.display = 'none';
                return;
            }

            data.students.forEach((s, idx) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${idx + 1}</td><td>${escapeHtml(s.name)}</td>` +
                    dates.map(d => {
                        const status = s.attendance[d] || '';
                        return `<td data-student="${s.id}" data-date="${d}">
                            <select onchange="autoSave(this, '${s.id}', '${d}')" class="status-${status.toLowerCase() || 'unmarked'}">
                                <option value="" ${status === '' ? 'selected' : ''}>Select</option>
                                <option value="Present" ${status === 'Present' ? 'selected' : ''}>🟢 Present</option>
                                <option value="Absent" ${status === 'Absent' ? 'selected' : ''}>🔴 Absent</option>
                            </select>
                        </td>`;
                    }).join('');
                tbody.appendChild(tr);
            });
            
            document.getElementById('loadingState').style.display = 'none';
        }

        async function autoSave(select, studentId, date) {
            if (isSaving) return;
            isSaving = true;
            select.classList.add('saving');
            
            try {
                const res = await fetch('?action=save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ student_id: studentId, date: date, status: select.value })
                });
                const result = await res.json();
                if (result.success) {
                    select.className = `status-${(select.value || 'unmarked').toLowerCase()}`;
                    showToast('✅ Saved');
                } else {
                    alert('Save failed: ' + (result.error || 'Unknown'));
                }
            } catch (err) {
                alert('Network error. Please check your connection.');
            } finally {
                select.classList.remove('saving');
                isSaving = false;
            }
        }

        function exportCSV() {
            window.location.href = '?action=export';
        }

        function formatDateHeader(dateStr) {
            const d = new Date(dateStr);
            return `${['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][d.getDay()]} ${d.getDate()}/${String(d.getMonth()+1).padStart(2,'0')}`;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showToast(msg) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 2000);
        }

        document.addEventListener('DOMContentLoaded', loadAttendance);
    </script>
</body>
</html>