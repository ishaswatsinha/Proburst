<?php
// =============================================
// pages/logout.php
// =============================================
session_start();
require_once '../includes/auth.php';

logoutUser(); // destroys session and redirects to /pages/login.php
