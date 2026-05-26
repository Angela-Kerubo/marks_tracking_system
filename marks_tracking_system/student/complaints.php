<?php
require_once '../includes/session.php';
requireRole('student');
$user = getCurrentUser();

// Handle complaint submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_complaint'])) {
    $unit_id = mysqli_real_escape_string($conn, $_POST['unit_id']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    $query = "INSERT INTO complaints (student_id, unit_id, subject, description, status) VALUES (?, ?, ?, ?, 'pending')";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iiss", $user['user_id'], $unit_id, $subject, $description);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = "Complaint submitted successfully!";
        sendNotification(1, "New Complaint", "Student {$user['full_name']} filed a new complaint: $subject", 'info');
    } else {
        $error = "Failed to submit complaint";
    }
}

// Get student's units
$units_query = "SELECT un.unit_id, un.unit_code, un.unit_name 
    FROM enrollments e 
    JOIN units un ON e.unit_id = un.unit_id 
    WHERE e.student_id = {$user['user_id']} AND e.status = 'active'
    ORDER BY un.unit_code";
$units = mysqli_query($conn, $units_query);

// Get existing complaints
$complaints_query = "SELECT c.*, un.unit_code, un.unit_name 
    FROM complaints c 
    JOIN units un ON c.unit_id = un.unit_id 
    WHERE c.student_id = {$user['user_id']} 
    ORDER BY c.created_at DESC";
$complaints = mysqli_query($conn, $complaints_query);
?>
<?php include '../includes/header.php'; ?>
<div class="navbar">
    <div class="nav-brand"><i class="fas fa-chart-line"></i><h2>Marks Tracking & Alert System - CUEA</h2></div>
    <div class="nav-links">
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="view_results.php"><i class="fas fa-file-alt"></i> My Results</a>
        <a href="complaints.php" class="active"><i class="fas fa-comment-dots"></i> Complaints</a>
        <div class="user-info"><i class="fas fa-user"></i><span><?php echo htmlspecialchars($user['full_name']); ?></span></div>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="container">
    <?php if(isset($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <!-- File a Complaint Card -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-plus-circle"></i> File a Complaint</h3>
        </div>
        <form method="POST" action="">
            <div class="form-group">
                <label><i class="fas fa-book"></i> Select Unit</label>
                <select name="unit_id" required>
                    <option value="">-- Select Unit --</option>
                    <?php while($unit = mysqli_fetch_assoc($units)): ?>
                        <option value="<?php echo $unit['unit_id']; ?>">
                            <?php echo $unit['unit_code'] . ' - ' . $unit['unit_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-tag"></i> Subject</label>
                <input type="text" name="subject" class="form-control" placeholder="Brief subject of your complaint" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-align-left"></i> Description</label>
                <textarea name="description" rows="5" class="form-control" placeholder="Please provide detailed information about your missing marks issue..." required></textarea>
            </div>
            
            <button type="submit" name="submit_complaint" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Submit Complaint
            </button>
        </form>
    </div>
    
    <!-- My Complaints Card -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> My Complaints</h3>
        </div>
        
        <?php if(mysqli_num_rows($complaints) > 0): ?>
            <div class="table-container">
                <table class="complaints-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Unit</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Response</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($complaint = mysqli_fetch_assoc($complaints)): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($complaint['created_at'])); ?></td>
                                <td><strong><?php echo $complaint['unit_code']; ?></strong><br>
                                    <small><?php echo $complaint['unit_name']; ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($complaint['subject']); ?></td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    switch($complaint['status']) {
                                        case 'pending':
                                            $status_class = 'badge-warning';
                                            break;
                                        case 'reviewing':
                                            $status_class = 'badge-info';
                                            break;
                                        case 'resolved':
                                            $status_class = 'badge-success';
                                            break;
                                        case 'rejected':
                                            $status_class = 'badge-danger';
                                            break;
                                        default:
                                            $status_class = 'badge-secondary';
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo ucfirst($complaint['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if($complaint['admin_response']) {
                                        echo '<div class="response-text">' . htmlspecialchars($complaint['admin_response']) . '</div>';
                                    } else {
                                        echo '<span class="text-muted">Awaiting response...</span>';
                                    }
                                    ?>
                                    <br>
                                    <small class="text-muted">
                                        <?php if($complaint['resolved_at']): ?>
                                            Resolved: <?php echo date('d/m/Y', strtotime($complaint['resolved_at'])); ?>
                                        <?php endif; ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                You haven't filed any complaints yet. Use the form above to report missing marks.
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.complaints-table {
    width: 100%;
    border-collapse: collapse;
}

.complaints-table th,
.complaints-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
    vertical-align: top;
}

.complaints-table th {
    background: #f8f9fa;
    font-weight: 600;
}

.complaints-table tr:hover {
    background: #f8f9fa;
}

.response-text {
    background: #f0f0f0;
    padding: 0.5rem;
    border-radius: 8px;
    margin-top: 0.25rem;
    font-size: 0.9rem;
}

.text-muted {
    color: #6c757d;
    font-size: 0.75rem;
}

textarea.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-family: inherit;
    font-size: 1rem;
    resize: vertical;
}

textarea.form-control:focus {
    outline: none;
    border-color: #667eea;
}

input.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
}

input.form-control:focus {
    outline: none;
    border-color: #667eea;
}

select {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
    background: white;
    cursor: pointer;
}

select:focus {
    outline: none;
    border-color: #667eea;
}
</style>

<?php include '../includes/footer.php'; ?>