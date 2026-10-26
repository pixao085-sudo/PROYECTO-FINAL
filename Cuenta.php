<?php
require_once __DIR__ . '/includes/modules.php';
roles_required(['admin', 'aspirante', 'empresa']);

$error = '';
try {
    $st = run_sql('SELECT id, usuario, Email, clave, rol FROM Usuarios WHERE id = ? LIMIT 1', 'i', [user_id()]);
    $cuenta = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$cuenta) { clear_login_session(); go('login.php'); }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $accion = (string)($_POST['accion'] ?? '');
        try {
            if ($accion === 'cambiar_clave') {
                $actual = (string)($_POST['clave_actual'] ?? '');
                $nueva = (string)($_POST['clave_nueva'] ?? '');
                $confirmar = (string)($_POST['clave_confirmar'] ?? '');
                $hashActual = (string)$cuenta['clave'];
                $esLegacy = (password_get_info($hashActual)['algoName'] ?? 'unknown') === 'unknown';
                $correcta = password_verify($actual, $hashActual)
                    || ($esLegacy && hash_equals($hashActual, $actual));

                if (!$correcta) {
                    throw new InvalidArgumentException('La contraseña actual es incorrecta.');
                }
                if (strlen($nueva) < 10 || !preg_match('/[a-z]/', $nueva)
                    || !preg_match('/[A-Z]/', $nueva) || !preg_match('/[0-9]/', $nueva)) {
                    throw new InvalidArgumentException('La nueva contraseña debe tener al menos 10 caracteres, una mayúscula, una minúscula y un número.');
                }
                if (!hash_equals($nueva, $confirmar)) {
                    throw new InvalidArgumentException('La confirmación no coincide con la nueva contraseña.');
                }
                if (hash_equals($actual, $nueva)) {
                    throw new InvalidArgumentException('La contraseña nueva debe ser diferente de la actual.');
                }
                $nuevoHash = password_hash($nueva, PASSWORD_DEFAULT);
                if ($nuevoHash === false) throw new RuntimeException('No se pudo proteger la nueva contraseña.');
                // Condición sobre el hash anterior: evita sobrescribir un cambio simultáneo.
                $st = run_sql('UPDATE Usuarios SET clave = ? WHERE id = ? AND clave = ?',
                    'sis', [$nuevoHash, user_id(), $hashActual]);
                $actualizadas = $st->affected_rows;
                $st->close();
                if ($actualizadas !== 1) {
                    throw new RuntimeException('La contraseña cambió en otra sesión. Actualiza la página y vuelve a intentarlo.');
                }
                session_regenerate_id(true);
                flash_set('ok', 'Contraseña actualizada correctamente.');
                go('Cuenta.php');
            }

            if ($accion === 'eliminar_cuenta') {
                if (role() === 'admin') {
                    throw new InvalidArgumentException('La cuenta administradora no puede eliminarse desde aquí.');
                }
                if ((string)($_POST['confirmacion'] ?? '') !== 'ELIMINAR') {
                    throw new InvalidArgumentException('Escribe ELIMINAR para confirmar.');
                }
                $clave = (string)($_POST['clave'] ?? '');
                $hash = (string)$cuenta['clave'];
                $esLegacy = (password_get_info($hash)['algoName'] ?? 'unknown') === 'unknown';
                $valida = password_verify($clave, $hash) || ($esLegacy && hash_equals($hash, $clave));
                if (!$valida) throw new InvalidArgumentException('La contraseña es incorrecta.');
                // Se conserva el historial de perfiles, ofertas y postulaciones.
                $st = run_sql('DELETE FROM Usuarios WHERE id = ?', 'i', [user_id()]);
                $eliminadas = $st->affected_rows;
                $st->close();
                if ($eliminadas !== 1) throw new RuntimeException('No se pudo eliminar la cuenta.');
                clear_login_session();
                go('login.php?cuenta=eliminada');
            }

            throw new InvalidArgumentException('Operación no válida.');
        } catch (InvalidArgumentException $ex) {
            $error = $ex->getMessage();
        } catch (Throwable $ex) {
            $error = fail_message($ex);
        }
    }
} catch (Throwable $ex) {
    $error = fail_message($ex);
    $cuenta = $cuenta ?? null;
}

ui_head('Mi cuenta', 'Consulta tus datos de acceso y actualiza tu contraseña.', 'bi-person-gear');
if ($error !== '') echo '<div class="alert alert-error" role="alert">' . e($error) . '</div>';
if ($cuenta) {
    echo '<section class="surface"><div class="surface-heading"><span class="section-icon"><i class="bi bi-person-circle"></i></span><div><h2>Datos de acceso</h2><p>Información asociada a tu sesión.</p></div></div>';
    echo '<p><strong>Usuario:</strong> ' . e($cuenta['usuario']) . '</p>';
    echo '<p><strong>Correo:</strong> ' . e($cuenta['Email']) . '</p>';
    echo '<p><strong>Rol:</strong> ' . e($cuenta['rol']) . '</p>';
    echo '<p class="muted">Tu sesión expira tras ' . (int)(session_limit()/60) . ' minutos de inactividad.</p></section>';

    echo '<section class="surface"><div class="surface-heading"><span class="section-icon"><i class="bi bi-shield-lock"></i></span><div><h2>Cambiar contraseña</h2><p>Esta opción está disponible para los tres tipos de usuario.</p></div></div>';
    echo '<form method="post" class="form-grid" autocomplete="on">' . csrf_field();
    echo '<input type="hidden" name="accion" value="cambiar_clave">';
    echo '<div class="field"><label for="clave_actual">Contraseña actual</label><input type="password" id="clave_actual" name="clave_actual" autocomplete="current-password" required></div>';
    echo '<div class="field"><label for="clave_nueva">Nueva contraseña</label><input type="password" id="clave_nueva" name="clave_nueva" autocomplete="new-password" minlength="10" required><small>Mínimo 10 caracteres, con mayúscula, minúscula y número.</small></div>';
    echo '<div class="field"><label for="clave_confirmar">Confirmar nueva contraseña</label><input type="password" id="clave_confirmar" name="clave_confirmar" autocomplete="new-password" minlength="10" required></div>';
    echo '<div class="form-actions"><button type="submit" class="btn btn-primary"><i class="bi bi-shield-check"></i> Guardar nueva contraseña</button></div></form></section>';

    if (role() !== 'admin') {
        echo '<section class="surface"><div class="surface-heading"><span class="section-icon"><i class="bi bi-shield-exclamation"></i></span><div><h2>Eliminar mi cuenta</h2><p>Esta acción necesita confirmación.</p></div></div>';
        echo '<div class="alert alert-info">Se elimina tu acceso de Usuarios y se cierra la sesión. Los registros históricos de perfiles, ofertas y postulaciones permanecen guardados.</div>';
        echo '<form method="post" class="form-grid" onsubmit="return confirm(\'¿Eliminar definitivamente tu acceso?\')">' . csrf_field();
        echo '<input type="hidden" name="accion" value="eliminar_cuenta">';
        echo '<div class="field"><label for="clave">Contraseña actual</label><input type="password" id="clave" name="clave" autocomplete="current-password" required></div>';
        echo '<div class="field"><label for="confirmacion">Escribe ELIMINAR</label><input type="text" id="confirmacion" name="confirmacion" autocomplete="off" required></div>';
        echo '<div class="form-actions"><button class="btn btn-danger" type="submit"><i class="bi bi-trash"></i> Eliminar mi cuenta</button></div></form></section>';
    }
}
ui_foot();
