<?php
require_once '../includes/session.php';
requireRole('student');
$user = getCurrentUser();

// Get current year and semester (you can make this dynamic later)
$current_year = 1;
$current_semester = 1;

// Get student's program/course info
$program_query = "SELECT p.program_code, p.program_name, p.duration_years, u.year_of_study 
                  FROM users u 
                  LEFT JOIN programs p ON u.program_id = p.program_id 
                  WHERE u.user_id = {$user['user_id']}";
$program = mysqli_fetch_assoc(mysqli_query($conn, $program_query));

// Get enrolled units for current year and semester
$units_query = "SELECT 
    u.unit_id, u.unit_code, u.unit_name, u.credits,
    e.year, e.semester, e.academic_year,
    m.mark_id, m.ca_score, m.exam_score, m.total_score, m.grade, m.status as mark_status
    FROM enrollments e
    JOIN units u ON e.unit_id = u.unit_id
    LEFT JOIN marks m ON m.student_id = e.student_id AND m.unit_id = e.unit_id
    WHERE e.student_id = {$user['user_id']} 
    AND e.year = $current_year 
    AND e.semester = $current_semester
    AND e.status = 'active'
    ORDER BY u.unit_code";

$enrolled_units = mysqli_query($conn, $units_query);
$total_units = mysqli_num_rows($enrolled_units);

// Calculate statistics
$stats = [
    'total_units' => 0,
    'marks_received' => 0,
    'marks_missing' => 0,
    'marks_approved' => 0,
    'pending_approval' => 0
];

while($unit = mysqli_fetch_assoc($enrolled_units)) {
    $stats['total_units']++;
    if ($unit['mark_status'] == 'approved') {
        $stats['marks_approved']++;
        $stats['marks_received']++;
    } elseif ($unit['mark_status'] == 'submitted') {
        $stats['pending_approval']++;
        $stats['marks_received']++;
    } elseif ($unit['mark_status'] == 'draft') {
        $stats['marks_received']++;
    } else {
        $stats['marks_missing']++;
    }
}
mysqli_data_seek($enrolled_units, 0);

// Get notifications
$alerts_query = "SELECT * FROM notifications WHERE user_id = {$user['user_id']} ORDER BY created_at DESC LIMIT 5";
$alerts = mysqli_query($conn, $alerts_query);
?>
<?php include '../includes/header.php'; ?>
<div class="navbar">
    <div class="nav-brand"><i class="fas fa-chart-line"></i><h2>Marks Tracking & Alert System - CUEA</h2></div>
    <div class="nav-links">
        <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
        <a href="view_results.php"><i class="fas fa-file-alt"></i> My Results</a>
        <a href="complaints.php"><i class="fas fa-comment-dots"></i> Complaints</a>
        <div class="user-info">
            <i class="fas fa-user"></i>
            <span><?php echo htmlspecialchars($user['full_name']); ?></span>
            <small>(<?php echo $program['program_code'] ?? 'No Program'; ?> - Y<?php echo $user['year_of_study']; ?>)</small>
        </div>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="container">
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-book"></i></div>
            <div class="stat-info">
                <h4>Enrolled Units</h4>
                <div class="number"><?php echo $stats['total_units']; ?></div>
                <small>Y<?php echo $current_year; ?>S<?php echo $current_semester; ?></small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <h4>Marks Received</h4>
                <div class="number"><?php echo $stats['marks_received']; ?>/<?php echo $stats['total_units']; ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-info">
                <h4>Missing Marks</h4>
                <div class="number"><?php echo $stats['marks_missing']; ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <h4>Pending Approval</h4>
                <div class="number"><?php echo $stats['pending_approval']; ?></div>
            </div>
        </div>
    </div>

    <!-- Missing Marks Alert -->
    <?php if($stats['marks_missing'] > 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-bell"></i> 
            <strong>Alert!</strong> You have <?php echo $stats['marks_missing']; ?> unit(s) with missing marks. 
            <a href="complaints.php" style="color: #856404;">Report an issue →</a>
        </div>
    <?php endif; ?>

    <!-- Enrolled Units Card -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-book-open"></i> My Enrolled Units - Year <?php echo $current_year; ?> Semester <?php echo $current_semester; ?> (Y<?php echo $current_year; ?>S<?php echo $current_semester; ?>)</h3>
        </div>
        
        <?php if(mysqli_num_rows($enrolled_units) > 0): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Unit Code</th>
                            <th>Unit Name</th>
                            <th>Credits</th>
                            <th>CA /30</th>
                            <th>Exam /70</th>
                            <th>Total</th>
                            <th>Grade</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($unit = mysqli_fetch_assoc($enrolled_units)): ?>
                            <tr>
                                <td><strong><?php echo $unit['unit_code']; ?></strong></td>
                                <td><?php echo $unit['unit_name']; ?></td>
                                <td><?php echo $unit['credits']; ?></td>
                                <td><?php echo $unit['ca_score'] ?? '—'; ?> / 30</td>
                                <td><?php echo $unit['exam_score'] ?? '—'; ?> / 70</td>
                                <td><strong><?php echo $unit['total_score'] ?? '—'; ?></strong></td>
                                <td><?php echo $unit['grade'] ?? '—'; ?></td>
                                <td>
                                    <?php
                                    if($unit['mark_status'] == 'approved') {
                                        echo '<span class="badge badge-success">✓ Approved</span>';
                                    } elseif($unit['mark_status'] == 'submitted') {
                                        echo '<span class="badge badge-info">⏳ Pending</span>';
                                    } elseif($unit['mark_status'] == 'draft') {
                                        echo '<span class="badge badge-warning">⚠️ Partial</span>';
                                    } else {
                                        echo '<span class="badge badge-danger">❌ Missing</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if(!$unit['mark_status'] || $unit['mark_status'] == 'draft'): ?>
                                        <a href="complaints.php?unit_id=<?php echo $unit['unit_id']; ?>" class="btn btn-warning btn-sm">Report Issue</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No units enrolled for this semester.</div>
        <?php endif; ?>
    </div>

    <!-- Notifications Card -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-bell"></i> Recent Notifications</h3>
        </div>
        <?php if(mysqli_num_rows($alerts) > 0): ?>
            <?php while($alert = mysqli_fetch_assoc($alerts)): ?>
                <div class="alert alert-<?php echo $alert['type']; ?>">
                    <strong><?php echo htmlspecialchars($alert['title']); ?></strong>
                    <p><?php echo htmlspecialchars($alert['message']); ?></p>
                    <small><?php echo date('F j, Y, g:i a', strtotime($alert['created_at'])); ?></small>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No new notifications</p>
        <?php endif; ?>
    </div>
</div>

<style>
.data-table {
    width: 100%;
    border-collapse: collapse;
}
.data-table th, .data-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
}
.data-table th {
    background: #f8f9fa;
    font-weight: 600;
}
.btn-sm {
    padding: 0.2rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 4px;
}
</style>

<?php include '../includes/footer.php'; ?>