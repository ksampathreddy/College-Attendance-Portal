<?php
include 'db_connection.php';

// Function to get distinct values from attendance table with optional filters
function getDistinctValues($conn, $field, $filters = []) {
    $query = "SELECT DISTINCT $field FROM attendance WHERE 1=1";
    $params = [];
    $types = '';
    
    foreach ($filters as $filterField => $filterValue) {
        if (!empty($filterValue)) {
            $query .= " AND $filterField = ?";
            $params[] = $filterValue;
            $types .= 's';
        }
    }
    
    $query .= " ORDER BY $field";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $values = [];
    while ($row = $result->fetch_assoc()) {
        if ($row[$field] !== null) { // Skip null values
            $values[] = $row[$field];
        }
    }
    
    return $values;
}

$batch = $_GET['batch'] ?? '';
$branch = $_GET['branch'] ?? '';
$semester = $_GET['semester'] ?? '';
$section = $_GET['section'] ?? '';
$viewType = $_GET['view_type'] ?? '';

$response = [];

// Get branches filtered by batch
$response['branches'] = getDistinctValues($conn, 'branch', ['batch' => $batch]);

// Get sections filtered by batch and branch
$response['sections'] = getDistinctValues($conn, 'section', [
    'batch' => $batch,
    'branch' => $branch
]);

// Get semesters filtered by batch and branch
$response['semesters'] = getDistinctValues($conn, 'semester', [
    'batch' => $batch,
    'branch' => $branch
]);

// Get dates filtered by batch, branch and semester (for date-wise view)
if ($viewType === 'date') {
    $response['dates'] = getDistinctValues($conn, 'date', [
        'batch' => $batch,
        'branch' => $branch,
        'semester' => $semester
    ]);
}

header('Content-Type: application/json');
echo json_encode($response);
?>