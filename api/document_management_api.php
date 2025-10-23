<?php
/**
 * Document Management API
 * Handles document uploads, retrieval, and management for SEP/PPE items
 */

session_start();
// Document management feature has been disabled per configuration.
http_response_code(410);
echo json_encode(['success' => false, 'message' => 'Document management feature is disabled.']);
exit;

// Set up document upload configuration
$upload_dir = '../assets/uploads/documents/';
$allowed_types = [
    'application/pdf' => 'pdf',
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
];
$max_file_size = 10 * 1024 * 1024; // 10MB

// Ensure upload directory exists
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle the request based on method and action
$action = $_GET['action'] ?? '';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            switch ($action) {
                case 'upload':
                    handleDocumentUpload();
                    break;
                case 'update':
                    handleDocumentUpdate();
                    break;
                default:
                    throw new Exception('Invalid action');
            }
            break;

        case 'GET':
            switch ($action) {
                case 'list':
                    getDocumentList();
                    break;
                case 'types':
                    getDocumentTypes();
                    break;
                case 'requirements':
                    getDocumentRequirements();
                    break;
                default:
                    throw new Exception('Invalid action');
            }
            break;

        case 'DELETE':
            handleDocumentDelete();
            break;

        default:
            throw new Exception('Invalid request method');
    }
} catch (Exception $e) {
    error_log("Document API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function handleDocumentUpload() {
    global $pdo, $upload_dir, $allowed_types, $max_file_size;

    // Validate request
    if (!isset($_FILES['file']) || !isset($_POST['document_type_id']) || 
        !isset($_POST['reference_type']) || !isset($_POST['reference_id'])) {
        throw new Exception('Missing required parameters');
    }

    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed');
    }

    // Validate file size
    if ($file['size'] > $max_file_size) {
        throw new Exception('File size exceeds limit');
    }

    // Validate file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed_types[$mime_type])) {
        throw new Exception('Invalid file type');
    }

    // Generate unique filename
    $extension = $allowed_types[$mime_type];
    $new_filename = uniqid() . '.' . $extension;
    $file_path = $upload_dir . $new_filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('Failed to save file');
    }

    // Save document record
    $stmt = $pdo->prepare("
        INSERT INTO tbl_document (
            document_type_id, reference_type, reference_id, 
            file_name, file_path, file_size, mime_type, 
            uploaded_by, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $_POST['document_type_id'],
        $_POST['reference_type'],
        $_POST['reference_id'],
        $file['name'],
        $new_filename,
        $file['size'],
        $mime_type,
        $_SESSION['user_id'],
        $_POST['notes'] ?? null
    ]);

    $document_id = $pdo->lastInsertId();

    // Update document completeness status for ICS
    if ($_POST['reference_type'] === 'ICS') {
        updateIcsDocumentStatus($_POST['reference_id']);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Document uploaded successfully',
        'data' => ['document_id' => $document_id, 'file_path' => $new_filename]
    ]);
}

function handleDocumentUpdate() {
    global $pdo;

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['document_id']) || !isset($data['notes'])) {
        throw new Exception('Invalid request data');
    }

    $stmt = $pdo->prepare("
        UPDATE tbl_document 
        SET notes = ?
        WHERE document_id = ? AND uploaded_by = ?
    ");

    $stmt->execute([$data['notes'], $data['document_id'], $_SESSION['user_id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Document updated successfully'
    ]);
}

function handleDocumentDelete() {
    global $pdo;

    $document_id = $_GET['id'] ?? null;
    if (!$document_id) {
        throw new Exception('Document ID required');
    }

    // Get document details
    $stmt = $pdo->prepare("
        SELECT file_path, reference_type, reference_id 
        FROM tbl_document 
        WHERE document_id = ? AND uploaded_by = ?
    ");
    $stmt->execute([$document_id, $_SESSION['user_id']]);
    $document = $stmt->fetch();

    if (!$document) {
        throw new Exception('Document not found or unauthorized');
    }

    // Delete file
    $file_path = '../assets/uploads/documents/' . $document['file_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    // Delete record
    $stmt = $pdo->prepare("DELETE FROM tbl_document WHERE document_id = ?");
    $stmt->execute([$document_id]);

    // Update ICS document status if needed
    if ($document['reference_type'] === 'ICS') {
        updateIcsDocumentStatus($document['reference_id']);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Document deleted successfully'
    ]);
}

function getDocumentList() {
    global $pdo;

    $reference_type = $_GET['reference_type'] ?? null;
    $reference_id = $_GET['reference_id'] ?? null;

    if (!$reference_type || !$reference_id) {
        throw new Exception('Reference type and ID required');
    }

    $stmt = $pdo->prepare("
        SELECT d.*, 
               dt.type_name,
               u.full_name as uploaded_by_name
        FROM tbl_document d
        JOIN tbl_document_type dt ON d.document_type_id = dt.document_type_id
        JOIN tbl_user u ON d.uploaded_by = u.user_id
        WHERE d.reference_type = ? 
        AND d.reference_id = ?
        AND d.is_active = TRUE
        ORDER BY d.upload_date DESC
    ");

    $stmt->execute([$reference_type, $reference_id]);
    $documents = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $documents
    ]);
}

function getDocumentTypes() {
    global $pdo;

    $stmt = $pdo->query("
        SELECT * FROM tbl_document_type 
        ORDER BY type_name
    ");

    echo json_encode([
        'success' => true,
        'data' => $stmt->fetchAll()
    ]);
}

function getDocumentRequirements() {
    global $pdo;

    $reference_type = $_GET['reference_type'] ?? null;
    $reference_id = $_GET['reference_id'] ?? null;

    if (!$reference_type || !$reference_id) {
        throw new Exception('Reference type and ID required');
    }

    $stmt = $pdo->prepare("
        SELECT 
            dt.*,
            CASE WHEN d.document_id IS NOT NULL THEN TRUE ELSE FALSE END as is_uploaded,
            d.upload_date,
            d.document_id
        FROM tbl_document_type dt
        LEFT JOIN tbl_document d ON d.document_type_id = dt.document_type_id 
            AND d.reference_type = ? 
            AND d.reference_id = ?
            AND d.is_active = TRUE
        ORDER BY dt.is_required DESC, dt.type_name
    ");

    $stmt->execute([$reference_type, $reference_id]);
    
    echo json_encode([
        'success' => true,
        'data' => $stmt->fetchAll()
    ]);
}

function updateIcsDocumentStatus($ics_id) {
    global $pdo;

    // Count required vs uploaded documents
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT dt.document_type_id) as required_docs,
            COUNT(DISTINCT d.document_id) as uploaded_docs
        FROM tbl_document_type dt
        LEFT JOIN tbl_document d ON d.document_type_id = dt.document_type_id 
            AND d.reference_type = 'ICS' 
            AND d.reference_id = ?
            AND d.is_active = TRUE
        WHERE dt.is_required = TRUE
    ");

    $stmt->execute([$ics_id]);
    $result = $stmt->fetch();

    // Update ICS status
    $stmt = $pdo->prepare("
        UPDATE tbl_ics 
        SET has_complete_docs = ?, 
            last_doc_check = CURRENT_TIMESTAMP
        WHERE ics_id = ?
    ");

    $is_complete = ($result['required_docs'] === $result['uploaded_docs']);
    $stmt->execute([$is_complete, $ics_id]);
}