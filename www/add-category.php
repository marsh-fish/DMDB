<?php
require_once(__DIR__ . '/dbconfig.php');

// Check if the form data is submitted via POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize the input
    $category_name = filter_input(INPUT_POST, 'category_name', FILTER_SANITIZE_STRING);

    // Perform validation checks
    if (empty($category_name)) {
        // Handle empty category name error
        echo json_encode(array("error" => "Category name cannot be empty"));
        exit;
    }

    // Prepare SQL statement to insert new category into the database
    $sql = "INSERT INTO category (category_name) VALUES (?)";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt === false) {
        // Handle SQL statement preparation error
        echo json_encode(array("error" => "Failed to prepare the SQL statement"));
        exit;
    }

    // Bind parameters and execute the statement
    mysqli_stmt_bind_param($stmt, "s", $category_name);
    $result = mysqli_stmt_execute($stmt);

    if ($result === false) {
        // Handle SQL statement execution error
        echo json_encode(array("error" => "Failed to execute the SQL statement"));
        exit;
    }

    // Get the ID of the newly inserted category
    $new_category_id = mysqli_insert_id($conn);

    // Return the newly inserted category ID and name as JSON response
    echo json_encode(array("category_id" => $new_category_id, "category_name" => $category_name));

    // Close the statement and database connection
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
} else {
    // If the request method is not POST, return an error response
    echo json_encode(array("error" => "Invalid request method"));
}
?>
