<?php
require_once(__DIR__ . '/dbconfig.php');

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if the user is not logged in
if (!isset($_SESSION["loggedin"]) || !isset($_SESSION["user_id"])) {
    header("Location: ./login.php");
    exit;
}

// Get the current user ID from session
$current_user_id = $_SESSION["user_id"];

// Fetch all categories
$categories = [];
try {
    $sql = 'SELECT * FROM category';
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    } else {
        echo "No categories found.";
    }
} catch (Exception $e) {
    echo "An error occurred while fetching categories: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}

// Fetch categories created by the current user
$user_categories = [];
$user_category_ids = [];
try {
    $sql = 'SELECT * FROM category WHERE user_id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $current_user_id);
    $stmt->execute();
    $user_categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $user_category_ids = array_column($user_categories, 'category_id');
} catch (Exception $e) {
    echo "An error occurred while fetching user categories: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link href="navbar.css" rel="stylesheet">
    <link href="category.css" rel="stylesheet">
    <title>Categories</title>
    <script>
        function toggleCategory(categoryId) {
            const element = document.getElementById("category-" + categoryId);
            if (element.style.display === 'none') {
                element.style.display = 'block';
                // Fetch documents for this category if not already fetched
                fetchDocuments(categoryId);
            } else {
                element.style.display = 'none';
            }
        }

        function toggleUserCategory(categoryId) {
            const element = document.getElementById("usercategory-" + categoryId);
            if (element.style.display === 'none') {
                element.style.display = 'block';
                // Fetch documents for this category if not already fetched
                fetchUserDocuments(categoryId);
            } else {
                element.style.display = 'none';
            }
        }

        function fetchDocuments(categoryId) {
            // AJAX call to fetch documents for this category
            const xhr = new XMLHttpRequest();
            xhr.open("GET", `fetch_documents.php?category_id=${categoryId}`, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById(`category-${categoryId}`).innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }

        function fetchUserDocuments(categoryId) {
            // AJAX call to fetch documents for this category
            const xhr = new XMLHttpRequest();
            xhr.open("GET", `fetch_documents.php?category_id=${categoryId}`, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById(`usercategory-${categoryId}`).innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }
    </script>
</head>
<body>
    <?php include 'navbar.php'; ?>  <!-- Include the navbar -->

    <h1>Categories</h1>

    <!-- Section for user-created categories ("My") -->
    <h2>My Categories</h2>
    <ul>
    <?php foreach ($user_categories as $user_category): ?>
        <?php
        $category_id = $user_category['category_id'];
        $category_name = htmlspecialchars($user_category['category_name'], ENT_QUOTES, 'UTF-8');
        ?>
        
        <li>
            <span
                onclick="toggleUserCategory(<?php echo $category_id; ?>)" 
                style="cursor: pointer; font-weight: bold;"
            >
                <?php echo $category_name; ?>
            </span>
            <div id="usercategory-<?php echo $category_id; ?>" style="display: none;">
                <!-- Document list will be filled by AJAX -->
                Loding...
            </div>
        </li>
    <?php endforeach; ?>
    </ul>
    
    <!-- Section for all categories -->
    <h2>All Categories</h2>
    <ul>
    <?php foreach ($categories as $category): ?>
        <?php
        $category_id = $category['category_id'];
        $category_name = htmlspecialchars($category['category_name'], ENT_QUOTES, 'UTF-8');
        ?>
        <li>
            <span
                onclick="toggleCategory(<?php echo $category_id; ?>)" 
                style="cursor: pointer; font-weight: bold;"
            >
                <?php echo $category_name; ?>
            </span>
            <div id="category-<?php echo $category_id; ?>" style="display: none;">
                <!-- Document list will be filled by AJAX -->
                Loding...
            </div>
        </li>
    <?php endforeach; ?>
    </ul>

</body>
</html>
