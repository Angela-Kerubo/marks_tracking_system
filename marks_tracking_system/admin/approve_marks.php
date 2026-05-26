<?php
require_once '../includes/session.php';
requireRole('admin');

$user = getCurrentUser();

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve'])) {
        $mark_id = intval($_POST['mark_id']);
        $query = "UPDATE marks SET status = 'approved', approved_by = {$user['user_id']}, approved_date = NOW() WHERE mark_id = $mark_id";
        if (mysqli_query($conn, $query)) {
            $success = "Marks approved successfully";
            $student_query = "SELECT m.student_id, u.unit_code FROM marks m JOIN units u ON m.unit_id = u.unit_id WHERE m.mark_id = $mark_id";
            $student_data = mysqli_fetch_assoc(mysqli_query($conn, $student_query));
            sendNotification($student_data['student_id'], "Marks Approved", "Your marks for {$student_data['unit_code']} have been approved by the examination office.", 'success');
        } else {
            $error = "Failed to approve marks: " . mysqli_error($conn);
        }
    } elseif (isset($_POST['reject'])) {
        $mark_id = intval($_POST['mark_id']);
        $query = "UPDATE marks SET status = 'rejected' WHERE mark_id = $mark_id";
        if (mysqli_query($conn, $query)) {
            $success = "Marks rejected successfully";
        } else {
            $error = "Failed to reject marks: " . mysqli_error($conn);
        }
    }
}

// Get pending marks - UPDATED to use 'units' instead of 'courses'
$pending_query = "SELECT m.*, u.full_name as student_name, u.reg_no, un.unit_code, un.unit_name, l.full_name as lecturer_name
    FROM marks m
    JOIN users u ON m.student_id = u.user_id
    JOIN units un ON m.unit_id = un.unit_id
    JOIN users l ON m.lecturer_id = l.user_id
    WHERE m.status = 'submitted'
    ORDER BY m.submission_date DESC";
$pending_marks = mysqli_query($conn, $pending_query);

$stats_query = "SELECT 
    (SELECT COUNT(*) FROM marks WHERE status = 'submitted') as pending_count,
    (SELECT COUNT(*) FROM marks WHERE status = 'approved') as approved_count,
    (SELECT COUNT(*) FROM marks WHERE status = 'rejected') as rejected_count";
$stats = mysqli_fetch_assoc(mysqli_query($conn, $stats_query));
?>
<?php include '../includes/header.php'; ?>
<div class="navbar">
    <div class="nav-brand"><i class="fas fa-chart-line"></i><h2>Marks Tracking & Alert System - CUEA</h2></div>
    <div class="nav-links">
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
        <a href="approve_marks.php" class="active"><i class="fas fa-check-double"></i> Approve Marks</a>
        <a href="manage_complaints.php"><i class="fas fa-ticket-alt"></i> Complaints</a>
        <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
        <div class="user-info"><i class="fas fa-user"></i><span>Admin: <?php echo htmlspecialchars($user['full_name']); ?></span></div>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="container">
    <?php if(isset($success)): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>
    <?php if(isset($error)): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-info"><h4>Pending Approval</h4><div class="number"><?php echo $stats['pending_count']; ?></div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info"><h4>Approved Marks</h4><div class="number"><?php echo $stats['approved_count']; ?></div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
            <div class="stat-info"><h4>Rejected Marks</h4><div class="number"><?php echo $stats['rejected_count']; ?></div></div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-check-circle"></i> Pending Marks Approval</h3></div>
        <?php if(mysqli_num_rows($pending_marks) > 0): ?>
            <div class="table-container">
                <table class="approval-table">
                    <thead><tr><th>Reg No</th><th>Student</th><th>Unit Code</th><th>Unit Name</th><th>CA</th><th>Exam</th><th>Total</th><th>Grade</th><th>Lecturer</th><th>Date</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php while($mark = mysqli_fetch_assoc($pending_marks)): ?>
                            <tr>
                                <td><?php echo $mark['reg_no']; ?></td>
                                <td><?php echo $mark['student_name']; ?></td>
                                <td><strong><?php echo $mark['unit_code']; ?></strong></td>
                                <td><?php echo $mark['unit_name']; ?></td>
                                <td><?php echo $mark['ca_score']; ?>/30</td>
                                <td><?php echo $mark['exam_score']; ?>/70</td>
                                <td><strong><?php echo $mark['total_score']; ?></strong></td>
                                <td><?php echo $mark['grade']; ?></td>
                                <td><?php echo $mark['lecturer_name']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($mark['submission_date'])); ?></td>
                                <td>
                                    <form method="POST" style="display: inline-flex; gap: 0.5rem;">
                                        <input type="hidden" name="mark_id" value="<?php echo $mark['mark_id']; ?>">
                                        <button type="submit" name="approve" class="btn btn-success btn-sm" onclick="return confirm('Approve these marks?')">Approve</button>
                                        <button type="submit" name="reject" class="btn btn-danger btn-sm" onclick="return confirm('Reject these marks?')">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No pending marks for approval.</div>
        <?php endif; ?>
    </div>
</div>
<style>
.approval-table { width: 100%; border-collapse: collapse; }
.approval-table th, .approval-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e0e0e0; }
.approval-table th { background: #f8f9fa; font-weight: 600; }
.btn-sm { padding: 0.3rem 0.6rem; font-size: 0.75rem; border-radius: 4px; }
.btn-success { background: #28a745; color: white; border: none; cursor: pointer; }
.btn-danger { background: #dc3545; color: white; border: none; cursor: pointer; }
</style>
<?php include '../includes/footer.php'; ?>