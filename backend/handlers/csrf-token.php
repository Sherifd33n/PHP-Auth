<?php
// Generates and returns a CSRF token for use in React form submissions.
session_start();
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/api.php';

echo json_encode(['token' => csrf_token()]);
exit();
