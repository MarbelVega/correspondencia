<?php

defined('SYSPATH') or die('Acceso denegado');

class Controller_Documento extends Controller_DefaultTemplate {

    protected $user;
    protected $menus;

    public function before() {
        parent::before();
    }

    public function after() {
        $this->template->menutop = View::factory('templates/menutop')->bind('menus', $this->menus)->set('controller', 'document');
        $this->template->nombre = $this->user->nombre;
        $this->template->username = $this->user->username;
        $this->template->email = $this->user->email;
        parent::after();
    }

    //nuevo
    public function action_transferir($id) {

        $documento = ORM::factory('documentos')
                ->where('id_user', '=', $this->user->id)
                ->and_where('id', '=', $id)
                ->find();
        if ($documento->loaded()) {
            //datos enviados por formulario
            if (isset($_POST['submit'])) {
                $user = ORM::factory('users', $_POST['destino']);
                $d_trans = ORM::factory('documentos', $documento->id);
                $d_trans->id_user = $user->id;
                $d_trans->save();
                $this->template->titulo.=$documento->cite_original;
                $this->template->descripcion.='Transferencia de Documento';
                $this->template->content = 'Documento Transferito exitosamente.';
            } else {
                $destino = array();
                $oVias = new Model_data();
                $superior = $oVias->superior($this->user->id);
                foreach ($superior as $k) {
                    $id = $k['id'];
                    $destino[$id] = $k['nombre'] . ' / ' . $k['cargo'];
                }
                $dependientes = $oVias->dependientes($this->user->id);
                foreach ($dependientes as $k) {
                    $id = $k['id'];
                    $destino[$id] = $k['nombre'] . ' / ' . $k['cargo'];
                }
                $oDestinatario = New Model_Destinatarios();
                $destinos = $oDestinatario->destinos($this->user->id);
                foreach ($destinos as $k) {
                    $destino[$k->id] = $k->nombre . ' / ' . $k->cargo;
                }
                asort($destino);
                $this->template->titulo.=$documento->cite_original;
                $this->template->descripcion.='Transferencia de Documento';
                $this->template->content = View::factory('documentos/transferir')
                        ->bind('destino', $destino)
                        ->bind('documento', $documento);
            }
        }
    }

    public function action_generar($t = '') {
        //$this->template->menubar = '';

        $tipo = ORM::factory('tipos', array('action' => $t)); //obtenemos el id del tipo            
        if ($tipo->loaded()) {
            if (isset($_POST['submit'])) {
                $oficina = ORM::factory('oficinas', $this->user->id_oficina);
                if (isset($_POST['cite_despacho'])) {
                    if ($oficina->padre > 0)
                        $oficina_id = $oficina->padre;
                    else
                        $oficina_id = $oficina->id;
                }
                else {
                    $oficina_id = $oficina->id;
                }
                $oOficina = New Model_Oficinas();                              
                //obtenemos informe juridico
                if($oficina->padre==156||$oficina->id==156){
                    $correlativo = $oOficina->correlativoJuridico(156, $tipo->id, date('Y'));
                }
                else{
                $correlativo = $oOficina->correlativo($oficina_id, $tipo->id, date('Y'));    
                }  
                //$correlativo = $oOficina->correlativo($oficina_id, $tipo->id, date('Y'));    
                $abre = $oOficina->tipo($tipo->id);
                $entidad = ORM::factory('entidades')->where('id', '=', $oficina->id_entidad)->find();
                //variables para el cite
                $ofi = $oOficina->sigla($oficina_id);
                $cor = $correlativo;
                $ent = $entidad->sigla;
                $mosca = $this->user->mosca;
                $anio = date('Y');
                $aniom = date('Y');
                $tip = $tipo->abreviatura;
                if ($tipo->cite_propio > 0) {
                    eval("\$str = \"$tipo->cite\";");
                    $codigo = $str;
                } else {
                    $cite_propio = '';
                    eval("\$str = \"$tipo->cite_tipo\";");
                    $codigo = $str;                   
                }                
                if ($_POST['proceso'] > 0 && $_POST['proceso'] < 30) {
                    $proceso = $_POST['proceso'];
                } else
                    $proceso = 4;
                $documento = ORM::factory('documentos'); //intanciamos el modelo documentos                        
                $documento->id_user = $this->user->id;
                $documento->id_tipo = $tipo->id;
                $documento->id_proceso = $proceso;
                $documento->id_oficina = $oficina_id;

                $documento->codigo = $codigo;
                $documento->cite_original = $codigo;
                $documento->nombre_destinatario = $_POST['destinatario']; //
                $documento->cargo_destinatario = $_POST['cargo_des'];
                $documento->institucion_destinatario = $_POST['institucion_des'];
                $documento->nombre_remitente = $_POST['remitente'];
                $documento->cargo_remitente = $_POST['cargo_rem'];
                $documento->mosca_remitente = $_POST['mosca'];
                $documento->referencia = $_POST['referencia'];
                $documento->contenido = $_POST['descripcion'];
                $documento->fecha_creacion = date('Y-m-d H:i:s');
                $documento->adjuntos = $_POST['adjuntos'];
                $documento->hojas = $_POST['hojas'];
                $documento->copias = $_POST['copias'];
                $documento->nombre_via = $_POST['via'];
                $documento->cargo_via = $_POST['cargovia'];
                $documento->titulo = $_POST['titulo'];
                $documento->id_entidad = $this->user->id_entidad;
                $documento->save();
                //si se creo el documento entonces
                //guardamos en vitacora
                //VITACORA_ABEN
                $accion = $this->user->nombre.' generó el documento : <b>' . $documento->cite_original . '</b>';

                if ($documento->id) {
                    if ($_POST['hojaruta']) { //asignamos hoja de ruta
                        //generamos la hoja de ruta a partir de la entidad
                        $entidad = ORM::factory('entidades', $this->user->id_entidad);
                        $oNur = New Model_nurs();
                        $nur = $oNur->correlativoHRI(-1, $entidad->sigla2 . '/', $this->user->id_entidad, date('Y'));
                        //$codigo = $oExt->correlativo(-3, $entidad->doc_externo, $entidad->id, date('Y'));
                        $nur_asignado = $oNur->asignarNur($nur, $this->user->id, $this->user->nombre);
                        $documento->nur = $nur;
                        $documento->save();
                        //cazamos al documento con el nur asignado
                        $rs = $documento->has('nurs', $nur_asignado);
                        $documento->add('nurs', $nur_asignado);
                        $accion.= ' con Hoja de Ruta : <b>' . $documento->nur . '</b>';
                    }
                    $this->save($this->user->id_entidad, $this->user->id, $accion);
                    //$this->save($this->user->id_entidad, $this->user->id);


                    //si se creo una solicitud de pasajes y viaticos entonces almacenamos los datos referentes a elo mas
                    switch ($t) {
                        //solicitud de pasajes y viaticos
                        case 'sol_pasajes':
                            $pasajes = ORM::factory('pasajes');
                            $pasajes->id_documento = $documento->id;

                            if (isset($_POST['pasaje']))
                                $pasajes->pasaje = $_POST['pasaje'];

                            if (isset($_POST['viatico']))
                                $pasajes->viatico = $_POST['viatico'];

                            $pasajes->lugar = $_POST['lugar'];
                            $pasajes->tipo_viaje = $_POST['tipo_viaje'];
                            $pasajes->medio_transporte = $_POST['medio_transporte'];
                            $pasajes->fecha_salida = $_POST['fecha_salida'] . " " . $_POST['hora_salida'] . ":00";
                            $pasajes->fecha_retorno = $_POST['fecha_retorno'] . " " . $_POST['hora_retorno'] . ":00";
                            $pasajes->dias = $_POST['dias'];
                            $pasajes->save();
                            break;
                        //informe de descargo de viaje
                        case 'inf_viaje':
                            //var_dump($_POST);
                            $viajes = ORM::factory('viajes');
                            $viajes->id_documento = $documento->id;
                            $viajes->lugar = $_POST['lugar'];
                            $viajes->tipo_viaje = $_POST['tipo_viaje'];
                            $viajes->medio_transporte1 = $_POST['transporte_ida'];
                            $viajes->medio_transporte2 = $_POST['transporte_retorno'];
                            $viajes->fecha_salida = $_POST['fecha_salida'] . " " . $_POST['hora_salida'] . ":00";
                            $viajes->fecha_retorno = $_POST['fecha_retorno'] . " " . $_POST['hora_retorno'] . ":00";
                            //$viajes->dias=$_POST['dias'];
                            $viajes->no_descripcion = $_POST['no_descripcion'];
                            $viajes->viatico = $_POST['viatico'];
                            $viajes->fecha_presentacion = date('Y-m-d');
                            $viajes->resolucion = $_POST['resolucion'];
                            $viajes->pases = $_POST['pases'];
                            $viajes->form110 = $_POST['form110'];
                            $viajes->cuenta_corriente = $_POST['cuenta_corriente'];
                            $viajes->cuenta_utc = $_POST['cuenta_utc'];
                            $viajes->fondos_copia = $_POST['fondos_copia'];
                            $viajes->pasaje_aereo = $_POST['pasaje_aereo'];
                            $viajes->pasaje_terrestre = $_POST['pasaje_terrestre'];
                            $viajes->form604 = $_POST['form604'];
                            $viajes->planilla_invitados = $_POST['planilla_invitados'];
                            $viajes->cedula_identidad = $_POST['cedula_identidad'];
                            $viajes->save();
                            break;

                        default:
                            $_POST = array();
                            $this->request->redirect('documento/edit/' . $documento->id);
                            break;
                    }
                }
            }
            $oVias = new Model_data();
            $vias = $oVias->vias($this->user->id);
            $superior = $oVias->superior($this->user->id);
            $dependientes = $oVias->dependientes($this->user->id);
            $oDestinatario = New Model_Destinatarios();
            $destinos = $oDestinatario->destinos($this->user->id);

            $destinatarios = array();
            foreach ($vias as $v) {
                $destinatarios[$v['id_d']]['nombre'] = $v['nombre'];
                $destinatarios[$v['id_d']]['cargo'] = $v['cargo'];
                $destinatarios[$v['id_d']]['genero'] = $v['genero'];
            }
            foreach ($superior as $v) {
                $destinatarios[$v['id']]['nombre'] = $v['nombre'];
                $destinatarios[$v['id']]['cargo'] = $v['cargo'];
                $destinatarios[$v['id']]['genero'] = $v['genero'];
            }
            foreach ($dependientes as $v) {
                $destinatarios[$v['id']]['nombre'] = $v['nombre'];
                $destinatarios[$v['id']]['cargo'] = $v['cargo'];
                $destinatarios[$v['id']]['genero'] = $v['genero'];
            }
            foreach ($destinos as $v) {
                $destinatarios[$v->id]['nombre'] = $v->nombre;
                $destinatarios[$v->id]['cargo'] = $v->cargo;
                $destinatarios[$v->id]['genero'] = $v->genero;
            }
            sort($destinatarios);
            $procesos = ORM::factory('procesos')->find_all();
            $options = array('' => '[Elija proceso]');
            foreach ($procesos as $p) {
                $options[$p->id] = $p->proceso;
            }
            // $this->template->scripts    = array('ckeditor/adapters/jquery.js','ckeditor/ckeditor.js');                                  
            $this->template->scripts = array('static/js/libs/select2/select2.min.js','static/js/eModal.min.js');
            $this->template->styles = array('static/css/theme-1/libs/select2/select2.css' => 'all');

            $this->template->title .= ' / Generar ' . $tipo->tipo;
            $this->template->titulo .= 'Generar ' . $tipo->tipo;
            $this->template->descripcion = 'LLENE CORRECTAMENTE LOS DATOS EN EL FORMULARIO';

            $this->template->content = View::factory('documentos/create')
                    ->bind('options', $options)
                    ->bind('user', $this->user)
                    ->bind('documento', $tipo)
                    ->bind('superior', $superior)
                    ->bind('dependientes', $dependientes)
                    ->bind('destinos', $destinos)
                    ->bind('destinatarios', $destinatarios)
                    ->bind('tipo', $tipo)
                    ->bind('vias', $vias);


            /*                 if($t=='circular')
              {
              $oficina=ORM::factory('oficinas')->where('id','=',$this->user->id_oficina)->find();
              $entidad=ORM::factory('entidades')->where('id','=',$oficina->id_entidad)->find();
              $oficinas=ORM::factory('oficinas')->where('id_entidad','=',$entidad->id)->find_all();
              $this->template->content    =View::factory('documentos/crear_circular')
              ->bind('options', $options)
              ->bind('user', $this->user)
              ->bind('documento', $tipo)
              ->bind('superior', $superior)
              ->bind('dependientes', $dependientes)
              ->bind('oficinas', $oficinas)
              ->bind('tipo', $tipo)
              ->bind('vias', $vias);
              }
              else
              {
             */
        }
        //            }
        else {
            $oDoc = New Model_Tipos();
            $documentos = $oDoc->misTipos($this->user->id);
            $this->template->title.= ' / Generar documentos';
            $this->template->content = View::factory('documentos/nuevo')
                    ->bind('documentos', $documentos);
        }
    }

    public function action_edit($id = '') {
        $mensajes = array();
        $documento = ORM::factory('documentos')->where('id', '=', $id)->and_where('id_user', '=', $this->user->id)->find();
        if ($documento->loaded()) {
            //si se envia los datos modificados entonces guardamamos
            if (isset($_POST['referencia'])) {
                $documento->nombre_destinatario = $_POST['destinatario'];
                ; //
                $documento->cargo_destinatario = $_POST['cargo_des'];
                $documento->institucion_destinatario = $_POST['institucion_des'];
                $documento->nombre_remitente = $_POST['remitente'];
                $documento->cargo_remitente = $_POST['cargo_rem'];
                $documento->mosca_remitente = $_POST['mosca'];
                $documento->referencia = $_POST['referencia'];
                $documento->contenido = $_POST['descripcion'];
                $documento->titulo = $_POST['titulo'];
//                   $documento->fecha_creacion=  time(); //fecha y hora en formato int
                $documento->adjuntos = $_POST['adjuntos'];
                $documento->copias = $_POST['copias'];
                $documento->hojas = $_POST['hojas'];
                $documento->nombre_via = $_POST['via'];
                $documento->cargo_via = $_POST['cargovia'];
                $documento->id_proceso = $_POST['proceso'];
                $documento->save();
                $mensajes['Modificado!'] = 'El documento se modifico correctamente.';
                $this->save($this->user->id_entidad, $this->user->id, $this->user->nombre.', modificó el documento: <b>' . $documento->cite_original . '</b>');
            }
            if (isset($_POST['adjuntar'])) {

                $path = '/home/sigec_adjuntos/archivo/' . date('Y_m');
                if (!is_dir($path)) {
                    // Creates the directory 
                    if (!mkdir($path, 0777, TRUE)) {
                        // On failure, throws an error 
                        throw new Exception("No se puedo crear el directorio!");
                        exit;
                    }
                }
                $filename = upload::save($_FILES ['archivo'], NULL, $path);
                if ($_FILES ['archivo']['name'] != '') {
                    $archivo = ORM::factory('archivos'); //intanciamos el modelo proveedor							                
                    $archivo->nombre_archivo = basename($filename);
                    $archivo->extension = $_FILES ['archivo'] ['type'];
                    $archivo->tamanio = $_FILES ['archivo'] ['size'];
                    $archivo->id_user = $this->user->id;
                    $archivo->id_documento = $_POST['id_doc'];
                    $archivo->sub_directorio = date('Y_m');
                    $archivo->fecha = date('Y-m-d H:i:s');
                    $archivo->save();
                    if ($archivo->id > 0)
                        $_POST = array();
                }
            }
            $oficina = ORM::factory('oficinas', $this->user->id_oficina);

            $oVias = new Model_data();
            $vias = $oVias->vias($this->user->id);
            $superior = $oVias->superior($this->user->id);
            $dependientes = $oVias->dependientes($this->user->id);
            $oDestinatario = New Model_Destinatarios();
            $destinos = $oDestinatario->destinos($this->user->id);

            $destinatarios = array();
            foreach ($vias as $v) {
                $destinatarios[$v['id_d']]['nombre'] = $v['nombre'];
                $destinatarios[$v['id_d']]['cargo'] = $v['cargo'];
                $destinatarios[$v['id_d']]['genero'] = $v['genero'];
            }
            foreach ($superior as $v) {
                $destinatarios[$v['id']]['nombre'] = $v['nombre'];
                $destinatarios[$v['id']]['cargo'] = $v['cargo'];
                $destinatarios[$v['id']]['genero'] = $v['genero'];
            }
            foreach ($dependientes as $v) {
                $destinatarios[$v['id']]['nombre'] = $v['nombre'];
                $destinatarios[$v['id']]['cargo'] = $v['cargo'];
                $destinatarios[$v['id']]['genero'] = $v['genero'];
            }
            foreach ($destinos as $v) {
                $destinatarios[$v->id]['nombre'] = $v->nombre;
                $destinatarios[$v->id]['cargo'] = $v->cargo;
                $destinatarios[$v->id]['genero'] = $v->genero;
            }
            sort($destinatarios);
            $tipo = ORM::factory('tipos', $documento->id_tipo);
            $archivos = ORM::factory('archivos')->where('id_documento', '=', $id)->and_where('estado', '=', 1)->find_all();
            $procesos = ORM::factory('procesos')->find_all();
            $options = array();
            foreach ($procesos as $p) {
                $options[$p->id] = $p->proceso;
            }
            $this->template->title .= ' / ' . $documento->codigo;
            $this->template->titulo .= ' Editar ' . $documento->codigo . ' | <v>' . $documento->nur . '</v>';
            $this->template->descripcion = 'Editar documento, subir archivos digitales y derivacion directa';
            //$this->template->scripts = array('media/redactor/redactor.min.js', 'media/redactor/langs/es.js');
            //$this->template->styles = array('media/redactor/css/redactor.css' => 'all', 'media/css/tablas.css' => 'screen');
            $this->template->scripts = array('static/js/libs/select2/select2.min.js','static/js/libs/spin.js/spin.min.j','static/js/eModal.min.js');
            $this->template->styles = array('static/css/theme-1/libs/select2/select2.css' => 'all');

            $this->template->content = View::factory('documentos/edit')
                    ->bind('documento', $documento)
                    ->bind('archivos', $archivos)
                    ->bind('tipo', $tipo)
                    ->bind('superior', $superior)
                    ->bind('destinatarios', $destinatarios)
                    ->bind('user', $this->user)
                    ->bind('options', $options)
                    ->bind('mensajes', $mensajes)
                    ->bind('archivos', $archivos);
        } else {
         //   $this->template->title .= ' / ' . $documento->codigo;
         //   $this->template->titulo .= ' Editar ' . $documento->codigo . ' | <v>' . $documento->nur . '</v>';
      //      $this->template->descripcion = 'Editar documento, subir archivos digitales y derivacion directa';
            $this->template->content = '<div class="error">Solo puede editar documentos creados por su usuario</div> ';
        }
    }

//function para obtoner las horas
    public function horas() {
        $m_horas = ORM::factory('horas')->find_all();
        $horas = array();
        foreach ($m_horas as $h) {
            $horas[$h->hora] = $h->hora;
        }
        return $horas;
    }

    //
    public function transportes() {
        $medios_transporte = ORM::factory('mediotransporte')->find_all();
        $m_transporte = array();

        foreach ($medios_transporte as $m) {
            $m_transporte[$m->id] = $m->medio_transporte;
        }
        return $m_transporte;
    }

}

?>