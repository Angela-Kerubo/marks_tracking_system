<?php
require_once 'config/database.php';

echo "<h1>Current Users in Database</h1>";
echo "<table border='1' cellpadding='8' cellspacing='0'>";
echo "<tr><th>ID</th><th>Reg No</th><th>Full Name</th><th>Email</th><th>Role</th></tr>";

$query = "SELECT user_id, reg_no, full_name, email, role FROM users ORDER BY role";
$result = mysqli_query($conn, $query);

while($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['user_id'] . "</td>";
    echo "<td>" . $row['reg_no'] . "</td>";
    echo "<td>" . $row['full_name'] . "</td>";
    echo "<td>" . $row['email'] . "</td>";
    echo "<td>" . $row['role'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><h3>Login URLs:</h3>";
echo "<ul>";
echo "<li><a href='admin/login.php'>Admin Login</a> - Email: michael.kinyua@cuea.edu | Password: admin123</li>";
echo "<li><a href='lecturer/login.php'>Lecturer Login</a> - Email: chris.nandasaba@cuea.edu | Password: lecturer123</li>";
echo "<li><a href='student/login.php'>Student Login</a> - Reg No: 1049489 | Password: student123</li>";
echo "</ul>";
?>