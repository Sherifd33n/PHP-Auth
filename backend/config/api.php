<?php
header('Content-Type: application/json');

// Allow CORS from the Vite development server so `npm run dev` works seamlessly.
// In production (React served from Apache on the same origin) these headers are
// harmless but never sent because HTTP_ORIGIN will not match.
$origin     = $_SERVER['HTTP_ORIGIN'] ?? '';
$devOrigins = ['http://localhost:5173', 'http://127.0.0.1:5173'];

if (in_array($origin, $devOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
}

// Immediately satisfy pre-flight OPTIONS requests
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit();
}
