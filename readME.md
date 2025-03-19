# 📚 CP400S Course Project — Class Management and SQL Security Demonstrator

Welcome to the Class Management and SQL Security Demonstrator — a full-featured web application designed to simulate a classroom environment for both teachers and students, while also demonstrating the difference between secure and insecure login practices, including SQL Injection vulnerabilities.

## ✨ Features

### 👨‍🏫 For Teachers
- View and manage student accounts 👩‍🎓
- Add, edit, and remove assignments ✍️
- Input and modify student grades 📊
- View detailed assignment statistics:
  - Mean, Median, Mode, Min, Max, Variance, Std Deviation
- Update teacher login credentials 🔐

### 👩‍🎓 For Students
- Secure or insecure login options 🔓🔒
- View only their personal grades per assignment 📈
- Update their own password 💬

## ⚠️ Demonstration of SQL Injection

This system comes with two login modes:
- 🔐 Secure Login: Uses prepared statements and input sanitization (prevents SQL Injection).
- 🔓 Insecure Login: Uses raw SQL string concatenation — intentionally vulnerable to demonstrate how SQL Injection attacks work.

Try entering:
    ' OR '1'='1
…as a username or password in the insecure login to see how it bypasses authentication.

## 🔧 Technologies Used
- PHP (Procedural)
- MySQL
- HTML & CSS (Basic Styling)
- Password hashing with password_hash() and password_verify()

## 🚪 File Structure Overview

cp400s_proj/
│
├── dashboard.php               # Teacher Dashboard
├── student_dashboard.php       # Student Dashboard
├── secure_login.php            # Secure login with protection
├── insecure_login.php          # Insecure login with SQL injection vulnerability
├── logout.php                  # Logs out users
├── db_secure.php               # Secure database connection
├── db_insecure.php             # Insecure database connection
├── style.css                   # Styling for UI
└── index.php                   # Landing page with login options and info

## 📊 Database Schema Overview

### users
| Column     | Type    | Description                    |
|------------|---------|---------------------------------|
| user_id 🔑 | INT     | Primary Key                    |
| username   | VARCHAR | Unique login username          |
| password   | VARCHAR | Hashed password (bcrypt)       |
| role       | ENUM    | Either teacher or student      |

### assignments
| Column         | Type    | Description            |
|----------------|---------|------------------------|
| assignment_id 🔑 | INT  | Primary Key            |
| title          | VARCHAR | Assignment title       |
| description    | TEXT    | Assignment details     |
| due_date       | DATE    | Submission deadline    |

### student_grades
| Column         | Type         | Description                     |
|----------------|--------------|---------------------------------|
| grade_id 🔑     | INT          | Primary Key                     |
| user_id         | INT (FK)     | References student in users     |
| assignment_id   | INT (FK)     | References assignment           |
| grade           | DECIMAL(5,2) | Grade value per assignment      |

## 🔐 Security Practices Demonstrated

| Practice                        | Secure Login | Insecure Login |
|--------------------------------|--------------|----------------|
| Prepared Statements            | ✅            | ❌              |
| Input Sanitization             | ✅            | ❌              |
| Password Hashing (bcrypt)      | ✅            | ✅              |
| Role-based Access Control      | ✅            | ✅              |
| SQL Injection Vulnerable       | ❌            | ✅              |

## 🚀 How to Run
1. Clone the project folder into your web server directory (e.g., htdocs for XAMPP or /var/www/html).
2. Import the MySQL database using the provided schema.
3. Configure db_secure.php and db_insecure.php with your database credentials.
4. Start your server and navigate to index.php.

### 🧪 Suggested Test Users

| Username | Password | Role     |
|----------|----------|----------|
| teacher  | test     | teacher  |
| student1 | 1        | student  |
| student2 | 2        | student  |

You can add more from the teacher dashboard or directly into the DB.

## 📝 Educational Purpose
This application was created for academic purposes under the CP400S course at Wilfrid Laurier University. It showcases classroom management system fundamentals, data analysis/statistics, and cybersecurity best/worst practices.

## 💡 Authors
Hilal Safi
Ruveyda Kizmaz
