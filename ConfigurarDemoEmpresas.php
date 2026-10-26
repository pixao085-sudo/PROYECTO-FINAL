<?php
require_once __DIR__.'/includes/modules.php';
roles_required(['admin']);
$error='';$credentials=[];$results=[];
// Solo IDs 1 y 2 ya existentes según las capturas; NO crea nuevas empresas.
$ids=[1,2];
try{
 if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check();
  try{
   if(($_POST['confirmar']??'')!=='SI')throw new InvalidArgumentException('Confirma la operación antes de continuar.');
   foreach($ids as $id){
    $st=run_sql('SELECT id_empresa,nombre FROM Empresa WHERE id_empresa=?','i',[$id]);
    $empresa=$st->get_result()->fetch_assoc();$st->close();
    if(!$empresa){$results[]="No existe la empresa con ID $id.";continue;}
    $st=run_sql('SELECT id FROM Usuarios WHERE id_empresa=? OR usuario=? LIMIT 1','is',[$id,'demo_empresa_'.$id]);
    $existe=$st->get_result()->fetch_assoc();$st->close();
    if($existe){$results[]='La empresa #'.$id.' ya tiene una cuenta vinculada o se usó ese nombre.';continue;}
    $user='demo_empresa_'.$id;$email='demo_empresa_'.$id.'@example.test';
    $plain=rtrim(strtr(base64_encode(random_bytes(15)),'+/','-_'),'=');
    $hash=password_hash($plain,PASSWORD_DEFAULT);
    $db=db();$db->begin_transaction();
    try{
     $st=run_sql("INSERT INTO Usuarios (usuario,Email,clave,rol,estado_cuenta,id_empresa) VALUES (?,?,?,'Empresa','aprobada',?)",'sssi',[$user,$email,$hash,$id]);$st->close();
     $db->commit();$credentials[]=['empresa'=>$empresa['nombre'],'usuario'=>$user,'clave'=>$plain];
    }catch(Throwable $ex){$db->rollback();throw $ex;}
   }
  }catch(InvalidArgumentException $ex){$error=$ex->getMessage();}
  catch(Throwable $ex){$error=fail_message($ex);}
 }
}catch(Throwable $ex){$error=fail_message($ex);}
ui_head('Empresas de prueba','Crear un máximo de dos accesos para empresas existentes.','bi-building-check');
if($error)echo '<div class="alert alert-error">'.e($error).'</div>';
echo '<section class="surface"><div class="surface-heading"><span class="section-icon"><i class="bi bi-building"></i></span><div><h2>Accesos de demostración</h2><p>Empresa ID 1 y 2; si ya existe un acceso vinculado, no se duplica.</p></div></div>';
echo '<p>Este proceso no modifica los datos de Empresa ni las vacantes publicadas. Solo añade usuarios del rol Empresa asociados a esos perfiles, para probar postulaciones recibidas y gestión de vacantes.</p>';
if($credentials){echo '<div class="alert alert-info"><strong>Guarda estas claves ahora:</strong> se muestran una sola vez. No quedan almacenadas en texto legible.</div><div class="table-wrap"><table><thead><tr><th>Empresa</th><th>Usuario</th><th>Clave temporal</th></tr></thead><tbody>';foreach($credentials as $c)echo '<tr><td>'.e($c['empresa']).'</td><td>'.e($c['usuario']).'</td><td><code>'.e($c['clave']).'</code></td></tr>';echo '</tbody></table></div>';}
foreach($results as $msg)echo '<div class="alert alert-info">'.e($msg).'</div>';
echo '<form method="post" onsubmit="return confirm(\'¿Crear cuentas de demostración para las empresas 1 y 2?\')">'.csrf_field().'<input type="hidden" name="confirmar" value="SI"><button class="btn btn-primary" type="submit"><i class="bi bi-person-plus"></i> Crear hasta dos cuentas de prueba</button></form></section>';ui_foot();
