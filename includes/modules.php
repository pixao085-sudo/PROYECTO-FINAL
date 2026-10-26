<?php
require_once __DIR__ . '/app.php';
function module_defs(): array {
    return [
        'aspirantes' => ['table'=>'Aspirante','pk'=>'id_aspirante','title'=>'Registro de aspirantes','icon'=>'bi-person-vcard', 'file'=>'GuardarAspirante.php', 'fields'=>[
            'nombre'=>['Nombre','text',true,100], 'apellido'=>['Apellido','text',true,100], 'edad'=>['Edad','number',true,3],
            'correo'=>['Correo electrónico','email',true,150], 'telefono'=>['Teléfono','tel',true,20],
            'nivel_academico'=>['Nivel académico','select',true,['Educación básica','Bachillerato','Técnico','Universitario','Posgrado']],
            'competencias'=>['Competencias y habilidades','textarea',false,1000], 'experiencia_laboral'=>['Experiencia laboral','textarea',false,1500]
        ]],
        'empresas' => ['table'=>'Empresa','pk'=>'id_empresa','title'=>'Registro de empresas','icon'=>'bi-building','file'=>'GuardarEmpresa.php','fields'=>[
            'nombre'=>['Nombre de la empresa','text',true,150], 'sector'=>['Sector económico','select',true,['Tecnología','Comercio','Educación','Salud','Industria','Construcción','Servicios','Agricultura','Finanzas','Otro']],
            'direccion'=>['Dirección','textarea',true,255], 'contacto'=>['Persona de contacto','text',true,100], 'correo'=>['Correo electrónico','email',true,150], 'telefono'=>['Teléfono','tel',true,20]
        ]],
        'ofertas' => ['table'=>'OfertaLaboral','pk'=>'id_oferta','title'=>'Ofertas laborales','icon'=>'bi-person-workspace','file'=>'GuardarOferta.php','fields'=>[
            'id_empresa'=>['Empresa','ref_empresa',true,0], 'cargo'=>['Cargo o puesto','text',true,100],
            'descripcion'=>['Descripción del puesto','textarea',true,2000], 'requisitos'=>['Requisitos','textarea',true,2000],
            'salario'=>['Salario mensual (USD)','decimal',true,0], 'fecha_publicacion'=>['Fecha de publicación','date',true,0],
            'estado'=>['Estado de la oferta','select',true,['Activa','En revision','Cerrada']]
        ]],
        'postulaciones' => ['table'=>'Postulacion','pk'=>'id_postulacion','title'=>'Postulaciones laborales','icon'=>'bi-file-earmark-text','file'=>'GuardarPostulacion.php','fields'=>[
            'id_aspirante'=>['Aspirante','ref_aspirante',true,0], 'id_oferta'=>['Oferta laboral','ref_oferta',true,0],
            'fecha'=>['Fecha de postulación','date',true,0], 'estado'=>['Estado','select',true,['Pendiente','Aceptado','Rechazado']]
        ]],
        'capacitaciones' => ['table'=>'Capacitacion','pk'=>'id_capacitacion','title'=>'Capacitaciones','icon'=>'bi-book','file'=>'GuardarCapacitacion.php','fields'=>[
            'nombre_curso'=>['Nombre del curso','text',true,150], 'descripcion'=>['Descripción del curso','textarea',false,2000], 'duracion'=>['Duración','text',true,100],
            'modalidad'=>['Modalidad','select',true,['Presencial','Virtual','Híbrida']],
            'certificacion'=>['¿Ofrece certificación?','select',true,['Sí','No']]
        ]]
    ];
}
function module_get(string $key): array {
    $all = module_defs();
    if (!isset($all[$key])) { http_response_code(404); ui_simple_error('Módulo no encontrado','La sección solicitada no existe.'); exit; }
    return $all[$key];
}
function ref_options(string $type): array {
    if ($type==='ref_empresa') return query_all('SELECT id_empresa AS id, nombre AS label FROM Empresa ORDER BY nombre');
    if ($type==='ref_aspirante') return query_all('SELECT id_aspirante AS id, CONCAT(nombre, " ", apellido) AS label FROM Aspirante ORDER BY nombre');
    if ($type==='ref_oferta') return query_all('SELECT o.id_oferta AS id, CONCAT(o.cargo, " — ", e.nombre) AS label FROM OfertaLaboral o INNER JOIN Empresa e ON o.id_empresa=e.id_empresa WHERE o.estado="Activa" ORDER BY o.cargo');
    return [];
}
function field_html(string $name, array $def, $value='', bool $locked=false): string {
    [$label,$type,$required,$rule] = $def;
    $id='f_'. $name;
    $req=$required ? ' required' : '';
    $html='<div class="field"><label for="' . e($id) . '">' . e($label) . ($required?' <span class="required">*</span>':'') . '</label>';
    if ($type==='textarea') {
        $html.='<textarea id="'.e($id).'" name="'.e($name).'" rows="4" maxlength="'.(int)$rule.'"'.$req.'>'.e($value).'</textarea>';
    } elseif ($type==='select' || strpos($type,'ref_')===0) {
        $options=($type==='select') ? array_map(function($x){return ['id'=>$x,'label'=>$x];}, $rule) : ref_options($type);
        $html.='<select id="'.e($id).'" name="'.e($name).'"'.$req.'><option value="">Selecciona una opción</option>';
        foreach($options as $o){ $val=(string)$o['id']; $sel=(string)$value===$val?' selected':''; $html.='<option value="'.e($val).'"'.$sel.'>'.e($o['label']).'</option>'; }
        $html.='</select>';
    } else {
        $htmltype= $type==='decimal'?'number':$type;
        $max= in_array($type,['text','email','tel'],true)?' maxlength="'.(int)$rule.'"':'';
        $lim=$type==='decimal'?' step="0.01" min="0" max="99999999.99"':($name==='edad'?' min="1" max="120"':'');
        $html.='<input type="'.e($htmltype).'" id="'.e($id).'" name="'.e($name).'" value="'.e($value).'"'.$max.$lim.$req.'>';
    }
    return $html.'</div>';
}
function form_values(array $module, array $source, bool $isEdit=false): array {
    $values=[];
    foreach ($module['fields'] as $field=>$def) {
        [$label,$type,$required,$rule]=$def;
        $v=trim((string)($source[$field]??''));
        if (!$isEdit && $v==='' && $type==='date') $v=date('Y-m-d');
        if (!$isEdit && $v==='' && $field==='estado') $v= $module['table']==='Postulacion'?'Pendiente':'Activa';
        if ($required && $v==='') throw new InvalidArgumentException('Completa el campo «'.$label.'».');
        if ($v!=='' && in_array($type,['text','email','tel','textarea'],true) && utf_len($v)>(int)$rule) throw new InvalidArgumentException('El campo «'.$label.'» supera la longitud permitida.');
        if ($type==='email' && $v!=='' && !filter_var($v,FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('El correo electrónico no es válido.');
        if ($type==='select' && $v!=='' && !in_array($v,$rule,true)) throw new InvalidArgumentException('Selecciona una opción válida en «'.$label.'».');
        if ($type==='date' && $v!=='') { $d=DateTime::createFromFormat('!Y-m-d',$v); if (!$d || $d->format('Y-m-d')!==$v) throw new InvalidArgumentException('La fecha indicada no es válida.'); }
        if (($type==='number'||strpos($type,'ref_')===0) && (!ctype_digit($v) || (int)$v<1 ||($field==='edad'&&(int)$v>120))) throw new InvalidArgumentException('Selecciona un valor válido para «'.$label.'».');
        if ($type==='decimal' && (!is_numeric($v)|| (float)$v<0 || (float)$v>99999999.99)) throw new InvalidArgumentException('Introduce un salario válido.');
        $values[$field]=$v;
    }
    return $values;
}
function values_types(array $module): string {
    $out=''; foreach($module['fields'] as $d) $out.= (in_array($d[1],['number','ref_empresa','ref_aspirante','ref_oferta'],true)?'i':($d[1]==='decimal'?'d':'s'));
    return $out;
}
function verify_relations(string $key, array $v, int $ignoreId=0): void {
    if ($key==='ofertas') {
        $st=run_sql('SELECT id_empresa FROM Empresa WHERE id_empresa=?','i',[(int)$v['id_empresa']]);
        if(!$st->get_result()->fetch_assoc()) throw new InvalidArgumentException('La empresa seleccionada no existe.');
        $st->close();
    }
    if ($key==='postulaciones') {
        $st=run_sql('SELECT id_aspirante FROM Aspirante WHERE id_aspirante=?','i',[(int)$v['id_aspirante']]);
        if(!$st->get_result()->fetch_assoc()) throw new InvalidArgumentException('El aspirante no existe.'); $st->close();
        $st=run_sql('SELECT id_oferta FROM OfertaLaboral WHERE id_oferta=? AND estado="Activa"','i',[(int)$v['id_oferta']]);
        if(!$st->get_result()->fetch_assoc()) throw new InvalidArgumentException('La oferta debe estar activa.'); $st->close();
        $st=run_sql('SELECT id_postulacion FROM Postulacion WHERE id_aspirante=? AND id_oferta=? AND id_postulacion<>?','iii',[(int)$v['id_aspirante'],(int)$v['id_oferta'],$ignoreId]);
        if($st->get_result()->fetch_assoc()) throw new InvalidArgumentException('Este aspirante ya se postuló a esa oferta.'); $st->close();
    }
}
