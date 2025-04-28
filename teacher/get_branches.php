<?php
include '../db_connection.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'teacher') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$batch = $_GET['batch'] ?? '';

if (empty($batch)) {
    echo json_encode(['error' => 'Batch is required']);
    exit();
}

try {
    $query = "SELECT DISTINCT branch FROM students WHERE batch = ? ORDER BY branch";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $batch);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $branches = [];
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row;
    }
    
    if (empty($branches)) {
        echo json_encode(['error' => 'No branches found for this batch']);
    } else {
        echo json_encode($branches);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}
?>