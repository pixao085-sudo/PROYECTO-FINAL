<?php
require_once __DIR__.'/includes/modules.php';roles_required(['empresa']);
$m=module_get('empresas');$id=linked_id('empresa');$error='';$values=[];
try{
    if($id){$st=run_sql('SELECT * FROM Empresa WHERE id_empresa=?','i',[$id]);$values=$st->get_result()->fetch_assoc()?:[];$st->close();}
    if($_SERVER['REQUEST_METHOD']==='POST'){
        csrf_check();
        try{
            $values=form_values($m,$_POST);$names=array_keys($m['fields']);$params=[];foreach($names as $n)$params[]=$values[$n];
            if($id){$sql='UPDATE Empresa SET '.implode(',',array_map(function($f){return '`'.$f.'`=?';},$names)).' WHERE id_empresa=?';$params[]=$id;$st=run_sql($sql,values_types($m).'i',$params);$st->close();}
            else{$database=db();$database->begin_transaction();try{
                $sql='INSERT INTO Empresa ('.implode(',',$names).') VALUES ('.implode(',',array_fill(0,count($names),'?')).')';
                $st=run_sql($sql,values_types($m),$params);$st->close();$id=$database->insert_id;
                $st=run_sql('UPDATE Usuarios SET id_empresa=? WHERE ID=? AND id_empresa IS NULL','ii',[$id,user_id()]);
                if($st->affected_rows!==1)throw new RuntimeException('No se pudo vincular la empresa.');$st->close();$database->commit();
            }catch(Throwable $ex){$database->rollback();throw $ex;}}
            flash_set('ok','Información de la empresa guardada correctamente.');go('MiEmpresa.php');
        }catch(InvalidArgumentException $ex){$error=$ex->getMessage();}catch(Throwable $ex){$error=fail_message($ex);}
    }
}catch(Throwable $ex){$error=fail_message($ex);}
ui_head('Mi empresa','Consulta o actualiza los datos de tu organización.','bi-building');
if($error)echo '<div class="alert alert-error">'.e($error).'</div>';
if(!$id)echo '<div class="alert alert-info">Completa los datos de tu empresa antes de publicar una oferta.</div>';
echo '<section class="surface"><div class="surface-heading"><span class="section-icon"><i class="bi bi-building-check"></i></span><div><h2>Datos de la empresa</h2><p>Solo podrás gestionar las ofertas vinculadas a este perfil.</p></div></div><form method="post" class="form-grid">'.csrf_field();
foreach($m['fields'] as $field=>$def)echo field_html($field,$def,$values[$field]??'');
echo '<div class="form-actions"><button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Guardar mi empresa</button></div></form></section>';ui_foot();
