<?php
// reset_all_passwords.php - Run this once to fix all logins
require_once 'config/database.php';

echo "<h1>Password Reset Utility</h1>";

// Function to hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Update all users with correct passwords
$users_to_update = [
    ['email' => 'michael.kinyua@cuea.edu', 'reg_no' => 'ADMIN001', 'role' => 'admin', 'password' => 'admin123'],
    ['email' => 'chris.nandasaba@cuea.edu', 'reg_no' => 'LEC001', 'role' => 'lecturer', 'password' => 'lecturer123'],
    ['email' => 'angela.kerubo@cuea.edu', 'reg_no' => '1049489', 'role' => 'student', 'password' => 'student123'],
    ['email' => 'john.doe@cuea.edu', 'reg_no' => '1050001', 'role' => 'student', 'password' => 'student123'],
];

foreach ($users_to_update as $user) {
    $hashed = hashPassword($user['password']);
    
    if ($user['role'] == 'student') {
        $query = "UPDATE users SET password = ? WHERE reg_no = ? AND role = 'student'";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $hashed, $user['reg_no']);
    } else {
        $query = "UPDATE users SET password = ? WHERE email = ? AND role = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sss", $hashed, $user['email'], $user['role']);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<p style='color:green'>✓ Password reset for: " . ($user['reg_no'] ?: $user['email']) . " (" . $user['role'] . ")</p>";
    } else {
        echo "<p style='color:red'>✗ Failed to reset for: " . ($user['reg_no'] ?: $user['email']) . "</p>";
    }
}

// Display all users with their login credentials
echo "<h2>Current Users in Database:</h2>";
echo "<table border='1' cellpadding='8' cellspacing='0'>";
echo "<tr><th>Role</th><th>Login (Reg No / Email)</th><th>Full Name</th><th>Password</th></tr>";

$users_query = "SELECT reg_no, email, full_name, role FROM users";
$users = mysqli_query($conn, $users_query);

while($row = mysqli_fetch_assoc($users)) {
    echo "<tr>";
    echo "<td>" . ucfirst($row['role']) . "</td>";
    if ($row['role'] == 'student') {
        echo "<td>" . $row['reg_no'] . "</td>";
        echo "<td>" . $row['full_name'] . "</td>";
        echo "<td>student123</td>";
    } else {
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['full_name'] . "</td>";
        echo "<td>" . ($row['role'] == 'admin' ? 'admin123' : 'lecturer123') . "</td>";
    }
    echo "</tr>";
}
echo "</table>";

echo "<br><hr>";
echo "<h3>Login URLs:</h3>";
echo "<ul>";
echo "<li><a href='student/login.php'>Student Login</a> - Use Reg No: 1049489, Password: student123</li>";
echo "<li><a href='lecturer/login.php'>Lecturer Login</a> - Use Email: chris.nandasaba@cuea.edu, Password: lecturer123</li>";
echo "<li><a href='admin/login.php'>Admin Login</a> - Use Email: michael.kinyua@cuea.edu, Password: admin123</li>";
echo "</ul>";
?>