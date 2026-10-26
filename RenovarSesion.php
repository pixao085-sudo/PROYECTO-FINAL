<?php
require_once __DIR__.'/includes/app.php';
roles_required(['admin','aspirante','empresa']);
if($_SERVER['REQUEST_METHOD']!=='POST') go('dashboard.php');
csrf_check();
$_SESSION['ultima_actividad']=time();
flash_set('ok','Sesión ampliada. Dispones de otros '.(int)(session_limit()/60).' minutos de inactividad.');
go('dashboard.php');
