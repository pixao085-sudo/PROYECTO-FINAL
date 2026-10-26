<?php
// Usuarios: id, usuario, Email, clave, rol (ENUM Admin/Aspirante/Empresa),
// id_aspirante, id_empresa y estado_cuenta tras la migración V3.
require_once __DIR__.'/includes/app.php';
if ($_SERVER['REQUEST_METHOD']!=='POST') go('login.php');
csrf_check();
$accion=(string)($_POST['accion']??'entrar');
try {
    if ($accion==='registrar') {
        // Campo invisible antirrobots; no sustituye la verificación de identidad.
        if (trim((string)($_POST['sitio_web']??''))!=='') {
            error_log('[INAC REGISTRO] Campo de control antirrobots completado.');
            throw new InvalidArgumentException('No fue posible completar la solicitud. Recarga el formulario e inténtalo nuevamente.');
        }
        if (time()-(int)($_SESSION['ultimo_registro']??0)<60) throw new InvalidArgumentException('Espera un minuto antes de volver a registrarte.');
        $usuario=trim((string)($_POST['nuevoUsuario']??''));
        $email=trim((string)($_POST['nuevoEmail']??''));
        $clave=(string)($_POST['nuevoClave']??'');
        $confirmar=(string)($_POST['confirmarClave']??'');
        $rol=(string)($_POST['tipo_cuenta']??'');
        if(!in_array($rol,['aspirante','empresa'],true)) throw new InvalidArgumentException('Selecciona Aspirante o Empresa.');
        if(!preg_match('/^[\p{L}\p{N}_.-]{3,50}$/u',$usuario)) throw new InvalidArgumentException('El usuario necesita de 3 a 50 caracteres válidos.');
        if(!filter_var($email,FILTER_VALIDATE_EMAIL) || utf_len($email)>150) throw new InvalidArgumentException('Escribe un correo electrónico válido.');
        if(strlen($clave)<10 || !preg_match('/[a-z]/',$clave) || !preg_match('/[A-Z]/',$clave) || !preg_match('/[0-9]/',$clave)) throw new InvalidArgumentException('La contraseña necesita 10 caracteres como mínimo, mayúscula, minúscula y número.');
        if(!hash_equals($clave,$confirmar)) throw new InvalidArgumentException('Las contraseñas no coinciden.');
        if(empty($_POST['acepto'])) throw new InvalidArgumentException('Confirma que los datos proporcionados son correctos.');
        $st=run_sql('SELECT id FROM Usuarios WHERE usuario=? OR Email=? LIMIT 1','ss',[$usuario,$email]);
        $existe=(bool)$st->get_result()->fetch_assoc();$st->close();
        if($existe) throw new InvalidArgumentException('Ya existe una cuenta con ese usuario o correo.');
        $hash=password_hash($clave,PASSWORD_DEFAULT);
        $rolDB=$rol==='empresa'?'Empresa':'Aspirante';
        $st=run_sql("INSERT INTO Usuarios (usuario,Email,clave,rol,estado_cuenta) VALUES (?,?,?,?,'pendiente')",'ssss',[$usuario,$email,$hash,$rolDB]);
        if ($st->affected_rows !== 1) throw new RuntimeException('La inserción de la cuenta no se completó.');
        $st->close();$_SESSION['ultimo_registro']=time();
        flash_set('ok','Solicitud recibida. Un administrador debe aprobar tu cuenta antes de que puedas entrar.');
        go('login.php');
    }
    if ($accion!=='entrar') throw new InvalidArgumentException('Acción no permitida.');
    $usuario=trim((string)($_POST['usuario']??''));$clave=(string)($_POST['clave']??'');
    if($usuario===''||$clave==='') throw new InvalidArgumentException('Introduce tu usuario y contraseña.');
    $fallos=(int)($_SESSION['fallos_login']??0);
    $inicio=(int)($_SESSION['inicio_fallos']??time());
    if(time()-$inicio>600){$fallos=0;$_SESSION['fallos_login']=0;$_SESSION['inicio_fallos']=time();}
    if($fallos>=5) throw new InvalidArgumentException('Demasiados intentos. Espera 10 minutos y vuelve a probar.');
    $st=run_sql('SELECT id,usuario,clave,rol,estado_cuenta FROM Usuarios WHERE usuario=? LIMIT 1','s',[$usuario]);
    $row=$st->get_result()->fetch_assoc();$st->close();
    $valida=$row ? password_verify($clave,(string)$row['clave']) : false;
    $plano=false;
    // Compatibilidad de migración: contraseña antigua sin hash, una sola vez.
    if($row && !$valida && (password_get_info((string)$row['clave'])['algoName']??'unknown')==='unknown'){
        $plano=hash_equals((string)$row['clave'],$clave);$valida=$plano;
    }
    if(!$row || !$valida){
        if($fallos===0)$_SESSION['inicio_fallos']=time();
        $_SESSION['fallos_login']=$fallos+1;
        throw new InvalidArgumentException('Usuario o contraseña incorrectos.');
    }
    $estado=(string)$row['estado_cuenta'];
    if($estado==='pendiente') throw new InvalidArgumentException('Tu cuenta está pendiente de aprobación del administrador.');
    if($estado!=='aprobada') throw new InvalidArgumentException('Tu cuenta no está habilitada. Contacta con el administrador.');
    $rol=strtolower(trim((string)$row['rol']));
    if(!in_array($rol,['admin','empresa','aspirante'],true))throw new InvalidArgumentException('El rol de esta cuenta no es válido.');
    if($plano){
        $hash=password_hash($clave,PASSWORD_DEFAULT);
        $st=run_sql('UPDATE Usuarios SET clave=? WHERE id=?','si',[$hash,(int)$row['id']]);$st->close();
    }
    session_regenerate_id(true);
    unset($_SESSION['csrf'],$_SESSION['fallos_login'],$_SESSION['inicio_fallos']);
    $_SESSION['id_usuario']=(int)$row['id'];
    $_SESSION['usuario']=(string)$row['usuario'];
    $_SESSION['rol']=$rol;
    $_SESSION['ultima_actividad']=time();
    go('dashboard.php');
} catch (InvalidArgumentException $ex) {
    flash_set('error',$ex->getMessage());
    go('login.php'.($accion==='registrar'?'?pestana=registro':''));
} catch (Throwable $ex) {
    error_log('[INAC NEXT] '.$ex->getMessage());
    flash_set('error','No se pudo completar la operación. Revisa la migración V3 y los nombres de columnas de Usuarios.');
    go('login.php'.($accion==='registrar'?'?pestana=registro':''));
}
