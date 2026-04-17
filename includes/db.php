<?php
$dbPath = __DIR__ . '/../data/attendance.db';

// Auto-create DB & tables if missing
if (!file_exists($dbPath)) {
    try {
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS students (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                birthday TEXT,
                subject TEXT NOT NULL,
                grade TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
            
            CREATE TABLE IF NOT EXISTS attendance (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INTEGER NOT NULL,
                date TEXT NOT NULL,
                status TEXT CHECK(status IN ('Present', 'Absent')) NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(student_id, date),
                FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE
            );
        ");
        
        // Seed 3 sample students for testing
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO students (name, birthday, subject, grade) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Alice Johnson', '2008-05-12', 'Computer Science', '10th']);
        $stmt->execute(['Ben Carter', '2009-01-24', 'Mathematics', '9th']);
        $stmt->execute(['Clara Nguyen', '2008-11-30', 'Physics', '11th']);
    } catch (PDOException $e) {
        die("DB Init Failed: " . $e->getMessage());
    }
}

// Reconnect for regular use
try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("DB Connection Failed: " . $e->getMessage());
}
?>