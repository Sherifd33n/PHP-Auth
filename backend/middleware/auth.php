<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: /Authentication/frontend/pages/login.php');
    exit();
}
