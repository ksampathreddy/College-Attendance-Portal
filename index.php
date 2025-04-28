<?php
session_start();
include 'db_connection.php';
// Get latest 2 notices
$notice_sql = "SELECT * FROM notices ORDER BY posted_at DESC LIMIT 2";
$notice_result = $conn->query($notice_sql);
$notices = [];
if ($notice_result->num_rows > 0) {
    while($row = $notice_result->fetch_assoc()) {
        $notices[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DRKIST Attendance Portal</title>
    <link rel="stylesheet" href="assets/style.css">
    <!-- Font Awesome for menu icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Favicon for browsers -->
    <link rel="icon" href="drkl.png" type="image/x-icon">

    <!-- Google Search Icon (PNG format, min 48x48px) -->
    <link rel="icon" type="image/png" href="assets/drk.png" sizes="48x48">

    <!-- Open Graph Tags (for social/sharing previews) -->
    <meta property="og:image" content="https://drkist.infinityfreeapp.com/drkl.png">
    <meta property="og:title" content="DRKIST Attendance Portal">
    <meta name="description" content="Track and manage attendance with our comprehensive portal. Students can check their records, teachers can mark attendance, and administrators can generate reports."> 

    <style>
        main.contain {
    padding-bottom: 0 !important;
    margin-bottom: 0 !important;
}
.contain {
  color:maroon;
  width: 100%;
  max-width: 1440px;
  margin: 0 auto;
  padding: 0 15px;
}
.container {
  color:maroon;
  width: 100%;
  max-width: 1440px;
  margin: 0 auto;
  padding: 0 15px;
}
        .welcome-section {
            
            background: linear-gradient(rgba(71, 63, 63, 0.7), rgba(0, 0, 0, 0.7)), 
                        url('assets/images/drk.jpg') center/cover no-repeat;
            padding: 1px 3px;
            margin-bottom: 1px !important; /* Force only 2px gap */
            padding-bottom: 0 !important; /* Ensure no extra padding */
            border-radius: 25px;
            margin: 1px 1px;
            color: white;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            min-height: 80vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .welcome-section h2 {
            font-size: 3rem;
            margin-bottom: 25px;
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .welcome-section p {
            font-size: 1.3rem;
            margin-bottom: 40px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.8;
        }

        .welcome-section .logout {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 15px 40px;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 2px solid #28a745;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .welcome-section .logout:hover {
            background-color: transparent;
            color: #28a745;
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }

        /* Header Logo Styles */
        .header-container {
            display: flex;
            align-items: center;
            width: 100%;
            position: relative;
        }

        .logo-title-container {
            display: flex;
            align-items: center;
            width: 100%;
            justify-content: center;
            position: relative;
           
        }

        .logo-title-container img {
            height: 50px;
            width: 30%;
            position: absolute;
            left:-18px;
           
        }

        .logo-title-container h1 {
            margin: 0;
            font-size: 2.2rem;
            color: maroon;
            text-align: center;
            width: 100%;
        }

        /* Desktop Navigation */
        .desktop-nav {
            position: absolute;
            right: 0px;
            top: 70%;
            transform: translateY(-50%);
            display: flex;
            gap: 14px;
        }

        .desktop-nav a {
            font-size:0.9rem;
            color: black;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .desktop-nav a:hover {
            color: maroon;
        }

        /* Mobile Navigation (Right Sidebar) */
        .mobile-nav {
            position: fixed;
            top: 0;
            right: -300px;
            width: 300px;
            height: 100vh;
            background-color: #fff;
            box-shadow: -5px 0 15px rgba(0,0,0,0.2);
            transition: right 0.3s ease;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            padding: 80px 20px 20px;
        }

        .mobile-nav.active {
            right: 0;
        }

        .mobile-nav a {
            color: black;
            text-decoration: none;
            padding: 12px 15px;
            margin: 5px 0;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .mobile-nav a:hover {
            background-color: #f0f0f0;
            color: maroon;
        }

        /* Mobile Menu Button */
        .menu-btn {
            display: none;
            background: none;
            border: none;
            color: black;
            font-size: 1.5rem;
            cursor: pointer;
            position: fixed;
            right: 20px;
            top: 20px;
            z-index: 1001;
        }

        /* Overlay when menu is open */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 999;
        }

        .overlay.active {
            display: block;
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .menu-btn {
                display: block;
            }
            
            .desktop-nav {
                display: none;
            }
            
            .header-container {
                flex-direction: column;
                gap: 15px;
                padding-bottom: 60px;
            }
            
            .logo-title-container {
                justify-content: center;
                margin-bottom: 15px;
                height:25px;
            }
            
            .logo-title-container img {
                position: relative;
                left: auto;
                margin-right: 15px;
            }
            
            .welcome-section {
                min-height: 70vh;
                padding: 60px 20px;
            }
            
            .welcome-section h2 {
                font-size: 2.5rem;
            }
            
            .welcome-section p {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 768px) {
            .logo-title-container {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
                height:45px;
            }
            
            .logo-title-container h1 {
                font-size: 2rem;
                margin-top: 10px;
                width: 100%;
                height:20px;
             
            }
              .logo-title-container img {

                     width: auto;
            }
            
            .welcome-section h2 {
                font-size: 2rem;
            }
            .welcome-section {
                min-height: 70vh;
                padding: 60px 20px;
            }
            
            
            .welcome-section p {
                font-size: 1rem;
            }
            
            .welcome-section .logout {
                padding: 12px 30px;
                font-size: 1.1rem;
            }
            
            .mobile-nav {
                width: 250px;
                right: -250px;
            }
        }

        @media (max-width: 480px) {
            .logo-title-container h1 {
                height:38px;
              }
            .welcome-section {
                padding: 40px 15px;
                min-height: 70vh;
            }
            
            .welcome-section h2 {
                font-size: 1.8rem;
            }
            
            .logo-title-container img {
                height: 40px;
                     width: auto;
            }
            
            .mobile-nav {
                width: 220px;
                right: -220px;
            }
        }
        .drk-footer {
            
    background-color: black;
    padding: 25px 20px 40px;
    font-family: Arial, sans-serif;
    position: relative;
    color: white;
    min-height: 146px;
    box-sizing: border-box;
    margin-top: 0px !important;
}

.footer-logo {
    position: absolute;
    top: 29px;
    left: 30px;
    height: 60px;
    max-width: 290px;
}

.footer-contact {
    position: absolute;
    top: 25px;
    right: 30px;
    text-align: right;
    color: #aaa;
    font-size: 13px;
    line-height: 1.5;
}

.footer-divider {
    position: absolute;
    bottom: 65px;
    left: 50%;
    transform: translateX(-50%);
    width: 80%;
    max-width: 800px;
    height: 1px;
    background-color: white;
}

.footer-copyright {
    position: absolute;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    color: white;
    font-size: 14px;
    text-align: center;
    width: 100%;
    max-width: 600px;
}

.footer-developer {
    position: absolute;
    bottom: 30px;
    right: 30px;
    color: #777;
    font-size: 14px;
    font-style: italic;
}

/* Tablet Styles (768px - 1024px) */
@media (max-width: 1024px) {
    .drk-footer {
        padding: 2px 5px 5px;
        min-height: 130px;
    }
    
    .footer-logo {
        height: 40px;
        top: 30px;
        left: 5px;
        wdith:auto;
    }
    
    .footer-contact {
        top: 25px;
        right: 10px;
        font-size: 12px;
    }
    
    .footer-divider {
        bottom: 42px;
    }
    
    .footer-copyright {
        bottom: 10px;
        font-size: 13px;
    }
    
    .footer-developer {
        bottom: 15px;
        right: 25px;
        font-size: 12px;
    }
}

/* Mobile Styles (up to 767px) */
@media (max-width: 767px) {
    .drk-footer {
        padding: 20px 20px 30px;
        text-align: center;
    }
    
    .footer-logo {
        position: relative;
        top: auto;
        left: auto;
        margin: 0 auto 15px;
        display: block;
    }
    
    .footer-contact {
        position: relative;
        top: auto;
        right: auto;
        text-align: center;
        margin: 0 auto 20px;
    }
    
    .footer-divider {
        position: relative;
        bottom: auto;
        left: auto;
        transform: none;
        margin: 20px auto;
        width: 70%;
    }
    
    .footer-copyright {
        position: relative;
        bottom: auto;
        left: auto;
        transform: none;
        margin: 10px auto;
    }
    
    .footer-developer {
        position: relative;
        bottom: auto;
        right: auto;
        margin: 15px auto 0;
        text-align: center;
    }
}

/* Small Phone Styles (up to 480px) */
@media (max-width: 480px) {
    footer{
        height:340px;
    }
    .drk-footer {
        padding: 22px 15px 70px;
    }
    
    .footer-logo {
        height: 35px;
    }
    
    .footer-contact {
        font-size: 11px;
    }
    
    .footer-copyright {
        font-size: 12px;
    }
    
    .footer-developer {
        font-size: 12px;
    }
}
/* About Section Styles */
.about-section {
    background-color: #f9f9f9;
    padding: 60px 0;
    margin: 40px 0;
}

.about-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 40px;
    margin: 30px 0;
}

.about-text {
    flex: 1;
}

.about-image {
    flex: 1;
    text-align: center;
}

.about-image img {
    max-width: 100%;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.institution-info {
    background-color: maroon;
    color: white;
    padding: 25px;
    border-radius: 8px;
    margin-top: 40px;
}

.institution-info h3 {
    color: white;
    margin-top: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .about-content {
        flex-direction: column;
    }
    
    .about-image {
        order: -1;
        margin-bottom: 30px;
    }
    
    .about-text, .about-image {
        width: 100%;
    }
}/* Latest Notices Section */
.latest-notices {
    background-color: #f8f9fa;
    padding: 40px 0;
    margin: 40px 0;
}

.notices-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.notice-card {
    background-color: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.notice-card:hover {
    transform: translateY(-5px);
}

.notice-card h3 {
    color: maroon;
    margin-top: 0;
}

.notice-meta {
    color: #6c757d;
    font-size: 0.9rem;
    margin: 10px 0;
}

.notice-excerpt {
    line-height: 1.6;
}

.read-more {
    color: #007bff;
    text-decoration: none;
    font-weight: 500;
    display: inline-block;
    margin-top: 10px;
}

.read-more:hover {
    text-decoration: underline;
}

.view-all-btn {
    display: inline-block;
    background-color: maroon;
    color: white;
    padding: 10px 20px;
    border-radius: 4px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.view-all-btn:hover {
    background-color: #5a0a0a;
    transform: translateY(-2px);
}


/* Responsive adjustments */
@media (max-width: 768px) {
    .notices-container {
        grid-template-columns: 1fr;
    }
    
    .latest-notices {
        padding: 30px 0;
    }
}
    </style>
</head>
<body>
    <!-- Overlay for menu -->
    <div class="overlay" id="overlay"></div>

    <header>
        <div class="container header-container">
            <div class="logo-title-container">
                <img src="assets/images/K.png" alt="DRKIST Logo">
                <h1>ATTENDANCE PORTAL</h1>
                <button class="menu-btn" id="menuBtn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <!-- Desktop Navigation -->
            <nav class="desktop-nav">
                <a href="student_attendance.php">Student</a>
                <a href="get_timetable.php">TimeTable</a>
                <a href="notice_board.php">Notice Board</a>
                <a href="#about">About</a> 
                <a href="staff_login.php">Login</a>
            </nav>
            <!-- Mobile Navigation -->
            <nav class="mobile-nav" id="mobileNav">
                <a href="student_attendance.php">Student</a>
                <a href="get_timetable.php">TimeTable</a>
                <a href="notice_board.php">Notice Board</a>
                <a href="#about">About</a> 
                <a href="staff_login.php">Login</a>
            </nav>
        </div>
    </header>

    <main class="contain">
        <section class="welcome-section">
            <h2>Welcome to DRK Attendance Portal</h2>
            <p>Track and manage attendance with our comprehensive portal. Students can check their records, teachers can mark attendance, and administrators can generate reports.</p>
            <a href="student_attendance.php" class="logout">Check Attendance</a>
        </section>

        <!-- Latest Notices Section -->
        <section class="latest-notices">
            <div class="container">
                <h2 style="color:black" class="text-center">Notices</h2>
                
                <?php if (!empty($notices)): ?>
                    <div class="notices-container">
                        <?php foreach ($notices as $notice): ?>
                            <div class="notice-card">
                                <h3><?= htmlspecialchars($notice['title']) ?></h3>
                                <p class="notice-meta">Posted on <?= date('d M Y', strtotime($notice['posted_at'])) ?></p>
                                <div class="notice-excerpt">
                                    <?= nl2br(htmlspecialchars(substr($notice['content'], 0, 150))) ?>...
                                    <a href="notice_board.php" class="read-more">Read more</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center" style="margin-top: 20px;">
                        <a href="notice_board.php" class="view-all-btn">View All Notices</a>
                    </div>
                <?php else: ?>
                    <p class="text-center">No notices available at this time.</p>
                <?php endif; ?>
            </div>
        </section>

        <section id="about" class="about-section">
            <div class="container">
                <h2 style="color:black" class="text-center">About</h2>
              
                <div class="about-content">
                    <div class="about-text">
                        <h3>WELCOME TO DRKIST ATTENDENCE PORTAL</h3>
                        <p style="color:black">The DRKIST Attendance Portal revolutionizes how attendance is managed in educational institutions. Our digital solution replaces traditional paper-based methods with a secure, efficient system that benefits students, faculty, and administrators alike.</p>
                        
                        <h3>Key Features</h3>
                        <ul style="color:black">
                            <li>Real-time attendance tracking with instant updates</li>
                            <li>Automated reports and analytics for administrators</li>
                            <li>Secure role-based access for different students</li>
                            <li>Mobile-friendly interface for on-the-go access</li>
                        </ul>
                    </div>
                </div>
                
                <div class="institution-info">
                    <h3>About DRKIST</h3>
                    <p>DRK Institute of Science and Technology, approved by AICTE and affiliated to JNTUH, is committed to excellence in technical education. Our state-of-the-art campus in Hyderabad features modern labs, experienced faculty, and a vibrant learning environment that nurtures innovation.</p>
                </div>
            </div>
        </section>
    </main>

    <footer class="drk-footer">
        <img src="assets/images/K.png" alt="DRKIST Logo" class="footer-logo">
        <div class="footer-contact">
            <p><strong>Address:</strong> Near Pragathi Nagar, Bowrampet (V),<br>
            Hyderabad - 500043, Telangana, India</p>
            <p><strong>Phone:</strong> +91-9000711899</p>
        </div>
        <div class="footer-description">
            <p>DRK Institute of Science and Technology and Management features excellent Infrastructure, 
            modern engineering labs with the latest equipment, experienced faculties and lots of 
            opportunities for development.</p>
        </div>
        <div class="footer-divider"></div>
        <div class="footer-copyright">
            © <span id="year"></span> DRK Institute of Science and Technology, All Rights Reserved
        </div>
        <div class="footer-developer">
            Developed by K Sampath Reddy CSE[AI&ML]
        </div>
    </footer>
    
    <div class="scroll-to-top">
        <img src="assets/images/ar.png" alt="Scroll to top" id="scrollTopBtn">
    </div>
    
<style>
/* Add this to your CSS */
.scroll-to-top {
    position: fixed;
    bottom: 30px;
    left: 30px;
    z-index: 99;
    cursor: pointer;
    opacity: 0.7;
    transition: all 0.3s ease;
    top:65%;
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

<script>
// Add this JavaScript
document.getElementById('scrollTopBtn').addEventListener('click', function() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});

// Optional: Show/hide button based on scroll position
window.addEventListener('scroll', function() {
    const scrollTopBtn = document.getElementById('scrollTopBtn');
    if (window.pageYOffset > 300) {
        scrollTopBtn.parentElement.style.display = 'block';
    } else {
        scrollTopBtn.parentElement.style.display = 'none';
    }
});

// Initially hide the button
document.querySelector('.scroll-to-top').style.display = 'none';
</script>


<script>
        // Toggle mobile menu
        const menuBtn = document.getElementById('menuBtn');
        const mobileNav = document.getElementById('mobileNav');
        const overlay = document.getElementById('overlay');
        
        menuBtn.addEventListener('click', () => {
            mobileNav.classList.toggle('active');
            overlay.classList.toggle('active');
            
            // Change icon between bars and times
            const icon = menuBtn.querySelector('i');
            if (mobileNav.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
        
        // Close menu when overlay is clicked
        overlay.addEventListener('click', () => {
            mobileNav.classList.remove('active');
            overlay.classList.remove('active');
            const icon = menuBtn.querySelector('i');
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        });
        
        // Close menu when a link is clicked
        const navLinks = document.querySelectorAll('.mobile-nav a');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                mobileNav.classList.remove('active');
                overlay.classList.remove('active');
                const icon = menuBtn.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            });
        });
    </script>
    <script>
    // Get current year
    document.getElementById("year").textContent = new Date().getFullYear();
</script>
   
<!--CHATBOT-->
<!-- Chatbot Container -->
    <div class="chatbot-container">
        <div id="chat-text-box">
            <div id="chat-text">Hi, I'm the DRKIST Assistant</div>
            <div id="chat-text">How can I assist you today?</div>
        </div>
        <div id="chat-icon" onclick="toggleChatbox()">
            <img src="assets/images/cbot.png" alt="Chatbot" style="width: 90px; height: 141px;"/>
        </div>
    </div>

    <!-- Chatbox -->
    <div class="chatbox" id="chatbox">
        <div id="chat-header">
        <strong>    <span>DRKIST Assistant</span></strong>
            <button id="close-chatbox" onclick="toggleChatbox()">×</button>
        </div>
        <div id="chat-messages"></div>
        <div id="chat-options">
            <button onclick="selectOption('College Timings')">College Timings</button>
            <button onclick="selectOption('Courses Available')">Courses Available</button>
            <button onclick="selectOption('Placements')">Placements</button>
            <button onclick="selectOption('Address')">Address</button>
        </div>
        <div id="chat-input">
            <input type="text" id="input-box" placeholder="Ask me anything...">
            <button id="send-btn" onclick="sendMessage()">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
    
<style>    
/* Chatbot Styles */
.chatbot-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: flex;
            align-items: flex-end;
            gap: 10px;
            z-index: 1000;
        }

        #chat-text-box {
            background-color:maroon;
            padding: 10px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            color: black;
            font-size: 14px;
            display: none;
            color:white;
        }

        #chat-icon {
            cursor: pointer;
            background: none;
            transition: transform 0.3s;
    
        }

        #chat-icon:hover {
            transform: scale(1.1);
        }

        .chatbox {
            position: fixed;
            bottom: 60px;
            right: 20px;
            width: 250px;
            height: 350px;
            background-color: #fff;
            border-radius: 10px;
            z-index: 1000;
            display: none;
            flex-direction: column;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            border: 1px solid #ddd;
        }

        #chat-header {
            background-color: maroon;
            color: white;
            padding: 5px 5px;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        #close-chatbox {
            background: none;
            border: none;
            color: white;
            font-size: 16px;
            cursor: pointer;
            padding: 5px;
        }

        #chat-messages {
            flex-grow: 1;
            overflow-y: auto;
            padding: 2px;
            background-color: #f9f9f9;
        }

        .message {
            margin-bottom: 10px;
            padding: 4px 5px;
            border-radius: 18px;
            max-width: 100%;
            word-wrap: break-word;
        }

        .user-message {
            background-color: #e3f2fd;
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }

        .bot-message {
            background-color: #f1f1f1;
            margin-right: auto;
            border-bottom-left-radius: 5px;
        }

        #chat-input {
            display: flex;
            padding: 10px;
            background-color: #fff;
            border-top: 1px solid #ddd;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
        }

        #input-box {
            flex-grow: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 20px;
            outline: none;
        }

        #send-btn {
            background-color: maroon;
            color: white;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            margin-left: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #chat-options {
            display: grid;
            grid-template-columns: 1fr;
            grid-template-rows: 0.5fr;
            gap: 1px;
            padding: 2px;
            background-color: #f9f9f9;
        }

        #chat-options button {
            background-color: maroon;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            text-align: left;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        #chat-options button:hover {
            background-color: #5a0a0a;
        }

        @media (max-width: 768px) {
            .chatbox {
                width: 280px;
                right: 10px;
            }
            
            #chat-text-box {
                max-width: 200px;
            }
        }
</style> 
    

<!-- chatbot*/ -->
<script>
const faqResponses = {
    "what is college name": "The college name is DRK Institute of Science and Technology.",
    "contact": "Mobile: +91-90007 11899 or +91-87907 11899, Mail: principal@drkist.edu.in",
    "courses": "We offer:\n- CSE\n- CSE (AI & ML)\n- CSE (Data Science)\n- CSE (Cyber Security)\n- Electronics and Communication Engineering\n- Electrical and Electronics Engineering \n- Mechanical Engineering\n- Civil Engineering",
    "admission": "Apply through Telangana EAPCET or contact Admission Department for Management Quota.",
    "timings": "9:20 AM to 4:20 PM, Monday to Saturday.",
    "about": "DRKIST is a premier educational institution dedicated to helping students learn and grow.",
    "address": "Near Pragathi Nagar, Bowrampet (V), Hyderabad - 500043, Telangana, India",
    "placement officer":"Syed Irfan Ali is the Placement Officer of Principal of DRK Institute of Science & Technology\n We have a dedicated Placement.",
    "placements": "We have a dedicated Placement Cell and Top companies visit our college",
    "hostel": "Not currently available.",
    "transport": "We provide bus facilities across Hyderabad.",
    "check results": "Check on JNTUH website or DRKIST Results Portal.",
    "eligibility": "Eligibility varies. For B.Tech: Intermediate (10+2) or Diploma. For MBA: Bachelor's degree in any field.",
    "library": "Yes, the college has a well-stocked library with a wide range of books.",
    "syllabus": "Available on JNTUH website.",
    "duration": "B.Tech is 4 years (8 semesters).",
    "cse of hod": "Dr. K. Kanaka Vardhini is the Head of CSE",
    "hod of csm": "Dr. Durga Prasad Kavadi is the Head of CSE (AI & ML).",
    "hod of csd": "Mr. Jacob Jayaraj G is the Head of CSE (Data Science).",
    "hod of csc": "Dr. Durga Prasad Kavadi is the Head of CSE (Cyber Security).",
    "hod of eee": "Mrs. Y. Sai Jyotirmayi is the Head of EEE.",
    "hod of mechanical": "Dr. Pavan Bagali is the Head of Mechanical Engineering.",
    "hod of ece": "Ms. M. Sravanthi is the Head of ECE.",
    "hod of hr": "Mr. K.T. Thomas is the Head of HR.",
    "hod of mba": "Dr. P. Prasanthi is the Head of MBA Department.",
    "how to join club":"Club events and sessions are conducted on Saturdays.You can join by contacting head of club",
    "clubs": "We have technical and cultural clubs. Sessions usually take place on Saturdays.",
    "labs": "We have specialized labs for each stream.",
    "fees": "Please contact the Admission Department for the latest fee structure.",
    "tc": "Visit the Admission Department for your Transfer Certificate (TC).",
    "bonafide": "Request your Bonafide Certificate at the Admission Department.",
    "professor": "Yes, contact details (email & mobile) are available on the official website.",
    "timetable": "Please refer to the DRKIST attendance portal for your class timetable.",
    "minimum attendance required": "75% attendance is mandatory.",
    "attendance": "Check your attendance on the DRKIST Attendance Portal using your Roll Number.",
    "sports": "Yes, we offer a variety of sports and activities including:\n- Cricket\n- Basketball\n- Volleyball\n- Kho-Kho\n- Throw Ball\n- Badminton",
    "chairman":"Sri D.B Chandra Sekhara Rao is the Chairman of DRK",
    "Secretary":"Sri D. Santosh",
    "Treasurer":"Sri D. Sriram",
    "principal":"Dr. Gnaneswara Rao Nitta is the Principal of DRK Institute of Science & Technology",
};

const keywordMap = {
    "support":"placements",
    "provide":"placements",
    "tpo":"placement officer",
    "training":"placement officer",
    "minimum attendance":"minimum attendance required",
    "student attendance":"attendance",
    "check attendance":"attendance",
    "faculty":"professor",
    "aiml":"hod of csm",
    "cyber security":"hod of csc",
    "data science":"hod of csd",
    "open":"timings",
    "admit":"admission",
    "seat":"admission",
    "course": "courses",
    "program": "courses",
    "study": "courses",
    "stream": "courses",
    "branch": "courses",
    "time": "timings",
    "schedule": "timings",
    "hour": "timings",
    "location": "address",
    "locate": "address",
    "where": "address",
    "place": "address",
    "job": "placements",
    "career": "placements",
    "recruitment": "placements",
    "stay": "hostel",
    "accommodation": "hostel",
    "bus": "transport",
    "travel": "transport",
    "result": "results",
    "mark": "results",
    "score": "results",
    "eligible": "eligibility",
    "qualification": "eligibility",
    "requirement": "eligibility",
    "fee": "fees",
    "payment": "fees",
    "cost": "fees",
    "record": "transcripts",
    "certificate": "bonafide",
    "proof": "bonafide",
    "tc": "tc",
    "transfer": "tc",
    "to join clubs":"how to join club",
    "club": "clubs",
    "society": "clubs",
    "group": "clubs",
    "lab": "labs",
    "practical": "labs",
    "experiment": "labs",
    "uniform": "dress code",
    "clothes": "dress code",
    "wear": "dress code",
    "class": "timetable",
    "schedule": "timetable",
    "present": "attendance",
    "absent": "attendance",
    "percentage": "minimum attendance",
    "requirement": "minimum attendance",
    "mandatory": "minimum attendance",
};
// Show initial message bubble
setTimeout(() => {
    document.getElementById('chat-text-box').style.display = 'block';
    setTimeout(() => {
        document.getElementById('chat-text-box').style.display = 'none';
    }, 3000);
}, 2000);

// Toggle chat visibility
function toggleChatbox() {
    const chatbox = document.getElementById('chatbox');
    if (chatbox.style.display === 'none' || !chatbox.style.display) {
        chatbox.style.display = 'flex';
        displayGreeting();
    } else {
        chatbox.style.display = 'none';
    }
}

// Greet based on time
function displayGreeting() {
    const hour = new Date().getHours();
    const greeting = hour < 12 ? "Good Morning!" : hour < 18 ? "Good Afternoon!" : "Good Evening!";
    addMessage(`<strong>DRK Assistant:</strong> ${greeting}`, 'bot');
    addMessage("<strong>DRK Assistant:</strong> How can I assist you today?", 'bot');
}

// Add message to chat UI
function addMessage(text, sender) {
    const chatMessages = document.getElementById('chat-messages');
    const messageDiv = document.createElement('div');
    messageDiv.classList.add('message', sender === 'user' ? 'user-message' : 'bot-message');
    
    // Use innerHTML to render bold tags
    messageDiv.innerHTML = text;

    // Handle line breaks for bot messages
    if (sender === 'bot') {
        const brCount = (text.match(/<br>/g) || []).length;
        if (brCount > 0) {
            const parts = text.split('<br>');
            messageDiv.innerHTML = parts.join('<br>');
        }
    }

    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Button click triggers
function selectOption(option) {
    addMessage(`<strong>You:</strong> ${option}`, 'user');
    const response = getBestResponse(option);
    setTimeout(() => {
        addMessage(`<strong>DRK Assistant:</strong> ${response}`, 'bot');
    }, 500);
}

function getBestResponse(message) {
    const cleanMsg = message.toLowerCase().replace(/[^\w\s]/gi, '');
    
    // Exact match
    if (faqResponses[cleanMsg]) {
        return faqResponses[cleanMsg];
    }

    // Check keyword mappings
    const words = cleanMsg.split(/\s+/);
    for (const word of words) {
        const mapped = keywordMap[word];
        if (mapped && faqResponses[mapped]) {
            return faqResponses[mapped];
        }
    }

    // Soft match: check if the user message includes any faq key
    for (const key in faqResponses) {
        if (cleanMsg.includes(key)) {
            return faqResponses[key];
        }
    }

    // Match with similar questions
    const similarity = (a, b) => {
        const aWords = a.split(' ');
        const bWords = b.split(' ');
        let matchCount = 0;
        aWords.forEach(word => {
            if (bWords.includes(word)) matchCount++;
        });
        return matchCount / Math.max(aWords.length, bWords.length);
    };

    let bestMatch = "";
    let bestScore = 0;
    for (const key in faqResponses) {
        const score = similarity(cleanMsg, key);
        if (score > bestScore) {
            bestScore = score;
            bestMatch = key;
        }
    }

    if (bestScore >= 0.4) {
        return faqResponses[bestMatch];
    }

    return "I'm sorry, I don't understand that question. Please try asking something else or use the options above.";
}

// Handle send
function sendMessage() {
    const inputBox = document.getElementById('input-box');
    const message = inputBox.value.trim();
    if (message) {
        addMessage(`<strong>You:</strong> ${message}`, 'user');
        inputBox.value = '';
        setTimeout(() => {
            const response = getBestResponse(message);
            addMessage(`<strong>DRK Assistant:</strong> ${response}`, 'bot');
        }, 800);
    }
}

// Enter key triggers send
document.getElementById('input-box').addEventListener('keypress', function (e) {
    if (e.key === 'Enter') sendMessage();
});

// Hide chat initially
document.getElementById('chatbox').style.display = 'none';
</script>

</body>
</html>