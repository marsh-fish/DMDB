<?php
require_once(__DIR__ . '/dbconfig.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 檢查用戶是否已經登錄
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Authentication required"]);
    exit;
}

// 檢查必要的參數：document_id
$input = json_decode(file_get_contents('php://input'), true);
$document_id = $input['document_id'] ?? null;
$changes = $input['changes'] ?? [];

if (empty($document_id) || empty($changes)) {
    http_response_code(400); // Bad request
    echo json_encode(["error" => "Document ID or changes data missing"]);
    exit;
}

if ($conn->connect_error) {
    http_response_code(500); // Internal server error
    echo json_encode(["error" => "Failed to connect to database"]);
    exit;
}

// 驗證當前用戶是否有權修改此文檔
$stmt = $conn->prepare("SELECT user_id FROM document WHERE document_id = ?");
$stmt->bind_param("i", $document_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    http_response_code(404); // Not found
    echo json_encode(["error" => "Document not found"]);
    $stmt->close();
    $conn->close();
    exit;
}

$row = $result->fetch_assoc();
if ($row['user_id'] != $_SESSION['user_id']) {
    http_response_code(403); // Forbidden
    echo json_encode(["error" => "User does not have permission to modify this document"]);
    $stmt->close();
    $conn->close();
    exit;
}

// 獲取當前文檔的所有分類關係
$current_categories = [];
$stmt = $conn->prepare("SELECT category_id FROM document_category WHERE document_id = ?");
$stmt->bind_param("i", $document_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $current_categories[$row['category_id']] = true;
}
$stmt->close();

// 處理分類更改
foreach ($changes as $change) {
    $category_id = $change['category_id'];
    $selected = $change['selected'];

    // 判斷是否需要更新
    if ($selected == 1 && !isset($current_categories[$category_id])) {
        $sql = "INSERT INTO document_category (document_id, category_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $document_id, $category_id);
        $stmt->execute();
    } elseif ($selected == 0 && isset($current_categories[$category_id])) {
        $sql = "DELETE FROM document_category WHERE document_id = ? AND category_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $document_id, $category_id);
        $stmt->execute();
    }
}
echo json_encode(["success" => true]);
$stmt->close();
$conn->close();
if (ob_get_level() > 0) {
    ob_end_flush();
}
?>
