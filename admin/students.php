<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

include '../db_connection.php';

$message = "";
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'add';

// Handle form submission for adding a single student
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    $batch = $_POST['batch'];
    $branch = strtoupper($_POST['branch']);
    $name = $_POST['name'];
    $rollno = $_POST['rollno'];
    $section = isset($_POST['section']) ? $_POST['section'] : null;

    // Check if the student already exists
    $sql = "SELECT rollno FROM students WHERE rollno = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $rollno);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "<div class='alert alert-error'>Student with this roll number already exists.</div>";
        } else {
            // Insert student into the database
            $sql = "INSERT INTO students (batch, branch, name, rollno, section) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("sssss", $batch, $branch, $name, $rollno, $section);
                if ($stmt->execute()) {
                    $message = "<div class='alert alert-success'>Student added successfully!</div>";
                } else {
                    $message = "<div class='alert alert-error'>Error adding student: " . $conn->error . "</div>";
                }
                $stmt->close();
            } else {
                $message = "<div class='alert alert-error'>Database error: " . $conn->error . "</div>";
            }
        }
    } else {
        $message = "<div class='alert alert-error'>Database error: " . $conn->error . "</div>";
    }
    $activeTab = 'add';
}

// Process file upload if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    // Check file size (max 2MB)
    if ($_FILES['file']['size'] > 2097152) {
        $message = "<div class='alert alert-error'>Error: File size exceeds 2MB limit.</div>";
        $activeTab = 'upload';
    } 
    // Check file extension
    elseif (!in_array(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION), ['xlsx', 'xls', 'csv'])) {
        $message = "<div class='alert alert-error'>Error: Invalid file format. Only .xlsx, .xls, or .csv files are allowed.</div>";
        $activeTab = 'upload';
    } 
    else {
        // Require PhpSpreadsheet
        require '../vendor/autoload.php';
        
        try {
            $file = $_FILES['file']['tmp_name'];
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();
            
            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            
            // Start transaction for better error handling
            $conn->begin_transaction();
            
            for ($row = 2; $row <= $highestRow; $row++) {
                $rollno = trim($sheet->getCell('A' . $row)->getValue());
                $name = trim($sheet->getCell('B' . $row)->getValue());
                $batch = trim($sheet->getCell('C' . $row)->getValue());
                $branch = trim($sheet->getCell('D' . $row)->getValue());
                $section = trim($sheet->getCell('E' . $row)->getValue()) ?: null;
            
                if (!empty($rollno)) {
                    if (empty($name) || empty($batch) || empty($branch)) {
                        $errors[] = "Row $row: Missing required fields";
                        $errorCount++;
                        continue;
                    }
            
                    $sql = "INSERT INTO students (rollno, name, batch, branch, section) VALUES (?, ?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE name = VALUES(name), batch = VALUES(batch), branch = VALUES(branch), section = VALUES(section)";
                    $stmt = $conn->prepare($sql);
            
                    if ($stmt) {
                        $stmt->bind_param("sssss", $rollno, $name, $batch, $branch, $section);
            
                        if ($stmt->execute()) {
                            $successCount++;
                        } else {
                            $errors[] = "Row $row: " . $stmt->error;
                            $errorCount++;
                        }
                    } else {
                        $errors[] = "Row $row: Database preparation error";
                        $errorCount++;
                    }
                }
            }
            
            if ($errorCount == 0) {
                $conn->commit();
                $message = "<div class='alert alert-success'>Upload completed successfully: $successCount records added/updated.</div>";
            } else {
                $conn->rollback();
                $errorDetails = implode("<br>", array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $errorDetails .= "<br>...and " . (count($errors) - 5) . " more errors";
                }
                $message = "<div class='alert alert-error'>Upload completed with $errorCount errors (out of " . ($successCount + $errorCount) . " records):<br>$errorDetails</div>";
            }
            
        } catch (Exception $e) {
            if (isset($conn) && $conn) {
                $conn->rollback();
            }
            $message = "<div class='alert alert-error'>Error processing file: " . $e->getMessage() . "</div>";
        }
        $activeTab = 'upload';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - DRKIST</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/drk.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .main-container {
            /* background: #fff; */
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .tab-buttons-horizontal {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .tab-btn {
            flex: 1;
            padding: 15px 20px;
            background: transparent;
            border: none;
            cursor: pointer;
            font-weight: 500;
            text-align: center;
            transition: all 0.3s ease;
            color: #495057;
            border-bottom: 3px solid transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .tab-btn:hover {
            background: #e9ecef;
            color: #212529;
        }
        
        .tab-btn.active {
            color: #6c757d;
            border-bottom: 3px solid #6c757d;
            font-weight: 600;
        }
        
        .tab-btn i {
            font-size: 1.1em;
        }
        
        .tab-content {
            display: none;
            padding: 25px;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            border-color: #6c757d;
            outline: none;
            box-shadow: 0 0 0 3px rgba(108, 117, 125, 0.1);
        }
        
        .btn {
            background-color: #3498db;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn:hover {
            background-color: #2980b9;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 15px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .upload-instructions {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 25px;
            border-left: 4px solid #6c757d;
        }
        
        .upload-instructions h3 {
            margin-top: 0;
            color: #343a40;
        }
        
        .upload-instructions ol {
            padding-left: 20px;
            margin-bottom: 0;
        }
        
        .file-format {
            margin-top: 25px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
        }
        
        .file-format h3 {
            margin-top: 0;
            color: #343a40;
        }
        
        .file-format table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .file-format th, .file-format td {
            border: 1px solid #dee2e6;
            padding: 12px;
            text-align: left;
        }
        
        .file-format th {
            background-color: #6c757d;
            color: white;
        }
        
        .file-format tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        
        .progress-container {
            margin-top: 20px;
            display: none;
        }
        
        .progress-bar {
            height: 20px;
            background-color: #e9ecef;
            border-radius: 6px;
            overflow: hidden;
        }
        
        .progress {
            height: 100%;
            background-color: #6c757d;
            width: 0%;
            transition: width 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .tab-buttons-horizontal {
                flex-direction: column;
            }
            
            .tab-btn {
                justify-content: flex-start;
                padding: 12px 15px;
            }
        }
        .back-link {
            display: block;
            margin-top: 50px;
            text-align: center;
            color: #007bff;
            text-decoration: none;
            font-size:20px;
        }
    </style>
</head>
<body>
<div class="container header-container">
    
    <main class="container">
        <h2 style="text-align: center;" >Add Students</h2>
        
        <?php if (!empty($message)): ?>
            <div class="alert <?= strpos($message, 'success') !== false ? 'alert-success' : 'alert-error' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>
        
        <div class="main-container">
            <div class="tab-buttons-horizontal">
                <button class="tab-btn <?= $activeTab == 'add' ? 'active' : '' ?>" data-tab="add">
                    <i class="fas fa-user-plus"></i> Manual Entry
                </button>
                <button class="tab-btn <?= $activeTab == 'upload' ? 'active' : '' ?>" data-tab="upload">
                    <i class="fas fa-file-upload"></i> Upload Excel
                </button>
            </div>
            
            <div id="add" class="tab-content <?= $activeTab == 'add' ? 'active' : '' ?>">
                <div class="form-container">
                    <form method="POST">
                        <input type="hidden" name="add_student" value="1">
                        <div class="form-group">
    <label for="batch">Batch (YYYY-YYYY):</label>
    <input type="text" id="batch" name="batch" pattern="\d{4}-\d{4}" title="Please enter in YYYY-YYYY format (e.g. 2023-2027)" required>
</div>

                        <div class="form-group">
                            <label for="branch">Branch:</label>
                            <input type="text" id="branch" name="branch" required>
                        </div>
                        <div class="form-group">
                            <label for="section">Section (optional):</label>
                            <input type="text" id="section" name="section" maxlength="10">
                        </div>
                        <div class="form-group">
                            <label for="name">Name (max 25 characters):</label>
                            <input type="text" id="name" name="name" maxlength="25" required>
                        </div>
                        <div class="form-group">
                            <label for="rollno">Roll No:</label>
                            <input type="text" id="rollno" name="rollno" maxlength="15" required>
                        </div>
                        <button type="submit" class="btn">
                            <i class="fas fa-save"></i> Add Student
                        </button>
                    </form>
                </div>
            </div>
            
            <div id="upload" class="tab-content <?= $activeTab == 'upload' ? 'active' : '' ?>">
                <form id="uploadForm" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="file">Select Excel File:</label>
                        <input type="file" id="file" name="file" accept=".xlsx, .xls, .csv" required>
                        <small>Accepted format: .xlsx (Max 2MB)</small>
                       
                    </div>
                    <button type="submit" class="btn" id="uploadBtn">
                        <i class="fas fa-upload"></i> Upload Students
                    </button>
                </form>
         
                <div class="upload-instructions">
                    <h3>Instructions:</h3>
                    <a href="../templates/students_template.xlsx" class="btn btn-secondary" download>
    <i class="fas fa-file-download"></i> Download Template
</a>
                    <ol>
                        <li>Existing records with the same roll numbers will be updated</li>
                        <li>Maximum file size: 2MB</li>
                    </ol>
                </div>
                
                <div class="file-format">
                    <h3>Excel File Format:</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Roll No</th>
                                <th>Name</th>
                                <th>Batch (YYYY-YYYY)</th>
                                <th>Branch</th>
                                <th>Section (optional)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>DRK001</td>
                                <td>John Doe</td>
                                <td>2023-2027</td>
                                <td>CSE</td>
                                <td>A</td>
                            </tr>
                            <tr>
                                <td>DRK002</td>
                                <td>Jane Smith</td>
                                <td>2022-2026</td>
                                <td>ECE</td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.querySelectorAll('.tab-btn').forEach(button => {
            button.addEventListener('click', () => {
                const tabId = button.getAttribute('data-tab');
                
                const url = new URL(window.location);
                url.searchParams.set('tab', tabId);
                window.history.pushState({}, '', url);
                
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                button.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            if (tab && (tab === 'add' || tab === 'upload')) {
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                document.querySelector(`.tab-btn[data-tab="${tab}"]`).classList.add('active');
                document.getElementById(tab).classList.add('active');
            }
        });
    </script>
    <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>
</div>
</body>
</html>