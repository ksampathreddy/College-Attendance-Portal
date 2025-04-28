-- Table for admins
CREATE TABLE admins (
    username VARCHAR(50) PRIMARY KEY,
    password VARCHAR(255) NOT NULL
);

-- Table for teachers
CREATE TABLE teachers (
    username VARCHAR(50) PRIMARY KEY,
    password VARCHAR(255) NOT NULL
);

-- Table for students
CREATE TABLE students (
    rollno VARCHAR(15) PRIMARY KEY,
    name VARCHAR(25) NOT NULL,
    batch VARCHAR(15) NOT NULL,
    branch VARCHAR(50) NOT NULL,
    section VARCHAR(10) NULL
);

-- Table for subjects
CREATE TABLE subjects (
    subject_name VARCHAR(100) NOT NULL,
    branch VARCHAR(50) NOT NULL,
    semester VARCHAR(10) NOT NULL
);

-- Table for assigning of subjects
CREATE TABLE assign_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100) NOT NULL,
    faculty_name VARCHAR(50) NOT NULL,
    FOREIGN KEY (faculty_name) REFERENCES teachers(username)
);

-- Table for attendance
CREATE TABLE attendance (
    rollno VARCHAR(15) NOT NULL,
    batch VARCHAR(15) NOT NULL,
    branch VARCHAR(50) NOT NULL,
    section VARCHAR(10) NULL,
    semester VARCHAR(10) NOT NULL,
    subject VARCHAR(50) NOT NULL,
    date DATE NOT NULL,
    status ENUM('Present', 'Absent') NOT NULL,
    FOREIGN KEY (rollno) REFERENCES students(rollno) ON DELETE CASCADE,
    faculty VARCHAR(50) NOT NULL,
    FOREIGN KEY (faculty) REFERENCES teachers(username)
);

-- Table for timetable
CREATE TABLE timetables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch VARCHAR(10) NOT NULL,
    branch VARCHAR(50) NOT NULL,
    semester VARCHAR(10) NOT NULL,
    section VARCHAR(10)  NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploaded_by VARCHAR(50) NOT NULL
);

-- Table for notices
CREATE TABLE notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    posted_by VARCHAR(50) NOT NULL,
    posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert a default admin user
INSERT INTO admins (username, password) 
VALUES ('drkist@admin', '$2y$10$BOyRmZ8KLYy6N15g6Jb3wecvdxlkBTXBpmGEsteq9sHOxl7xFWSXm');
-- username drkist@admin
-- Password Drkist@29