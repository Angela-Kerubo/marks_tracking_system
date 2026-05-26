<?php
require_once '../includes/session.php';
requireRole('admin');
$user = getCurrentUser();

// Get system statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM users WHERE role = 'student') as total_students,
    (SELECT COUNT(*) FROM users WHERE role = 'lecturer') as total_lecturers,
    (SELECT COUNT(*) FROM programs) as total_programs,
    (SELECT COUNT(*) FROM units) as total_units,
    (SELECT COUNT(*) FROM enrollments WHERE status = 'active') as total_enrollments,
    (SELECT COUNT(*) FROM marks WHERE status = 'submitted') as pending_approval,
    (SELECT COUNT(*) FROM marks WHERE status = 'draft' OR (exam_score IS NULL AND status != 'approved')) as missing_marks,
    (SELECT COUNT(*) FROM complaints WHERE status = 'pending' OR status = 'reviewing') as pending_complaints,
    (SELECT COUNT(*) FROM marks WHERE status = 'approved') as approved_marks";
$stats = mysqli_fetch_assoc(mysqli_query($conn, $stats_query));

// Get recent marks submissions
$recent_marks = mysqli_query($conn, "SELECT m.*, u.full_name as student_name, u.reg_no, un.unit_code, un.unit_name 
    FROM marks m 
    JOIN users u ON m.student_id = u.user_id 
    JOIN units un ON m.unit_id = un.unit_id 
    ORDER BY m.submission_date DESC LIMIT 10");

// Get recent complaints
$recent_complaints = mysqli_query($conn, "SELECT c.*, u.full_name as student_name, u.reg_no, un.unit_code 
    FROM complaints c 
    JOIN users u ON c.student_id = u.user_id 
    JOIN units un ON c.unit_id = un.unit_id 
    ORDER BY c.created_at DESC LIMIT 10");

// Get enrollment summary by unit
$enrollment_summary = mysqli_query($conn, "SELECT un.unit_code, un.unit_name, un.year, un.semester,
    COUNT(DISTINCT e.student_id) as enrolled,
    COUNT(DISTINCT m.mark_id) as marks_submitted
    FROM units un
    LEFT JOIN enrollments e ON un.unit_id = e.unit_id AND e.status = 'active'
    LEFT JOIN marks m ON un.unit_id = m.unit_id
    GROUP BY un.unit_id
    ORDER BY un.year, un.semester, un.unit_code
    LIMIT 10");
?>
<?php include '../includes/header.php'; ?>
<div class="navbar">
    <div class="nav-brand"><i class="fas fa-chart-line"></i><h2>Marks Tracking & Alert System - CUEA</h2></div>
    <div class="nav-links">
        <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
        <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
        <a href="approve_marks.php"><i class="fas fa-check-double"></i> Approve Marks</a>
        <a href="manage_complaints.php"><i class="fas fa-ticket-alt"></i> Complaints</a>
        <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
        <div class="user-info">
            <i class="fas fa-user-shield"></i>
            <span>Admin: <?php echo htmlspecialchars($user['full_name']); ?></span>
        </div>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="container">
    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
            <div class="stat-info">
                <h4>Total Students</h4>
                <div class="number"><?php echo $stats['total_students']; ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-chalkboard-user"></i></div>
            <div class="stat-info">
                <h4>Total Lecturers</h4>
                <div class="number"><?php echo $stats['total_lecturers']; ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-book"></i></div>
            <div class="stat-info">
                <h4>Programs</h4>
                <div class="number"><?php echo $stats['total_programs']; ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
            <div class="stat-info">
                <h4>Total Units</h4>
                <div class="number"><?php echo $stats['total_units']; ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <h4>Active Enrollments</h4>
                <div class="number"><?php echo $stats['total_enrollments']; ?></div>
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

    <!-- Unit Enrollment Summary -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chart-line"></i> Unit Enrollment & Marks Summary</h3>
        </div>
        <div class="table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Unit Code</th>
                        <th>Unit Name</th>
                        <th>Year/Sem</th>
                        <th>Enrolled</th>
                        <th>Marks In</th>
                        <th>Completion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($unit = mysqli_fetch_assoc($enrollment_summary)): 
                        $completion = $unit['enrolled'] > 0 ? round(($unit['marks_submitted'] / $unit['enrolled']) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td><strong><?php echo $unit['unit_code']; ?></strong></td>
                            <td><?php echo $unit['unit_name']; ?></td>
                            <td>Y<?php echo $unit['year']; ?>S<?php echo $unit['semester']; ?></td>
                            <td><?php echo $unit['enrolled']; ?></td>
                            <td><?php echo $unit['marks_submitted']; ?></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $completion; ?>%; background: <?php echo $completion >= 80 ? '#28a745' : ($completion >= 50 ? '#ffc107' : '#dc3545'); ?>;">
                                        <span><?php echo $completion; ?>%</span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Marks Submissions -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> Recent Marks Submissions</h3>
        </div>
        <div class="table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Reg No</th>
                        <th>Student</th>
                        <th>Unit</th>
                        <th>CA</th>
                        <th>Exam</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($mark = mysqli_fetch_assoc($recent_marks)): ?>
                        <tr>
                            <td><?php echo $mark['reg_no']; ?></td>
                            <td><?php echo $mark['student_name']; ?></td>
                            <td><?php echo $mark['unit_code']; ?></td>
                            <td><?php echo $mark['ca_score']; ?>/30</td>
                            <td><?php echo $mark['exam_score'] ?? '—'; ?>/70</td>
                            <td><strong><?php echo $mark['total_score']; ?></strong></td>
                            <td>
                                <span class="badge badge-<?php echo $mark['status'] == 'approved' ? 'success' : ($mark['status'] == 'submitted' ? 'info' : 'warning'); ?>">
                                    <?php echo ucfirst($mark['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($mark['submission_date'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Complaints -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-ticket-alt"></i> Recent Complaints</h3>
        </div>
        <div class="table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reg No</th>
                        <th>Student</th>
                        <th>Unit</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($complaint = mysqli_fetch_assoc($recent_complaints)): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($complaint['created_at'])); ?></td>
                            <td><?php echo $complaint['reg_no']; ?></td>
                            <td><?php echo $complaint['student_name']; ?></td>
                            <td><?php echo $complaint['unit_code']; ?></td>
                            <td><?php echo substr($complaint['subject'], 0, 40); ?>...</td>
                            <td>
                                <?php
                                $status_class = '';
                                switch($complaint['status']) {
                                    case 'pending': $status_class = 'badge-warning'; break;
                                    case 'reviewing': $status_class = 'badge-info'; break;
                                    case 'resolved': $status_class = 'badge-success'; break;
                                    default: $status_class = 'badge-danger';
                                }
                                ?>
                                <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($complaint['status']); ?></span>
                            </td>
                            <td>
                                <a href="manage_complaints.php?id=<?php echo $complaint['complaint_id']; ?>" class="btn btn-primary btn-sm">View</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.admin-table {
    width: 100%;
    border-collapse: collapse;
}
.admin-table th, .admin-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
}
.admin-table th {
    background: #f8f9fa;
    font-weight: 600;
}
.progress-bar {
    width: 150px;
    height: 25px;
    background: #e0e0e0;
    border-radius: 12px;
    overflow: hidden;
}
.progress-fill {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.7rem;
    font-weight: bold;
}
.btn-sm {
    padding: 0.2rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 4px;
}
</style>

<?php include '../includes/footer.php'; ?>