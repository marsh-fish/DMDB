<?php
require_once(__DIR__ . '/dbconfig.php');
include 'toast.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 檢查 document_id 是否已傳遞
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    header('Location: public.php');  // 未傳遞 document_id 時重定向
    exit;
}

$user_id = $_GET['user_id'];
$session_user_id = $_SESSION['user_id'] ?? null;  // Safely get the session user_id

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $search = trim($_POST['search']);  // 獲取並清理輸入的搜尋關鍵字
    $category_filter = $_POST['category_id'] ?? '';  // 可選的類別篩選器
}

if (isset($_SESSION['user_id']) && isset($_GET['delete_document_id']) && !empty($_GET['delete_document_id'])) {
    $delete_document_id = $_GET['delete_document_id'];
    $session_user_id = $_SESSION['user_id'];  // 假設已經有一個有效的 session user_id

    $error_message = "";  // 用於存儲可能出現的錯誤訊息
    $operation_successful = true;  // 用於追蹤操作是否成功

    // 檢查 document 的 user_id 是否與 session 中的 user_id 相符
    $check_user_sql = "SELECT user_id FROM document WHERE document_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_user_sql);
    if (!$check_stmt) {
        $error_message = "Prepare failed: (" . mysqli_errno($conn) . ") " . mysqli_error($conn);
        $operation_successful = false;
    } else {
        mysqli_stmt_bind_param($check_stmt, "i", $delete_document_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_bind_result($check_stmt, $document_user_id);
        mysqli_stmt_fetch($check_stmt);
        mysqli_stmt_close($check_stmt);

        if ($document_user_id != $session_user_id) {
            $error_message = "You do not have permission to delete this document.";
            $operation_successful = false;
        }
    }

    // 如果用戶驗證通過，則嘗試從 document 表中刪除記錄
    if ($operation_successful) {
        $delete_from_document_sql = "DELETE FROM document WHERE document_id = ?";
        $stmt = mysqli_prepare($conn, $delete_from_document_sql);
        if (!$stmt) {
            $error_message = "Prepare failed: (" . mysqli_errno($conn) . ") " . mysqli_error($conn);
            $operation_successful = false;  // 更新操作狀態
        } else {
            mysqli_stmt_bind_param($stmt, "i", $delete_document_id);
            mysqli_stmt_execute($stmt);
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);

            if ($affected_rows < 0) {
                $error_message = "Error deleting record from document: " . mysqli_error($conn);
                $operation_successful = false;  // 更新操作狀態
            } else if ($affected_rows == 0) {
                $error_message = "No records found to delete in document.";
                $operation_successful = false;  // 更新操作狀態
            }
        }
    }

    // 如果 document 表中的記錄成功刪除，則繼續刪除 document_category 表中的記錄
    if ($operation_successful) {
        $delete_from_category_sql = "DELETE FROM document_category WHERE document_id = ?";
        $stmt = mysqli_prepare($conn, $delete_from_category_sql);
        if (!$stmt) {
            $error_message = "Prepare failed: (" . mysqli_errno($conn) . ") " . mysqli_error($conn);
            $operation_successful = false;  // 更新操作狀態
        } else {
            mysqli_stmt_bind_param($stmt, "i", $delete_document_id);
            mysqli_stmt_execute($stmt);
            if (mysqli_stmt_affected_rows($stmt) < 0) {
                $error_message = "Error deleting record from document_category: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }

    // 根據操作成功與否，給予用戶相應的反饋
    if ($operation_successful) {
        showToastOnLoad("Records successfully deleted.", "success");
    } else {
        showToastOnLoad($error_message, "danger");
    }
}

// Prepare SQL to check if the user exists and fetch username
$user_exists_sql = "SELECT EXISTS(SELECT 1 FROM member WHERE user_id = ?) as user_exists, username FROM member WHERE user_id = ?";
$user_exists_stmt = mysqli_prepare($conn, $user_exists_sql);

// Error handling for the preparation of the statement
if (!$user_exists_stmt) {
    echo "Prepare failed: (" . mysqli_errno($conn) . ") " . mysqli_error($conn);
    exit;
}

mysqli_stmt_bind_param($user_exists_stmt, "ii", $user_id, $user_id);
mysqli_stmt_execute($user_exists_stmt);

// Initialize variables to hold the results
$user_exists = $username = null;

// Bind result variables
mysqli_stmt_bind_result($user_exists_stmt, $user_exists, $username);
mysqli_stmt_fetch($user_exists_stmt);
mysqli_stmt_close($user_exists_stmt);

$documents = [];  // Ensure $documents is initialized
$user_categories = [];
if ($user_exists) {
    // 構建 SQL 查詢，加入搜索條件和類別過濾
    $sql = "SELECT d.document_id, d.user_id, d.title, d.author, d.path, d.publish_date, d.public, 
            GROUP_CONCAT(DISTINCT c.category_name ORDER BY c.category_name SEPARATOR ', ') AS categories
        FROM document d
        LEFT JOIN document_category dc ON d.document_id = dc.document_id
        LEFT JOIN category c ON dc.category_id = c.category_id
        WHERE d.user_id = ?";

    $params = [$user_id];  // 參數初始化
    $types = "i";  // 參數類型初始化

    if (!empty($search)) {
        $sql .= " AND (d.title LIKE CONCAT('%',?, '%') OR d.author LIKE CONCAT('%', ?, '%'))";
        $params[] = $search;
        $params[] = $search;
        $types .= "ss";
    }

    if (!empty($category_filter)) {
        $sql .= " AND c.category_id = ?";
        $params[] = $category_filter;
        $types .= "i";
    }

    $sql .= " GROUP BY d.document_id, d.user_id, d.title, d.author, d.path, d.publish_date, d.public";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $documents = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['download_name'] = $row['title'] . '.' . pathinfo($row['path'], PATHINFO_EXTENSION);
    $documents[] = $row;
    }
    mysqli_stmt_close($stmt);

    if (isset($_SESSION['user_id']) && $user_id == $_SESSION['user_id']) {
        $sql = "SELECT category_id, category_name FROM category WHERE user_id = ?";
        if ($stmt = $conn->prepare($sql)) {  // Prepare the SQL statement
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $user_categories[] = $row;  // Append each row to categories array
            }
            $stmt->close();
        }
    }
}

// Close connection
mysqli_close($conn);
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
    <title>User</title>
</head>

<body class="text-center">
    <!-- Navbar component -->
    <?php include 'navbar.php'; ?>
    <?php include 'toast.html'; ?>

    <?php if ($user_exists): ?>
    <div class="container">
        <div class="row justify-content-between align-items-center">
            <div class="col">
                <h2><?php echo htmlspecialchars($username); ?></h2>
            </div>
            <div class="col-auto">
                <form action="user.php?user_id=<?php echo $user_id; ?>" method="post" class="form-inline">
                    <input type="text" name="search" class="form-control mb-2 mr-sm-2" placeholder="Search documents...">
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id): ?>
                    <select name="category_id" class="form-control mb-2 mr-sm-2">
                        <option value="">All Categories</option>
                        <?php foreach ($user_categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary mb-2">Search</button>
                </form>
            </div>
        </div>
    <div class="row justify-content-center">
        <div class="col-auto">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Issue Date</th>
                            <?php if ($session_user_id == $user_id): ?><th>Visibility</th><?php endif; ?>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($doc['title']); ?></td>
                                <td><?php echo htmlspecialchars($doc['author']); ?></td>
                                <td><?php echo is_null($doc['publish_date']) ? '' : htmlspecialchars($doc['publish_date']); ?></td>
                                <?php if ($session_user_id == $user_id): ?>
                                    <td>
                                        <?php if ($doc['public']): ?>
                                            <i class="fa fa-globe" aria-hidden="true" title="Public"></i>
                                        <?php else: ?>
                                            <i class="fa fa-lock" aria-hidden="true" title="Private"></i>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $doc['user_id']): ?>
                                        <a href="user.php?&user_id=<?= $_SESSION['user_id']."&delete_document_id=".$doc['document_id'] ?>">Delete</a> |
                                    <?php endif; ?>
                                    <a href="<?php echo htmlspecialchars($doc['path']); ?>" download="<?php echo htmlspecialchars($doc['download_name']); ?>">Download</a>
                                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $doc['user_id']): ?>
                                        | <a href="edit.php?document_id=<?php echo $doc['document_id']; ?>">Edit >></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($documents)): ?>
                            <tr><td colspan="6">No documents found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php else: ?>
        <p>This page does not exist.</p>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" 
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</body>
</html>