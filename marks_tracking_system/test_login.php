<?php
session_start();
require_once 'config/database.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reg_no = $_POST['reg_no'];
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE reg_no = ? AND role = 'student' AND is_active = 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $reg_no);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    
    if ($user) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            header("Location: student/dashboard.php");
            exit();
        } else {
            $message = "Password incorrect!";
        }
    } else {
        $message = "Student not found!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Login</title>
    <style>
        body { font-family: Arial; padding: 50px; }
        .container { max-width: 400px; margin: auto; padding: 20px; border: 1px solid #ccc; border-radius: 10px; }
        input { width: 100%; padding: 10px; margin: 10px 0; }
        button { width: 100%; padding: 10px; background: #667eea; color: white; border: none; cursor: pointer; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Test Student Login</h2>
        <?php if($message): ?>
            <p class="error"><?php echo $message; ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="reg_no" placeholder="Registration Number" value="1049489" required>
            <input type="password" name="password" placeholder="Password" value="student123" required>
            <button type="submit">Login</button>
        </form>
        <hr>
        <p><strong>Use these credentials:</strong></p>
        <p>Reg No: 1049489<br>Password: student123</p>
    </div>
</body>
</html>