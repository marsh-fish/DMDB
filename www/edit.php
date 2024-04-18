<?php
require_once(__DIR__ . '/dbconfig.php');
include 'toast.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 檢查是否已經登入
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// 確保 document_id 是有效的並且存在
$document_id = isset($_GET['document_id']) ? intval($_GET['document_id']) : 0;
if ($document_id === 0) {
    header("location: error.php?error=Invalid Document ID");
    exit;
}

// 從數據庫獲取文檔資料
$sql = "SELECT * FROM document WHERE document_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $document_id);
$stmt->execute();
$result = $stmt->get_result();
$document = $result->fetch_assoc();

// 檢查是否找到文檔和是否為用戶文檔
if (!$document || $_SESSION['user_id'] != $document['user_id']) {
    header("location: error.php?error=Unauthorized access to document");
    exit;
}

$edit_fault = false;

// 處理表單提交更新文檔
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 清理和賦值表單數據
    $title = $conn->real_escape_string($_POST['title']);
    $author = $conn->real_escape_string($_POST['author']);
    $page_number = filter_input(INPUT_POST, 'page_number', FILTER_VALIDATE_INT, ['options' => ['default' => NULL]]);
    $publish_date = empty($_POST['publish_date']) ? NULL : $_POST['publish_date'];
    $source = filter_input(INPUT_POST, 'source', FILTER_SANITIZE_SPECIAL_CHARS, ['options' => ['default' => NULL]]);
    $vol = filter_input(INPUT_POST, 'vol', FILTER_VALIDATE_INT, ['options' => ['default' => NULL]]);
    $no = filter_input(INPUT_POST, 'no', FILTER_VALIDATE_INT, ['options' => ['default' => NULL]]);
    $public = ($_POST['visibility'] == 'public') ? 1 : 0;

    // 更新數據庫記錄
    $update_sql = "UPDATE document SET title=?, author=?, page_number=?, publish_date=?, source=?, vol=?, no=?, public=? WHERE document_id=?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssissiiii", $title, $author, $page_number, $publish_date, $source, $vol, $no, $public, $document_id);
    $update_stmt->execute();

    if ($update_stmt->errno) {  // Check for execution errors
        showToastOnLoad("Update failed: " . $update_stmt->error, "danger");
    } else {
        if ($update_stmt->affected_rows > 0) {
            header("Location: " . toastURL("detail.php?document_id=$document_id", "Document updated successfully.", "success")); // Redirect if successful
            exit();  // Ensure no further execution
        } else {
            showToastOnLoad("No changes made or update failed.", "warning");
        }
    }

    $update_stmt->close();
}

// Show Toast Message
if (isset($_GET['toast_message']) && isset($_GET['toast_type'])) {
    $toast_message = $_GET['toast_message'];
    $toast_type = $_GET['toast_type'];
    showToastOnLoad($toast_message, $toast_type);
}
?>
<!doctype html>
<html lang="zh-hant">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <!-- Font-Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" 
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Custom styles for this template -->
    <link href="navbar.css" rel="stylesheet">
    <title>Edit</title>
</head>

<body class="text-center">
    <!-- Navbar component -->
    <?php include 'navbar.php'; ?>
    <?php include 'toast.html'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <form action="edit.php?document_id=<?php echo $document_id; ?>" method="post" enctype="multipart/form-data" class="form-signin">
                            <div class="form-group">
                                <input type="text" class="form-control mb-2" name="title" placeholder="Title" value="<?php echo htmlspecialchars($document['title']); ?>" required>
                            </div>
                            <div class="form-group">
                                <input type="text" class="form-control mb-2" name="author" placeholder="Author" value="<?php echo htmlspecialchars($document['author']); ?>" required>
                            </div>
                            <div class="form-group">
                                <input type="number" class="form-control mb-2" name="page_number" placeholder="Page Number" value="<?php echo $document['page_number']; ?>">
                            </div>
                            <div class="form-group">
                                <input type="date" class="form-control mb-2" name="publish_date" placeholder="Publish Date" value="<?php echo $document['publish_date']; ?>">
                            </div>
                            <div class="form-group">
                                <input type="text" class="form-control mb-2" name="source" placeholder="Source" value="<?php echo htmlspecialchars($document['source']); ?>">
                            </div>
                            <div class="form-group">
                                <input type="number" class="form-control mb-2" name="vol" placeholder="Volume" value="<?php echo $document['vol']; ?>">
                            </div>
                            <div class="form-group">
                                <input type="number" class="form-control mb-2" name="no" placeholder="Number" value="<?php echo $document['no']; ?>">
                            </div>
                            <div class="form-group">
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="visibility" id="public" value="public" <?php echo ($document['public'] == 1 ? 'checked' : ''); ?>>
                                    <label class="form-check-label" for="public">
                                        <i class="fa fa-globe" aria-hidden="true" title="Public"></i>
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="visibility" id="private" value="private" <?php echo ($document['public'] == 0 ? 'checked' : ''); ?>>
                                    <label class="form-check-label" for="private">
                                        <i class="fa fa-lock" aria-hidden="true" title="Private"></i>
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" 
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</body>
</html>