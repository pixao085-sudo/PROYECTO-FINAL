<?php
require_once __DIR__ . '/modules.php';
$key=$moduleKey; $module=module_get($key);
roles_required($key==='ofertas' ? ['admin','empresa'] : ['admin']);
$companyId=role()==='empresa' ? linked_id('empresa') : 0;
if (role()==='empresa' && $companyId===0) { go('MiEmpresa.php'); }
$error=''; $values=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    try {
        $source=$_POST; if($key==='ofertas' && role()==='empresa') $source['id_empresa']=(string)$companyId;
        $values=form_values($module,$source);
        if ($key==='ofertas' && role()==='empresa') $values['id_empresa']=(string)$companyId;
        verify_relations($key,$values);
        $names=array_keys($module['fields']);
        $namesSql=implode(',',array_map(function($v){return '`'.$v.'`';},$names));
        $holders=implode(',',array_fill(0,count($names),'?'));
        $sql='INSERT INTO '.$module['table'].' ('.$namesSql.') VALUES ('.$holders.')';
        $params=[];foreach($names as $name)$params[]=$values[$name];
        $st=run_sql($sql, values_types($module), $params); $st->close();
        flash_set('ok','Registro guardado correctamente en la base de datos.');
        go($module['file']);
    } catch(InvalidArgumentException $ex){$error=$ex->getMessage();}
    catch(Throwable $ex){$error=fail_message($ex);}
}
ui_head($module['title'],'Completa los datos para crear un registro. Los detalles de la base de datos se consultan desde el panel de administración.',$module['icon']);
if($error!=='')echo '<div class="alert alert-error" role="alert">'.e($error).'</div>';
echo '<section class="surface"><div class="surface-heading"><span class="section-icon"><i class="bi bi-pencil-square"></i></span><div><h2>Nuevo registro</h2><p>Los campos marcados con * son obligatorios.</p></div></div>';
echo '<form class="form-grid" method="post" action="'.e($module['file']).'">'.csrf_field();
foreach ($module['fields'] as $field=>$def) {
    if ($key==='ofertas' && role()==='empresa' && $field==='id_empresa') continue;
    $value=$values[$field] ?? ($def[1]==='date'? date('Y-m-d') : ($field==='estado'?($key==='postulaciones'?'Pendiente':'Activa'):''));
    echo field_html($field,$def,$value);
}
echo '<div class="form-actions"><button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Guardar registro</button><button type="reset" class="btn btn-muted"><i class="bi bi-arrow-counterclockwise"></i> Limpiar campos</button></div></form></section>';
ui_foot();
