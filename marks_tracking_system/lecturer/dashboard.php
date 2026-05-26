<?php
require_once '../includes/session.php';
requireRole('lecturer');
$user = getCurrentUser();

// Get lecturer's units with student counts
$units_query = "SELECT 
    u.unit_id, u.unit_code, u.unit_name, u.year, u.semester, u.credits,
    (SELECT COUNT(*) FROM enrollments e WHERE e.unit_id = u.unit_id AND e.status = 'active') as student_count,
    (SELECT COUNT(*) FROM marks m WHERE m.unit_id = u.unit_id AND m.status != 'draft') as marks_submitted
    FROM units u
    WHERE u.lecturer_id = {$user['user_id']}
    ORDER BY u.year, u.semester, u.unit_code";

$units = mysqli_query($conn, $units_query);

// Calculate totals
$total_units = mysqli_num_rows($units);
$total_students = 0;
$submitted_marks = 0;
$missing_marks = 0;

while($unit = mysqli_fetch_assoc($units)) {
    $total_students += $unit['student_count'];
    $submitted_marks += $unit['marks_submitted'];
    $missing_marks += ($unit['student_count'] - $unit['marks_submitted']);
}
mysqli_data_seek($units, 0);

// Get recent alerts
$alerts_query = "SELECT * FROM notifications WHERE user_id = {$user['user_id']} ORDER BY created_at DESC LIMIT 5";
$alerts = mysqli_query($conn, $alerts_query);
?>
<?php include '../includes/header.php'; ?>
<div class="navbar">
    <div class="nav-brand"><i class="fas fa-chart-line"></i><h2>Marks Tracking & Alert System - CUEA</h2></div>
    <div class="nav-links">
        <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
        <a href="enter_marks.php"><i class="fas fa-edit"></i> Enter Marks</a>
        <a href="view_submissions.php"><i class="fas fa-eye"></i> View Submissions</a>
        <div class="user-info">
            <i class="fas fa-user"></i>
            <span><?php echo htmlspecialchars($user['full_name']); ?></span>
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
                <h4>My Units</h4>
                <div class="number"><?php echo $total_units; ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <h4>Total Students</h4>
                <div class="number"><?php echo $total_students; ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <h4>Marks Submitted</h4>
                <div class="number"><?php echo $submitted_marks; ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-info">
                <h4>Missing Marks</h4>
                <div class="number"><?php echo $missing_marks; ?></div>
            </div>
        </div>
    </div>
    
    <?php if($missing_marks > 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-bell"></i> You have <?php echo $missing_marks; ?> pending marks submissions. Please complete them promptly.
        </div>
    <?php endif; ?>
    
    <!-- My Units Card -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chalkboard"></i> My Teaching Units</h3>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Unit Code</th>
                        <th>Unit Name</th>
                        <th>Year/Sem</th>
                        <th>Credits</th>
                        <th>Students</th>
                        <th>Submitted</th>
                        <th>Missing</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($unit = mysqli_fetch_assoc($units)): 
                        $missing = $unit['student_count'] - $unit['marks_submitted'];
                    ?>
                        <tr>
                            <td><strong><?php echo $unit['unit_code']; ?></strong></td>
                            <td><?php echo $unit['unit_name']; ?></td>
                            <td>Y<?php echo $unit['year']; ?>S<?php echo $unit['semester']; ?></td>
                            <td><?php echo $unit['credits']; ?></td>
                            <td><?php echo $unit['student_count']; ?></td>
                            <td><?php echo $unit['marks_submitted']; ?></td>
                            <td class="<?php echo $missing > 0 ? 'text-danger' : 'text-success'; ?>"><?php echo $missing; ?></td>
                            <td>
                                <a href="enter_marks.php?unit_id=<?php echo $unit['unit_id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> Enter Marks
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Recent Alerts Card -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-bell"></i> Recent Alerts</h3>
        </div>
        <?php if(mysqli_num_rows($alerts) > 0): ?>
            <?php while($alert = mysqli_fetch_assoc($alerts)): ?>
                <div class="alert alert-<?php echo $alert['type']; ?>">
                    <strong><?php echo htmlspecialchars($alert['title']); ?></strong>
                    <p><?php echo htmlspecialchars($alert['message']); ?></p>
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
.text-danger { color: #dc3545; font-weight: bold; }
.text-success { color: #28a745; font-weight: bold; }
.btn-sm {
    padding: 0.3rem 0.6rem;
    font-size: 0.75rem;
    border-radius: 4px;
}
</style>

<?php include '../includes/footer.php'; ?>