<?php
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current logged in user data
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    global $conn;
    $user_id = $_SESSION['user_id'];
    $query = "SELECT * FROM users WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Require user to be logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../index.php");
        exit();
    }
}

// Require specific role (student, lecturer, admin)
function requireRole($role) {
    requireLogin();
    $user = getCurrentUser();
    if ($user['role'] !== $role && $user['role'] !== 'admin') {
        header("Location: ../index.php");
        exit();
    }
}

// Send notification to a user
function sendNotification($user_id, $title, $message, $type = 'info') {
    global $conn;
    $query = "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "isss", $user_id, $title, $message, $type);
    return mysqli_stmt_execute($stmt);
}

// Get unread notifications count for a user
function getUnreadNotificationCount($user_id) {
    global $conn;
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    return $data['count'];
}

// Mark notification as read
function markNotificationRead($notification_id) {
    global $conn;
    $query = "UPDATE notifications SET is_read = 1 WHERE notification_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $notification_id);
    return mysqli_stmt_execute($stmt);
}

// Get user by registration number (for students)
function getUserByRegNo($reg_no) {
    global $conn;
    $query = "SELECT * FROM users WHERE reg_no = ? AND role = 'student'";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $reg_no);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Get user by email (for lecturers and admins)
function getUserByEmail($email) {
    global $conn;
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Get all enrolled courses for a student
function getStudentCourses($student_id) {
    global $conn;
    $query = "SELECT c.*, e.semester, e.academic_year, 
              m.ca_score, m.exam_score, m.total_score, m.grade, m.status as mark_status
              FROM enrollments e
              JOIN courses c ON e.course_id = c.course_id
              LEFT JOIN marks m ON m.student_id = e.student_id AND m.course_id = c.course_id
              WHERE e.student_id = ? AND e.status = 'active'
              ORDER BY c.course_code";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $courses = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $courses[] = $row;
    }
    return $courses;
}

// Get all students enrolled in a course for a lecturer
function getCourseStudents($course_id) {
    global $conn;
    $query = "SELECT u.user_id, u.reg_no, u.full_name, u.email,
              m.mark_id, m.ca_score, m.exam_score, m.total_score, m.grade, m.status
              FROM enrollments e
              JOIN users u ON e.student_id = u.user_id
              LEFT JOIN marks m ON m.student_id = u.user_id AND m.course_id = e.course_id
              WHERE e.course_id = ? AND e.status = 'active'
              ORDER BY u.reg_no";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $course_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $students = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
    return $students;
}

// Calculate grade from total score
function calculateGrade($score) {
    if ($score >= 70) return 'A';
    if ($score >= 60) return 'B';
    if ($score >= 50) return 'C';
    if ($score >= 40) return 'D';
    return 'F';
}

// Get current semester and academic year
function getCurrentSemester() {
    $current_year = date('Y');
    $next_year = $current_year + 1;
    $month = date('n');
    
    if ($month >= 1 && $month <= 4) {
        return ['semester' => 2, 'academic_year' => ($current_year - 1) . '/' . $current_year];
    } elseif ($month >= 5 && $month <= 8) {
        return ['semester' => 1, 'academic_year' => $current_year . '/' . $next_year];
    } else {
        return ['semester' => 1, 'academic_year' => $current_year . '/' . $next_year];
    }
}
?>