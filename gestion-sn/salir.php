<?php
declare(strict_types=1);
require __DIR__ . '/../inc/auth.php';
sn_cerrar_sesion();
header('Location: index.php');
