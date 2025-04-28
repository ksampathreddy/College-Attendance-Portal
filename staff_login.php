<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['username'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/admin_dashboard.php");
    } elseif ($_SESSION['role'] == 'teacher') {
        header("Location: teacher/teacher_dashboard.php");
    }
    exit();
}

include 'db_connection.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // First check admin table
    $sql = "SELECT username, password FROM admins WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($db_username, $hashed_password);
    $stmt->fetch();

    if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
        $_SESSION['username'] = $db_username;
        $_SESSION['role'] = 'admin';
        header("Location: admin/admin_dashboard.php");
        exit();
    } else {
        // If not admin, check teacher table
        $sql = "SELECT username, password FROM teachers WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($db_username, $hashed_password);
        $stmt->fetch();

        if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
            $_SESSION['username'] = $db_username;
            $_SESSION['role'] = 'teacher';
            header("Location: teacher/teacher_dashboard.php");
            exit();
        } else {
            $message = "<p class='error'>Invalid username or password.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DRKIST</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" type="image/x-icon" href="assets/images/drk.png">
    <style>
        /* Center the form */
        .login-form {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            width: 100%;
            margin-bottom: 15px;
            color:red;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            left:0px;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .btn {
            width: 100%;
            padding: 10px;
            background-color: maroon;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn:hover {
            background-color: #800000;
        }
        
        .text-center {
            text-align: center;
        }
        
        .error {
            color: red;
            text-align: center;
        }
        
        .role-selector {
            text-align: center;
            margin: 10px 0;
        }
        
        .role-selector span {
            font-weight: bold;
            color: maroon;
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

        /* Main container */
        .container {
            max-width: 1500px;
            /* margin: 30px auto;
            padding: 0 20px; */
            background: url('../assets/images/B.jpg') center/cover no-repeat; 
            padding: 1px 3px;
            /* margin-bottom: 1px !important; */
             /* Force only 2px gap */
            padding-bottom: 0 !important; /* Ensure no extra padding */
            border-radius: 25px;
            margin: 1px 1px;
            color: white;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
  @media (max-width: 992px) {
        .container{
                min-height: 100vh;
                /* padding: 60px 20px; */
            }
  }

        footer {
            background-color: black;
            color: white;
            padding: 20px 0;
            text-align: center;
            width: 100%;
        }
        
        .contain {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .footer-develop {
            font-size: 0.9rem;
            color: #aaa;
            margin-top: 10px;
        }

        /* Responsive adjustments */
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
            
            .contain {
                flex-direction: column;
            }
             .login-form {
            max-width: 215px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
           .form-group {
            width: 50%;
            /* margin-bottom: 15px; */
            left:0px;
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
        <section class="login-form">
            <h2 class="text-center" style="color:maroon">Login</h2>
            <div class="role-selector">
                <span>For both Faculty and Administrators</span>
            </div>
            <?php echo $message; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn">Login</button>
            </form>
        </section>
    </main>

    <footer>
        <div class="contain">
            <p class="text-center" style="color: white;">© <span id="year"></span> DRK Institute of Science and Technology, All Rights Reserved</p>
            <p class="footer-develop" style="margin: 0;color:white">Developed by K Sampath Reddy CSE[AI&ML]</p>
        </div>
    </footer>
    <script>
        // Get current year
        document.getElementById("year").textContent = new Date().getFullYear();
    </script>
</body>
</html>