<?php
require_once __DIR__.'/includes/modules.php';roles_required(['aspirante']);
$error='';$aspirante=0;$ofertas=[];
try{
    $aspirante=linked_id('aspirante');
    if($_SERVER['REQUEST_METHOD']==='POST'){
        csrf_check();
        try{
            if(!$aspirante)throw new InvalidArgumentException('Primero completa tu perfil de aspirante.');
            $oferta=filter_input(INPUT_POST,'id_oferta',FILTER_VALIDATE_INT);
            if(!$oferta)throw new InvalidArgumentException('Selecciona una oferta válida.');
            $st=run_sql('SELECT id_oferta FROM OfertaLaboral WHERE id_oferta=? AND estado="Activa"','i',[$oferta]);
            if(!$st->get_result()->fetch_assoc())throw new InvalidArgumentException('La oferta ya no está disponible.');$st->close();
            $st=run_sql('SELECT id_postulacion FROM Postulacion WHERE id_aspirante=? AND id_oferta=?','ii',[$aspirante,$oferta]);
            if($st->get_result()->fetch_assoc())throw new InvalidArgumentException('Ya te has postulado a esta oferta.');$st->close();
            $fecha=date('Y-m-d');$estado='Pendiente';
            $st=run_sql('INSERT INTO Postulacion (id_aspirante,id_oferta,fecha,estado) VALUES (?,?,?,?)','iiss',[$aspirante,$oferta,$fecha,$estado]);$st->close();
            flash_set('ok','Postulación enviada correctamente. Consulta su estado en Mis postulaciones.');go('OfertasDisponibles.php');
        }catch(InvalidArgumentException $ex){$error=$ex->getMessage();}
        catch(Throwable $ex){$error=fail_message($ex);}
    }
    $ofertas=query_all('SELECT o.id_oferta,o.cargo,o.descripcion,o.requisitos,o.salario,o.fecha_publicacion,e.nombre AS empresa FROM OfertaLaboral o INNER JOIN Empresa e ON e.id_empresa=o.id_empresa WHERE o.estado="Activa" ORDER BY o.fecha_publicacion DESC,o.id_oferta DESC LIMIT 100');
}catch(Throwable $ex){$error=fail_message($ex);}
ui_head('Ofertas laborales','Explora oportunidades activas. Puedes postularte cuando tu perfil esté completo.','bi-person');
if($error)echo '<div class="alert alert-error">'.e($error).'</div>';
if(!$aspirante)echo '<div class="alert alert-info">Para enviar solicitudes, primero <a href="MiPerfil.php">completa tu perfil</a>.</div>';
echo '<section class="surface"><div class="surface-heading"><span class="section-icon"><i class="bi bi-briefcase"></i></span><div><h2>Vacantes disponibles</h2><p>La información visible aquí es pública; tus postulaciones son privadas.</p></div></div>';
if(!$ofertas)echo '<div class="empty">Actualmente no hay ofertas activas.</div>';
else{echo '<div class="card-grid">';foreach($ofertas as $o){
 echo '<article class="opportunity"><span class="tag"><i class="bi bi-circle-fill" style="font-size:6px;margin-right:6px"></i>Activa</span><h3>'.e($o['cargo']).'</h3><p><i class="bi bi-building"></i> '.e($o['empresa']).'</p><p>'.nl2br(e(utf_cut((string)$o['descripcion'],220))).'</p><p><b>Requisitos:</b> '.nl2br(e(utf_cut((string)$o['requisitos'],160))).'</p><p><b>Salario:</b> USD '.e(number_format((float)$o['salario'],2)).' · <b>Publicada:</b> '.e($o['fecha_publicacion']).'</p><div class="spacer"></div>';
 if($aspirante)echo '<form method="post">'.csrf_field().'<input type="hidden" name="id_oferta" value="'.(int)$o['id_oferta'].'"><button class="btn btn-primary" type="submit"><i class="bi bi-send"></i> Postularme</button></form>';
 else echo '<a class="btn btn-secondary" href="MiPerfil.php">Completar mi perfil</a>';
 echo '</article>';}
 echo '</div>';}
echo '</section>';ui_foot();
