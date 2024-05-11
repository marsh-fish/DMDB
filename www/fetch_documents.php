<?php
require_once(__DIR__ . '/dbconfig.php');

// Validate category_id from the GET parameter
if (!isset($_GET['category_id']) || !is_numeric($_GET['category_id'])) {
    echo "Invalid category ID.";
    exit;
}

$category_id = intval($_GET['category_id']);  // Convert to integer for safety
$is_user = $_GET['is_user'] === 'true';

if ($is_user) {
    $sql = 'SELECT document.title, document.document_id, document.author, document.publish_date, document.vol, document.no, document.page_number, document.source
        FROM document_category
        JOIN document ON document_category.document_id = document.document_id
        WHERE document_category.category_id = ?';
}
else {
    $sql = 'SELECT document.title, document.document_id, document.author, document.publish_date, document.vol, document.no, document.page_number, document.source
        FROM document_category
        JOIN document ON document_category.document_id = document.document_id
        WHERE document_category.category_id = ? AND document.public = 1';
}

try {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $category_id);  // Bind the parameter
    $stmt->execute();
    $documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($documents)) {
        echo "<p>No documents found for this category.</p>";
    } else {
        echo "<ul>";
        foreach ($documents as $document) {
            echo "<li>";
            
            // Format based on whether it's a journal or conference
            echo htmlspecialchars($document['author'], ENT_QUOTES, 'UTF-8') . ", ";

            if (!is_null($document['vol']) && !is_null($document['no'])) {
                // Journal formatting
                echo '"' . htmlspecialchars($document['title'], ENT_QUOTES, 'UTF-8') . '", ';
                echo "<i>" . htmlspecialchars($document['source'], ENT_QUOTES, 'UTF-8') . "</i>, ";
                echo "Vol. " . htmlspecialchars($document['vol'], ENT_QUOTES, 'UTF-8') . ", ";
                echo "No. " . htmlspecialchars($document['no'], ENT_QUOTES, 'UTF-8') . ", ";
                echo "pp." . htmlspecialchars($document['page_number'], ENT_QUOTES, 'UTF-8') . ", ";
                echo htmlspecialchars($document['publish_date'], ENT_QUOTES, 'UTF-8');
            } else {
                // Conference formatting
                echo '"' . htmlspecialchars($document['title'], ENT_QUOTES, 'UTF-8') . '", ';
                echo "<i>" . htmlspecialchars($document['source'], ENT_QUOTES, 'UTF-8') . "</i>, ";
                echo "pp. " . htmlspecialchars($document['page_number'], ENT_QUOTES, 'UTF-8') . ", ";
                echo htmlspecialchars($document['publish_date'], ENT_QUOTES, 'UTF-8');
            }
            
            echo '<a href="detail.php?document_id=' . htmlspecialchars($document['document_id'], ENT_QUOTES, 'UTF-8') . '"> Details >></a>';
            echo "</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "Error fetching documents: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
