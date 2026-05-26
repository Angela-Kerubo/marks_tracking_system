<?php
require_once '../includes/session.php';
requireRole('admin');
$user = getCurrentUser();

$report_type = isset($_GET['type']) ? $_GET['type'] : 'marks';

// Marks Report - UPDATED to use 'units' instead of 'courses'
if ($report_type == 'marks') {
    $report_query = "SELECT un.unit_code, un.unit_name, un.year, un.semester,
        COUNT(DISTINCT e.student_id) as enrolled,
        COUNT(DISTINCT m.mark_id) as marks_submitted,
        SUM(CASE WHEN m.status = 'approved' THEN 1 ELSE 0 END) as approved
        FROM units un
        LEFT JOIN enrollments e ON un.unit_id = e.unit_id
        LEFT JOIN marks m ON un.unit_id = m.unit_id
        GROUP BY un.unit_id
        ORDER BY un.year, un.semester, un.unit_code";
    $report_data = mysqli_query($conn, $report_query);
}
// Complaints Report - UPDATED to use 'units' instead of 'courses'
elseif ($report_type == 'complaints') {
    $report_query = "SELECT un.unit_code, un.unit_name,
        COUNT(DISTINCT comp.complaint_id) as total_complaints,
        SUM(CASE WHEN comp.status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN comp.status = 'resolved' THEN 1 ELSE 0 END) as resolved
        FROM units un
        LEFT JOIN complaints comp ON un.unit_id = comp.unit_id
        GROUP BY un.unit_id
        ORDER BY total_complaints DESC";
    $report_data = mysqli_query($conn, $report_query);
}
// Lecturer Performance Report
else {
    $report_query = "SELECT u.full_name as lecturer_name, u.email,
        COUNT(DISTINCT un.unit_id) as units,
        COUNT(DISTINCT m.mark_id) as marks_submitted
        FROM users u
        LEFT JOIN units un ON u.user_id = un.lecturer_id
        LEFT JOIN marks m ON un.unit_id = m.unit_id
        WHERE u.role = 'lecturer'
        GROUP BY u.user_id
        ORDER BY marks_submitted DESC";
    $report_data = mysqli_query($conn, $report_query);
}
?>
<?php include '../includes/header.php'; ?>
<div class="navbar">
    <div class="nav-brand"><i class="fas fa-chart-line"></i><h2>Marks Tracking & Alert System - CUEA</h2></div>
    <div class="nav-links">
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
        <a href="approve_marks.php"><i class="fas fa-check-double"></i> Approve Marks</a>
        <a href="manage_complaints.php"><i class="fas fa-ticket-alt"></i> Complaints</a>
        <a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a>
        <div class="user-info"><i class="fas fa-user"></i><span>Admin: <?php echo htmlspecialchars($user['full_name']); ?></span></div>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>
<div class="container">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chart-bar"></i> Analytics Reports</h3>
            <div>
                <a href="?type=marks" class="btn btn-<?php echo $report_type == 'marks' ? 'primary' : 'secondary'; ?>">Marks Report</a>
                <a href="?type=complaints" class="btn btn-<?php echo $report_type == 'complaints' ? 'primary' : 'secondary'; ?>">Complaints Report</a>
                <a href="?type=lecturers" class="btn btn-<?php echo $report_type == 'lecturers' ? 'primary' : 'secondary'; ?>">Lecturer Performance</a>
            </div>
        </div>
        
        <?php if($report_type == 'marks'): ?>
            <div class="table-container">
                <table class="report-table">
                    <thead><tr><th>Unit Code</th><th>Unit Name</th><th>Year/Sem</th><th>Enrolled</th><th>Marks Submitted</th><th>Approved</th><th>Missing</th><th>Rate</th></tr></thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($report_data)): 
                            $missing = $row['enrolled'] - $row['marks_submitted'];
                            $completion = $row['enrolled'] > 0 ? round(($row['marks_submitted'] / $row['enrolled']) * 100, 1) : 0;
                        ?>
                            <tr>
                                <td><?php echo $row['unit_code']; ?></td>
                                <td><?php echo $row['unit_name']; ?></td>
                                <td>Y<?php echo $row['year']; ?>S<?php echo $row['semester']; ?></td>
                                <td><?php echo $row['enrolled']; ?></td>
                                <td><?php echo $row['marks_submitted']; ?></td>
                                <td><?php echo $row['approved']; ?></td>
                                <td class="<?php echo $missing > 0 ? 'text-danger' : 'text-success'; ?>"><?php echo $missing; ?></td>
                                <td><span class="badge badge-<?php echo $completion >= 80 ? 'success' : ($completion >= 50 ? 'warning' : 'danger'); ?>"><?php echo $completion; ?>%</span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif($report_type == 'complaints'): ?>
            <div class="table-container">
                <table class="report-table">
                    <thead><tr><th>Unit Code</th><th>Unit Name</th><th>Total</th><th>Pending</th><th>Resolved</th><th>Rate</th></tr></thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($report_data)): 
                            $resolution = $row['total_complaints'] > 0 ? round(($row['resolved'] / $row['total_complaints']) * 100, 1) : 0;
                        ?>
                            <tr>
                                <td><?php echo $row['unit_code']; ?></td>
                                <td><?php echo $row['unit_name']; ?></td>
                                <td><?php echo $row['total_complaints']; ?></td>
                                <td><span class="badge badge-warning"><?php echo $row['pending']; ?></span></td>
                                <td><span class="badge badge-success"><?php echo $row['resolved']; ?></span></td>
                                <td><?php echo $resolution; ?>%</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="report-table">
                    <thead><tr><th>Lecturer</th><th>Email</th><th>Units</th><th>Marks Submitted</th></tr></thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($report_data)): ?>
                            <tr>
                                <td><?php echo $row['lecturer_name']; ?></td>
                                <td><?php echo $row['email']; ?></td>
                                <td><?php echo $row['units']; ?></td>
                                <td><?php echo $row['marks_submitted']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<style>
.report-table { width: 100%; border-collapse: collapse; }
.report-table th, .report-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e0e0e0; }
.report-table th { background: #f8f9fa; font-weight: 600; }
.text-danger { color: #dc3545; font-weight: bold; }
.text-success { color: #28a745; font-weight: bold; }
</style>
<?php include '../includes/footer.php'; ?>