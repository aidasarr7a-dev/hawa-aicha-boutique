<?php
require_once __DIR__ . '/../includes/functions.php';

function require_admin(): void
{
    if (empty($_SESSION['admin_id'])) {
        redirect(base_url('admin/login.php'));
    }
}

require_admin();
