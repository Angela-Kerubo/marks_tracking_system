# Marks Tracking and Alert System

## 1. Problem Solved

Kenyan universities, including the Catholic University of Eastern Africa (CUEA), face recurring issues with missing student marks due to manual errors, delayed submissions, and lack of real-time tracking. This results in delayed student progression, graduation hold-ups, increased administrative workload, and student frustration. Existing exam systems store grades but lack proactive detection and alert mechanisms.

This system solves that by automatically detecting missing marks, sending instant alerts, and providing real-time transparency for all stakeholders.

## 2. Features

**For Students:**
- View real-time marks status per course
- Receive email/SMS alerts when marks are missing
- Log and track complaints about missing marks

**For Lecturers:**
- Upload marks with validation (0–100, no duplicates)
- View pending submission status
- Receive alerts for incomplete submissions

**For Administrators:**
- Monitor marks submission across all departments
- Approve final results
- Generate analytics reports on submission delays and patterns
- Manage and resolve student complaints

**General:**
- Real-time dashboard for all user roles
- Automated missing marks detection (90% accuracy target)
- Email and SMS notifications
- Semester-end performance analytics

## 3. Tech Used

| Category | Technology |
|----------|------------|
| Backend | Python, Django |
| Frontend | HTML, CSS, JavaScript |
| Database | MySQL |
| Local Server | XAMPP (Apache, MySQL, PHP, Perl) |
| Deployment | AWS EC2 |
| Notifications | SMTP (Email), SMS Gateway |
| UI Design | Figma |
| Version Control | Git, GitHub |

## 4. Setup Instructions

### Prerequisites
- Python 3.8+
- MySQL or XAMPP
- Git

### Steps

```bash
# 1. Clone the repository
git clone https://github.com/Angela-Kerubo/marks_tracking_system.git
cd marks_tracking_system

# 2. Create virtual environment
python -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate

# 3. Install dependencies
pip install -r requirements.txt

# 4. Create database in MySQL
mysql -u root -p
CREATE DATABASE marks_tracking_db;
EXIT;

# 5. Update database settings in marks_tracking/settings.py
# Set NAME, USER, PASSWORD for your MySQL configuration

# 6. Run migrations
python manage.py makemigrations
python manage.py migrate

# 7. Create admin user
python manage.py createsuperuser

# 8. Start the server
python manage.py runserver
