<?php
require_once __DIR__.'/includes/modules.php';roles_required(['empresa']);
$error='';$rows=[];$idEmpresa=0;
try{
 $idEmpresa=linked_id('empresa');if(!$idEmpresa)go('MiEmpresa.php');
 if($_SERVER['REQUEST_METHOD']==='POST'){
     csrf_check();
     try{
         $id=filter_input(INPUT_POST,'id_postulacion',FILTER_VALIDATE_INT);$estado=(string)($_POST['estado']??'');
         if(!$id||!in_array($estado,['Pendiente','Aceptado','Rechazado'],true))throw new InvalidArgumentException('Datos de la postulación no válidos.');
         $st=run_sql('UPDATE Postulacion p INNER JOIN OfertaLaboral o ON o.id_oferta=p.id_oferta SET p.estado=? WHERE p.id_postulacion=? AND o.id_empresa=?','sii',[$estado,$id,$idEmpresa]);
         if(!$st->affected_rows){$check=run_sql('SELECT p.id_postulacion FROM Postulacion p INNER JOIN OfertaLaboral o ON o.id_oferta=p.id_oferta WHERE p.id_postulacion=? AND o.id_empresa=?','ii',[$id,$idEmpresa]);if(!$check->get_result()->fetch_assoc())throw new InvalidArgumentException('No existe la postulación o no corresponde a tu empresa.');$check->close();}
         $st->close();flash_set('ok','Estado actualizado correctamente.');go('PostulacionesEmpresa.php');
     }catch(InvalidArgumentException $ex){$error=$ex->getMessage();}catch(Throwable $ex){$error=fail_message($ex);}
 }
 $st=run_sql('SELECT p.id_postulacion,p.fecha,p.estado,a.nombre,a.apellido,a.correo,o.cargo FROM Postulacion p INNER JOIN OfertaLaboral o ON o.id_oferta=p.id_oferta INNER JOIN Aspirante a ON a.id_aspirante=p.id_aspirante WHERE o.id_empresa=? ORDER BY p.id_postulacion DESC LIMIT 100','i',[$idEmpresa]);
 $rows=$st->get_result()->fetch_all(MYSQLI_ASSOC);$st->close();
}catch(Throwable $ex){$error=fail_message($ex);}
ui_head('Postulaciones recibidas','Revisa únicamente las solicitudes enviadas a las ofertas de tu empresa.','bi-file-earmark-text');
if($error)echo '<div class="alert alert-error">'.e($error).'</div>';
echo '<section class="surface"><div class="surface-heading"><span class="section-icon"><i class="bi bi-people"></i></span><div><h2>Solicitudes de mis ofertas</h2><p>Solo las empresas propietarias y el administrador deben acceder a estos datos.</p></div></div>';
if(!$rows)echo '<div class="empty">Aún no hay postulaciones para tus ofertas.</div>';
else {echo '<div class="table-wrap"><table><thead><tr><th>Cargo</th><th>Aspirante</th><th>Correo</th><th>Fecha</th><th>Estado</th><th>Actualizar</th></tr></thead><tbody>';
foreach($rows as $r){echo '<tr><td>'.e($r['cargo']).'</td><td>'.e($r['nombre'].' '.$r['apellido']).'</td><td>'.e($r['correo']).'</td><td>'.e($r['fecha']).'</td><td><span class="tag">'.e($r['estado']).'</span></td><td><form method="post" class="row-actions">'.csrf_field().'<input type="hidden" name="id_postulacion" value="'.(int)$r['id_postulacion'].'"><select name="estado" aria-label="Actualizar estado">';
foreach(['Pendiente','Aceptado','Rechazado'] as $op)echo '<option value="'.e($op).'" '.($r['estado']===$op?'selected':'').'>'.e($op).'</option>';
echo '</select><button type="submit" class="btn btn-secondary"><i class="bi bi-check2"></i> Actualizar</button></form></td></tr>';}
echo '</tbody></table></div>';}
echo '</section>';ui_foot();
