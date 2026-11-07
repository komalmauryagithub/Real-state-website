<?php
/**
 * EstateVista India - Client Logout Handler
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

logout_user();

header('Location: ' . BASE_URL . 'login.php?logged_out=1');
exit;
