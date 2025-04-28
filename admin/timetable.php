<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

include '../db_connection.php';

$message = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['timetable_file'])) {
    $batch = $conn->real_escape_string($_POST['batch']);
    $branch = $conn->real_escape_string($_POST['branch']);
    $semester = $conn->real_escape_string($_POST['semester']);
    $section = isset($_POST['section']) ? $conn->real_escape_string($_POST['section']) : null;
    $uploaded_by = $_SESSION['username'];
    
    // File upload handling
    $target_dir = "../uploads/timetables/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = basename($_FILES['timetable_file']['name']);
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
    
    if (!in_array($file_ext, $allowed_ext)) {
        $message = '<div class="alert alert-error">Only PDF, JPG, JPEG, PNG files are allowed.</div>';
    } elseif ($_FILES['timetable_file']['size'] > 5 * 1024 * 1024) { // 5MB limit
        $message = '<div class="alert alert-error">File size must be less than 5MB.</div>';
    } else {
        // Generate unique filename
        $section_part = $section ? "_" . $section : "";
        $new_file_name = "timetable_" . $batch . "_" . $branch . "_" . $semester . $section_part . "_" . time() . "." . $file_ext;
        $target_file = $target_dir . $new_file_name;
        
        if (move_uploaded_file($_FILES['timetable_file']['tmp_name'], $target_file)) {
            // Insert into database
            $sql = "INSERT INTO timetables (batch, branch, semester, section, file_name, file_path, uploaded_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", $batch, $branch, $semester, $section, $file_name, $target_file, $uploaded_by);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Timetable uploaded successfully!</div>';
            } else {
                unlink($target_file); // Delete the uploaded file if DB insert fails
                $message = '<div class="alert alert-error">Error saving to database: ' . $conn->error . '</div>';
            }
        } else {
            $message = '<div class="alert alert-error">Error uploading file.</div>';
        }
    }
}

// Get existing timetables
$timetables = [];
$sql = "SELECT * FROM timetables ORDER BY batch DESC, branch, semester, section";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $timetables[] = $row;
    }
}

// Get all distinct values from students table for dropdowns
$batches = [];
$branches = [];
$sections = ['']; // Start with empty option

// Get batches
$batch_query = $conn->query("SELECT DISTINCT batch FROM students ORDER BY batch DESC");
if ($batch_query) {
    while ($row = $batch_query->fetch_assoc()) {
        $batches[] = $row;
    }
}

// Get branches
$branch_query = $conn->query("SELECT DISTINCT branch FROM students ORDER BY branch");
if ($branch_query) {
    while ($row = $branch_query->fetch_assoc()) {
        $branches[] = $row;
    }
}

// Get sections (including NULL values)
$section_query = $conn->query("SELECT DISTINCT section FROM students WHERE section IS NOT NULL ORDER BY section");
if ($section_query) {
    while ($row = $section_query->fetch_assoc()) {
        $sections[] = $row['section'];
    }
}

// Fixed semesters
$semesters = ['1-1', '1-2', '2-1', '2-2', '3-1', '3-2', '4-1', '4-2'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable Management</title>
    <style>
        .view-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            /* background: white; */
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .tab-btn {
            padding: 10px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            border-bottom: 3px solid transparent;
        }
        
        .tab-btn.active {
            border-bottom: 3px solid #8e0000;
            font-weight: bold;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn {
            padding: 10px 20px;
            background-color: #8e0000;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .btn-view {
            background-color: #007bff;
            padding: 5px 10px;
            margin-right: 5px;
        }
        
        .btn-delete {
            background-color: #dc3545;
            padding: 5px 10px;
        }
        
        .back-link {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #007bff;
            text-decoration: none;
        }
        
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #8e0000;
            color: white;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 900px;
            position: relative;
        }

        .close-btn {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }

        .close-btn:hover {
            color: #8e0000;
        }

        .viewer-container {
            margin-top: 20px;
            text-align: center;
        }

        /* PDF viewer styling */
        #pdfViewer {
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
            height: 500px;
        }

        /* Image viewer styling */
        #imageViewer {
            border: 1px solid #ddd;
            border-radius: 4px;
            max-width: 100%;
            max-height: 500px;
        }
    </style>
</head>
<body>
<h2 style="text-align:center">Timetable Management</h2>
    <div class="view-container">
     
        
        <?php echo $message; ?>
        
        <div class="tabs">
            <button class="tab-btn active" data-tab="upload">Upload Timetable</button>
            <button class="tab-btn" data-tab="view">View Timetables</button>
        </div>
        
        <div class="tab-content active" id="upload-tab">
            <form method="POST" enctype="multipart/form-data" class="timetable-form">
         
                    <div class="form-group">
                        <label for="batch">Batch</label>
                        <select id="batch" name="batch" required class="form-control">
                            <option value="">Select Batch</option>
                            <?php foreach ($batches as $b): ?>
                                <option value="<?= htmlspecialchars($b['batch']) ?>"><?= htmlspecialchars($b['batch']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="branch">Branch</label>
                        <select id="branch" name="branch" required class="form-control">
                            <option value="">Select Branch</option>
                            <?php foreach ($branches as $br): ?>
                                <option value="<?= htmlspecialchars($br['branch']) ?>"><?= htmlspecialchars($br['branch']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
           
                     
                <div class="form-group">
                        <label for="section">Section (Optional)</label>
                        <select id="section" name="section" class="form-control">
                            <option value="">No Section</option>
                            <?php foreach ($sections as $sec): ?>
                                <?php if (!empty($sec)): ?>
                                    <option value="<?= htmlspecialchars($sec) ?>"><?= htmlspecialchars($sec) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="semester">Semester</label>
                        <select id="semester" name="semester" required class="form-control">
                            <option value="">Select Semester</option>
                            <?php foreach ($semesters as $sem): ?>
                                <option value="<?= htmlspecialchars($sem) ?>"><?= htmlspecialchars($sem) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                   
            
                
                <div class="form-group">
                    <label for="timetable_file">Timetable File (PDF/JPG/PNG, max 5MB)</label>
                    <input type="file" id="timetable_file" name="timetable_file" accept=".pdf,.jpg,.jpeg,.png" required class="form-control">
                </div>
                
                <button type="submit" class="btn">Upload Timetable</button>
            </form>
        </div>
        
        <div class="tab-content" id="view-tab">
        <?php if (empty($timetables)): ?>
            <p>No timetables uploaded yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Batch</th>
                            <th>Branch</th>
                            <th>Semester</th>
                            <th>Section</th>
                            <th>File Name</th>
                            <th>Uploaded At</th>
                            <th>Uploaded By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($timetables as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['batch']) ?></td>
                                <td><?= htmlspecialchars($t['branch']) ?></td>
                                <td><?= htmlspecialchars($t['semester']) ?></td>
                                <td><?= $t['section'] ? htmlspecialchars($t['section']) : 'None' ?></td>
                                <td><?= htmlspecialchars($t['file_name']) ?></td>
                                <td><?= date('d-m-Y H:i', strtotime($t['uploaded_at'])) ?></td>
                                <td><?= htmlspecialchars($t['uploaded_by']) ?></td>
                                <td>
                                    <button class="btn btn-view" onclick="viewTimetable('<?= htmlspecialchars($t['file_path']) ?>', '<?= htmlspecialchars($t['file_name']) ?>')">View</button>
                                    <button class="btn btn-delete" onclick="deleteTimetable(<?= $t['id'] ?>)">Delete</button>
                                                     </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Timetable Viewer Modal -->
            <div id="timetableModal" class="modal">
                <div class="modal-content">
                    <span class="close-btn" onclick="closeModal()">&times;</span>
                    <h3 id="modalTitle"></h3>
                    <div class="viewer-container">
                        <iframe id="pdfViewer" style="display:none;" frameborder="0"></iframe>
                        <img id="imageViewer" style="display:none;">
                    </div>
                </div>
            </div>
        <?php endif; ?>
        </div>
    </div>

    <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>

    <script>
        // Tab functionality
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                // Remove active class from all buttons and contents
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked button and corresponding content
                btn.classList.add('active');
                const tabId = btn.getAttribute('data-tab') + '-tab';
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // View timetable in modal
        function viewTimetable(filePath, fileName) {
            const modal = document.getElementById('timetableModal');
            const modalTitle = document.getElementById('modalTitle');
            const pdfViewer = document.getElementById('pdfViewer');
            const imageViewer = document.getElementById('imageViewer');
            
            // Set the modal title
            modalTitle.textContent = fileName;
            
            // Determine file type and display accordingly
            const fileExt = filePath.split('.').pop().toLowerCase();
            
            if (fileExt === 'pdf') {
                pdfViewer.style.display = 'block';
                imageViewer.style.display = 'none';
                pdfViewer.src = filePath;
            } else {
                pdfViewer.style.display = 'none';
                imageViewer.style.display = 'block';
                imageViewer.src = filePath;
            }
            
            // Show the modal
            modal.style.display = 'block';
            
            // Close modal when clicking outside content
            window.onclick = function(event) {
                if (event.target == modal) {
                    closeModal();
                }
            }
        }
        function deleteTimetable(id) {
    if (confirm('Are you sure you want to delete this timetable?')) {
        // Get the row element for removal if successful
        const row = document.querySelector(`tr[data-id="${id}"]`);
        
        fetch('delete_timetable.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the row from the table
                if (row) row.remove();
                // Show success message (you could also use a toast notification)
                alert(data.message);
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting timetable');
        });
    }
}

        function closeModal() {
            document.getElementById('timetableModal').style.display = 'none';
            document.getElementById('pdfViewer').src = '';
            document.getElementById('imageViewer').src = '';
        }
    </script>
</body>
</html>