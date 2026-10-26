<?php
require_once __DIR__ . '/includes/modules.php';
roles_required(['admin','empresa']);
$key=(string)($_GET['modulo']??'');
if(role()==='empresa')$key='ofertas';
$m=module_get($key);
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if(!$id){http_response_code(400);ui_simple_error('Registro no válido','No se indicó el registro.');exit;}
$idEmpresa=role()==='empresa'?linked_id('empresa'):0;
if(role()==='empresa'&&$idEmpresa===0)go('MiEmpresa.php');
$error='';$values=[];
try{
    $sql='SELECT * FROM '.$m['table'].' WHERE '.$m['pk'].'=?';$params=[$id];$types='i';
    if(role()==='empresa'){$sql.=' AND id_empresa=?';$params[]=$idEmpresa;$types.='i';}
    $st=run_sql($sql,$types,$params);$row=$st->get_result()->fetch_assoc();$st->close();
    if(!$row){http_response_code(404);ui_simple_error('Registro no encontrado','No tienes acceso a este registro.');exit;}
    $values=$row;
    if($_SERVER['REQUEST_METHOD']==='POST'){
        csrf_check();
        try{
            $source=$_POST; if(role()==='empresa') $source['id_empresa']=(string)$idEmpresa;
            $values=form_values($m,$source,true);
            if(role()==='empresa')$values['id_empresa']=(string)$idEmpresa;
            verify_relations($key,$values,(int)$id);
            $names=array_keys($m['fields']);$sql='UPDATE '.$m['table'].' SET '.implode(',',array_map(function($field){return '`'.$field.'`=?';},$names)).' WHERE '.$m['pk'].'=?';
            $params=[];foreach($names as $n)$params[]=$values[$n];$params[]=(int)$id;
            $types2=values_types($m).'i';
            if(role()==='empresa'){$sql.=' AND id_empresa=?';$types2.='i';$params[]=$idEmpresa;}
            $st=run_sql($sql,$types2,$params);$st->close();
            flash_set('ok','Registro actualizado correctamente.');go('GestionarRegistros.php?modulo='.urlencode($key));
        }catch(InvalidArgumentException $ex){$error=$ex->getMessage();}
        catch(Throwable $ex){$error=fail_message($ex);}
    }
}catch(Throwable $ex){$error=fail_message($ex);}
ui_head('Editar registro','Modifica únicamente la información autorizada.','bi-pencil-square');
if($error)echo '<div class="alert alert-error">'.e($error).'</div>';
echo '<section class="surface"><div class="surface-heading"><span class="section-icon"><i class="bi bi-pencil"></i></span><div><h2>'.e($m['title']).' · #'.(int)$id.'</h2><p>Guarda los cambios para actualizar el registro existente.</p></div></div>';
echo '<form class="form-grid" method="post">'.csrf_field();
foreach($m['fields'] as $name=>$def){if(role()==='empresa'&&$name==='id_empresa')continue;echo field_html($name,$def,$values[$name]??'');}
echo '<div class="form-actions"><button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle"></i> Guardar cambios</button><a class="btn btn-muted" href="GestionarRegistros.php?modulo='.e($key).'">Cancelar</a></div></form></section>';ui_foot();
