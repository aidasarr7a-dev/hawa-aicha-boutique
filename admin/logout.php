<?php
require_once __DIR__ . '/../includes/functions.php';

unset($_SESSION['admin_id'], $_SESSION['admin_nom']);
session_regenerate_id(true);
redirect('login.php');
