<?php
include 'db_connection.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notice Board - DRKIST</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" type="image/x-icon" href="assets/images/drk.png">
    <style>
          body {
            margin: 0;
            font-family: Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .notice-board h2 {
            text-align: center;
            color: maroon;
            margin-bottom: 30px;
        }

        .notice {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 25px;
        }

        .notice h3 {
            color: maroon;
            margin-top: 0;
            margin-bottom: 10px;
        }

        .notice-meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .notice-content {
            line-height: 1.6;
        }

        /* ===== Footer Styles ===== */
        footer {
            background-color: black;
            padding: 20px;
            color: white;
            text-align: center;
        }
      
        header {
            background-color: white;
            padding: 15px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative;
        }

        .logo-title-container {
            display: flex;
            align-items: center;
            width: 100%;
            position: relative;
        }

      .logo-title-container img {
            height: 50px;
            width: 30%;
            position: absolute;
            left: 50px;
        }

        .logo-title-container h1 {
            margin: 0;
            font-size: 1.8rem;
            color: maroon;
            text-align: center;
            width: 100%;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }

        .home-btn {
            display: inline-block;
            background-color: #343a40;
            color: white;
            padding: 5px 20px;
            border-radius: 4px;
            text-decoration: none;
            margin: 10px 0;
            text-align: center;
            transition: background-color 0.3s;
            margin-left: auto;
            margin-right: 50px;
            position: relative;
            z-index: 1;
        }
        
        .home-btn:hover {
            background-color: rgb(27, 86, 144);
        }
        .role-selector {
            margin: 15px 0;
            text-align: center;
        }
        .role-selector span {
            font-weight: bold;
            color: maroon;
        }
      
        .role-selector {
            margin: 15px 0;
            text-align: center;
        }
        .role-selector span {
            font-weight: bold;
            color: maroon;
        }
       
       
        /* ===== Main Content Styles ===== */
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .notice-board h2 {
            text-align: center;
            color: maroon;
            margin-bottom: 30px;
        }

        .notice {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 25px;
        }

        .notice h3 {
            color: maroon;
            margin-top: 0;
            margin-bottom: 10px;
        }

        .notice-meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .notice-content {
            line-height: 1.6;
        }

        /* ===== Footer Styles ===== */
        footer {
            background-color: black;
            padding: 20px;
    
            text-align: center;
        }

        /* ===== Responsive Adjustments ===== */
   
        @media (max-width: 767px) {
            .logo-title-container {
                flex-direction: column;
                padding-top: 10px;
            }
            
            .logo-title-container img {
                position: static;
                margin-bottom: 10px;
                width:auto;
            }
            
            .logo-title-container h1 {
                position: static;
                transform: none;
                order: 2;
            }
            
            .home-btn {
                order: 3;
                margin: 10px auto 0;
            }
        }

        @media (max-width: 480px) {
            .logo-title-container h1 {
                font-size: 1.3rem;
            }
            
            .logo-title-container img {
                height: 40px;
            }
            
            .home-btn {
                padding: 8px 15px;
                font-size: 0.9rem;
            }
        }
        


/* Footer Styles */
footer {
    background-color: black;
    padding: 20px;
    width: 100%;
    heidht:5px;
}

.contain {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Responsive adjustments for footer */
@media (max-width: 767px) {
    .contain {
        flex-direction: column;
        text-align: center;
    }
    
    .footer-develop {
        margin-top: 10px;
    }
}

   
     </style>
</head>
<body>
<header>
            <div class="logo-title-container">
                <img src="assets/images/D.png" alt="DRKIST Logo">
                <h1>ATTENDANCE PORTAL</h1>
                <a href="index.php" class="home-btn">Home</a>
            </div>     
    </header>

    <main class="container">
        <section class="notice-board">
            <h2 class="text-center">Notice Board</h2>
            
            <?php
            $sql = "SELECT * FROM notices ORDER BY posted_at DESC";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<div class='notice'>";
                    echo "<h3>" . htmlspecialchars($row['title']) . "</h3>";
                    echo "<p class='notice-meta'>Posted by " . htmlspecialchars($row['posted_by']) . " on " . date('d M Y, h:i A', strtotime($row['posted_at'])) . "</p>";
                    echo "<div class='notice-content'>" . nl2br(htmlspecialchars($row['content'])) . "</div>";
                    echo "</div>";
                }
            } else {
                echo "<p class='text-center'>No notices found.</p>";
            }
            ?>
        </section>
    </main>

    <footer>
        <div class="contain">
            <p class="text-center" style="color: white;">© <span id="year"></span>  DRK Institute of Science and Technology, All Rights Reserved</p>
            <p class="footer-develop" style="margin: 0;color:white">Developed by K Sampath Reddy CSE[AI&ML]</p>

        </div>
    </footer>
    <script>
    // Get current year
    document.getElementById("year").textContent = new Date().getFullYear();
</script>

<div class="scroll-to-top">
    <img src="assets/images/ar.png" alt="Scroll to top" id="scrollTopBtn">
</div>

<style>
/* Add this to your CSS */
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

</body>
</html>