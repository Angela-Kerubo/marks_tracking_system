<?php
require_once 'config/database.php';

echo "<h1>Database Check</h1>";

// Check users table
$result = mysqli_query($conn, "SELECT * FROM users");
if (!$result) {
    echo "<p style='color:red'>Error: " . mysqli_error($conn) . "</p>";
} else {
    echo "<h2>Users in Database:</h2>";
    if (mysqli_num_rows($result) > 0) {
        echo "<table border='1' cellpadding='8'>";
        echo "<tr><th>ID</th><th>Reg No</th><th>Email</th><th>Full Name</th><th>Role</th><th>Active</th></tr>";
        while($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['user_id'] . "</td>";
            echo "<td>" . ($row['reg_no'] ?? '-') . "</td>";
            echo "<td>" . $row['email'] . "</td>";
            echo "<td>" . $row['full_name'] . "</td>";
            echo "<td>" . $row['role'] . "</td>";
            echo "<td>" . ($row['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red'>No users found! Database is empty.</p>";
    }
}
?>