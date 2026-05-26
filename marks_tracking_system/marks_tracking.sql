-- ============================================
-- MARKS TRACKING AND ALERT SYSTEM - COMPLETE DATABASE
-- The Catholic University of Eastern Africa (CUEA)
-- ============================================

-- ============================================
-- STEP 1: Create tables (parents first)
-- ============================================

-- Table 1: programs (degree programs)
CREATE TABLE IF NOT EXISTS programs (
    program_id INT PRIMARY KEY AUTO_INCREMENT,
    program_code VARCHAR(20) UNIQUE NOT NULL,
    program_name VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    duration_years INT DEFAULT 4,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table 2: users (students, lecturers, admin)
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    reg_no VARCHAR(50) UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15),
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'lecturer', 'admin') DEFAULT 'student',
    program_id INT,
    department VARCHAR(100),
    year_of_study INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(program_id) ON DELETE SET NULL
);

-- Table 3: units (individual subjects/modules)
CREATE TABLE IF NOT EXISTS units (
    unit_id INT PRIMARY KEY AUTO_INCREMENT,
    unit_code VARCHAR(20) UNIQUE NOT NULL,
    unit_name VARCHAR(100) NOT NULL,
    program_id INT,
    year INT NOT NULL,
    semester INT NOT NULL,
    lecturer_id INT,
    credits INT DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(program_id) ON DELETE CASCADE,
    FOREIGN KEY (lecturer_id) REFERENCES users(user_id) ON DELETE SET NULL,
    UNIQUE KEY unique_unit (unit_code, year, semester)
);

-- Table 4: enrollments (students registered for units)
CREATE TABLE IF NOT EXISTS enrollments (
    enrollment_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    unit_id INT NOT NULL,
    year INT NOT NULL,
    semester INT NOT NULL,
    academic_year VARCHAR(20),
    status ENUM('active', 'completed', 'dropped') DEFAULT 'active',
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (unit_id) REFERENCES units(unit_id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, unit_id, year, semester)
);

-- Table 5: marks (student scores)
CREATE TABLE IF NOT EXISTS marks (
    mark_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    unit_id INT NOT NULL,
    lecturer_id INT NOT NULL,
    ca_score DECIMAL(5,2) DEFAULT 0,
    exam_score DECIMAL(5,2) DEFAULT 0,
    total_score DECIMAL(5,2) GENERATED ALWAYS AS (ca_score + exam_score) STORED,
    grade VARCHAR(2),
    status ENUM('draft', 'submitted', 'approved', 'rejected') DEFAULT 'draft',
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_by INT,
    approved_date TIMESTAMP NULL,
    remarks TEXT,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (unit_id) REFERENCES units(unit_id) ON DELETE CASCADE,
    FOREIGN KEY (lecturer_id) REFERENCES users(user_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id),
    UNIQUE KEY unique_mark (student_id, unit_id)
);

-- Table 6: complaints (student complaints about missing marks)
CREATE TABLE IF NOT EXISTS complaints (
    complaint_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    unit_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'reviewing', 'resolved', 'rejected') DEFAULT 'pending',
    admin_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    resolved_by INT,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (unit_id) REFERENCES units(unit_id),
    FOREIGN KEY (resolved_by) REFERENCES users(user_id)
);

-- Table 7: notifications (alerts for users)
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ============================================
-- STEP 2: Insert Sample Data
-- ============================================

-- Insert Programs
INSERT INTO programs (program_id, program_code, program_name, department, duration_years) VALUES
(1, 'BScCS', 'Bachelor of Science in Computer Science', 'Computer Science', 4),
(2, 'BScIT', 'Bachelor of Science in Information Technology', 'Computer Science', 4),
(3, 'BScIS', 'Bachelor of Science in Information Systems', 'Computer Science', 4);

-- Insert Admin (password: admin123)
INSERT INTO users (user_id, reg_no, full_name, email, phone, password, role, department, is_active) VALUES
(1, 'ADMIN001', 'Mr. Michael Kinyua', 'michael.kinyua@cuea.edu', '+254700000001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Computer Science', 1);

-- Insert Lecturer (password: lecturer123)
INSERT INTO users (user_id, reg_no, full_name, email, phone, password, role, department, is_active) VALUES
(2, 'LEC001', 'Mr. Chris Nandasaba', 'chris.nandasaba@cuea.edu', '+254700000002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lecturer', 'Computer Science', 1);

-- Insert Students (password: student123)
INSERT INTO users (user_id, reg_no, full_name, email, phone, password, role, program_id, year_of_study, is_active) VALUES
(3, '1049489', 'Angela Kerubo', 'angela.kerubo@cuea.edu', '+254745462107', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, 1, 1),
(4, '1050001', 'John Doe', 'john.doe@cuea.edu', '+254712345678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, 1, 1);

-- Insert Units for Year 1 Semester 1 (Y1S1)
INSERT INTO units (unit_id, unit_code, unit_name, program_id, year, semester, lecturer_id, credits) VALUES
(1, 'CSC401', 'Web Development', 1, 1, 1, 2, 3),
(2, 'CSC402', 'Database Systems', 1, 1, 1, 2, 3),
(3, 'CSC403', 'Software Engineering', 1, 1, 1, 2, 3),
(4, 'CSC404', 'Computer Networks', 1, 1, 1, 2, 3),
(5, 'CSC405', 'Programming Fundamentals', 1, 1, 1, 2, 3);

-- Insert Units for Year 1 Semester 2 (Y1S2)
INSERT INTO units (unit_id, unit_code, unit_name, program_id, year, semester, lecturer_id, credits) VALUES
(6, 'CSC406', 'Object Oriented Programming', 1, 1, 2, 2, 3),
(7, 'CSC407', 'Data Structures and Algorithms', 1, 1, 2, 2, 3),
(8, 'CSC408', 'Operating Systems', 1, 1, 2, 2, 3);

-- Insert Units for Year 2 Semester 1 (Y2S1)
INSERT INTO units (unit_id, unit_code, unit_name, program_id, year, semester, lecturer_id, credits) VALUES
(9, 'CSC501', 'Artificial Intelligence', 1, 2, 1, 2, 3),
(10, 'CSC502', 'Machine Learning', 1, 2, 1, 2, 3),
(11, 'CSC503', 'Cloud Computing', 1, 2, 1, 2, 3);

-- Enroll Students in Year 1 Semester 1 (Y1S1)
INSERT INTO enrollments (student_id, unit_id, year, semester, academic_year, status) VALUES
(3, 1, 1, 1, '2025/2026', 'active'),
(3, 2, 1, 1, '2025/2026', 'active'),
(3, 3, 1, 1, '2025/2026', 'active'),
(3, 4, 1, 1, '2025/2026', 'active'),
(3, 5, 1, 1, '2025/2026', 'active'),
(4, 1, 1, 1, '2025/2026', 'active'),
(4, 2, 1, 1, '2025/2026', 'active'),
(4, 3, 1, 1, '2025/2026', 'active'),
(4, 4, 1, 1, '2025/2026', 'active'),
(4, 5, 1, 1, '2025/2026', 'active');

-- Enroll Students in Year 1 Semester 2 (Y1S2)
INSERT INTO enrollments (student_id, unit_id, year, semester, academic_year, status) VALUES
(3, 6, 1, 2, '2025/2026', 'active'),
(3, 7, 1, 2, '2025/2026', 'active'),
(3, 8, 1, 2, '2025/2026', 'active'),
(4, 6, 1, 2, '2025/2026', 'active'),
(4, 7, 1, 2, '2025/2026', 'active'),
(4, 8, 1, 2, '2025/2026', 'active');

-- Sample Marks Data (for demonstration)
INSERT INTO marks (student_id, unit_id, lecturer_id, ca_score, exam_score, status) VALUES
(3, 1, 2, 25, 60, 'approved'),
(3, 2, 2, 28, 0, 'draft'),
(3, 3, 2, 22, 0, 'draft'),
(4, 1, 2, 22, 55, 'submitted'),
(4, 2, 2, 24, 0, 'draft');

-- Sample Complaint
INSERT INTO complaints (student_id, unit_id, subject, description, status) VALUES
(3, 2, 'Missing Marks for Database Systems', 'I submitted my exam but marks are not showing. Please check.', 'pending');

-- Sample Notifications
INSERT INTO notifications (user_id, title, message, type) VALUES
(3, 'Missing Mark Alert', 'Your mark for CSC402 (Database Systems) is pending submission', 'warning'),
(3, 'Welcome', 'Welcome to the Marks Tracking and Alert System', 'success'),
(2, 'Pending Submissions', 'You have pending marks to submit for CSC402', 'warning');