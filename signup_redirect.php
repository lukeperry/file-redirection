<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

// You can add your redirection logic here
// For example, redirect to a specific folder or page
echo "You have been successfully redirected after signup!";
?>