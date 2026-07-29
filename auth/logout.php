<?php
require_once __DIR__ . '/../includes/auth.php';

logoutUser();
session_start(); // start a fresh session so we can set a flash message
setFlash('success', 'You have been logged out successfully.');
header('Location: ' . BASE_URL . '/auth/login.php');
exit;
