<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

logout_all();
redirect_to('/admin/login.php');
