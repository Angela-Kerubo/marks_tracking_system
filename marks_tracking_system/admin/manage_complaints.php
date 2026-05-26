<?php
require_once '../includes/session.php';
requireRole('admin');
$user = getCurrentUser();

// Handle complaint response
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['respond'])) {
    $complaint_id = intval($_POST['complaint_id']);
    $response = mysqli_real_escape_string($conn, $_POST['response']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $query = "UPDATE complaints SET status = ?, admin_response = ?, resolved_at = NOW(), resolved_by = ? WHERE complaint_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssiii", $status, $response, $user['user_id'], $complaint_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = "Complaint updated successfully";
        $complaint_query = "SELECT student_id, subject FROM complaints WHERE complaint_id = $complaint_id";
        $complaint_data = mysqli_fetch_assoc(mysqli_query($conn, $complaint_query));
        sendNotification($complaint_data['student_id'], "Complaint Update", "Your complaint regarding '{$complaint_data['subject']}' has been {$status}.", $status == 'resolved' ? 'success' : 'info');
    }
}

// Get all complaints - UPDATED to use 'units' instead of 'courses'
$complaints_query = "SELECT c.*, u.full_name as student_name, u.reg_no, u.email, un.unit_code, un.unit_name
    FROM complaints c
    JOIN users u ON c.student_id = u.user_id
    JOIN units un ON c.unit_id = un.unit_id
    ORDER BY FIELD(c.status, 'pending', 'reviewing', 'resolved', 'rejected'), c.created_at DESC";
$complaints = mysqli_query($conn, $complaints_query);
$selected_complaint = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>
<?php include '../includes/header.php'; ?>
<div class="navbar">
    <div class="nav-brand"><i class="fas fa-chart-line"></i><h2>Marks Tracking & Alert System - CUEA</h2></div>
    <div class="nav-links">
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
        <a href="approve_marks.php"><i class="fas fa-check-double"></i> Approve Marks</a>
        <a href="manage_complaints.php" class="active"><i class="fas fa-ticket-alt"></i> Complaints</a>
        <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
        <div class="user-info"><i class="fas fa-user"></i><span>Admin: <?php echo htmlspecialchars($user['full_name']); ?></span></div>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>
<div class="container">
    <?php if(isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-ticket-alt"></i> Student Complaints</h3></div>
        <div class="table-container">
            <table class="complaint-table">
                <thead><tr><th>Date</th><th>Reg No</th><th>Student</th><th>Unit</th><th>Subject</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php while($complaint = mysqli_fetch_assoc($complaints)): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($complaint['created_at'])); ?></td>
                            <td><?php echo $complaint['reg_no']; ?></td>
                            <td><?php echo $complaint['student_name']; ?></td>
                            <td><?php echo $complaint['unit_code']; ?></td>
                            <td><?php echo substr($complaint['subject'], 0, 50); ?>...</td>
                            <td><span class="badge badge-<?php echo $complaint['status']; ?>"><?php echo ucfirst($complaint['status']); ?></span></td>
                            <td><a href="?id=<?php echo $complaint['complaint_id']; ?>" class="btn btn-primary btn-sm">View & Respond</a></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php if($selected_complaint): 
        $detail_query = "SELECT c.*, u.full_name as student_name, u.reg_no, u.email, u.phone, un.unit_code, un.unit_name
            FROM complaints c
            JOIN users u ON c.student_id = u.user_id
            JOIN units un ON c.unit_id = un.unit_id
            WHERE c.complaint_id = $selected_complaint";
        $detail = mysqli_fetch_assoc(mysqli_query($conn, $detail_query));
    ?>
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-reply"></i> Respond to Complaint</h3></div>
            <div class="alert alert-info">
                <strong>From: <?php echo $detail['student_name']; ?> (<?php echo $detail['reg_no']; ?>)</strong><br>
                <strong>Unit:</strong> <?php echo $detail['unit_code'] . ' - ' . $detail['unit_name']; ?><br>
                <strong>Subject:</strong> <?php echo htmlspecialchars($detail['subject']); ?><br>
                <strong>Description:</strong> <?php echo nl2br(htmlspecialchars($detail['description'])); ?>
            </div>
            <form method="POST">
                <input type="hidden" name="complaint_id" value="<?php echo $selected_complaint; ?>">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="pending" <?php echo $detail['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="reviewing" <?php echo $detail['status'] == 'reviewing' ? 'selected' : ''; ?>>Under Review</option>
                        <option value="resolved" <?php echo $detail['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="rejected" <?php echo $detail['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Response Message</label>
                    <textarea name="response" rows="4" placeholder="Type your response here..." required><?php echo htmlspecialchars($detail['admin_response'] ?? ''); ?></textarea>
                </div>
                <button type="submit" name="respond" class="btn btn-primary">Send Response</button>
                <a href="manage_complaints.php" class="btn btn-danger">Back</a>
            </form>
        </div>
    <?php endif; ?>
</div>
<style>
.complaint-table { width: 100%; border-collapse: collapse; }
.complaint-table th, .complaint-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e0e0e0; }
.complaint-table th { background: #f8f9fa; font-weight: 600; }
.btn-sm { padding: 0.2rem 0.5rem; font-size: 0.75rem; border-radius: 4px; }
.badge-pending { background: #fff3cd; color: #856404; padding: 0.25rem 0.5rem; border-radius: 20px; }
.badge-reviewing { background: #d1ecf1; color: #0c5460; padding: 0.25rem 0.5rem; border-radius: 20px; }
.badge-resolved { background: #d4edda; color: #155724; padding: 0.25rem 0.5rem; border-radius: 20px; }
.badge-rejected { background: #f8d7da; color: #721c24; padding: 0.25rem 0.5rem; border-radius: 20px; }
</style>
<?php include '../includes/footer.php'; ?>