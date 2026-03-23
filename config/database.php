<?php

$host = "localhost";
$user = "root";
$password = "";
$dbname = "proburst_db";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// INFINITYFREE DATABASE CONFIGURATION

// $host = "sql306.infinityfree.com";
// $user = "if0_40870720";
// $password = "fe9AKUBWEB";
// $dbname = "if0_40870720_proburst_db";

// $conn = new mysqli($host, $user, $password, $dbname);

// if ($conn->connect_error) {
//     die("Database connection failed: " . $conn->connect_error);
// }


// HOSTINGER DATABASE CONFIGURATION

// $host = "localhost";
// $user = "u398982203_proburst";
// $password = "Proburst$1234$";
// $dbname = "u398982203_proburst";

// $conn = new mysqli($host, $user, $password, $dbname);

// if ($conn->connect_error) {
//     die("Database connection failed: " . $conn->connect_error);
// }
