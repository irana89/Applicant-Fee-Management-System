<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "psc_tmk";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
session_start(); // Start session if not already started
?>