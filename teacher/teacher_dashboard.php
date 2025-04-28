<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../index.php");
    exit();
}
include '../db_connection.php';

// Get teacher's name
$teacher_username = $_SESSION['username'];
$sql = "SELECT username FROM teachers WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $teacher_username);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();
$teacher_name = $teacher['username'];

// Get assigned subjects count and list
$assigned_subjects = [];
$sql = "SELECT subject_name FROM assign_subjects WHERE faculty_name = ? ORDER BY subject_name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $teacher_username);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $assigned_subjects[] = $row['subject_name'];
}
$assigned_subjects_count = count($assigned_subjects);

// Handle password change form
$password_message = '';
$show_password_form = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['change_password'])) {
        // Toggle password form visibility
        $show_password_form = true;
    } elseif (isset($_POST['update_password'])) {
        // Process password change
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $password_message = '<div class="alert alert-danger">All fields are required!</div>';
        } elseif ($new_password != $confirm_password) {
            $password_message = '<div class="alert alert-danger">New passwords do not match!</div>';
        } else {
            // Verify current password
            $sql = "SELECT password FROM teachers WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $teacher_username);
            $stmt->execute();
            $result = $stmt->get_result();
            $teacher_data = $result->fetch_assoc();
            
            if (password_verify($current_password, $teacher_data['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "UPDATE teachers SET password = ? WHERE username = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $hashed_password, $teacher_username);
                
                if ($stmt->execute()) {
                    $password_message = '<div class="alert alert-success">Password changed successfully!</div>';
                    $show_password_form = false;
                } else {
                    $password_message = '<div class="alert alert-danger">Error updating password!</div>';
                }
            } else {
                $password_message = '<div class="alert alert-danger">Current password is incorrect!</div>';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty - DRKIST</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/drk.png">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap CSS for alerts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #8e0000; /* DRK maroon */
            --secondary-color: #6c757d;
            --accent-color: #007bff;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --danger-color: #dc3545;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Header Styles */
        .main-header {
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-container {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 0;
            position: relative;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo-img {
            height: 50px;
        }
        
        .logo-title {
            margin: 0;
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        /* Navigation */
        .secondary-nav {
            background-color: var(--secondary-color);
        }
        
        .nav-container {
            display: flex;
            justify-content: center;
            position: relative;
        }
        
        .teacher-nav {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .teacher-nav li {
            position: relative;
        }
        
        .teacher-nav a {
            color: white;
            text-decoration: none;
            padding: 15px 20px;
            display: block;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .teacher-nav a:hover, 
        .teacher-nav a.active {
            background-color: #5a6268;
        }
        
        .teacher-nav .logout {
            background-color: var(--danger-color);
        }
        
        .admin-nav .logout:hover {
            background-color: #007bff;
        }
        
        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.8rem;
            cursor: pointer;
            color: var(--primary-color);
            padding: 5px 10px;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
        }
        
        /* Main Content */
        main {
            padding: 30px 0;
            min-height: calc(100vh - 180px);
        }
        
        .welcome-message {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .welcome-message h2 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .welcome-message p {
            font-size: 1.1rem;
            color: var(--secondary-color);
        }
        
        /* Dashboard Stats */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 4px solid var(--primary-color);
        }
        
        .stat-card i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .stat-card h3 {
            color: var(--dark-color);
            margin-bottom: 5px;
            font-size: 1.2rem;
        }
        
        .stat-card .count {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        /* Subjects List */
        .subjects-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .subjects-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .subject-item {
            background: var(--light-color);
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .subject-item i {
            color: var(--primary-color);
        }
        
        /* Password Form */
        .password-form-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: 20px auto;
        }
        
        .toggle-password-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            margin: 20px 0;
            display: inline-block;
        }
        
        .toggle-password-btn:hover {
            background: #6e0000;
        }
        
        /* Footer */
        footer {
            background-color: black;
            color: white;
            padding: 2px 0;
            text-align: center;
        }
        
        .footer-develop {
            font-size: 0.9rem;
            color: #aaa;
            margin-top: 10px;
        }
        
        /* Mobile Menu Styles */
        @media (max-width: 992px) {
            .logo-title {
                font-size: 1.3rem;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .teacher-nav {
                position: fixed;
                top: 0;
                right: -300px;
                width: 300px;
                height: 100vh;
                background-color: var(--secondary-color);
                flex-direction: column;
                padding: 80px 0 20px;
                transition: right 0.3s ease;
                z-index: 1000;
            }
            
            .teacher-nav.active {
                right: 0;
            }
            
            .teacher-nav a {
                padding: 15px 25px;
                border-bottom: 1px solid rgba(255,255,255,0.1);
            }
            
            /* Overlay when menu is open */
            .mobile-nav-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.5);
                z-index: 999;
                display: none;
            }
            
            .mobile-nav-overlay.active {
                display: block;
            }
        }
        
        @media (max-width: 768px) {
            .logo-title {
                font-size: 1.2rem;
            }
            
            .logo-img {
                height: 45px;
            }
            
            .welcome-message {
                margin: 20px 0;
                padding: 15px;
            }
            
            .welcome-message h2 {
                font-size: 1.5rem;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 576px) {
            .logo-title {
                font-size: 1.1rem;
            }
            
            .logo-img {
                height: 40px;
            }
            
            .logo-container {
                gap: 10px;
            }
            
            .teacher-nav {
                width: 250px;
                right: -250px;
            }
            
            .header-container {
                padding: 10px 0;
            }
            
            .subjects-list {
                grid-template-columns: 1fr;
            }
        }
        
        .out {
            background: red;
        }
        
        .out:hover {
            background: green;
        }
        
        /* Scroll to top button */
        .scroll-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 99;
            cursor: pointer;
            opacity: 0.7;
            transition: all 0.3s ease;
        }
        
        .scroll-to-top:hover {
            opacity: 1;
            transform: translateY(-5px);
        }
        
        .scroll-to-top img {
            width: 50px;
            height: auto;
            border-radius: 50%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        @media (max-width: 768px) {
            .scroll-to-top {
                bottom: 20px;
                right: 20px;
            }
            
            .scroll-to-top img {
                width: 40px;
            }
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="container header-container">
            <div class="logo-container">
                <img src="../assets/images/drk.png" alt="DRKIST Logo" class="logo-img">
                <h1 class="logo-title">ATTENDANCE PORTAL</h1>
            </div>
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <div class="secondary-nav">
            <div class="container nav-container">
                <ul class="teacher-nav" id="teacherNav">
                    <li><a href="?action=dashboard" class="<?= (!isset($_GET['action']) || $_GET['action'] == 'dashboard' ? 'active' : '') ?>">Dashboard</a></li>
                    <li><a href="?action=take_attendance" class="<?= (isset($_GET['action']) && $_GET['action'] == 'take_attendance' ? 'active' : '') ?>">Post Attendance</a></li>
                    <li><a href="?action=view_attendance" class="<?= (isset($_GET['action']) && $_GET['action'] == 'view_attendance' ? 'active' : '') ?>">View Attendance</a></li>
                    <li><a href="../logout.php" class="logout">Logout</a></li>
                </ul>
            </div>
        </div>
    </header>
    
    <div class="mobile-nav-overlay" id="mobileNavOverlay"></div>

    <main class="container">
        <?php
        $action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';
        
        switch ($action) {
            case 'dashboard':
                echo '<div class="welcome-message">';
                echo '<h2>Welcome, ' . htmlspecialchars($teacher_name) . '</h2>';
                echo '<p>Faculty Dashboard - DRK Institute of Science and Technology</p>';
                echo '</div>';
                
                // Display statistics only on dashboard
                echo '<div class="stats-container">';
                echo '<div class="stat-card">';
                echo '<i class="fas fa-book-open"></i>';
                echo '<div class="count">' . $assigned_subjects_count . '</div>';
                echo '<h3>Assigned Subjects</h3>';
                echo '</div>';
                echo '</div>';
                
                // Display assigned subjects list
                if (!empty($assigned_subjects)) {
                    echo '<div class="subjects-container">';
                    echo '<h3><i class="fas fa-book me-2"></i>Your Assigned Subjects</h3>';
                    echo '<div class="subjects-list">';
                    foreach ($assigned_subjects as $subject) {
                        echo '<div class="subject-item">';
                        echo '<i class="fas fa-bookmark"></i>';
                        echo '<span>' . htmlspecialchars($subject) . '</span>';
                        echo '</div>';
                    }
                    echo '</div>';
                    echo '</div>';
                }
                
                // Password change button
                echo '<div class="text-center">';
                echo '<button class="toggle-password-btn" onclick="document.getElementById(\'passwordForm\').style.display=\'block\'">';
                echo '<i class="fas fa-key me-2"></i>Change Password';
                echo '</button>';
                echo '</div>';
                
                // Password change form (initially hidden)
                echo '<div id="passwordForm" style="display: ' . ($show_password_form ? 'block' : 'none') . ';" class="password-form-container">';
                echo '<h3 class="text-center mb-4"><i class="fas fa-key me-2"></i>Change Password</h3>';
                
                if (!empty($password_message)) echo $password_message;
                
                echo '<form method="POST">';
                echo '<div class="mb-3">';
                echo '<label for="current_password" class="form-label">Current Password</label>';
                echo '<input type="password" class="form-control" id="current_password" name="current_password" required>';
                echo '</div>';
                
                echo '<div class="mb-3">';
                echo '<label for="new_password" class="form-label">New Password</label>';
                echo '<input type="password" class="form-control" id="new_password" name="new_password" required>';
                echo '</div>';
                
                echo '<div class="mb-3">';
                echo '<label for="confirm_password" class="form-label">Confirm New Password</label>';
                echo '<input type="password" class="form-control" id="confirm_password" name="confirm_password" required>';
                echo '</div>';
                
                echo '<div class="d-grid gap-2">';
                echo '<button type="submit" name="update_password" class="btn btn-primary">';
                echo '<i class="fas fa-save me-2"></i>Update Password';
                echo '</button>';
                echo '<button type="button" class="btn btn-secondary" onclick="document.getElementById(\'passwordForm\').style.display=\'none\'">';
                echo '<i class="fas fa-times me-2"></i>Cancel';
                echo '</button>';
                echo '</div>';
                echo '</form>';
                echo '</div>';
                break;
                
            case 'take_attendance':
                include 'take_attendance.php';
                break;
                
            case 'view_attendance':
                include 'view_attendance.php';
                break;
                
            default:
                echo '<div class="welcome-message">';
                echo '<h2>Welcome, ' . htmlspecialchars($teacher_name) . '</h2>';
                echo '<p>Faculty Dashboard - DRK Institute of Science and Technology</p>';
                echo '</div>';
        }
        ?>
        
        <div style="display: flex; justify-content: center; align-items: center; margin-top: 30px;">
            <a href="../logout.php" class="out" style="font-size: 18px; text-decoration: none; padding: 10px 20px; color: white; border-radius: 10px;">Logout</a>
        </div>
    </main>

    <footer>
        <div class="contain">
            <p style="color:white">© <span id="year"></span> DRK Institute of Science and Technology, All Rights Reserved</p>
            <p class="footer-develop" style="color:white">Developed by K Sampath Reddy CSE[AI&ML]</p>
        </div>
    </footer>

    <div class="scroll-to-top">
        <img src="../assets/images/ar.png" alt="Scroll to top" id="scrollTopBtn">
    </div>

    <script>
        // Mobile menu functionality
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const teacherNav = document.getElementById('teacherNav');
        const mobileNavOverlay = document.getElementById('mobileNavOverlay');
        
        mobileMenuBtn.addEventListener('click', () => {
            teacherNav.classList.toggle('active');
            mobileNavOverlay.classList.toggle('active');
            
            // Change menu icon
            const icon = mobileMenuBtn.querySelector('i');
            if (teacherNav.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.add('fa-bars');
                icon.classList.remove('fa-times');
            }
        });
        
        // Close menu when overlay is clicked
        mobileNavOverlay.addEventListener('click', () => {
            teacherNav.classList.remove('active');
            mobileNavOverlay.classList.remove('active');
            const icon = mobileMenuBtn.querySelector('i');
            icon.classList.add('fa-bars');
            icon.classList.remove('fa-times');
        });
        
        // Close menu when a nav link is clicked (for mobile)
        const navLinks = document.querySelectorAll('.teacher-nav a');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 992) {
                    teacherNav.classList.remove('active');
                    mobileNavOverlay.classList.remove('active');
                    const icon = mobileMenuBtn.querySelector('i');
                    icon.classList.add('fa-bars');
                    icon.classList.remove('fa-times');
                }
            });
        });
        
        // Close menu when window is resized to desktop size
        window.addEventListener('resize', () => {
            if (window.innerWidth > 992 && teacherNav.classList.contains('active')) {
                teacherNav.classList.remove('active');
                mobileNavOverlay.classList.remove('active');
                const icon = mobileMenuBtn.querySelector('i');
                icon.classList.add('fa-bars');
                icon.classList.remove('fa-times');
            }
        });
        
        // Get current year
        document.getElementById("year").textContent = new Date().getFullYear();
        
        // Scroll to top functionality
        document.getElementById('scrollTopBtn').addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Show/hide scroll to top button based on scroll position
        window.addEventListener('scroll', function() {
            const scrollTopBtn = document.getElementById('scrollTopBtn');
            if (window.pageYOffset > 300) {
                scrollTopBtn.parentElement.style.display = 'block';
            } else {
                scrollTopBtn.parentElement.style.display = 'none';
            }
        });
        
        // Initially hide the scroll to top button
        document.querySelector('.scroll-to-top').style.display = 'none';
    </script>
</body>
</html>