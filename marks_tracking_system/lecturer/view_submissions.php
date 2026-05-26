<?php
require_once '../includes/session.php';
requireRole('lecturer');
$user = getCurrentUser();

// UPDATED to use 'units' instead of 'courses'
$units_query = "SELECT unit_id, unit_code, unit_name, year, semester FROM units WHERE lecturer_id = {$user['user_id']} ORDER BY year, semester, unit_code";
$units = mysqli_query($conn, $units_query);

$selected_unit = isset($_GET['unit_id']) ? intval($_GET['unit_id']) : 0;
$submissions = null;
$unit_info = null;

if ($selected_unit) {
    $unit_info_query = "SELECT unit_code, unit_name, year, semester FROM units WHERE unit_id = $selected_unit";
    $unit_info = mysqli_fetch_assoc(mysqli_query($conn, $unit_info_query));
    
    $submissions_query = "SELECT u.reg_no, u.full_name, u.email, m.ca_score, m.exam_score, m.total_score, m.grade, m.status, m.submission_date
        FROM enrollments e
        JOIN users u ON e.student_id = u.user_id
        LEFT JOIN marks m ON m.student_id = u.user_id AND m.unit_id = e.unit_id
        WHERE e.unit_id = $selected_unit AND e.status = 'active'
        ORDER BY u.reg_no";
    $submissions = mysqli_query($conn, $submissions_query);
}
?>
<?php include '../includes/header.php'; ?>
<div class="navbar">
    <div class="nav-brand"><i class="fas fa-chart-line"></i><h2>Marks Tracking & Alert System - CUEA</h2></div>
    <div class="nav-links">
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="enter_marks.php"><i class="fas fa-edit"></i> Enter Marks</a>
        <a href="view_submissions.php" class="active"><i class="fas fa-eye"></i> View Submissions</a>
        <div class="user-info"><i class="fas fa-user"></i><span><?php echo htmlspecialchars($user['full_name']); ?></span></div>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>
<div class="container">
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-search"></i> Select Unit</h3></div>
        <form method="GET" style="display: flex; gap: 1rem;">
            <div class="form-group" style="flex:1;">
                <label>Unit</label>
                <select name="unit_id" required>
                    <option value="">-- Select Unit --</option>
                    <?php while($unit = mysqli_fetch_assoc($units)): ?>
                        <option value="<?php echo $unit['unit_id']; ?>" <?php echo $selected_unit == $unit['unit_id'] ? 'selected' : ''; ?>>
                            <?php echo $unit['unit_code']; ?> - <?php echo $unit['unit_name']; ?> (Y<?php echo $unit['year']; ?>S<?php echo $unit['semester']; ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top: 1.5rem;">View</button>
        </form>
    </div>
    
    <?php if($submissions && mysqli_num_rows($submissions) > 0): ?>
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-file-alt"></i> Marks Submissions - <?php echo $unit_info['unit_code']; ?> (Y<?php echo $unit_info['year']; ?>S<?php echo $unit_info['semester']; ?>)</h3></div>
            <div class="table-container">
                <table class="data-table">
                    <thead><tr><th>Reg No</th><th>Student Name</th><th>Email</th><th>CA /30</th><th>Exam /70</th><th>Total</th><th>Grade</th><th>Status</th><th>Submission Date</th></tr></thead>
                    <tbody>
                        <?php while($sub = mysqli_fetch_assoc($submissions)): ?>
                            <tr>
                                <td><?php echo $sub['reg_no']; ?></td>
                                <td><?php echo $sub['full_name']; ?></td>
                                <td><?php echo $sub['email']; ?></td>
                                <td><?php echo $sub['ca_score'] ?? '—'; ?></td>
                                <td><?php echo $sub['exam_score'] ?? '—'; ?></td>
                                <td><strong><?php echo $sub['total_score'] ?? '—'; ?></strong></td>
                                <td><?php echo $sub['grade'] ?? '—'; ?></td>
                                <td><span class="badge badge-<?php echo ($sub['status'] ?? 'draft') == 'approved' ? 'success' : (($sub['status'] ?? 'draft') == 'submitted' ? 'info' : 'warning'); ?>"><?php echo ucfirst($sub['status'] ?? 'Draft'); ?></span></td>
                                <td><?php echo $sub['submission_date'] ?? 'Not submitted'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif($selected_unit): ?>
        <div class="alert alert-info">No students enrolled in this unit</div>
    <?php endif; ?>
</div>
<style>
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e0e0e0; }
.data-table th { background: #f8f9fa; font-weight: 600; }
</style>
<?php include '../includes/footer.php'; ?>