<?php
include 'db_connection.php';

if (isset($_GET['batch']) && isset($_GET['branch'])) {
    $batch = $conn->real_escape_string($_GET['batch']);
    $branch = $conn->real_escape_string($_GET['branch']);
    
    $result = $conn->query("SELECT DISTINCT section FROM students 
                          WHERE batch='$batch' AND branch='$branch' 
                          AND section IS NOT NULL ORDER BY section");
    
    $sections = [];
    while ($row = $result->fetch_assoc()) {
        $sections[] = $row['section'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($sections);
    exit();
}

header('HTTP/1.1 400 Bad Request');
echo json_encode(['error' => 'Missing parameters']);