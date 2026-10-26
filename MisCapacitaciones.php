<?php
require_once __DIR__.'/includes/modules.php';roles_required(['aspirante']);
$error='';$rows=[];$id=0;
try{
 $id=linked_id('aspirante');
 if($id){$st=run_sql('SELECT c.nombre_curso,c.duracion,c.modalidad,ac.fecha_inscripcion FROM AspiranteCapacitacion ac INNER JOIN Capacitacion c ON c.id_capacitacion=ac.id_capacitacion WHERE ac.id_aspirante=? ORDER BY ac.fecha_inscripcion DESC LIMIT 100','i',[$id]);$rows=$st->get_result()->fetch_all(MYSQLI_ASSOC);$st->close();}
}catch(Throwable $ex){$error=fail_message($ex);}
ui_head('Mis capacitaciones','Consulta únicamente tus propias inscripciones.','bi-journal-check');
if($error)echo '<div class="alert alert-error">'.e($error).'</div>';
echo '<section class="surface"><h2>Historial de inscripciones</h2>';
if(!$rows)echo '<div class="empty">Todavía no tienes inscripciones registradas.</div>';
else{echo '<div class="table-wrap"><table><thead><tr><th>Curso</th><th>Duración</th><th>Modalidad</th><th>Inscripción</th></tr></thead><tbody>';foreach($rows as $r)echo '<tr><td>'.e($r['nombre_curso']).'</td><td>'.e($r['duracion']).'</td><td>'.e($r['modalidad']).'</td><td>'.e($r['fecha_inscripcion']).'</td></tr>';echo '</tbody></table></div>';}
echo '<p><a class="btn btn-secondary" href="CapacitacionesDisponibles.php"><i class="bi bi-arrow-left"></i> Consultar más capacitaciones</a></p></section>';ui_foot();
