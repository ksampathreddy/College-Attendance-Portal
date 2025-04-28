<?php
include '../db_connection.php';
require_once '../vendor/autoload.php'; // Ensure this path is correct

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['subjects_file'])) {
    $uploadOk = 1;
    $targetDir = __DIR__ . "/uploads/"; // Ensure this folder exists and is writable

    // Create uploads directory if not exists
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // Check if file was uploaded
    if (!isset($_FILES['subjects_file']['tmp_name']) || !file_exists($_FILES['subjects_file']['tmp_name'])) {
        $message = '<div class="alert alert-error">No file uploaded or upload failed.</div>';
        $uploadOk = 0;
    }

    // Validate file type
    $fileType = strtolower(pathinfo($_FILES['subjects_file']['name'], PATHINFO_EXTENSION));
    if ($fileType != 'xlsx' && $fileType != 'xls') {
        $message = '<div class="alert alert-error">Only Excel files (.xlsx, .xls) are allowed!</div>';
        $uploadOk = 0;
    }

    // Validate file size (max 5MB)
    if ($_FILES['subjects_file']['size'] > 5000000) {
        $message = '<div class="alert alert-error">File is too large (max 5MB)!</div>';
        $uploadOk = 0;
    }

    if ($uploadOk == 1) {
        $targetFile = $targetDir . basename($_FILES['subjects_file']['name']);

        // Move file to a writable directory before processing
        if (move_uploaded_file($_FILES['subjects_file']['tmp_name'], $targetFile)) {
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($targetFile);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();

                // Remove empty rows
                $rows = array_filter($rows, function($row) {
                    return !empty(array_filter($row, fn($cell) => trim($cell) !== ''));
                });

                if (empty($rows)) {
                    throw new Exception("Excel file contains no data.");
                }

                // Check for header row
                $headerRow = reset($rows);
                $headerString = implode('', array_map('strval', $headerRow));
                if (stripos($headerString, 'subject') !== false || stripos($headerString, 'branch') !== false) {
                    array_shift($rows); // Remove header row
                }

                // Prepare SQL statement
                $stmt = $conn->prepare("INSERT INTO subjects (subject_name, branch, semester) VALUES (?, ?, ?)");
                if (!$stmt) {
                    throw new Exception("Database prepare failed: " . $conn->error);
                }

                $successCount = 0;
                $errorCount = 0;
                $conn->begin_transaction();

                foreach ($rows as $row) {
                    if (count($row) < 3) {
                        $errorCount++;
                        continue;
                    }

                    $subject_name = trim($row[0] ?? '');
                    $branch = trim($row[1] ?? '');
                    $semester = trim($row[2] ?? '');

                    if (empty($subject_name) || empty($branch) || empty($semester)) {
                        $errorCount++;
                        continue;
                    }

                    try {
                        $stmt->bind_param("sss", $subject_name, $branch, $semester);
                        if ($stmt->execute()) {
                            $successCount++;
                        } else {
                            $errorCount++;
                        }
                    } catch (mysqli_sql_exception $e) {
                        if ($e->getCode() == 1062) { // Skip duplicate entries
                            $errorCount++;
                            continue;
                        }
                        throw $e;
                    }
                }

                $conn->commit();
                unlink($targetFile); // Delete file after processing

                $message = '<div class="alert alert-success">Subjects imported successfully! ' . 
                          $successCount . ' records added. ' . 
                          ($errorCount > 0 ? $errorCount . ' duplicates/skipped.' : '') . '</div>';
            } catch (Exception $e) {
                if ($conn->in_transaction) {
                    $conn->rollback();
                }
                $message = '<div class="alert alert-error">Error importing subjects: ' . htmlspecialchars($e->getMessage()) . '</div>';
                error_log("Excel Import Error: " . $e->getMessage());
            }
        } else {
            $message = '<div class="alert alert-error">File upload failed.</div>';
        }
    }
}
?>

<!-- HTML Form for Upload -->
<h2 style="text-align:center">Upload Subjects</h2>
<div class="upload-form">
    
  
    <?php if (isset($message)) echo $message; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="subjects_file">Select Excel File (XLSX)</label>
            <input type="file" id="subjects_file" name="subjects_file" accept=".xlsx, .xls" required>
     
        </div>
        <button type="submit" class="btn">Upload Subjects</button>
    </form>
</div>

<!-- Excel File Format Requirements -->
<div class="format-instructions">
    <h3>Excel File Format </h3>
    <a href="../templates/subjects_template.xlsx" class="btn btn-secondary" download>
    <i class="fas fa-file-download"></i> Download Template
</a>
    <p>Your Excel file must contain exactly these columns in the first row:</p>
    
    <div class="format-table-container">
        <table class="format-example">
            <thead>
                <tr class="header-row">
                    <th>Subject Name</th>
                    <th>Branch</th>
                    <th>Semester</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Mathematics</td>
                    <td>CSE</td>
                    <td>1-1</td>
                </tr>
                <tr>
                    <td>Physics</td>
                    <td>ECE</td>
                    <td>2-2</td>
                </tr>
                <tr>
                    <td>Programming</td>
                    <td>CSE</td>
                    <td>4-1</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="format-requirements">
        <h4>Important:</h4>
        <ul>
            <li>First row must be the header row exactly as shown</li>
            <li>Semester must be a valid semester number (1-1, 1-2, 2-1, 2-2, 3-1, 3-2, 4-1, 4-2)</li>
            <li>Only .xlsx or .xls files accepted (max 5MB)</li>
        </ul>
    </div>
</div>

<!-- CSS -->
<style>
.upload-form {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
    /* background: #f8f9fa; */
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.format-instructions {
    margin: 25px 0;
    padding: 20px;
    /* background: #f8f9fa; */
    border-radius: 8px;
    border: 1px solid #e0e0e0;
}
.format-example {
    width: 100%;
    border-collapse: collapse;
}
.format-example th, .format-example td {
    padding: 12px;
    border: 1px solid #ddd;
    text-align: left;
}
.format-example th {
    background-color: #3498db;
    color: white;
    font-weight: 600;
}
.format-requirements {
    padding: 15px;
    /* background: #eaf2f8; */
    border-radius: 5px;
}
</style>
