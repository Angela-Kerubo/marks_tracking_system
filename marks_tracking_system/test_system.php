<?php
// test_system.php - Test if everything is working
require_once 'config/database.php';

echo "<h1>Marks Tracking System - Test Results</h1>";

// Test 1: Database Connection
echo "<h3>Test 1: Database Connection</h3>";
if ($conn) {
    echo "<p style='color:green'>✓ Database connected successfully</p>";
} else {
    echo "<p style='color:red'>✗ Database connection failed</p>";
}

// Test 2: Check Tables
echo "<h3>Test 2: Database Tables</h3>";
$tables = ['users', 'courses', 'enrollments', 'marks', 'complaints', 'notifications'];
foreach ($tables as $table) {
    $check = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($check) > 0) {
        echo "<p style='color:green'>✓ Table '$table' exists</p>";
    } else {
        echo "<p style='color:red'>✗ Table '$table' missing</p>";
    }
}

// Test 3: Check Users
echo "<h3>Test 3: User Accounts</h3>";
$users_query = "SELECT reg_no, email, full_name, role FROM users";
$users = mysqli_query($conn, $users_query);
if (mysqli_num_rows($users) > 0) {
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>Reg No/Email</th><th>Full Name</th><th>Role</th></tr>";
    while($user = mysqli_fetch_assoc($users)) {
        $login = ($user['role'] == 'student') ? $user['reg_no'] : $user['email'];
        echo "<tr><td>$login</td><td>{$user['full_name']}</td><td>{$user['role']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>✗ No users found</p>";
}

// Test 4: Check Courses
echo "<h3>Test 4: Courses</h3>";
$courses_query = "SELECT COUNT(*) as count FROM courses";
$courses = mysqli_fetch_assoc(mysqli_query($conn, $courses_query));
echo "<p>Total Courses: " . $courses['count'] . "</p>";

// Test 5: File Structure Check
echo "<h3>Test 5: File Structure</h3>";
$files = [
    'student/login.php',
    'student/dashboard.php',
    'lecturer/login.php',
    'lecturer/dashboard.php',
    'admin/login.php',
    'admin/dashboard.php',
    'config/database.php',
    'includes/session.php',
    'assets/css/style.css'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<p style='color:green'>✓ $file exists</p>";
    } else {
        echo "<p style='color:red'>✗ $file missing</p>";
    }
}

echo "<hr>";
echo "<h2>Login Credentials:</h2>";
echo "<ul>";
echo "<li><strong>Student:</strong> Reg No: 1049489, Password: student123</li>";
echo "<li><strong>Lecturer:</strong> Email: chris.nandasaba@cuea.edu, Password: lecturer123</li>";
echo "<li><strong>Admin:</strong> Email: michael.kinyua@cuea.edu, Password: admin123</li>";
echo "</ul>";

echo "<h2>Access URLs:</h2>";
echo "<ul>";
echo "<li><a href='index.php'>Landing Page</a> - http://localhost/marks_tracking_system/</li>";
echo "<li><a href='student/login.php'>Student Login</a> - http://localhost/marks_tracking_system/student/login.php</li>";
echo "<li><a href='lecturer/login.php'>Lecturer Login</a> - http://localhost/marks_tracking_system/lecturer/login.php</li>";
echo "<li><a href='admin/login.php'>Admin Login</a> - http://localhost/marks_tracking_system/admin/login.php</li>";
echo "</ul>";
?>