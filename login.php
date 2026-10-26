<?php
require_once __DIR__ . '/includes/app.php';
if (authenticated()) go('dashboard.php');
$registro = (($_GET['pestana'] ?? '') === 'registro');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#101B2E">
  <title>Acceso y registro | Empleabilidad INAC</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/inac.css?v=5">
  <link rel="stylesheet" href="assets/auth.css?v=6">
  <link rel="stylesheet" href="assets/auth-refinado.css?v=6">
</head>
<body class="login-page auth-page">
  <div class="auth-backdrop" aria-hidden="true"></div>
  <header class="auth-topbar">
    <a class="auth-topbrand" href="login.php" aria-label="Empleabilidad INAC, inicio de sesión">
      <span class="auth-topbrand-icon"><svg viewBox="0 0 64 64" width="25" height="25" aria-hidden="true" focusable="false" fill="none"><path d="M22 18v-4a7 7 0 0 1 7-7h6a7 7 0 0 1 7 7v4" stroke="currentColor" stroke-width="4.4" stroke-linecap="round"/><rect x="8" y="19" width="48" height="35" rx="7" stroke="currentColor" stroke-width="4.4"/><path d="M8 30c6.7 6.5 15 9.7 24 9.7S49.3 36.5 56 30" stroke="currentColor" stroke-width="4.4" stroke-linecap="round"/><rect x="27" y="35" width="10" height="9" rx="2.5" fill="#7AB9F0"/></svg></span>
      <span>Empleabilidad <strong>INAC</strong><small>Instituto Nacional Albert Camus</small></span>
    </a>
    <span class="auth-topbar-caption"><i class="bi bi-shield-check" aria-hidden="true"></i> Acceso institucional</span>
  </header>

  <main class="auth-main">
    <section class="auth-card" aria-labelledby="auth-heading">
      <div class="auth-intro">
        <div class="auth-emblem" aria-hidden="true">
          <span class="auth-emblem-orbit"></span>
          <span class="auth-emblem-center">
            <svg viewBox="0 0 64 64" width="56" height="56" aria-hidden="true" focusable="false" fill="none">
              <path d="M22 18v-4a7 7 0 0 1 7-7h6a7 7 0 0 1 7 7v4" stroke="currentColor" stroke-width="4.4" stroke-linecap="round"/>
              <rect x="8" y="19" width="48" height="35" rx="7" stroke="currentColor" stroke-width="4.4"/>
              <path d="M8 30c6.7 6.5 15 9.7 24 9.7S49.3 36.5 56 30" stroke="currentColor" stroke-width="4.4" stroke-linecap="round"/>
              <rect x="27" y="35" width="10" height="9" rx="2.5" fill="#7AB9F0"/>
            </svg>
          </span>
        </div>
        <div class="auth-kicker">PORTAL DE OPORTUNIDADES</div>
        <h1 id="auth-heading">Empleabilidad INAC</h1>
        <p class="auth-institution">Instituto Nacional Albert Camus</p>
        <p class="auth-tagline">Consulta ofertas, presenta tus postulaciones y revisa su estado en un solo lugar.</p>
      </div>

      <div class="auth-form-zone">
        <?= flash_html() ?>
        <?php if (isset($_GET['cuenta']) && $_GET['cuenta'] === 'eliminada'): ?>
          <div class="alert alert-info">Tu cuenta de acceso se eliminó correctamente.</div>
        <?php endif; ?>
        <?php if (isset($_GET['sesion']) && $_GET['sesion'] === 'expirada'): ?>
          <div class="alert alert-info">La sesión finalizó por inactividad. Inicia sesión de nuevo.</div>
        <?php endif; ?>
        <?php if (isset($_GET['salida'])): ?>
          <div class="alert alert-info">Sesión cerrada correctamente.</div>
        <?php endif; ?>

        <div class="auth-tabs" role="tablist" aria-label="Acceder o crear una cuenta">
          <button id="t-entrar" type="button" role="tab" aria-controls="p-entrar" aria-selected="<?= !$registro ? 'true' : 'false' ?>" class="auth-tab <?= !$registro ? 'active' : '' ?>" onclick="switchTab('entrar')">Iniciar sesión</button>
          <button id="t-registro" type="button" role="tab" aria-controls="p-registro" aria-selected="<?= $registro ? 'true' : 'false' ?>" class="auth-tab <?= $registro ? 'active' : '' ?>" onclick="switchTab('registro')">Crear cuenta</button>
        </div>

        <div id="p-entrar" class="auth-panel" role="tabpanel" aria-labelledby="t-entrar" <?= $registro ? 'hidden' : '' ?>>
          <p class="auth-panel-description">Ingresa tus datos para continuar.</p>
          <form action="NEXT.php" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="entrar">
            <div class="field auth-field">
              <label for="usuario">Nombre de usuario</label>
              <div class="auth-input-wrap"><i class="bi bi-person" aria-hidden="true"></i><input type="text" id="usuario" name="usuario" autocomplete="username" placeholder="Escribe tu usuario" required></div>
            </div>
            <div class="field auth-field">
              <label for="clave">Contraseña</label>
              <div class="auth-input-wrap"><i class="bi bi-lock" aria-hidden="true"></i><input type="password" id="clave" name="clave" autocomplete="current-password" placeholder="Escribe tu contraseña" required><button type="button" class="auth-show-password" data-toggle-password="clave" aria-label="Mostrar contraseña" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button></div>
            </div>
            <button class="btn auth-submit" type="submit"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Iniciar sesión</button>
          </form>
          <p class="auth-assistance"><i class="bi bi-info-circle" aria-hidden="true"></i> Si olvidaste tu contraseña, contacta al administrador para restablecerla. No se pueden recuperar contraseñas originales.</p>
        </div>

        <div id="p-registro" class="auth-panel" role="tabpanel" aria-labelledby="t-registro" <?= !$registro ? 'hidden' : '' ?>>
          <p class="auth-panel-description">Solicita tu acceso como aspirante o empresa.</p>
          <form action="NEXT.php" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="registrar">
            <div class="field auth-field">
              <label for="tipo_cuenta">Tipo de cuenta</label>
              <div class="auth-input-wrap"><i class="bi bi-people" aria-hidden="true"></i><select name="tipo_cuenta" id="tipo_cuenta" required><option value="">Selecciona tu perfil</option><option value="aspirante">Aspirante</option><option value="empresa">Empresa</option></select></div>
            </div>
            <div class="field auth-field">
              <label for="nuevoUsuario">Nombre de usuario</label>
              <div class="auth-input-wrap"><i class="bi bi-person" aria-hidden="true"></i><input type="text" id="nuevoUsuario" name="nuevoUsuario" minlength="3" maxlength="50" autocomplete="username" placeholder="Ejemplo: empresa_inac26" required></div>
              <small class="auth-hint">De 3 a 50 caracteres, sin espacios. Se permiten letras, números, punto, guion y guion bajo.</small>
            </div>
            <div class="field auth-field">
              <label for="nuevoEmail">Correo electrónico</label>
              <div class="auth-input-wrap"><i class="bi bi-envelope" aria-hidden="true"></i><input type="email" id="nuevoEmail" name="nuevoEmail" autocomplete="email" placeholder="nombre@correo.com" required></div>
            </div>
            <div class="field auth-field">
              <label for="nuevoClave">Contraseña</label>
              <div class="auth-input-wrap"><i class="bi bi-lock" aria-hidden="true"></i><input type="password" id="nuevoClave" name="nuevoClave" minlength="10" autocomplete="new-password" placeholder="Crea tu contraseña" required><button type="button" class="auth-show-password" data-toggle-password="nuevoClave" aria-label="Mostrar contraseña" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button></div>
              <small class="auth-hint">Al menos 10 caracteres, con una mayúscula, una minúscula y un número.</small>
            </div>
            <div class="field auth-field">
              <label for="confirmarClave">Confirmar contraseña</label>
              <div class="auth-input-wrap"><i class="bi bi-shield-lock" aria-hidden="true"></i><input type="password" id="confirmarClave" name="confirmarClave" minlength="10" autocomplete="new-password" placeholder="Repite tu contraseña" required><button type="button" class="auth-show-password" data-toggle-password="confirmarClave" aria-label="Mostrar contraseña" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button></div>
            </div>
            <!-- Campo antirrobots. No es un dato que deba completar una empresa. -->
            <div class="honeypot" hidden aria-hidden="true" inert style="display:none!important"><input type="text" id="sitio_web" name="sitio_web" tabindex="-1" autocomplete="off"></div>
            <label class="check-row auth-consent"><input type="checkbox" name="acepto" value="1" required><span>Confirmo que los datos son verdaderos y acepto la revisión de mi solicitud por el administrador.</span></label>
            <p class="auth-notice"><i class="bi bi-clock-history" aria-hidden="true"></i> La cuenta quedará pendiente hasta que el administrador la apruebe.</p>
            <button class="btn auth-submit" type="submit"><i class="bi bi-person-plus" aria-hidden="true"></i> Enviar solicitud de registro</button>
          </form>
        </div>
      </div>
    </section>
  </main>
  <footer class="auth-footer">© <?= e(date('Y')) ?> Instituto Nacional Albert Camus. Todos los derechos reservados.<span>Empleabilidad INAC · Bolsa de trabajo y formación</span></footer>
  <script src="assets/auth.js?v=6" defer></script>
</body>
</html>
