<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}
include '../db_connection.php';

// Get statistics
$students_count = $conn->query("SELECT COUNT(*) FROM students")->fetch_row()[0];
$teachers_count = $conn->query("SELECT COUNT(*) FROM teachers")->fetch_row()[0];
$branches_count = $conn->query("SELECT COUNT(DISTINCT branch) FROM students")->fetch_row()[0];

// Get today's attendance count
$today = date('Y-m-d');
$attended_today = $conn->query("SELECT COUNT(DISTINCT rollno) FROM attendance WHERE date = '$today' AND status = 'Present'")->fetch_row()[0];

// Get number of distinct batches
$batches_count = $conn->query("SELECT COUNT(DISTINCT batch) FROM students")->fetch_row()[0];

// Get number of classes (unique combinations of batch, branch, and section including NULL)
$classes_count = $conn->query("SELECT COUNT(DISTINCT CONCAT(batch, '-', branch, '-', IFNULL(section, 'NULL'))) FROM students")->fetch_row()[0];


// Get today's attendance count by batch
$batch_attendance_today = [];
$batch_query = $conn->query("
    SELECT s.batch, 
           COUNT(DISTINCT a.rollno) as attended_count,
           COUNT(DISTINCT s.rollno) as total_students
    FROM students s
    LEFT JOIN attendance a ON s.rollno = a.rollno AND a.date = '$today' AND a.status = 'Present'
    GROUP BY s.batch
    ORDER BY s.batch
");

if ($batch_query) {
    while ($row = $batch_query->fetch_assoc()) {
        $batch_attendance_today[] = $row;
    }
} else {
    // Handle query error
    $batch_error = $conn->error;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - DRKIST</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/drk.png">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #8e0000; /* DRK maroon color */
            --secondary-color: #6c757d;
            --accent-color: #007bff;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --danger-color: #dc3545;
        }
        
        /* Base Styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
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
        
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 0;
            position: relative;
        }
        
        .logo-img {
            height: 50px;
            margin-right: 10px;
        }
        
        .logo-title {
            margin: 0;
            color: var(--primary-color);
            font-size: 1.5rem;
            text-align: center;
        }
        
        .mobile-menu-btn {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--primary-color);
            display: none;
            cursor: pointer;
        }
        
        /* Navigation */
        .secondary-nav {
            background-color: var(--secondary-color);
        }
        
        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            justify-content: center;
        }
        
        .admin-nav {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .admin-nav li {
            position: relative;
        }
        
        .admin-nav a {
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            display: block;
            transition: background-color 0.3s;
        }
        
        .admin-nav a:hover, .admin-nav a.active {
            background-color: #5a6268;
        }
        
        .admin-nav .logout {
            background-color: var(--danger-color);
        }
        
        .admin-nav .logout:hover {
            background-color: green;
        }
        
        /* Dashboard Content */
        main {
            padding: 20px 0;
            min-height: calc(100vh - 180px);
        }
        
        .dashboard-header {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .dashboard-header h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        /* Stats Grid */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--accent-color);
            margin: 10px 0;
        }
        
        .stat-label {
            font-size: 1rem;
            color: var(--secondary-color);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Footer */
        footer {
            background-color: black;
            color: white;
            padding: 20px 0;
            text-align: center;
        }
        
        .footer-develop {
            font-size: 0.9rem;
            color: #aaa;
            margin-top: 10px;
        }
        
        /* Mobile Menu Overlay */
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

        
        /* Batch attendance table styles */
        .batch-attendance {
            margin-top: 30px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .batch-attendance h2 {
            color: var(--primary-color);
            margin-top: 0;
            text-align: center;
        }
        
        .batch-attendance table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .batch-attendance th {
            background-color: var(--primary-color);
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        .batch-attendance td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        
        .good {
            color: #28a745;
            font-weight: bold;
        }
        
        .average {
            color: #ffc107;
            font-weight: bold;
        }
        
        .poor {
            color: #dc3545;
            font-weight: bold;
        }
        
        .error-message {
            color: var(--danger-color);
            text-align: center;
            padding: 10px;
            background-color: #f8d7da;
            border-radius: 4px;
            margin: 10px 0;
        }
        
        
        /* Responsive Styles */
        @media (max-width: 992px) {
            .logo-title {
                font-size: 1.3rem;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .admin-nav {
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
            
            .admin-nav.active {
                right: 0;
            }
            
            .mobile-nav-overlay.active {
                display: block;
            }
            
            .stats-container {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .logo-title {
                font-size: 1.2rem;
            }
            
            .stat-box {
                padding: 15px;
            }
            
            .stat-number {
                font-size: 2rem;
            }
                   .stats-container {
                grid-template-columns: 1fr;
            }
            
            .batch-attendance table {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 576px) {
            .logo-title {
                font-size: 1.1rem;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .admin-nav {
                width: 250px;
            }
            
            .logo-img {
                height: 40px;
            }
        }
        .out{
            background: red;
        }
        .out:hover{
            background:green;
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
        <div class="container">
            <div class="logo-container">
                <img src="../assets/images/drk.png" alt="DRKIST Logo" class="logo-img">
                <h1 class="logo-title">ATTENDANCE PORTAL</h1>
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
        
        <div class="secondary-nav">
            <div class="container nav-container">
                <ul class="admin-nav" id="adminNav">
                    <li><a href="?action=dashboard" class="<?= (isset($_GET['action']) && $_GET['action'] == 'dashboard' ? 'active' : '') ?>">Dashboard</a></li>
                    <li><a href="?action=add_teacher" class="<?= (isset($_GET['action']) && $_GET['action'] == 'add_teacher' ? 'active' : '' )?>">Faculty</a></li>
                    <li><a href="?action=students" class="<?= (isset($_GET['action']) && $_GET['action'] == 'students' ? 'active' : '' )?>">Students</a></li>
                    <li><a href="?action=add_subjects" class="<?= (isset($_GET['action']) && $_GET['action'] == 'add_subjects' ? 'active' : '') ?>">Subjects</a></li>
                    <li><a href="?action=timetable" class="<?= (isset($_GET['action']) && $_GET['action'] == 'timetable' ? 'active' : '') ?>">Timetable</a></li>
                    <li><a href="?action=view_attendance" class="<?= (isset($_GET['action']) && $_GET['action'] == 'view_attendance' ? 'active' : '') ?>">Attendance</a></li>
                    <li><a href="?action=notice_board" class="<?= (isset($_GET['action']) && $_GET['action'] == 'notice_board' ? 'active' : '' )?>">Notices</a></li>
                    <li><a href="?action=erase" class="<?= (isset($_GET['action']) && $_GET['action'] == 'erase' ? 'active' : '' )?>">Erase</a></li>
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
                ?>
                <div class="dashboard-header">
                    <h1>Admin Dashboard</h1>
                    <p>Welcome back, <?= htmlspecialchars($_SESSION['username']) ?></p>
                </div>
                
                <div class="stats-container">
                    <div class="stat-box">
                        <div class="stat-number"><?= $teachers_count ?></div>
                        <div class="stat-label">Total Faculty</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?= $batches_count ?></div>
                        <div class="stat-label">Batches</div>
                    </div>
                    
                    <div class="stat-box">
                        <div class="stat-number"><?= $branches_count ?></div>
                        <div class="stat-label">Branches</div>
                    </div>
                    
                    <div class="stat-box">
                        <div class="stat-number"><?= $classes_count ?></div>
                        <div class="stat-label">Total Classes</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?= $students_count ?></div>
                        <div class="stat-label">Total Students</div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-number"><?= $attended_today ?></div>
                        <div class="stat-label">Total Attended Today</div>
                    </div>
                </div>
                
        <div class="batch-attendance">
            <h2>Today's Attendance by Batch (<?= $today ?>)</h2>
            
            <?php if (!empty($batch_attendance_today)): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Batch</th>
                                <th style="text-align: center;">Attended</th>
                                <th style="text-align: center;">Total Students</th>
                                <th style="text-align: center;">Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($batch_attendance_today as $batch): 
                                $percentage = ($batch['total_students'] > 0) 
                                    ? round(($batch['attended_count'] / $batch['total_students']) * 100, 2) 
                                    : 0;
                                $percentage_class = ($percentage >= 75) ? 'good' : (($percentage >= 50) ? 'average' : 'poor');
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($batch['batch']) ?></td>
                                    <td style="text-align: center;"><?= $batch['attended_count'] ?></td>
                                    <td style="text-align: center;"><?= $batch['total_students'] ?></td>
                                    <td style="text-align: center;" class="<?= $percentage_class ?>">
                                        <?= $percentage ?>%
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: var(--secondary-color);">No attendance data available for today.</p>
            <?php endif; ?>
        </div>
        
                <!-- Password change section -->
                <div style="text-align: center; margin: 20px 0;">
                    <button id="showPasswordForm" class="password-toggle-btn">Change Password</button>
                </div>

                <div id="passwordChangeForm" class="password-change-section" style="display: none;">
                    <h2>Change Password</h2>
                    
                    <?php
                    if (isset($_POST['change_password'])) {
                        $current_password = $_POST['current_password'];
                        $new_password = $_POST['new_password'];
                        $confirm_password = $_POST['confirm_password'];
                        
                        // Verify current password
                        $username = $_SESSION['username'];
                        $stmt = $conn->prepare("SELECT password FROM admins WHERE username = ?");
                        $stmt->bind_param("s", $username);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $admin = $result->fetch_assoc();
                        
                        if (password_verify($current_password, $admin['password'])) {
                            if ($new_password === $confirm_password) {
                                // Update password
                                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                                $update_stmt = $conn->prepare("UPDATE admins SET password = ? WHERE username = ?");
                                $update_stmt->bind_param("ss", $hashed_password, $username);
                                
                                if ($update_stmt->execute()) {
                                    echo '<div class="alert alert-success">Password changed successfully!</div>';
                                } else {
                                    echo '<div class="alert alert-danger">Error updating password.</div>';
                                }
                            } else {
                                echo '<div class="alert alert-danger">New passwords do not match.</div>';
                            }
                        } else {
                            echo '<div class="alert alert-danger">Current password is incorrect.</div>';
                        }
                    }
                    ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" name="change_password">Update Password</button>
                        <button type="button" id="cancelPasswordChange" class="cancel-btn">Cancel</button>
                    </form>
                </div>
                <?php
                break;
            case 'add_teacher':
                include 'add_teacher.php';
                break;
            case 'students':
                include 'students.php';
                break;
            case 'add_student':
                include 'add_student.php';
                break;
            case 'upload_students':
                include 'upload_students.php';
                break;
            case 'timetable':
                include 'timetable.php';
                break;
            case 'view_attendance':
                include 'view_attendance.php';
                break;
            case 'notice_board':
                include 'notice_board.php';
                break;
            case 'add_subjects':
                include 'add_subjects.php';
                break;
            case 'erase':
                include 'erase.php';
                break;
            default:
                header("Location: ?action=dashboard");
                exit();
        }
        ?>
        
        <div style="display: flex; justify-content: center; align-items: center;">
            <a href="../logout.php" class="out" style="font-size: 18px; text-decoration: none; padding: 10px 20px; color: white; border-radius: 10px;">Logout</a>
        </div>
    </main>

    <footer>
        <div class="container">
            <p style="color:white">© <span id="year"></span> DRK Institute of Science and Technology, All Rights Reserved</p>
            <p class="footer-develop" style="color:white">Developed by K Sampath Reddy CSE[AI&ML]</p>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const adminNav = document.getElementById('adminNav');
        const mobileNavOverlay = document.getElementById('mobileNavOverlay');
        
        mobileMenuBtn.addEventListener('click', () => {
            adminNav.classList.toggle('active');
            mobileNavOverlay.classList.toggle('active');
            
            // Change menu icon
            const icon = mobileMenuBtn.querySelector('i');
            if (adminNav.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.add('fa-bars');
                icon.classList.remove('fa-times');
            }
        });
        
        // Close menu when overlay is clicked
        mobileNavOverlay.addEventListener('click', () => {
            adminNav.classList.remove('active');
            mobileNavOverlay.classList.remove('active');
            const icon = mobileMenuBtn.querySelector('i');
            icon.classList.add('fa-bars');
            icon.classList.remove('fa-times');
        });
        
        // Close menu when a nav link is clicked (for mobile)
        const navLinks = document.querySelectorAll('.admin-nav a');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 992) {
                    adminNav.classList.remove('active');
                    mobileNavOverlay.classList.remove('active');
                    const icon = mobileMenuBtn.querySelector('i');
                    icon.classList.add('fa-bars');
                    icon.classList.remove('fa-times');
                }
            });
        });

        // Password change form toggle
        document.addEventListener('DOMContentLoaded', function() {
            const showPasswordBtn = document.getElementById('showPasswordForm');
            const passwordForm = document.getElementById('passwordChangeForm');
            const cancelBtn = document.getElementById('cancelPasswordChange');
            
            // Toggle password form visibility
            showPasswordBtn.addEventListener('click', function() {
                passwordForm.style.display = passwordForm.style.display === 'none' ? 'block' : 'none';
            });
            
            // Hide form when cancel is clicked
            cancelBtn.addEventListener('click', function() {
                passwordForm.style.display = 'none';
            });
            
            // Keep form visible if there are validation messages
            <?php if (isset($_POST['change_password'])): ?>
                passwordForm.style.display = 'block';
            <?php endif; ?>
        });

        // Get current year for footer
        document.getElementById("year").textContent = new Date().getFullYear();
// Scroll to top functionality
document.addEventListener('DOMContentLoaded', function() {
    const scrollTopBtn = document.getElementById('scrollTopBtn');
    
    if (scrollTopBtn) {
        // Scroll to top when clicked
        scrollTopBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Show/hide based on scroll position
        window.addEventListener('scroll', function() {
            const scrollTopContainer = document.querySelector('.scroll-to-top');
            if (window.pageYOffset > 300) {
                scrollTopContainer.style.display = 'block';
            } else {
                scrollTopContainer.style.display = 'none';
            }
        });
        
        // Initially hide the button
        document.querySelector('.scroll-to-top').style.display = 'none';
    }
});
</script>

    <div class="scroll-to-top">
        <img src="../assets/images/ar.png" alt="Scroll to top" id="scrollTopBtn">
    </div>
</body>
</html>