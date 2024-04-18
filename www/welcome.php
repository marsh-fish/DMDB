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

// 初始化一個變量來存儲查詢結果
$documents = [];
$keyword = $_POST['keyword'] ?? '';  // 安全地獲取 POST 提交的 keyword

// 構建基礎 SQL 查詢
$sql = "SELECT d.document_id, d.user_id, d.title, d.author, d.path, d.publish_date, m.name AS owner_name 
        FROM document d
        JOIN member m ON d.user_id = m.user_id
        WHERE d.public = 1";

// 如果設置了 keyword，根據 keyword 篩選文件
if (!empty($keyword)) {
    $sql .= " AND (d.title LIKE CONCAT('%', ?, '%') OR d.author LIKE CONCAT('%', ?, '%'))";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $keyword, $keyword);
} else {
    $stmt = $conn->prepare($sql);
}

// 執行查詢並處理結果
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $extension = pathinfo($row['path'], PATHINFO_EXTENSION);
        $row['download_name'] = $row['title'] . '.' . $extension;
        $documents[] = $row;
    }
}
$stmt->close();
// Close connection
mysqli_close($conn);

// Output documents array as JSON
$documents_json = json_encode($documents);  
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" 
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="welcome.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <title>Welcome</title>
</head>

<body>

    <section id="screen1">

    <h1>DMDB</h1>
    <h3>Document Management in Datebase</h3>

    <?php include 'navbar.php'; ?>

    </section>

    <section id="screen2">
        <table class="table table-striped zotero-table" id="zotero-table">
        <thead class="thead-dark">
            <tr>
            <th>Title</th>
            <th>Author(s)</th>
            <th>Date</th>
            <th>Creator</th>
            <th>Options</th>
            </tr>
        </thead>
        <tbody id="content" class="zotero-content">
            <tr>
                <td><a href="#">test</a></td>
                <td>test</td>
                <td>test</td>
                <td>test</td>
            </tr>
        </tbody>
        </table>
        <p class="prev-next">
            <button id="loadPrev" class="prev-next-btn" disabled>Previous</button>
            <button id="loadNext" class="prev-next-btn" disabled>Next</button>
        </p>
    </section>
    
    <section id="screen3"><?php include 'bongo-cat.php'; ?></section>

    <script>
        $(document).ready(function(){
        $(window).bind('scroll', function() {
        var navHeight = $( window ).height() - 70;
                if ($(window).scrollTop() > navHeight) {
                    $('nav').addClass('fixed');
                }
                else {
                    $('nav').removeClass('fixed');
                }
            });
        });
        let documents = <?php echo $documents_json; ?>;
        let prevBtn = document.getElementById("loadPrev");
        let nextBtn = document.getElementById("loadNext");
        let content = document.getElementById("content");
        // Define variables for pagination
        let currentPage = 0;
        const documentsPerPage = 15;

        function updateContent() {
            content.innerHTML = '';
            // Calculate the starting index for the current page
            let startIndex = currentPage * documentsPerPage;
            // Slice the documents array to get documents for the current page
            let pageDocuments = documents.slice(startIndex, startIndex + documentsPerPage);

            // Loop through the documents for the current page
            pageDocuments.forEach(item => {
                let itemTitle = item.title;
                let itemLink = item.path;
                let itemCreator = item.author;
                let itemDate = item.publish_date;
                let itemOwner = item.owner_name;
                let extension = item.path.split('.').pop();
                let downloadName = itemTitle + '.' + extension;

                // Append item details to content
                content.innerHTML += `
                    <tr>
                        <td><a href="${itemLink}">${itemTitle}</a></td>
                        <td>${itemCreator}</td>
                        <td>${itemDate}</td>
                        <td>
                            <a href="user.php?user_id=${item.user_id}">${itemOwner}</a>
                        </td>
                        <td>
                            <a href="${itemLink}" download="${downloadName}">Download</a>
                        </td>
                    </tr>
                `;
            });
        }

        updateContent(); // Initialize content

        // Disable previous button initially if we're on the first page
        prevBtn.disabled = true;
        if (documents.length > documentsPerPage)
            nextBtn.disabled = false;

        // Event listener for next button
        nextBtn.addEventListener("click", function() {
            currentPage++;
            updateContent();
            prevBtn.disabled = false; // Enable previous button after moving to next page

            // Disable next button if we're at the end of the documents
            if (currentPage === Math.ceil(documents.length / documentsPerPage) - 1) {
                nextBtn.disabled = true;
            }
        });

        // Event listener for previous button
        prevBtn.addEventListener("click", function() {
            currentPage--;
            updateContent();
            nextBtn.disabled = false; // Enable next button after moving to previous page

            // Disable previous button if we're on the first page
            if (currentPage === 0) {
                prevBtn.disabled = true;
            }
        });
    </script>

</body>

</html>

