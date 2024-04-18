<?php
require_once(__DIR__ . '/dbconfig.php');
include 'toast.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION["loggedin"])){
    header("location: ./login.php");
    exit;
}

// 檢查 user_id 是否設定，如果沒設定則中斷後續執行
if (!isset($_SESSION["user_id"])) {
    // 可以選擇重定向到錯誤頁面或者登入頁面
    header("location: ./login.php");  // 重定向到登入頁面
    exit;  // 確保腳本不會繼續執行
}

$hasError = false;  // 用於標記是否發生錯誤
$errors = [];  // 錯誤收集陣列
$solutions = [];  // 解決方法收集陣列

// TODO: 處理表單提交
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // TODO: 判斷表單資訊正確性
    // TODO: 測試 alert 所有資訊
    // 測試 alert 所有資訊，這裡假設已經通過前面的 user_id 檢查
    $title = htmlspecialchars($_POST['title']);
    $author = htmlspecialchars($_POST['author']);
    $page_number = intval($_POST['page_number']);
    $publish_date = $_POST['publish_date'];
    $source = htmlspecialchars($_POST['source']);
    $vol = intval($_POST['vol']);
    $no = intval($_POST['no']);
    $public = ($_POST['visibility'] == 'public') ? 1 : 0;
    // 上傳檔案處理
    $document = $_FILES['document'];
    if ($document['error'] == 0) {
        $tmp_name = $document['tmp_name'];
        $original_name = basename($document['name']);
        $file_ext = pathinfo($original_name, PATHINFO_EXTENSION);

        // 生成新的檔案名稱：將標題和當前時間戳結合，避免重複，並確保檔案名安全
        $doc_name = $title . '_' . time() . '.' . $file_ext;
        $upload_dir = __DIR__ . '/uploads/';  // 確保這個目錄已存在且可寫
        $destination = $upload_dir . $doc_name; // 完整路徑
        $path = '/uploads/' . $doc_name;

        // 檔案類型過濾，僅允許特定擴展名
        $allowed_exts = ['pdf', 'doc', 'docx', 'txt'];
        if (!in_array(strtolower($file_ext), $allowed_exts)) {
            $hasError = true;
            showToastOnLoad("Invalid file type.", "danger");
            showToastOnLoad("Please upload a document in PDF, DOC, DOCX, or TXT format.", "danger");
            
        }

        if (!$hasError && !move_uploaded_file($tmp_name, $destination)) {
            $hasError = true;
            showToastOnLoad("Error moving uploaded file.", "danger");
            showToastOnLoad("Check the server permissions and the upload directory.", "danger");
        }
    } else {
        $hasError = true;
        showToastOnLoad("Error uploading file. Error Code: " . $document['error'], "danger");
        showToastOnLoad("Please ensure the file is not corrupt and try again.", "danger");
    }

    if (!$hasError) {
        // showToastOnLoad("File uploaded successfully!\\nFilename: " . $doc_name, "success");

        // 開始寫入資料庫
        // SQL 插入語句
        $sql = "INSERT INTO `document` (user_id, title, author, public, path, page_number, publish_date, source, vol, no) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt === false) {
            $hasError = true;
            showToastOnLoad("Failed to prepare the SQL statement.", "danger");
            showToastOnLoad("Check SQL syntax and the database connection settings.", "danger");
        } else {
            // 綁定參數
            mysqli_stmt_bind_param($stmt, "issisissii", $_SESSION['user_id'], $title, $author, $public, $path, $page_number, $publish_date, $source, $vol, $no);

            // 執行預備語句
            if (mysqli_stmt_execute($stmt)) {
                if (mysqli_stmt_affected_rows($stmt) <= 0) {
                    $hasError = true;
                    showToastOnLoad("No rows affected.", "danger");
                    showToastOnLoad("Ensure that the data you are trying to insert does not already exist and is valid.", "danger");
                }
            } else {
                $hasError = true;
                showToastOnLoad("Execute failed: " . mysqli_stmt_error($stmt), "danger");
                showToastOnLoad("Please try again later, and ensure all fields are entered correctly.", "danger");
            }

            // 關閉預備語句
            mysqli_stmt_close($stmt);
        }
    }

    if (!$hasError) {
        $new_document_id = mysqli_insert_id($conn);
        header("Location: " . toastURL("detail.php?document_id=$new_document_id", "Document has been successfully uploaded and saved.", "success")); // Redirect if successful
        exit();  // Ensure no further execution
    }
}

// Close connection
mysqli_close($conn);

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
    <link href="navbar.css" rel="stylesheet">
    <title>Upload</title>
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
          <form action="upload.php" method="post" enctype="multipart/form-data" class="form-signin">
            <div class="form-group">
              <input type="text" class="form-control mb-2" name="title" placeholder="Title" required>
            </div>
            <div class="form-group">
              <input type="text" class="form-control mb-2" name="author" placeholder="Author" required>
            </div>
            <div class="form-group">
              <input type="number" class="form-control mb-2" name="page_number" placeholder="Page Number">
            </div>
            <div class="form-group">
              <input type="date" class="form-control mb-2" name="publish_date" placeholder="Publish Date">
            </div>
            <div class="form-group">
              <input type="text" class="form-control mb-2" name="source" placeholder="Source">
            </div>
            <div class="form-group">
              <input type="number" class="form-control mb-2" name="vol" placeholder="Volume">
            </div>
            <div class="form-group">
              <input type="number" class="form-control mb-2" name="no" placeholder="Number">
            </div>
            <div class="form-group">
                <input type="file" class="form-control-file mb-4" name="document" required>
            </div>
            <div class="form-group">
                <div class="form-check form-check-inline">
                    <input type="radio" class="form-check-input" name="visibility" id="public" value="public" checked>
                    <label class="form-check-label" for="public">
                        <i class="fa fa-globe" aria-hidden="true" title="Public"></i>
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <input type="radio" class="form-check-input" name="visibility" id="private" value="private">
                    <label class="form-check-label" for="private">
                        <i class="fa fa-lock" aria-hidden="true" title="Private"></i>
                    </label>
                </div>
            </div>
            <button type="submit" class="btn btn-secondary btn-block mt-3" name="submit">Upload</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" 
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</body>
</html>