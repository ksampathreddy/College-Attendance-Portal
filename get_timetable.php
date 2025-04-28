<?php
include 'db_connection.php';

// Get unique values for dropdowns
$batches = $conn->query("SELECT DISTINCT batch FROM students ORDER BY batch DESC")->fetch_all(MYSQLI_ASSOC);
$branches = $conn->query("SELECT DISTINCT branch FROM students ORDER BY branch")->fetch_all(MYSQLI_ASSOC);
$semesters = ['1-1', '1-2', '2-1', '2-2', '3-1', '3-2', '4-1', '4-2'];

// Get unique sections from students table
$sections = $conn->query("SELECT DISTINCT section FROM students WHERE section IS NOT NULL AND section != '' ORDER BY section")->fetch_all(MYSQLI_ASSOC);

// Get timetables based on filters
$timetables = [];
if (isset($_GET['batch']) && isset($_GET['branch']) && isset($_GET['semester'])) {
    $batch = $conn->real_escape_string($_GET['batch']);
    $branch = $conn->real_escape_string($_GET['branch']);
    $semester = $conn->real_escape_string($_GET['semester']);
    $section = isset($_GET['section']) && $_GET['section'] !== '' ? $conn->real_escape_string($_GET['section']) : NULL;
    
    $sql = "SELECT * FROM timetables 
            WHERE batch = '$batch' 
            AND branch = '$branch' 
            AND semester = '$semester' 
            AND (section = '$section' OR (section IS NULL AND '$section' IS NULL))
            ORDER BY uploaded_at DESC";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Fix file path to be relative to the web root
            $row['file_path'] = str_replace('../', '', $row['file_path']);
            $timetables[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable - DRKIST</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" type="image/x-icon" href="assets/images/drk.png">
    <style>
        /* Header Styles */
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ===== Header Styles from code2 ===== */
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
            left: -18px;
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

        /* ===== Main Content Styles ===== */
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            flex: 1;
            
        }

        .timetable-board h2 {
            text-align: center;
            color: maroon;
            margin-bottom: 30px;
        }

        .filter-form {
            /* background-color: #fff; */
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 25px;
            
            
        }

        .form-row {
            flex-direction: column;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
         margin: 0 auto; /* Center the input box while keeping text left-aligned */
justify-content:center;
            width:50%;
            
        }

        .form-group {
            flex: 1;
            width:50%;
               margin: 0 auto; /* Center the input box while keeping text left-aligned */

        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .btn {
            display: inline-block;
            background-color: maroon;
            color: white;
            padding: 5px 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
            right:40px;
            margin:10px;
            
        }

        .btn:hover {
            background-color: #5a0a0a;
        }

        .timetable {
            /* background-color: #fff; */
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 25px;
        }

        .timetable h3 {
            color: maroon;
            margin-top: 0;
            margin-bottom: 15px;
        }

        .timetable-meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .timetable-file {
            margin-top: 20px;
            text-align: center;
        }

        .timetable-file img {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .timetable-file iframe {
            width: 100%;
            height: 500px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .no-timetable {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        /* ===== Footer Styles ===== */
        footer {
            background-color: black;
            padding: 20px;
            color: white;
            text-align: center;
            margin-top: auto;
        }

        .contain {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
            
            .form-group {
                min-width: 100%;
            }
            
            .contain {
                flex-direction: column;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .logo-title-container h1 {
                font-size: 1.3rem;
            }
            
            .timetable-file iframe {
                height: 300px;
            }
        }
       @media (max-width: 480px) {        
            .logo-title-container img {
                height: 40px;
                     width: auto;
            }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo-title-container">
                <img src="assets/images/D.png" alt="DRKIST Logo">
                <h1>ATTENDANCE PORTAL</h1>
                <a href="index.php" class="home-btn">Home</a>
            </div>     
        </div>
    </header>

    <main class="container">
        <section class="timetable-board">
            <h2 class="text-center">Timetable</h2>
            
            <form method="GET" class="filter-form">
                
                    <div class="form-group">
                        <label for="batch">Batch</label>
                        <select id="batch" name="batch" class="form-control" required>
                            <option value="">Select Batch</option>
                            <?php foreach ($batches as $b): ?>
                                <option value="<?= htmlspecialchars($b['batch']) ?>" <?= isset($_GET['batch']) && $_GET['batch'] == $b['batch'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['batch']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="branch">Branch</label>
                        <select id="branch" name="branch" class="form-control" required>
                            <option value="">Select Branch</option>
                            <?php foreach ($branches as $br): ?>
                                <option value="<?= htmlspecialchars($br['branch']) ?>" <?= isset($_GET['branch']) && $_GET['branch'] == $br['branch'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($br['branch']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
              
                <div class="form-group">
    <label for="section">Section(Optional)</label>
    <select id="section" name="section" class="form-control">
        <option value="" <?= isset($_GET['section']) && $_GET['section'] === '' ? 'selected' : '' ?>>No Section</option>
        <?php foreach ($sections as $sec): ?>
            <option value="<?= htmlspecialchars($sec['section']) ?>" <?= isset($_GET['section']) && $_GET['section'] == $sec['section'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($sec['section']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>  
                    <div class="form-group">
                        <label for="semester">Semester</label>
                        <select id="semester" name="semester" class="form-control" required>
                            <option value="">Select Semester</option>
                            <?php foreach ($semesters as $sem): ?>
                                <option value="<?= htmlspecialchars($sem) ?>" <?= isset($_GET['semester']) && $_GET['semester'] == $sem ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sem) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
            
                
                <div class="form-row">
                    <button type="submit" class="btn">View Timetable</button>
                    <a href="get_timetable.php" class="btn btn-reset">Reset Filters</a>
                </div>
            </form>
            
            <?php if (!empty($timetables)): ?>
                <?php foreach ($timetables as $timetable): ?>
                    <div class="timetable">
                        <h3><?= htmlspecialchars($timetable['file_name']) ?></h3>
                        <p class="timetable-meta">
    Uploaded for: <?= htmlspecialchars($timetable['batch']) ?> - 
    <?= htmlspecialchars($timetable['branch']) ?> - 
    Semester <?= htmlspecialchars($timetable['semester']) ?> - 
    <?= $timetable['section'] ? 'Section '.htmlspecialchars($timetable['section']) : 'All Sections' ?>
    <br>
    Uploaded by: <?= htmlspecialchars($timetable['uploaded_by']) ?> on <?= date('d M Y, h:i A', strtotime($timetable['uploaded_at'])) ?>
</p>
                        
                        <div class="timetable-file">
                            <?php 
                            $file_ext = strtolower(pathinfo($timetable['file_path'], PATHINFO_EXTENSION));
                            if ($file_ext === 'pdf'): ?>
                                <iframe src="<?= htmlspecialchars($timetable['file_path']) ?>#toolbar=0"></iframe>
                                <br>
                                <a href="<?= htmlspecialchars($timetable['file_path']) ?>" class="file-download" download>Download PDF</a>
                            <?php elseif (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                <img src="<?= htmlspecialchars($timetable['file_path']) ?>" alt="Timetable Image">
                                <br>
                                <a href="<?= htmlspecialchars($timetable['file_path']) ?>" class="file-download" download>Download Image</a>
                            <?php else: ?>
                                <p>Unsupported file format. Please download the file.</p>
                                <a href="<?= htmlspecialchars($timetable['file_path']) ?>" class="file-download" download>Download File</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php elseif (isset($_GET['batch'])): ?>
                <div class="no-timetable">
                    <p>No timetable found for the selected criteria.</p>
                </div>
            <?php else: ?>
                <div class="no-timetable">
                    <p>Please select batch, branch, semester and section to view timetable.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <div class="contain">
            <p class="text-center">© <span id="year"></span> DRK Institute of Science and Technology, All Rights Reserved</p>
            <p class="footer-develop" style="color:white;font-size:13px;">Developed by K Sampath Reddy CSE[AI&ML]</p>
        </div>
    </footer>
    
    <script>
        document.getElementById("year").textContent = new Date().getFullYear();
    </script>
</body>
</html>