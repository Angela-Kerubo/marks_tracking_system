<?php
require_once '../includes/session.php';
requireRole('lecturer');
$user = getCurrentUser();

$unit_id = isset($_GET['unit_id']) ? intval($_GET['unit_id']) : 0;

// Verify unit belongs to lecturer
$unit_query = "SELECT * FROM units WHERE unit_id = $unit_id AND lecturer_id = {$user['user_id']}";
$unit_result = mysqli_query($conn, $unit_query);
$unit = mysqli_fetch_assoc($unit_result);

if (!$unit && $unit_id != 0) {
    header("Location: dashboard.php");
    exit();
}

// Get all units for dropdown
$all_units_query = "SELECT unit_id, unit_code, unit_name, year, semester FROM units WHERE lecturer_id = {$user['user_id']} ORDER BY year, semester, unit_code";
$all_units = mysqli_query($conn, $all_units_query);

// Handle CSV Upload
$upload_success = '';
$upload_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['marks_file']) && $_FILES['marks_file']['error'] == 0) {
    $file = $_FILES['marks_file'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if ($file_ext == 'csv') {
        $handle = fopen($file['tmp_name'], 'r');
        $header = fgetcsv($handle);
        
        $upload_count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 4) {
                $reg_no = trim($row[0]);
                $student_name = trim($row[1]);
                $ca_score = floatval($row[2]);
                $exam_score = !empty($row[3]) ? floatval($row[3]) : null;
                
                $student_query = "SELECT user_id FROM users WHERE reg_no = ? AND role = 'student'";
                $stmt = mysqli_prepare($conn, $student_query);
                mysqli_stmt_bind_param($stmt, "s", $reg_no);
                mysqli_stmt_execute($stmt);
                $student_result = mysqli_stmt_get_result($stmt);
                $student = mysqli_fetch_assoc($student_result);
                
                if ($student) {
                    $total = $ca_score + ($exam_score ?? 0);
                    $grade = getGrade($total);
                    $status = 'draft';
                    
                    $check_query = "SELECT mark_id FROM marks WHERE student_id = ? AND unit_id = ?";
                    $stmt = mysqli_prepare($conn, $check_query);
                    mysqli_stmt_bind_param($stmt, "ii", $student['user_id'], $unit_id);
                    mysqli_stmt_execute($stmt);
                    $check_result = mysqli_stmt_get_result($stmt);
                    
                    if (mysqli_num_rows($check_result) > 0) {
                        $update_query = "UPDATE marks SET ca_score = ?, exam_score = ?, total_score = ?, grade = ?, status = ?, submission_date = NOW() WHERE student_id = ? AND unit_id = ?";
                        $stmt = mysqli_prepare($conn, $update_query);
                        mysqli_stmt_bind_param($stmt, "dddssii", $ca_score, $exam_score, $total, $grade, $status, $student['user_id'], $unit_id);
                    } else {
                        $insert_query = "INSERT INTO marks (student_id, unit_id, lecturer_id, ca_score, exam_score, total_score, grade, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = mysqli_prepare($conn, $insert_query);
                        mysqli_stmt_bind_param($stmt, "iiidddss", $student['user_id'], $unit_id, $user['user_id'], $ca_score, $exam_score, $total, $grade, $status);
                    }
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $upload_count++;
                    }
                }
            }
        }
        fclose($handle);
        $upload_success = "Successfully uploaded marks for $upload_count student(s)";
    } else {
        $upload_error = "Invalid file type. Please upload CSV file.";
    }
}

// Handle manual marks submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['save_draft']) || isset($_POST['submit_approval']))) {
    $action = isset($_POST['submit_approval']) ? 'submitted' : 'draft';
    
    $student_ids = $_POST['student_id'] ?? [];
    $ca_scores = $_POST['ca_score'] ?? [];
    $exam_scores = $_POST['exam_score'] ?? [];
    
    $success_count = 0;
    $error_count = 0;
    
    for ($i = 0; $i < count($student_ids); $i++) {
        $student_id = intval($student_ids[$i]);
        $ca = !empty($ca_scores[$i]) ? floatval($ca_scores[$i]) : null;
        $exam = !empty($exam_scores[$i]) ? floatval($exam_scores[$i]) : null;
        
        if ($ca !== null || $exam !== null) {
            $total = ($ca ?? 0) + ($exam ?? 0);
            $grade = getGrade($total);
            $status = $action;
            
            $check_query = "SELECT mark_id FROM marks WHERE student_id = $student_id AND unit_id = $unit_id";
            $check_result = mysqli_query($conn, $check_query);
            
            if (mysqli_num_rows($check_result) > 0) {
                $update_query = "UPDATE marks SET ca_score = ?, exam_score = ?, total_score = ?, grade = ?, status = ?, submission_date = NOW() WHERE student_id = ? AND unit_id = ?";
                $stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($stmt, "dddssii", $ca, $exam, $total, $grade, $status, $student_id, $unit_id);
            } else {
                $insert_query = "INSERT INTO marks (student_id, unit_id, lecturer_id, ca_score, exam_score, total_score, grade, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($stmt, "iiidddss", $student_id, $unit_id, $user['user_id'], $ca, $exam, $total, $grade, $status);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                $success_count++;
                if ($status == 'submitted') {
                    sendNotification($student_id, "Marks Submitted", "Your marks for {$unit['unit_code']} have been submitted. CA: $ca, Exam: " . ($exam ?? 'Pending'), 'info');
                }
            } else {
                $error_count++;
            }
        }
    }
    
    if ($success_count > 0) {
        $success = "Successfully saved marks for $success_count student(s)";
        if ($error_count > 0) $success .= " ($error_count failed)";
    } else {
        $error = "Failed to save marks";
    }
}

// Get enrolled students
$students = [];
if ($unit_id) {
    $students_query = "SELECT u.user_id, u.reg_no, u.full_name, u.email, m.mark_id, m.ca_score, m.exam_score, m.status, m.grade
        FROM enrollments e
        JOIN users u ON e.student_id = u.user_id
        LEFT JOIN marks m ON m.student_id = u.user_id AND m.unit_id = e.unit_id
        WHERE e.unit_id = $unit_id AND e.status = 'active'
        ORDER BY u.reg_no";
    $students_result = mysqli_query($conn, $students_query);
    $students = [];
    while ($row = mysqli_fetch_assoc($students_result)) {
        $students[] = $row;
    }
}

function getGrade($score) {
    if ($score >= 70) return 'A';
    if ($score >= 60) return 'B';
    if ($score >= 50) return 'C';
    if ($score >= 40) return 'D';
    return 'F';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Marks - MTAS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; color: #1a1a2e; }
        .header { background: white; border-bottom: 1px solid #e8e8e8; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .logo h1 { font-size: 1.5rem; font-weight: 700; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .logo p { font-size: 0.75rem; color: #666; }
        .user-menu { display: flex; align-items: center; gap: 1rem; }
        .user-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; }
        .app-container { display: flex; min-height: calc(100vh - 73px); }
        .sidebar { width: 260px; background: white; border-right: 1px solid #e8e8e8; padding: 1.5rem 0; }
        .semester-badge { padding: 0 1.5rem; margin-bottom: 1.5rem; }
        .semester-badge span { background: #f0f0f0; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; color: #666; }
        .nav-item { padding: 0.75rem 1.5rem; display: flex; align-items: center; gap: 0.75rem; color: #666; text-decoration: none; border-left: 3px solid transparent; }
        .nav-item:hover, .nav-item.active { background: #f5f7fa; color: #667eea; border-left-color: #667eea; }
        .nav-item i { width: 20px; }
        .nav-group { margin-top: 1rem; }
        .nav-group-title { padding: 0.5rem 1.5rem; font-size: 0.7rem; text-transform: uppercase; color: #999; }
        .main-content { flex: 1; padding: 2rem; overflow-x: auto; }
        .page-title { margin-bottom: 1.5rem; }
        .page-title h2 { font-size: 1.5rem; font-weight: 600; }
        .page-title p { color: #666; font-size: 0.875rem; }
        .card { background: white; border-radius: 16px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e8e8e8; }
        .card-header h3 { font-size: 1rem; font-weight: 600; }
        .unit-select { width: 100%; padding: 0.875rem; border: 2px solid #e8e8e8; border-radius: 12px; font-size: 1rem; cursor: pointer; }
        .unit-select:focus { outline: none; border-color: #667eea; }
        .upload-area { border: 2px dashed #e8e8e8; border-radius: 16px; padding: 2rem; text-align: center; cursor: pointer; background: #fafafa; }
        .upload-area:hover { border-color: #667eea; background: #f5f7fa; }
        .upload-area i { font-size: 2.5rem; color: #667eea; margin-bottom: 1rem; }
        .or-divider { text-align: center; margin: 1.5rem 0; position: relative; }
        .or-divider::before { content: ''; position: absolute; left: 0; top: 50%; width: 45%; height: 1px; background: #e8e8e8; }
        .or-divider::after { content: ''; position: absolute; right: 0; top: 50%; width: 45%; height: 1px; background: #e8e8e8; }
        .or-divider span { background: white; padding: 0 1rem; color: #999; }
        .marks-table { width: 100%; border-collapse: collapse; }
        .marks-table th { text-align: left; padding: 1rem; background: #f8f9fa; font-weight: 600; border-bottom: 2px solid #e8e8e8; }
        .marks-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #e8e8e8; }
        .marks-table input { width: 100px; padding: 0.5rem; border: 1px solid #e8e8e8; border-radius: 8px; text-align: center; }
        .marks-table input.error { border-color: #dc2626; background: #fee2e2; }
        .selection-bar { background: #f8f9fa; padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1rem; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
        .selection-info { display: flex; align-items: center; gap: 1rem; }
        .batch-input { display: flex; align-items: center; gap: 0.5rem; }
        .batch-input input { width: 80px; padding: 0.5rem; border: 1px solid #e8e8e8; border-radius: 8px; }
        .action-buttons { display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e8e8e8; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 12px; font-size: 0.875rem; font-weight: 500; cursor: pointer; }
        .btn-draft { background: #f0f0f0; color: #666; }
        .btn-submit { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .btn-primary { background: #667eea; color: white; }
        .alert { padding: 1rem; border-radius: 12px; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.75rem; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .status-badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 20px; font-size: 0.7rem; }
        .status-draft { background: #fff3cd; color: #856404; }
        .status-submitted { background: #cff4fc; color: #055160; }
        .status-approved { background: #d4edda; color: #155724; }
        @media (max-width: 768px) { .sidebar { display: none; } .main-content { padding: 1rem; } }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo"><h1>MTAS</h1><p>Semester 1 · 2025/2026</p></div>
        <div class="user-menu">
            <span><?php echo htmlspecialchars($user['full_name']); ?></span>
            <div class="user-avatar"><i class="fas fa-user"></i></div>
            <a href="logout.php" style="color: #dc3545;"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>
    
    <div class="app-container">
        <aside class="sidebar">
            <div class="semester-badge"><span>Semester 1 · 2025/2026</span></div>
            <div class="nav-group">
                <div class="nav-group-title">OVERVIEW</div>
                <a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="#" class="nav-item"><i class="fas fa-book"></i> My Units</a>
            </div>
            <div class="nav-group">
                <div class="nav-group-title">MARKS</div>
                <a href="enter_marks.php<?php echo $unit_id ? "?unit_id=$unit_id" : ''; ?>" class="nav-item active"><i class="fas fa-upload"></i> Submit Marks</a>
                <a href="view_submissions.php" class="nav-item"><i class="fas fa-history"></i> Submission History</a>
                <a href="#" class="nav-item"><i class="fas fa-bell"></i> Alerts</a>
            </div>
        </aside>
        
        <main class="main-content">
            <?php if(isset($upload_success) && $upload_success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $upload_success; ?></div>
            <?php endif; ?>
            <?php if(isset($upload_error) && $upload_error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $upload_error; ?></div>
            <?php endif; ?>
            <?php if(isset($success)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="page-title">
                <h2>Submit Marks</h2>
                <p>Upload or enter marks for your units</p>
            </div>
            
            <!-- Unit Selection -->
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-book-open"></i> SELECT UNIT</h3></div>
                <form method="GET" action="">
                    <select name="unit_id" class="unit-select" onchange="this.form.submit()">
                        <option value="">-- Select a unit --</option>
                        <?php while($u = mysqli_fetch_assoc($all_units)): ?>
                            <option value="<?php echo $u['unit_id']; ?>" <?php echo $unit_id == $u['unit_id'] ? 'selected' : ''; ?>>
                                <?php echo $u['unit_code']; ?> — <?php echo $u['unit_name']; ?> (Y<?php echo $u['year']; ?>S<?php echo $u['semester']; ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </form>
            </div>
            
            <?php if($unit_id && $unit && !empty($students)): ?>
            
            <!-- CSV Upload -->
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-cloud-upload-alt"></i> UPLOAD MARKS SHEET (CSV)</h3></div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="upload-area" onclick="document.getElementById('csvFile').click()">
                        <i class="fas fa-file-csv"></i>
                        <p>Drag & drop CSV file, or click to browse</p>
                        <small>Format: Reg No, Student Name, CA Score (/30), Exam Score (/70)</small>
                        <input type="file" name="marks_file" id="csvFile" accept=".csv" style="display: none;" onchange="this.form.submit()">
                    </div>
                </form>
                
                <div class="or-divider"><span>OR ENTER MANUALLY</span></div>
                
                <!-- Batch Operations -->
                <div class="selection-bar">
                    <div class="selection-info">
                        <input type="checkbox" id="selectAll"> <label for="selectAll">Select All Students</label>
                        <span id="selectedCount">0</span> selected
                    </div>
                    <div class="batch-input">
                        <span>Apply to selected:</span>
                        <input type="number" id="batchCA" placeholder="CA (/30)" min="0" max="30" step="0.5">
                        <input type="number" id="batchExam" placeholder="Exam (/70)" min="0" max="70" step="0.5">
                        <button type="button" class="btn btn-primary" onclick="applyBatch()" style="padding: 0.5rem 1rem;">Apply</button>
                    </div>
                </div>
                
                <!-- Marks Entry Table -->
                <form method="POST" id="marksForm">
                    <div style="overflow-x: auto;">
                        <table class="marks-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;"><input type="checkbox" id="selectAllTable"></th>
                                    <th>REG. NO.</th>
                                    <th>STUDENT NAME</th>
                                    <th>CAT (/30)</th>
                                    <th>EXAM (/70)</th>
                                    <th>Total</th>
                                    <th>Grade</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($students as $student): 
                                    $ca = $student['ca_score'] ?? '';
                                    $exam = $student['exam_score'] ?? '';
                                    $total = ($ca && $exam) ? $ca + $exam : '';
                                    $grade = $student['grade'] ?? '';
                                    $status = $student['status'] ?? 'draft';
                                    $status_class = $status == 'draft' ? 'status-draft' : ($status == 'submitted' ? 'status-submitted' : 'status-approved');
                                    $status_text = $status == 'draft' ? 'Draft' : ($status == 'submitted' ? 'Submitted' : 'Approved');
                                ?>
                                    <tr data-student-id="<?php echo $student['user_id']; ?>">
                                        <td><input type="checkbox" class="student-checkbox"></td>
                                        <td><?php echo $student['reg_no']; ?></td>
                                        <td><?php echo $student['full_name']; ?></td>
                                        <td>
                                            <input type="hidden" name="student_id[]" value="<?php echo $student['user_id']; ?>">
                                            <input type="number" name="ca_score[]" step="0.5" min="0" max="30" value="<?php echo $ca; ?>" class="ca-input" placeholder="—" onchange="updateTotal(this)">
                                        </td>
                                        <td>
                                            <input type="number" name="exam_score[]" step="0.5" min="0" max="70" value="<?php echo $exam; ?>" class="exam-input" placeholder="—" onchange="updateTotal(this)">
                                        </td>
                                        <td class="total-cell"><?php echo $total ?: '—'; ?></td>
                                        <td class="grade-cell"><?php echo $grade ?: '—'; ?></td>
                                        <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" name="save_draft" class="btn btn-draft"><i class="fas fa-save"></i> Save Draft</button>
                        <button type="submit" name="submit_approval" class="btn btn-submit"><i class="fas fa-paper-plane"></i> Submit for Approval</button>
                    </div>
                </form>
            </div>
            
            <?php elseif($unit_id && empty($students)): ?>
                <div class="alert alert-info">No students enrolled in this unit yet.</div>
            <?php elseif($unit_id && !$unit): ?>
                <div class="alert alert-danger">Invalid unit selected.</div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
        function updateTotal(input) {
            const row = input.closest('tr');
            const ca = parseFloat(row.querySelector('.ca-input').value) || 0;
            const exam = parseFloat(row.querySelector('.exam-input').value) || 0;
            const total = ca + exam;
            row.querySelector('.total-cell').textContent = total.toFixed(1);
            if (total >= 70) row.querySelector('.grade-cell').textContent = 'A';
            else if (total >= 60) row.querySelector('.grade-cell').textContent = 'B';
            else if (total >= 50) row.querySelector('.grade-cell').textContent = 'C';
            else if (total >= 40) row.querySelector('.grade-cell').textContent = 'D';
            else row.querySelector('.grade-cell').textContent = 'F';
        }
        
        const selectAll = document.getElementById('selectAllTable');
        const checkboxes = document.querySelectorAll('.student-checkbox');
        
        function updateSelectedCount() {
            const checked = document.querySelectorAll('.student-checkbox:checked').length;
            document.getElementById('selectedCount').textContent = checked;
        }
        
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
                updateSelectedCount();
            });
        }
        checkboxes.forEach(cb => cb.addEventListener('change', updateSelectedCount));
        
        function applyBatch() {
            const batchCA = document.getElementById('batchCA').value;
            const batchExam = document.getElementById('batchExam').value;
            const selected = document.querySelectorAll('.student-checkbox:checked');
            selected.forEach(cb => {
                const row = cb.closest('tr');
                if (batchCA !== '') {
                    row.querySelector('.ca-input').value = parseFloat(batchCA);
                    updateTotal(row.querySelector('.ca-input'));
                }
                if (batchExam !== '') {
                    row.querySelector('.exam-input').value = parseFloat(batchExam);
                    updateTotal(row.querySelector('.exam-input'));
                }
            });
            alert(`Applied to ${selected.length} student(s)`);
        }
        
        document.getElementById('marksForm')?.addEventListener('submit', function(e) {
            const isSubmit = e.submitter && e.submitter.name === 'submit_approval';
            if (!isSubmit) return;
            let missing = 0;
            document.querySelectorAll('.exam-input').forEach(i => { if (!i.value) { i.classList.add('error'); missing++; } else i.classList.remove('error'); });
            document.querySelectorAll('.ca-input').forEach(i => { if (!i.value) { i.classList.add('error'); missing++; } else i.classList.remove('error'); });
            if (missing > 0) { alert(`Please fill in ${missing} missing field(s)`); e.preventDefault(); }
        });
        updateSelectedCount();
    </script>
</body>
</html>