<?php
require_once __DIR__ . '/includes/modules.php';
roles_required(['admin','empresa']);
$key=(string)($_GET['modulo']??'aspirantes');
if(role()==='empresa') $key='ofertas';
$m=module_get($key);
$idEmpresa=role()==='empresa'?linked_id('empresa'):0;
if(role()==='empresa'&&$idEmpresa===0)go('MiEmpresa.php');
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    if($key==='postulaciones'){http_response_code(403);ui_simple_error('Acción no permitida','Las postulaciones no se eliminan desde el CRUD: se conserva el historial.');exit;}
    try{
        $id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT);
        if(!$id) throw new InvalidArgumentException('Registro no válido.');
        $sql='DELETE FROM '.$m['table'].' WHERE '.$m['pk'].'=?'; $types='i';$params=[$id];
        if(role()==='empresa'){ $sql.=' AND id_empresa=?';$types.='i';$params[]=$idEmpresa; }
        $st=run_sql($sql,$types,$params);
        if($st->affected_rows!==1) throw new InvalidArgumentException('No se encontró el registro o no tienes permiso para eliminarlo.');
        $st->close();flash_set('ok','Registro eliminado correctamente.');go('GestionarRegistros.php?modulo='.urlencode($key));
    }catch(InvalidArgumentException $ex){$error=$ex->getMessage();}
    catch(Throwable $ex){$error=fail_message($ex).' Si tiene registros relacionados, no podrá eliminarse hasta resolver esas relaciones.';}
}
$records=[];
try{
    if(role()==='empresa'){
        $st=run_sql('SELECT * FROM OfertaLaboral WHERE id_empresa=? ORDER BY id_oferta DESC LIMIT 100','i',[$idEmpresa]);
        $records=$st->get_result()->fetch_all(MYSQLI_ASSOC);$st->close();
    }else $records=query_all('SELECT * FROM '.$m['table'].' ORDER BY '.$m['pk'].' DESC LIMIT 100');
}catch(Throwable $ex){$error=fail_message($ex);}
ui_head(role()==='admin'?'Administrar registros':'Mis ofertas laborales','Consulta los registros y utiliza las acciones de edición y eliminación según tus permisos.','bi-database-gear');
if($error)echo '<div class="alert alert-error">'.e($error).'</div>';
echo '<section class="surface"><div class="surface-heading"><span class="section-icon"><i class="bi bi-table"></i></span><div><h2>'.e($m['title']).'</h2><p>Los registros se consultan aquí, separados de las pantallas de guardado. Máximo 100 recientes.</p></div></div>';
if(role()==='admin'){
 echo '<div class="row-actions" style="margin-bottom:20px">';foreach(module_defs() as $k=>$other) echo '<a class="btn '.($k===$key?'btn-primary':'btn-secondary').'" href="GestionarRegistros.php?modulo='.e($k).'">'.e(ucfirst($k)).'</a>';echo '</div>';
}
echo '<p><a class="btn btn-primary" href="'.e($m['file']).'"><i class="bi bi-plus-circle"></i> Crear nuevo registro</a></p>';
if(!$records)echo '<div class="empty">No hay registros para mostrar.</div>';
else {
 echo '<div class="table-wrap"><table><thead><tr>';
 $cols=array_keys($m['fields']);echo '<th>ID</th>';
 foreach($cols as $f)echo '<th>'.e($m['fields'][$f][0]).'</th>';
 echo '<th>Acciones</th></tr></thead><tbody>';
 foreach($records as $row){
   $id=(int)$row[$m['pk']];echo '<tr><td>'.e($id).'</td>';
   foreach($cols as $f){$v=(string)($row[$f]??'');if(utf_len($v)>75)$v=utf_cut($v,75).'…';echo '<td>'.e($v).'</td>';}
   echo '<td><div class="row-actions"><a class="btn btn-secondary" href="EditarRegistro.php?modulo='.e($key).'&id='.$id.'"><i class="bi bi-pencil"></i> Editar</a>';
   if($key!=='postulaciones') echo '<form method="post" action="GestionarRegistros.php?modulo='.e($key).'" onsubmit="return confirm(\'¿Eliminar este registro? Esta acción no se puede deshacer.\')">'.csrf_field().'<input type="hidden" name="id" value="'.$id.'"><button class="btn btn-danger" type="submit"><i class="bi bi-trash"></i> Eliminar</button></form>';
   echo '</div></td></tr>';
 }
 echo '</tbody></table></div>';
}
echo '</section>';ui_foot();
