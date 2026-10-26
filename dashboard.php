<?php
require_once __DIR__ . '/SECIONES.php';
require_once __DIR__ . '/includes/modules.php';
$rol = role();
$nombreUsuario = (string)($_SESSION['usuario'] ?? 'Usuario');
$admin = $rol === 'admin';
$empresa = $rol === 'empresa';
$menus = [];
if ($admin) {
    $menus = [
        ['GuardarAspirante.php','bi-person-vcard','Aspirantes','Registrar nuevos aspirantes y mantener su información organizada.'],
        ['GuardarEmpresa.php','bi-building','Empresas','Incorporar empresas a la plataforma.'],
        ['GuardarOferta.php','bi-briefcase','Ofertas laborales','Publicar vacantes y sus requisitos.'],
        ['GuardarPostulacion.php','bi-file-earmark-text','Postulaciones','Gestionar solicitudes laborales.'],
        ['GuardarCapacitacion.php','bi-book','Capacitaciones','Publicar cursos y capacitaciones.'],
        ['GestionarRegistros.php','bi-database-gear','Administrar registros','Consultar y actualizar datos del sistema.'],
        ['AprobarCuentas.php','bi-person-check','Revisar cuentas','Aprobar o rechazar solicitudes nuevas.'],
        ['CorregirTipoCuenta.php','bi-person-gear','Corregir cuentas','Corregir roles asignados por error.'],
        ['ConfigurarDemoEmpresas.php','bi-building-check','Empresas de prueba','Preparar accesos de demostración.'],
        ['ConfiguracionAdmin.php','bi-gear','Configuración','Administración de la plataforma.']
    ];
    $grupos = [
        ['registros','bi-plus-square','Registrar información', array_slice($menus,0,5)],
        ['administracion','bi-sliders','Gestión y revisión', array_slice($menus,5)]
    ];
} elseif ($empresa) {
    $menus = [
        ['MiEmpresa.php','bi-building','Perfil de empresa','Consulta o completa los datos de tu empresa.'],
        ['GuardarOferta.php','bi-briefcase','Publicar una oferta','Registra una vacante laboral.'],
        ['GestionarRegistros.php?modulo=ofertas','bi-card-list','Mis ofertas','Edita y administra tus ofertas.'],
        ['PostulacionesEmpresa.php','bi-file-earmark-text','Postulaciones','Revisa solicitudes y actualiza su estado.']
    ];
    $grupos = [
        ['empresa','bi-building','Mi empresa',array_slice($menus,0,1)],
        ['ofertas','bi-briefcase','Ofertas y postulaciones',array_slice($menus,1)]
    ];
} else {
    $menus = [
        ['MiPerfil.php','bi-person-vcard','Mi perfil','Completa tus datos personales y académicos.'],
        ['OfertasDisponibles.php','bi-briefcase','Ofertas laborales','Explora vacantes disponibles y postúlate.'],
        ['MisPostulaciones.php','bi-file-earmark-text','Mis postulaciones','Consulta el estado de tus solicitudes.'],
        ['CapacitacionesDisponibles.php','bi-book','Capacitaciones','Revisa los cursos e inscríbete.'],
        ['MisCapacitaciones.php','bi-journal-check','Mis capacitaciones','Consulta tu historial de inscripciones.']
    ];
    $grupos = [
        ['perfil','bi-person','Mi perfil',array_slice($menus,0,1)],
        ['empleo','bi-briefcase','Empleo y postulaciones',array_slice($menus,1,2)],
        ['formacion','bi-mortarboard','Formación',array_slice($menus,3)]
    ];
}
$descrip = $admin ? 'Supervisa las cuentas y mantén organizada la información del sistema.' : ($empresa ? 'Publica oportunidades y acompaña el proceso de selección de talento.' : 'Encuentra oportunidades laborales, fortalece tu formación y da seguimiento a cada postulación.');
$anio = date('Y');
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#142B4A">
<title>Panel principal | Empleabilidad INAC</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="assets/dashboard.css?v=4">
</head>
<body class="inac-dashboard">
<div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
<aside id="sidebar" class="inac-sidebar" aria-label="Menú lateral">
  <div class="sidebar-top">
    <a href="dashboard.php" class="sidebar-brand" aria-label="Ir al inicio de Empleabilidad INAC">
      <span class="brand-mark"><i class="bi bi-briefcase-fill" aria-hidden="true"></i></span>
      <span class="brand-name"><strong>Empleabilidad INAC</strong><small>Instituto Nacional Albert Camus</small></span>
    </a>
    <button class="sidebar-close" type="button" data-toggle-sidebar aria-label="Ocultar menú lateral" title="Ocultar menú"><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
  </div>
  <nav class="sidebar-nav" aria-label="Navegación principal">
    <a href="dashboard.php" class="sidebar-home" aria-current="page"><i class="bi bi-house-door" aria-hidden="true"></i><span>Inicio</span></a>
    <p class="sidebar-caption">MÓDULOS</p>
    <?php foreach ($grupos as $grupo): ?>
    <details class="sidebar-group" data-group="<?= e($grupo[0]) ?>" open>
      <summary><span class="group-title"><i class="bi <?= e($grupo[1]) ?>" aria-hidden="true"></i><?= e($grupo[2]) ?></span><i class="bi bi-chevron-down group-chevron" aria-hidden="true"></i></summary>
      <div class="group-links">
        <?php foreach ($grupo[3] as $item): ?>
        <a href="<?= e($item[0]) ?>" class="sidebar-link"><i class="bi <?= e($item[1]) ?>" aria-hidden="true"></i><span><?= e($item[2]) ?></span></a>
        <?php endforeach; ?>
      </div>
    </details>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-bottom">
    <a href="Cuenta.php" class="sidebar-fixed-link"><i class="bi bi-person-gear" aria-hidden="true"></i><span>Mi cuenta</span></a>
    <form method="post" action="LOGOUT.php" class="sidebar-logout">
      <?= csrf_field() ?>
      <button type="submit" class="sidebar-fixed-link"><i class="bi bi-box-arrow-right" aria-hidden="true"></i><span>Cerrar sesión</span></button>
    </form>
  </div>
</aside>
<main id="contenido" class="inac-main">
  <header class="inac-topbar">
    <div class="topbar-start">
      <button id="sidebarToggle" class="menu-toggle" type="button" data-toggle-sidebar aria-controls="sidebar" aria-expanded="true" aria-label="Ocultar menú lateral" title="Mostrar u ocultar menú"><i class="bi bi-layout-sidebar-inset" aria-hidden="true"></i></button>
      <div class="topbar-heading"><h2>Panel principal</h2><p>Bolsa de trabajo y empleabilidad</p></div>
    </div>
    <div class="user-chip" aria-label="Cuenta activa"><span class="user-avatar"><i class="bi bi-person" aria-hidden="true"></i></span><span><strong><?= e($nombreUsuario) ?></strong><small><?= e(ucfirst($rol)) ?></small></span></div>
  </header>
  <section class="welcome-panel" aria-label="Bienvenida institucional">
    <div class="welcome-content">
      <div class="welcome-eyebrow"><span class="welcome-dot"></span> PLATAFORMA INSTITUCIONAL</div>
      <p class="welcome-institution">Instituto Nacional Albert Camus</p>
      <h1>Empleabilidad <span>INAC</span></h1>
      <p class="welcome-phrase">Del aprendizaje a las oportunidades laborales.</p>
      <div class="welcome-rule"></div>
      <h2>Bienvenido, <?= e($nombreUsuario) ?></h2>
      <p class="welcome-description"><?= e($descrip) ?></p>
    </div>
    <div class="welcome-illustration" aria-hidden="true"><i class="bi bi-briefcase-fill"></i></div>
  </section>
  <section class="modules-section" aria-label="Herramientas disponibles">
    <div class="section-heading"><div><p class="section-overline">TU ESPACIO DE TRABAJO</p><h2><?= $admin ? 'Gestión del sistema' : 'Tus herramientas' ?></h2></div><span class="module-count"><?= count($menus) ?> módulos</span></div>
    <div class="row g-3">
      <?php foreach ($menus as $item): ?>
      <div class="col-12 col-md-6 col-xl-4">
        <a href="<?= e($item[0]) ?>" class="module-card">
          <div class="module-icon"><i class="bi <?= e($item[1]) ?>" aria-hidden="true"></i></div>
          <h3><?= e($item[2]) ?></h3>
          <p><?= e($item[3]) ?></p>
          <span class="module-action">Abrir módulo <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <section class="session-panel" aria-label="Estado de sesión">
    <span class="session-icon"><i class="bi bi-shield-check" aria-hidden="true"></i></span>
    <div><strong>Sesión protegida</strong><p>Rol: <?= e(ucfirst($rol)) ?> · Cierre por inactividad: <?= (int)(session_limit()/60) ?> minutos.</p></div>
    <form action="RenovarSesion.php" method="post"><?= csrf_field() ?><button class="renew-button" type="submit"><i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Ampliar tiempo</button></form>
  </section>
  <footer class="inac-footer">© <?= e($anio) ?> Instituto Nacional Albert Camus. Todos los derechos reservados.<span>Empleabilidad INAC · Bolsa de trabajo y formación</span></footer>
</main>
<script src="assets/dashboard.js?v=4" defer></script>
</body>
</html>
