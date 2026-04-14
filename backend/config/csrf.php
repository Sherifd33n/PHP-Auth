<?php
/**
 * CSRF token helpers.
 *
 * Provides a cryptographically secure token that React can fetch
 * via the csrf-token.php endpoint to include in POST requests.
 */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}
