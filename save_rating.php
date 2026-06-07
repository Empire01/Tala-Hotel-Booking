<?php
require_once 'config/config.php';

// Start session to access session variables
session_start();

// Debug: Check if session is started and if user_id exists in the session
error_log("Session data: " . print_r($_SESSION, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
  $comment = isset($_POST['users_comment']) && !empty(trim($_POST['users_comment'])) ? trim($_POST['users_comment']) : NULL;

  // Debugging the comment and rating
  error_log("Received rating: " . $rating);
  error_log("Received comment: " . $comment);

  // Check if the user is logged in
  $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;

  // Debugging user_id
  if ($user_id === NULL) {
    error_log("User ID is NULL or not set.");
  } else {
    error_log("User ID: " . $user_id);
  }

  // If rating is valid, proceed to insert into the database
  if ($rating > 0 && $rating <= 5) {
    $database = new Database();
    $conn = $database->connect();

    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO ratings (rating, comment, user_id) VALUES (:rating, :users_comment, :user_id)");
    $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
    $stmt->bindParam(':users_comment', $comment, PDO::PARAM_STR); // Allow comment to be NULL
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT); // Bind the user_id, which can be NULL if not logged in

    if ($stmt->execute()) {
      echo "success";
    } else {
      echo "error";
    }
  } else {
    echo "invalid";
  }
}
