<?php
require_once 'config/config.php';

// Destroy session
session_destroy();

// Set flash message
setFlashMessage('success', 'You have been logged out successfully.');

// Redirect to login page
redirect(APP_URL . '/login.php');
?> 