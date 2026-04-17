<?php
require_once '../includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Attendance Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
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
            min-height: 100vh;
        }

        .navbar {
            background: var(--surface);
            box-shadow: var(--shadow);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
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
            font-size: 1.5rem;
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
            padding: 4rem 1rem;
        }

        .hero {
            text-align: center;
            margin-bottom: 4rem;
            animation: fadeInDown 0.8s ease;
        }

        .hero-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--text);
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 3rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            border-radius: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            font-size: 1rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-secondary {
            background: var(--surface);
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .feature-card {
            background: var(--surface);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            text-align: center;
            animation: fadeInUp 0.8s ease 0.2s backwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            font-size: 1.25rem;
            margin-bottom: 0.75rem;
            color: var(--text);
        }

        .feature-card p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .stats {
            background: var(--surface);
            padding: 3rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            text-align: center;
            margin: 3rem 0;
        }

        .stat-item h4 {
            font-size: 2.5rem;
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-item p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        footer {
            background: var(--surface);
            padding: 2rem 1rem;
            margin-top: 4rem;
            border-top: 1px solid var(--border);
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 2rem;
            }

            .navbar-nav {
                gap: 1rem;
                font-size: 0.9rem;
            }

            .cta-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-brand">
                <i class="fas fa-clipboard-check"></i> AttendX
            </div>
            <div class="navbar-nav">
                <a href="#features">Features</a>
                <a href="students.php">Students</a>
                <a href="attendance.php">Attendance</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="hero">
            <div class="hero-icon">📚</div>
            <h1>Student Attendance Manager</h1>
            <p class="hero-subtitle">Modern, intuitive attendance tracking system designed for educators. Manage student records, track attendance, and generate reports with ease.</p>

            <div class="cta-buttons">
                <a href="students.php" class="btn btn-primary">
                    <i class="fas fa-users"></i> Manage Students
                </a>
                <a href="attendance.php" class="btn btn-secondary">
                    <i class="fas fa-calendar-check"></i> Track Attendance
                </a>
            </div>
        </div>

        <div id="features" class="features">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-user-graduate"></i></div>
                <h3>Student Management</h3>
                <p>Create, edit, and manage student records with essential information including grades and subjects.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-calendar-alt"></i></div>
                <h3>Daily Attendance</h3>
                <p>Quick and easy daily attendance marking with visual calendar interface for better tracking.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-download"></i></div>
                <h3>Export Reports</h3>
                <p>Export attendance data to CSV for further analysis, reporting, and record keeping.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-mobile-alt"></i></div>
                <h3>Mobile Friendly</h3>
                <p>Fully responsive design works seamlessly on tablets and smartphones for on-the-go access.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                <h3>Lightning Fast</h3>
                <p>Optimized performance with minimal load times for maximum productivity and efficiency.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                <h3>Secure & Reliable</h3>
                <p>Built with security best practices to protect sensitive student data with integrity.</p>
            </div>
        </div>

        <div class="stats">
            <div class="stat-item">
                <h4>∞</h4>
                <p>Unlimited Students</p>
            </div>
            <div class="stat-item">
                <h4>24/7</h4>
                <p>Always Available</p>
            </div>
            <div class="stat-item">
                <h4>100%</h4>
                <p>Data Safe</p>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2026 Student Attendance Manager. Built for educators, by educators. <i class="fas fa-heart" style="color: var(--danger);"></i></p>
    </footer>
</body>

</html>