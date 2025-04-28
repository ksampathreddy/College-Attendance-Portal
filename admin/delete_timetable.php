<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include '../db_connection.php';

$response = ['success' => false, 'message' => ''];

try {
    if (isset($_POST['id'])) {
        $id = intval($_POST['id']);
        
        // Get file path before deleting
        $sql = "SELECT file_path FROM timetables WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $file_path = $row['file_path'];
            
            // Delete from database
            $delete_sql = "DELETE FROM timetables WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $id);
            
            if ($delete_stmt->execute()) {
                // Delete the file
                if (file_exists($file_path) && is_writable($file_path)) {
                    if (!unlink($file_path)) {
                        throw new Exception("Could not delete file");
                    }
                }
                $response = [
                    'success' => true,
                    'message' => 'Timetable deleted successfully.'
                ];
            } else {
                throw new Exception("Database delete failed");
            }
        } else {
            $response = [
                'success' => false,
                'message' => 'Timetable not found.'
            ];
        }
    } else {
        $response = [
            'success' => false,
            'message' => 'Invalid request. Missing ID.'
        ];
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>