<?php
require_once 'config/database.php';

echo "<h1>Direct Password Fix</h1>";

// Simple password hash function (using PHP's built-in)
$student_password = password_hash('student123', PASSWORD_DEFAULT);
$lecturer_password = password_hash('lecturer123', PASSWORD_DEFAULT);
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);

echo "<p>Student password hash: " . $student_password . "</p>";
echo "<p>Lecturer password hash: " . $lecturer_password . "</p>";
echo "<p>Admin password hash: " . $admin_password . "</p>";

// Update all users
$queries = [
    "UPDATE users SET password = '$student_password' WHERE role = 'student'",
    "UPDATE users SET password = '$lecturer_password' WHERE role = 'lecturer'",
    "UPDATE users SET password = '$admin_password' WHERE role = 'admin'"
];

foreach ($queries as $query) {
    if (mysqli_query($conn, $query)) {
        echo "<p style='color:green'>✓ Executed: " . substr($query, 0, 50) . "...</p>";
    } else {
        echo "<p style='color:red'>✗ Error: " . mysqli_error($conn) . "</p>";
    }
}

// Show updated users
$result = mysqli_query($conn, "SELECT user_id, reg_no, email, role FROM users");
echo "<h2>Users in Database:</h2>";
echo "<table border='1' cellpadding='8'>";
echo "<tr><th>ID</th><th>Reg No/Email</th><th>Role</th></tr>";
while($row = mysqli_fetch_assoc($result)) {
    $login = $row['reg_no'] ?? $row['email'];
    echo "<tr><td>{$row['user_id']}</td><td>$login</td><td>{$row['role']}</td></tr>";
}
echo "</table>";

echo "<h3>Try logging in now:</h3>";
echo "<ul>";
echo "<li><a href='student/login.php'>Student Login</a> - 1049489 / student123</li>";
echo "<li><a href='lecturer/login.php'>Lecturer Login</a> - chris.nandasaba@cuea.edu / lecturer123</li>";
echo "<li><a href='admin/login.php'>Admin Login</a> - michael.kinyua@cuea.edu / admin123</li>";
echo "</ul>";
?>