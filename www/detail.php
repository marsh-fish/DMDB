<?php
require_once(__DIR__ . '/dbconfig.php');
include 'toast.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 檢查 document_id 是否已傳遞
if (!isset($_GET['document_id']) || empty($_GET['document_id'])) {
    header('Location: public.php');  // 未傳遞 document_id 時重定向
    exit;
}

$document_id = $_GET['document_id'];
$duplicate_document_id = $_GET['duplicate_document_id'] ?? null; // for duplicating document
$found = false;   // 初始化 found 變量
$permission = true;  // 初始化 permission 變量

// 建立副本
if ($duplicate_document_id) {
    $sql = "SELECT * FROM document WHERE document_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $duplicate_document_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $docToDuplicate = $result->fetch_assoc();

    if ($docToDuplicate && $_SESSION['user_id'] != $docToDuplicate['user_id']) {
        $sqlInsert = "INSERT INTO document (user_id, title, author, path, page_number, publish_date, source, vol, no, public)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->bind_param("isssisssii", $_SESSION['user_id'], $docToDuplicate['title'], $docToDuplicate['author'],
                                 $docToDuplicate['path'], $docToDuplicate['page_number'], $docToDuplicate['publish_date'],
                                 $docToDuplicate['source'], $docToDuplicate['vol'], $docToDuplicate['no'], $docToDuplicate['public']);
        if ($stmtInsert->execute()) {
            $newDocumentId = $stmtInsert->insert_id;
            header("Location: detail.php?document_id=$newDocumentId");
            exit();
        } else {
            showToastOnLoad("Failed to duplicate document.", "error");
        }
    }
}

// SQL 查詢獲取文檔詳情
$sql = "SELECT d.document_id, d.user_id, d.title, d.author, d.path, d.page_number, d.publish_date, d.source, d.vol, d.no, d.public, m.name AS owner_name 
        FROM document d
        JOIN member m ON d.user_id = m.user_id
        WHERE d.document_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $document_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $document = mysqli_fetch_assoc($result);
    $found = true;
    if ($document['public'] == 0 && $_SESSION['user_id'] != $document['user_id']) {
        // 如果文檔是私有的且當前用戶不是文檔的所有者
        $permission = false;
    }
} else {
    $found = false;  // 未查詢到文檔或查詢出錯
}

mysqli_stmt_close($stmt);

// 搜尋 category
$doc_categories = [];

// 如果找到文檔且用戶有權限訪問，則查詢此文檔的所有分類
if ($found && $permission) {
    $sql = "SELECT c.category_id, c.category_name 
            FROM category c
            JOIN document_category dc ON c.category_id = dc.category_id
            WHERE dc.document_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $document_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $doc_categories[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// 搜尋 user category
$user_categories = [];

// 搜尋當前用戶擁有的所有分類
if ($found && $permission) {
    $sql = "SELECT DISTINCT category_id, category_name 
            FROM category
            WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $user_categories[] = $row;
    }
    mysqli_stmt_close($stmt);
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
    <!-- Custom styles for this template -->
    <link href="navbar.css" rel="stylesheet">
    <title>Detail</title>
</head>

<body class="text-center">
    <!-- Navbar component -->
    <?php include 'navbar.php'; ?>
    <?php include 'toast.html'; ?>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <!-- 判斷是不是權限以及搜尋結果 -->
                <!-- 如果沒有權限或是document_id搜尋不到就會顯示 "This page does not exist." -->
                <?php if ($found && $permission): ?>
                    <table class="table mt-5">
                        <tbody>
                            <tr><th>Title</th><td><?php echo htmlspecialchars($document['title'] ?? ''); ?></td></tr>
                            <tr>
                                <th>Author</th>
                                <td><?php echo htmlspecialchars($document['author'] ?? ''); ?></td>
                            </tr>
                            <tr><th>Page Number</th><td><?php echo htmlspecialchars($document['page_number'] ?? ''); ?></td></tr>
                            <tr><th>Publish Date</th><td><?php echo htmlspecialchars($document['publish_date'] ?? ''); ?></td></tr>
                            <tr><th>Source</th><td><?php echo htmlspecialchars($document['source'] ?? ''); ?></td></tr>
                            <tr><th>Volume</th><td><?php echo htmlspecialchars($document['vol'] ?? ''); ?></td></tr>
                            <tr><th>Number</th><td><?php echo htmlspecialchars($document['no'] ?? ''); ?></td></tr>
                            <tr>
                                <th>Owner</th>
                                <td>
                                    <?php if (!empty($document['author'])): ?>
                                        <a href="user.php?user_id=<?php echo $document['user_id']; ?>"><?php echo htmlspecialchars($document['owner_name']); ?></a>
                                    <?php else: ?>
                                        <?php echo ''; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Visibility</th>
                                <td>
                                    <?php if ($document['public']): ?>
                                        <i class="fa fa-globe" aria-hidden="true" title="Public"></i>
                                    <?php else: ?>
                                        <i class="fa fa-lock" aria-hidden="true" title="Private"></i>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Action</th>
                                <td>
                                    <?php if (!empty($document['path'])): ?>
                                        <a href="<?php echo htmlspecialchars($document['path']); ?>" download="<?php echo htmlspecialchars($document['title'] . '.' . pathinfo($document['path'], PATHINFO_EXTENSION)); ?>">Download</a>
                                        <?php if ($_SESSION['user_id'] == $document['user_id']): ?>
                                            | <a href="edit.php?document_id=<?php echo $document['document_id']; ?>">Edit</a>
                                        <?php elseif (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
                                            | <a href="detail.php?document_id=<?php echo $document_id; ?>&duplicate_document_id=<?php echo $document['document_id']; ?>" onclick="return confirm('Are you sure you want to create a duplicate of this document?');">Create Duplicate</a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php echo ''; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $document['user_id']): ?>
                                <tr>
                                    <th>Categories</th>
                                    <td>
                                        <?php
                                            // 印出所有 doc_categories 中的 category_name
                                            $categories_count = count($doc_categories);
                                            foreach ($doc_categories as $index => $category) {
                                                echo htmlspecialchars($category['category_name']);
                                                if ($index < $categories_count - 1) {
                                                    echo ', ';
                                                }
                                            }
                                        ?>
                                        <!-- Trigger Modal Button -->
                                        <button type="button" class="btn btn-link" data-bs-toggle="modal" data-bs-target="#categoryModal" style="color: gray;">
                                            <i class="fa fa-tag" aria-hidden="true"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>This page does not exist.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Category Modal -->
    <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalLabel">Edit Categories</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Category Name</th>
                                <th>Select</th>
                            </tr>
                        </thead>
                        <tbody id="categoryTable">
                            <!-- Categories will be populated here by JavaScript -->
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="saveCategories()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Modal script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var docCats = <?= json_encode($doc_categories); ?>;
            var userCats = <?= json_encode($user_categories); ?>;
            var tableBody = document.getElementById('categoryTable');

            userCats.forEach(cat => {
                var row = tableBody.insertRow();
                var cellName = row.insertCell(0);
                var cellCheckbox = row.insertCell(1);

                cellName.textContent = cat.category_name;
                var checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.name = 'category';
                checkbox.dataset.category_id = cat.category_id;
                checkbox.checked = docCats.some(docCat => docCat.category_id === cat.category_id);
                cellCheckbox.appendChild(checkbox);
            });
        });

        function saveCategories() {
            var checkboxes = document.querySelectorAll('#categoryTable input[type="checkbox"]');
            var changes = Array.from(checkboxes).map(checkbox => {
                return {
                    category_id: checkbox.dataset.category_id,  // Assuming you are storing category_id in a data attribute
                    selected: checkbox.checked ? 1 : 0
                };
            });

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_document_categories.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onreadystatechange = function () {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    var responseText = xhr.responseText;
                    try {
                        var response = JSON.parse(responseText);
                        if (response.success) {
                            console.log('Update successful, redirecting...');
                            var successUrl = <?= json_encode(toastURL("detail.php?document_id=$document_id", "Categories updated successfully.", "success")); ?>;
                            window.location.href = successUrl;
                        } else {
                            console.error('Update failed:', response.error);
                        }
                    } catch (e) {
                        console.error('Failed to parse response:', responseText);
                    }
                }
                    };
                    xhr.send(JSON.stringify({document_id: <?= $document_id; ?>, changes: changes}));  // Sends the request with document ID and changes
                }
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" 
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</body>
</html>