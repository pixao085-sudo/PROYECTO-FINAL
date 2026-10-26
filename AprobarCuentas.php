<?php
require_once __DIR__ . '/includes/modules.php';
roles_required(['admin']);
$error = '';
$pendientes = [];
$recientes = [];
$conteos = [];
$nombreBase = '';
$consultaOK = false;
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $decision = (string)($_POST['decision'] ?? '');
            if (!$id || !in_array($decision, ['aprobada','rechazada'], true)) {
                throw new InvalidArgumentException('Solicitud no válida.');
            }
            if ($id === user_id()) {
                throw new InvalidArgumentException('No puedes revisar tu propia cuenta desde esta sección.');
            }
            $st = run_sql("UPDATE Usuarios SET estado_cuenta=? WHERE id=? AND estado_cuenta='pendiente' AND LOWER(TRIM(rol)) IN ('aspirante','empresa')", 'si', [$decision, $id]);
            $afectadas = $st->affected_rows;
            $st->close();
            if ($afectadas !== 1) throw new InvalidArgumentException('La cuenta ya fue revisada o no está pendiente.');
            flash_set('ok', $decision === 'aprobada' ? 'Cuenta aprobada. Ahora puede iniciar sesión.' : 'Solicitud rechazada correctamente.');
            go('AprobarCuentas.php');
        } catch (InvalidArgumentException $ex) {
            $error = $ex->getMessage();
        } catch (Throwable $ex) {
            $error = fail_message($ex);
        }
    }
    // Información de lectura: permite distinguir un registro fallido de una cuenta ya aprobada.
    $bd = run_sql('SELECT DATABASE() AS nombre');
    $nombreBase = (string)($bd->get_result()->fetch_assoc()['nombre'] ?? '');
    $bd->close();
    $st = run_sql('SELECT estado_cuenta, COUNT(*) AS total FROM Usuarios GROUP BY estado_cuenta');
    foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $fila) {
        $conteos[(string)$fila['estado_cuenta']] = (int)$fila['total'];
    }
    $st->close();
    $st = run_sql("SELECT id,usuario,Email,rol,estado_cuenta FROM Usuarios WHERE estado_cuenta='pendiente' AND LOWER(TRIM(rol)) IN ('aspirante','empresa') ORDER BY id DESC LIMIT 100");
    $pendientes = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    $st = run_sql('SELECT id, usuario, rol, estado_cuenta FROM Usuarios ORDER BY id DESC LIMIT 6');
    $recientes = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    $consultaOK = true;
} catch (Throwable $ex) {
    $error = fail_message($ex);
}
ui_head('Revisar cuentas','Aprueba o rechaza solicitudes antes de habilitar el acceso.','bi-person-check');
if ($error) echo '<div class="alert alert-error">' . e($error) . '</div>';
echo '<section class="surface">';
echo '<div class="surface-heading"><span class="section-icon"><i class="bi bi-person-lines-fill"></i></span><div><h2>Estado de las cuentas</h2><p>Información obtenida de la base conectada al sitio.</p></div></div>';
if ($consultaOK) echo '<div class="account-summary">';
foreach ($consultaOK ? ['pendiente' => 'Pendientes', 'aprobada' => 'Aprobadas', 'rechazada' => 'Rechazadas'] : [] as $clave => $rotulo) {
    echo '<div class="account-stat"><span>' . e($rotulo) . '</span><strong>' . (int)($conteos[$clave] ?? 0) . '</strong></div>';
}
if ($consultaOK) echo '</div>';
if ($nombreBase !== '') echo '<p class="muted small">Base de datos utilizada por esta página: <code>' . e($nombreBase) . '</code>.</p>';
echo '<div class="alert alert-info">Al crear una cuenta correctamente, debe aparecer como <strong>Pendiente</strong>. Si no aparece aquí ni en «Últimas cuentas registradas», comprueba que el formulario haya mostrado el mensaje de solicitud recibida y que NEXT.php y Conexion.php apunten a esta misma base de datos.</div>';
echo '</section>';
echo '<section class="surface"><h2>Solicitudes pendientes de aprobación</h2>';
if (!$consultaOK) {
    echo '<div class="alert alert-error">No es posible determinar si existen solicitudes hasta restaurar la conexión y revisar las columnas de Usuarios.</div>';
} elseif (!$pendientes) {
    echo '<div class="empty">No hay solicitudes pendientes en esta base de datos.</div>';
} else {
    echo '<div class="table-wrap"><table><thead><tr><th>Usuario</th><th>Correo declarado</th><th>Rol</th><th>Acción</th></tr></thead><tbody>';
    foreach ($pendientes as $fila) {
        echo '<tr><td>' . e($fila['usuario']) . '</td><td>' . e($fila['Email']) . '</td><td>' . e($fila['rol']) . '</td><td><div class="row-actions">';
        foreach (['aprobada' => 'Aprobar', 'rechazada' => 'Rechazar'] as $accion => $rotulo) {
            echo '<form method="post" onsubmit="return confirm(\'¿Confirmas esta decisión?\')">' . csrf_field() . '<input type="hidden" name="id" value="' . (int)$fila['id'] . '"><input type="hidden" name="decision" value="' . e($accion) . '"><button type="submit" class="btn ' . ($accion === 'aprobada' ? 'btn-primary' : 'btn-muted') . '">' . e($rotulo) . '</button></form>';
        }
        echo '</div></td></tr>';
    }
    echo '</tbody></table></div>';
}
echo '</section>';
echo '<section class="surface"><h2>Últimas cuentas registradas</h2><p class="muted small">Este listado sirve para saber si el registro se guardó, aunque la cuenta no esté pendiente.</p>';
if (!$consultaOK) echo '<div class="alert alert-error">No se pudo consultar el registro reciente.</div>';
elseif (!$recientes) echo '<div class="empty">Todavía no hay cuentas en la tabla Usuarios.</div>';
else {
    echo '<div class="table-wrap"><table><thead><tr><th>ID</th><th>Usuario</th><th>Rol</th><th>Estado</th></tr></thead><tbody>';
    foreach ($recientes as $fila) echo '<tr><td>' . (int)$fila['id'] . '</td><td>' . e($fila['usuario']) . '</td><td>' . e($fila['rol']) . '</td><td><span class="tag">' . e($fila['estado_cuenta']) . '</span></td></tr>';
    echo '</tbody></table></div>';
}
echo '<p class="muted small" style="margin-top:14px">La aprobación manual no confirma la titularidad del correo ni la identidad de una empresa. Verifica las solicitudes por un canal independiente.</p>';
echo '</section>';
ui_foot();
