<?php
/**
 * EstateVista India - Admin Panel Header Component
 */
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/admin_functions.php';

// Enforce login on all admin pages that include this header
require_admin_login();

$current_admin = get_logged_in_admin();
$admin_title = isset($page_title) ? $page_title : 'Command Center';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= e($admin_title) ?> | EstateVista Admin</title>

    <!-- Google Fonts: Playfair Display & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Admin Design System CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>admin/assets/css/admin.css">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='18' fill='%230B1528'/><text x='50%' y='62%' font-size='42' font-family='serif' font-weight='bold' fill='%23C5A880' text-anchor='middle'>EV</text></svg>">

    <script>
        window.BASE_URL = <?= json_encode(BASE_URL) ?>;
    </script>
</head>
<body class="admin-body">
