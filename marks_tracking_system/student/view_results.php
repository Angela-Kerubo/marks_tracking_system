<?php
require_once '../includes/session.php';
requireRole('student');
$user = getCurrentUser();

$current_year = 1;
$current_semester = 1;

// UPDATED to use 'units' instead of 'courses'
$query = "SELECT 
    un.unit_code, un.unit_name, un.credits, un.year, un.semester,
    m.ca_score, m.exam_score, m.total_score, m.grade, m.status,
    CASE 
        WHEN m.status = 'approved' THEN 'completed'
        WHEN m.status = 'submitted' THEN 'pending'
        WHEN m.status = 'draft' THEN 'partial'
        ELSE 'missing'
    END as mark_status
    FROM enrollments e
    JOIN units un ON e.unit_id = un.unit_id
    LEFT JOIN marks m ON m.student_id = e.student_id AND m.unit_id = e.unit_id
    WHERE e.student_id = {$user['user_id']} 
    AND e.year = $current_year 
    AND e.semester = $current_semester
    AND e.status = 'active'
    ORDER BY un.unit_code";

$results = mysqli_query($conn, $query);
?>
<?php include '../includes/header.php'; ?>
<div class="navbar">
    <div class="nav-brand"><i class="fas fa-chart-line"></i><h2>Marks Tracking & Alert System - CUEA</h2></div>
    <div class="nav-links">
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="view_results.php" class="active"><i class="fas fa-file-alt"></i> My Results</a>
        <a href="complaints.php"><i class="fas fa-comment-dots"></i> Complaints</a>
        <div class="user-info"><i class="fas fa-user"></i><span><?php echo htmlspecialchars($user['full_name']); ?></span></div>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>
<div class="container">
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-file-alt"></i> Academic Results - Year <?php echo $current_year; ?> Semester <?php echo $current_semester; ?></h3></div>
        <div class="table-container">
            <table class="results-table">
                <thead><tr><th>Unit Code</th><th>Unit Name</th><th>Credits</th><th>CA /30</th><th>Exam /70</th><th>Total</th><th>Grade</th><th>Status</th></tr></thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($results)): ?>
                        <tr>
                            <td><strong><?php echo $row['unit_code']; ?></strong></td>
                            <td><?php echo $row['unit_name']; ?></td>
                            <td><?php echo $row['credits']; ?></td>
                            <td><?php echo $row['ca_score'] ?? '—'; ?></td>
                            <td><?php echo $row['exam_score'] ?? '—'; ?></td>
                            <td><strong><?php echo $row['total_score'] ?? '—'; ?></strong></td>
                            <td><?php echo $row['grade'] ?? '—'; ?></td>
                            <td>
                                <?php
                                switch($row['mark_status']) {
                                    case 'completed': echo '<span class="badge badge-success">Approved</span>'; break;
                                    case 'pending': echo '<span class="badge badge-info">Pending</span>'; break;
                                    case 'partial': echo '<span class="badge badge-warning">Partial</span>'; break;
                                    default: echo '<span class="badge badge-danger">Missing</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<style>
.results-table { width: 100%; border-collapse: collapse; }
.results-table th, .results-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e0e0e0; }
.results-table th { background: #f8f9fa; font-weight: 600; }
</style>
<?php include '../includes/footer.php'; ?>