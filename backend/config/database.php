<?php
require_once __DIR__ . '/env.php';

// .env is two levels up: backend/config/ → backend/ → Authentication/
loadEnv(__DIR__ . '/../../.env');

$conn = mysqli_connect(
    getenv('DB_HOST'),
    getenv('DB_USER'),
    getenv('DB_PASSWORD'),
    getenv('DB_NAME')
);

if (!$conn) {
    // Never expose raw connection errors to end users in production
    error_log('Database connection failed: ' . mysqli_connect_error());
    die('A database error occurred. Please try again later.');
}
