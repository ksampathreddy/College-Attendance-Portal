<?php
include '../db_connection.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'teacher') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$batch = $_GET['batch'] ?? '';
$branch = $_GET['branch'] ?? '';

if (empty($batch) || empty($branch)) {
    echo json_encode(['error' => 'Batch and branch are required']);
    exit();
}

try {
    $query = "SELECT DISTINCT section FROM students 
              WHERE batch = ? AND branch = ? AND section IS NOT NULL
              ORDER BY section";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ss", $batch, $branch);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sections = [];
    while ($row = $result->fetch_assoc()) {
        // Only add if section is not null or empty
        if (!empty($row['section'])) {
            $sections[] = $row;
        }
    }
    
    if (empty($sections)) {
        echo json_encode(['message' => 'No sections found for this batch and branch']);
    } else {
        echo json_encode($sections);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}
?>