<?php
require_once '../includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Attendance Portal</title>
    <style>
        :root {
            --primary: #0f766e;
            --bg: #f8fafc;
            --text: #1e293b;
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
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        header {
            text-align: center;
            padding: 3rem 0;
        }

        h1 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }

        p {
            color: #64748b;
            margin-bottom: 2rem;
        }

        .btn {
            display: inline-block;
            padding: 0.8rem 1.8rem;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: 0.2s;
        }

        .btn:hover {
            background: #0d9488;
            transform: translateY(-2px);
        }

        .nav {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .nav a {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <h1>📚 Student Attendance Manager</h1>
            <p>A lightweight, fast system to track daily attendance and manage student records. Built for simplicity, speed, and zero friction.</p>
            <a href="students.php" class="btn">Start Managing Students</a>
        </header>
        <div class="nav">
            <a href="students.php">Student Records</a>
            <a href="attendance.php">Daily Attendance Log</a>
        </div>
    </div>
</body>

</html>