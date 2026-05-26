<?php
require_once '../includes/session.php';
requireRole('admin');
$user = getCurrentUser();

// Handle Delete User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $user_id = intval($_POST['user_id']);
    
    // Don't allow admin to delete themselves
    if ($user_id == $user['user_id']) {
        $error = "You cannot delete your own account!";
    } else {
        // Check if user has any related records
        $check_query = "SELECT 
            (SELECT COUNT(*) FROM marks WHERE student_id = $user_id OR lecturer_id = $user_id) as marks_count,
            (SELECT COUNT(*) FROM complaints WHERE student_id = $user_id OR resolved_by = $user_id) as complaints_count,
            (SELECT COUNT(*) FROM courses WHERE lecturer_id = $user_id) as courses_count";
        $check_result = mysqli_fetch_assoc(mysqli_query($conn, $check_query));
        
        if ($check_result['marks_count'] > 0 || $check_result['complaints_count'] > 0 || $check_result['courses_count'] > 0) {
            // Instead of deleting, just deactivate the user
            $query = "UPDATE users SET is_active = 0 WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = "User has been deactivated (they have existing records).";
            } else {
                $error = "Failed to deactivate user.";
            }
        } else {
            // Safe to delete completely
            $query = "DELETE FROM users WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = "User deleted successfully!";
            } else {
                $error = "Failed to delete user.";
            }
        }
    }
}

// Handle Reactivate User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reactivate_user'])) {
    $user_id = intval($_POST['user_id']);
    $query = "UPDATE users SET is_active = 1 WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = "User reactivated successfully!";
    } else {
        $error = "Failed to reactivate user.";
    }
}

// Handle user addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $reg_no = mysqli_real_escape_string($conn, $_POST['reg_no']);
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $course = isset($_POST['course']) ? mysqli_real_escape_string($conn, $_POST['course']) : null;
    $department = isset($_POST['department']) ? mysqli_real_escape_string($conn, $_POST['department']) : null;
    $password = password_hash('student123', PASSWORD_DEFAULT);
    
    $query = "INSERT INTO users (reg_no, full_name, email, phone, password, role, course, department, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssssssss", $reg_no, $full_name, $email, $phone, $password, $role, $course, $department);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = "User added successfully. Default password: student123";
    } else {
        $error = "Failed to add user. Email or Registration Number may already exist.";
    }
}

// Get all users (including inactive ones)
$users_query = "SELECT * FROM users ORDER BY role, is_active DESC, full_name";
$users = mysqli_query($conn, $users_query);
?>
<?php include '../includes/header.php'; ?>
<div class="navbar">
    <div class="nav-brand"><i class="fas fa-chart-line"></i><h2>Marks Tracking & Alert System - CUEA</h2></div>
    <div class="nav-links">
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="manage_users.php" class="active"><i class="fas fa-users"></i> Manage Users</a>
        <a href="approve_marks.php"><i class="fas fa-check-double"></i> Approve Marks</a>
        <a href="manage_complaints.php"><i class="fas fa-ticket-alt"></i> Complaints</a>
        <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
        <div class="user-info"><i class="fas fa-user"></i><span>Admin: <?php echo htmlspecialchars($user['full_name']); ?></span></div>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="container">
    <?php if(isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if(isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Add User Card -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-user-plus"></i> Add New User</h3></div>
        <form method="POST">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div class="form-group"><label>Registration Number</label><input type="text" name="reg_no" placeholder="e.g., 1049489" required></div>
                <div class="form-group"><label>Full Name</label><input type="text" name="full_name" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
                <div class="form-group"><label>Phone</label><input type="tel" name="phone" placeholder="+254XXXXXXXXX"></div>
                <div class="form-group"><label>Role</label>
                    <select name="role" id="roleSelect" required>
                        <option value="student">Student</option>
                        <option value="lecturer">Lecturer</option>
                    </select>
                </div>
                <div class="form-group" id="courseField"><label>Course/Program</label><input type="text" name="course" placeholder="e.g., BSc Computer Science"></div>
                <div class="form-group" id="deptField" style="display: none;"><label>Department</label><input type="text" name="department" placeholder="e.g., Computer Science"></div>
            </div>
            <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
        </form>
    </div>
    
    <!-- Users List Card with Delete Option -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> System Users</h3>
            <span class="badge badge-info">Total: <?php echo mysqli_num_rows($users); ?> users</span>
        </div>
        <div class="table-container">
            <table class="user-table">
                <thead>
                    <tr>
                        <th>Reg No</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Course/Dept</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($u = mysqli_fetch_assoc($users)): ?>
                        <tr>
                            <td><?php echo $u['reg_no'] ?? '-'; ?></td>
                            <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                            <td><?php echo $u['email']; ?></td>
                            <td><?php echo $u['phone'] ?? '-'; ?></td>
                            <td>
                                <span class="badge badge-<?php echo $u['role'] == 'admin' ? 'danger' : ($u['role'] == 'lecturer' ? 'info' : 'success'); ?>">
                                    <?php echo ucfirst($u['role']); ?>
                                </span>
                            </td>
                            <td><?php echo $u['course'] ?? $u['department'] ?? '-'; ?></td>
                            <td>
                                <?php if($u['is_active']): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <?php if($u['user_id'] != $user['user_id']): ?>
                                        <?php if($u['is_active']): ?>
                                            <form method="POST" onsubmit="return confirm('Are you sure you want to deactivate/delete this user?');">
                                                <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                                <button type="submit" name="delete_user" class="btn btn-danger btn-sm" title="Deactivate/Delete User">
                                                    <i class="fas fa-trash"></i> Remove
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST">
                                                <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                                <button type="submit" name="reactivate_user" class="btn btn-success btn-sm" title="Reactivate User">
                                                    <i class="fas fa-undo"></i> Reactivate
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Current User</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.user-table {
    width: 100%;
    border-collapse: collapse;
}
.user-table th, .user-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
}
.user-table th {
    background: #f8f9fa;
    font-weight: 600;
}
.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 4px;
    cursor: pointer;
}
.text-muted {
    color: #6c757d;
    font-size: 0.8rem;
}
.btn-danger {
    background: #dc3545;
    color: white;
    border: none;
}
.btn-danger:hover {
    background: #c82333;
}
.btn-success {
    background: #28a745;
    color: white;
    border: none;
}
.btn-success:hover {
    background: #218838;
}
</style>

<script>
// Toggle between course and department field based on role
document.getElementById('roleSelect').addEventListener('change', function() {
    const courseField = document.getElementById('courseField');
    const deptField = document.getElementById('deptField');
    if (this.value === 'student') {
        courseField.style.display = 'block';
        deptField.style.display = 'none';
        document.querySelector('input[name="department"]').required = false;
        document.querySelector('input[name="course"]').required = true;
    } else {
        courseField.style.display = 'none';
        deptField.style.display = 'block';
        document.querySelector('input[name="course"]').required = false;
        document.querySelector('input[name="department"]').required = true;
    }
});
</script>

<?php include '../includes/footer.php'; ?>