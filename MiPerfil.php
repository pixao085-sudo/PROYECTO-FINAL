<?php
require_once __DIR__.'/includes/modules.php';roles_required(['aspirante']);
$m=module_get('aspirantes');$id=linked_id('aspirante');$error='';$values=[];
try{
    if($id){$st=run_sql('SELECT * FROM Aspirante WHERE id_aspirante=?','i',[$id]);$values=$st->get_result()->fetch_assoc()?:[];$st->close();}
    if($_SERVER['REQUEST_METHOD']==='POST'){
        csrf_check();
        try{
            $values=form_values($m,$_POST);
            $names=array_keys($m['fields']);$params=[];foreach($names as $n)$params[]=$values[$n];
            if($id){
                $sql='UPDATE Aspirante SET '.implode(',',array_map(function($f){return '`'.$f.'`=?';},$names)).' WHERE id_aspirante=?';
                $params[]=$id;$st=run_sql($sql,values_types($m).'i',$params);$st->close();
            }else{
                $database=db();$database->begin_transaction();
                try{
                    $sql='INSERT INTO Aspirante ('.implode(',',$names).') VALUES ('.implode(',',array_fill(0,count($names),'?')).')';
                    $st=run_sql($sql,values_types($m),$params);$st->close();$id=$database->insert_id;
                    $st=run_sql('UPDATE Usuarios SET id_aspirante=? WHERE ID=? AND id_aspirante IS NULL','ii',[$id,user_id()]);
                    if($st->affected_rows!==1)throw new RuntimeException('No se pudo vincular el perfil.');$st->close();$database->commit();
                }catch(Throwable $ex){$database->rollback();throw $ex;}
            }
            flash_set('ok','Tu perfil se guardó correctamente.');go('MiPerfil.php');
        }catch(InvalidArgumentException $ex){$error=$ex->getMessage();}catch(Throwable $ex){$error=fail_message($ex);}
    }
}catch(Throwable $ex){$error=fail_message($ex);}
ui_head('Mi perfil de aspirante','Esta información es privada y te identifica en las postulaciones.','bi-person-vcard');
if($error)echo '<div class="alert alert-error">'.e($error).'</div>';
if(!$id)echo '<div class="alert alert-info">Completa tu perfil antes de postularte a una oferta.</div>';
echo '<section class="surface"><div class="surface-heading"><span class="section-icon"><i class="bi bi-person"></i></span><div><h2>Información personal y académica</h2><p>Solamente tú y el administrador pueden gestionar estos datos.</p></div></div><form method="post" class="form-grid">'.csrf_field();
foreach($m['fields'] as $field=>$def)echo field_html($field,$def,$values[$field]??'');
echo '<div class="form-actions"><button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Guardar mi perfil</button></div></form></section>';ui_foot();
