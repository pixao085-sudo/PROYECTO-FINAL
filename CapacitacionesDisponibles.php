<?php
require_once __DIR__.'/includes/modules.php';roles_required(['aspirante']);
$error='';$rows=[];$inscripciones=[];$aspirante=0;$detalle=null;
try{
 $aspirante=linked_id('aspirante');
 if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check();
  try{
   if(!$aspirante)throw new InvalidArgumentException('Primero completa tu perfil de aspirante.');
   $id=filter_input(INPUT_POST,'id_capacitacion',FILTER_VALIDATE_INT);
   if(!$id)throw new InvalidArgumentException('Selecciona una capacitación válida.');
   $st=run_sql('SELECT id_capacitacion FROM Capacitacion WHERE id_capacitacion=?','i',[$id]);
   $existe=(bool)$st->get_result()->fetch_assoc();$st->close();
   if(!$existe)throw new InvalidArgumentException('La capacitación ya no existe.');
   $st=run_sql('SELECT id_aspirante FROM AspiranteCapacitacion WHERE id_aspirante=? AND id_capacitacion=? LIMIT 1','ii',[$aspirante,$id]);
   $repetida=(bool)$st->get_result()->fetch_assoc();$st->close();
   if($repetida)throw new InvalidArgumentException('Ya te inscribiste en esta capacitación.');
   $fecha=date('Y-m-d');
   $st=run_sql('INSERT INTO AspiranteCapacitacion (id_aspirante,id_capacitacion,fecha_inscripcion) VALUES (?,?,?)','iis',[$aspirante,$id,$fecha]);$st->close();
   flash_set('ok','Inscripción registrada correctamente. Puedes verla en Mis capacitaciones.');
   go('CapacitacionesDisponibles.php');
  }catch(InvalidArgumentException $ex){$error=$ex->getMessage();}
  catch(Throwable $ex){$error=fail_message($ex);}
 }
 $rows=query_all('SELECT id_capacitacion,nombre_curso,descripcion,duracion,modalidad,certificacion FROM Capacitacion ORDER BY id_capacitacion DESC LIMIT 100');
 if($aspirante){$st=run_sql('SELECT id_capacitacion FROM AspiranteCapacitacion WHERE id_aspirante=?','i',[$aspirante]);foreach($st->get_result()->fetch_all(MYSQLI_ASSOC) as $v)$inscripciones[(int)$v['id_capacitacion']]=true;$st->close();}
 $ver=filter_input(INPUT_GET,'ver',FILTER_VALIDATE_INT);
 if($ver)foreach($rows as $curso)if((int)$curso['id_capacitacion']===$ver){$detalle=$curso;break;}
}catch(Throwable $ex){$error=fail_message($ex);}
ui_head('Capacitaciones disponibles','Consulta detalles y solicita tu inscripción en los cursos disponibles.','bi-book');
if($error)echo '<div class="alert alert-error">'.e($error).'</div>';
if(!$aspirante)echo '<div class="alert alert-info">Primero completa <a href="MiPerfil.php">tu perfil</a> para inscribirte.</div>';
if($detalle){
 echo '<section class="surface"><div class="surface-heading"><span class="section-icon"><i class="bi bi-journal-text"></i></span><div><h2>'.e($detalle['nombre_curso']).'</h2><p>Detalles de la capacitación</p></div></div>';
 echo '<p><strong>Descripción:</strong></p><p>'.nl2br(e($detalle['descripcion']?:'La institución todavía no ha publicado una descripción para este curso.')).'</p>';
 echo '<p><strong>Duración:</strong> '.e($detalle['duracion']).' · <strong>Modalidad:</strong> '.e($detalle['modalidad']).' · <strong>Certificación:</strong> '.e($detalle['certificacion']).'</p>';
 if(isset($inscripciones[(int)$detalle['id_capacitacion']]))echo '<span class="tag"><i class="bi bi-check-circle"></i> Ya inscrito</span>';
 elseif($aspirante)echo '<form method="post" onsubmit="return confirm(\'¿Confirmar la inscripción en esta capacitación?\')">'.csrf_field().'<input type="hidden" name="id_capacitacion" value="'.(int)$detalle['id_capacitacion'].'"><button class="btn btn-primary"><i class="bi bi-bookmark-check"></i> Inscribirme</button></form>';
 echo '<p><a class="btn btn-secondary" href="CapacitacionesDisponibles.php"><i class="bi bi-arrow-left"></i> Volver al catálogo</a></p></section>';
}
echo '<section class="surface"><div class="surface-heading"><span class="section-icon"><i class="bi bi-book"></i></span><div><h2>Catálogo de cursos</h2><p>Solo muestra información académica pública; tu inscripción es privada.</p></div></div>';
if(!$rows)echo '<div class="empty">Todavía no hay capacitaciones registradas.</div>';
else{echo '<div class="card-grid">';foreach($rows as $r){
 $id=(int)$r['id_capacitacion'];
 echo '<article class="opportunity"><span class="tag">'.e($r['modalidad']).'</span><h3>'.e($r['nombre_curso']).'</h3><p>'.e(utf_cut((string)($r['descripcion']?:'Consultar información y opciones de inscripción.'),140)).'</p><p><i class="bi bi-clock"></i> '.e($r['duracion']).' · Certificación: '.e($r['certificacion']).'</p><div class="spacer"></div><div class="row-actions"><a class="btn btn-secondary" href="CapacitacionesDisponibles.php?ver='.$id.'"><i class="bi bi-eye"></i> Ver detalles</a>';
 if(isset($inscripciones[$id]))echo '<span class="tag">Inscrito</span>';
 elseif($aspirante)echo '<form method="post" onsubmit="return confirm(\'¿Confirmar inscripción en este curso?\')">'.csrf_field().'<input type="hidden" name="id_capacitacion" value="'.$id.'"><button class="btn btn-primary" type="submit"><i class="bi bi-bookmark-check"></i> Inscribirme</button></form>';
 echo '</div></article>';
}echo '</div>';}
echo '</section>';ui_foot();
