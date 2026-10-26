<?php
require_once __DIR__ . '/includes/app.php';
auth_required();
$usuarioActual = (string)$_SESSION['usuario'];
$rolActual = role();
