
<?php
require_once __DIR__ . '/includes/modules.php';

roles_required(['aspirante']);

$error = '';
$rows = [];
$idAspirante = 0;

try {
    $idAspirante = linked_id('aspirante');

    if ($idAspirante > 0) {
        $sql = "
            SELECT
                p.fecha,
                p.estado,
                o.cargo,
                e.nombre AS empresa
            FROM Postulacion p
            INNER JOIN OfertaLaboral o
                ON o.id_oferta = p.id_oferta
            INNER JOIN Empresa e
                ON e.id_empresa = o.id_empresa
            WHERE p.id_aspirante = ?
            ORDER BY p.id_postulacion DESC
        ";

        $stmt = run_sql(
            $sql,
            'i',
            [$idAspirante]
        );

        $rows = $stmt->get_result()
                     ->fetch_all(MYSQLI_ASSOC);

        $stmt->close();
    }

} catch (Throwable $ex) {
    $error = fail_message($ex);
}

ui_head(
    'Mis postulaciones',
    'Consulta tus solicitudes laborales y su estado actual.',
    'bi-file-earmark-text'
);

if ($error !== '') {
    echo '<div class="alert alert-error">'
       . e($error)
       . '</div>';
}

if (!$idAspirante) {
    echo '<div class="alert alert-info">
        Debes completar tu perfil de aspirante.
        <a href="MiPerfil.php">Completar perfil</a>
    </div>';
}

echo '
<section class="surface">
    <div class="surface-heading">
        <span class="section-icon">
            <i class="bi bi-clipboard-check"></i>
        </span>
        <div>
            <h2>Seguimiento de postulaciones</h2>
            <p>Consulta las ofertas a las que te has postulado.</p>
        </div>
    </div>
';

if (empty($rows)) {

    echo '
    <div class="empty">
        Todavía no tienes postulaciones registradas.
    </div>';

} else {

    echo '
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Puesto laboral</th>
                    <th>Empresa</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
    ';

    foreach ($rows as $fila) {

        echo '<tr>';

        echo '<td>' . e($fila['cargo']) . '</td>';

        echo '<td>' . e($fila['empresa']) . '</td>';

        echo '<td>' . e($fila['fecha']) . '</td>';

        echo '<td>
                <span class="tag">'
                . e($fila['estado'])
                . '</span>
              </td>';

        echo '</tr>';
    }

    echo '
            </tbody>
        </table>
    </div>';
}

echo '</section>';

echo '
<div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:20px;">

    <a href="dashboard.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left-circle"></i>
        Regresar al dashboard
    </a>

    <a href="OfertasDisponibles.php" class="btn btn-primary">
        <i class="bi bi-briefcase"></i>
        Ver ofertas disponibles
    </a>

</div>
';

ui_foot();
