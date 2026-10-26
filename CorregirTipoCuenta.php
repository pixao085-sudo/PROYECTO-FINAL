<?php
require_once __DIR__.'/includes/modules.php';roles_required(['admin']);
$error='';$rows=[];
try{
 if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check();
  try{
   $id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT);
   $rol=(string)($_POST['rol']??'');
   if(!$id || !in_array($rol,['Empresa','Aspirante'],true))throw new InvalidArgumentException('Tipo de cuenta no válido.');
   $st=run_sql('SELECT rol,id_aspirante,id_empresa FROM Usuarios WHERE id=? LIMIT 1','i',[$id]);
   $actual=$st->get_result()->fetch_assoc();$st->close();
   if(!$actual || strtolower((string)$actual['rol'])==='admin')throw new InvalidArgumentException('No puedes modificar una cuenta administrativa.');
   if((int)($actual['id_aspirante']??0)!==0 || (int)($actual['id_empresa']??0)!==0)throw new InvalidArgumentException('Esta cuenta ya tiene un perfil vinculado. Para evitar perder el historial, revisa la asociación antes de cambiar el rol.');
   $st=run_sql("UPDATE Usuarios SET rol=? WHERE id=? AND rol IN ('Aspirante','Empresa') AND id_aspirante IS NULL AND id_empresa IS NULL",'si',[$rol,$id]);
   $st->close();flash_set('ok','Tipo de cuenta actualizado. La persona debe cerrar sesión y volver a entrar.');go('CorregirTipoCuenta.php');
  }catch(InvalidArgumentException $ex){$error=$ex->getMessage();}catch(Throwable $ex){$error=fail_message($ex);}
 }
 $st=run_sql("SELECT id,usuario,Email,rol,id_aspirante,id_empresa FROM Usuarios WHERE rol IN ('Aspirante','Empresa') ORDER BY id DESC LIMIT 100");
 $rows=$st->get_result()->fetch_all(MYSQLI_ASSOC);$st->close();
}catch(Throwable $ex){$error=fail_message($ex);}
ui_head('Corregir tipo de cuenta','Arregla cuentas registradas con el rol equivocado sin convertirlas en administradores.','bi-person-gear');
if($error)echo '<div class="alert alert-error">'.e($error).'</div>';
echo '<div class="alert alert-info">Utiliza esta opción solo si una cuenta se creó como Aspirante por error y realmente pertenece a una Empresa (o viceversa). Se bloquean cambios en cuentas con perfiles vinculados para proteger sus relaciones.</div>';
echo '<section class="surface"><h2>Usuarios registrados</h2>';
if(!$rows)echo '<div class="empty">No hay cuentas disponibles.</div>';
else{echo '<div class="table-wrap"><table><thead><tr><th>Usuario</th><th>Correo</th><th>Rol actual</th><th>Corregir</th></tr></thead><tbody>';
foreach($rows as $r){$linked=(int)$r['id_aspirante']>0||(int)$r['id_empresa']>0;
 echo '<tr><td>'.e($r['usuario']).'</td><td>'.e($r['Email']).'</td><td>'.e($r['rol']).'</td><td>';
 if($linked)echo '<span class="tag">Perfil vinculado: requiere revisión</span>';
 else{echo '<form method="post" class="row-actions" onsubmit="return confirm(\'¿Cambiar el tipo de cuenta de este usuario?\')">'.csrf_field().'<input type="hidden" name="id" value="'.(int)$r['id'].'"><select name="rol" aria-label="Tipo de cuenta">';foreach(['Aspirante','Empresa'] as $op)echo '<option value="'.$op.'" '.($r['rol']===$op?'selected':'').'>'.$op.'</option>';echo '</select><button class="btn btn-secondary" type="submit">Guardar</button></form>';}
 echo '</td></tr>';}
echo '</tbody></table></div>';}
echo '</section>';ui_foot();
