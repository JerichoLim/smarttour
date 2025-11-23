<?php
session_start();
require 'koneksi.php';

// =====================================
// Wajib login
// =====================================
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user = (int)$_SESSION['user_id'];

// =====================================
// Handle POST: Delete Itinerary
// =====================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itinerary_id = (int)($_POST['itinerary_id'] ?? 0);
    
    if ($itinerary_id <= 0) {
        // Invalid itinerary ID
        header("Location: itineraries.php");
        exit;
    }
    
    // Verify that the itinerary belongs to the current user
    $sqlCheck = "
        SELECT itinerary_id 
        FROM itineraries 
        WHERE itinerary_id = ? AND id_user = ?
        LIMIT 1
    ";
    
    $stmtCheck = mysqli_prepare($koneksi, $sqlCheck);
    if (!$stmtCheck) {
        die("Database error: " . mysqli_error($koneksi));
    }
    
    mysqli_stmt_bind_param($stmtCheck, "ii", $itinerary_id, $id_user);
    mysqli_stmt_execute($stmtCheck);
    mysqli_stmt_store_result($stmtCheck);
    
    if (mysqli_stmt_num_rows($stmtCheck) === 0) {
        // Itinerary not found or doesn't belong to user
        mysqli_stmt_close($stmtCheck);
        header("Location: itineraries.php");
        exit;
    }
    mysqli_stmt_close($stmtCheck);
    
    // =====================================
    // Delete itinerary items first (cascade)
    // =====================================
    $sqlDeleteItems = "
        DELETE FROM itinerary_items 
        WHERE itinerary_id = ?
    ";
    
    $stmtDeleteItems = mysqli_prepare($koneksi, $sqlDeleteItems);
    if ($stmtDeleteItems) {
        mysqli_stmt_bind_param($stmtDeleteItems, "i", $itinerary_id);
        mysqli_stmt_execute($stmtDeleteItems);
        mysqli_stmt_close($stmtDeleteItems);
    }
    
    // =====================================
    // Delete the itinerary itself
    // =====================================
    $sqlDeleteItinerary = "
        DELETE FROM itineraries 
        WHERE itinerary_id = ? AND id_user = ?
    ";
    
    $stmtDeleteItinerary = mysqli_prepare($koneksi, $sqlDeleteItinerary);
    if ($stmtDeleteItinerary) {
        mysqli_stmt_bind_param($stmtDeleteItinerary, "ii", $itinerary_id, $id_user);
        
        if (mysqli_stmt_execute($stmtDeleteItinerary)) {
            // Success - redirect to itineraries list
            mysqli_stmt_close($stmtDeleteItinerary);
            header("Location: itineraries.php?deleted=1");
            exit;
        } else {
            // Error deleting
            mysqli_stmt_close($stmtDeleteItinerary);
            header("Location: itineraries.php?error=delete_failed");
            exit;
        }
    } else {
        // Database error
        header("Location: itineraries.php?error=db_error");
        exit;
    }
} else {
    // Not a POST request, redirect to itineraries list
    header("Location: itineraries.php");
    exit;
}
