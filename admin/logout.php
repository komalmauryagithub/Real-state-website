<?php
/**
 * EstateVista India - Admin Logout
 */
require_once __DIR__ . '/includes/admin_auth.php';

logout_admin();
header('Location: ' . BASE_URL . 'admin/login.php?logged_out=1');
exit;
