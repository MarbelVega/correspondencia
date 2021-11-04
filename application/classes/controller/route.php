<?php

defined('SYSPATH') or die('Acceso denegado');

class Controller_route extends Controller_DefaultTemplate {

    protected $user;
    protected $menus;

    public function before() {

        parent::before();
    }

    public function after() {
        $this->template->menutop = View::factory('templates/menutop')->bind('menus', $this->menus)->set('controller', 'route');
        $this->template->nombre = $this->user->nombre;
        $this->template->username = $this->user->username;
        $this->template->email = $this->user->email;
        parent::after();
    }

    //listar nuris generados por el usuario logeado
    public function action_index() {
        $count = ORM::factory('documentos')->where('original', '=', 1)->and_where('id_user', '=', $this->user->id)->count_all();
        $pagination = Pagination::factory(array(
                    'total_items' => $count,
                    'current_page' => array('source' => 'query_string', 'key' => 'page'),
                    'items_per_page' => 50,
                    'view' => 'pagination/floating',
        ));
        $oNur = New Model_Hojasruta();
        $result = $oNur->hojasruta($this->user->id, $pagination->offset, $pagination->items_per_page);
        $page_links = $pagination->render();
        $oDoc = New Model_Tipos();
        $documentos = $oDoc->misTipos($this->user->id);
        $options = array();
        foreach ($documentos as $d) {
            $options[$d->id] = $d->tipo;
        }
        $this->template->title .= ' / Lista de Hojas de Ruta generadas';
        $this->template->titulo.='Lista';
        $this->template->descripcion = ' lista de hojas de ruta generados';
        $this->template->styles = array('media/css/tablas.csss' => 'all', 'media/css/datatable.css' => 'all', 'media/css/modal.css' => 'screen');
        $this->template->scripts = array('media/js/datatable.js',
            'media/js/resizable.min.js',
            'media/js/jquery.tablesorter.min.js');
        $this->template->content = View::factory('hojaruta/index')
                ->bind('result', $result)
                ->bind('page_links', $page_links)
                ->bind('count', $count)
                ->bind('options', $options);
    }

    public function action_seguimiento() {
        $id = Arr::get($_GET, 'hr', '');
        if ($id != '') {
            //obtenemos el documento ligado al nur                                     
            $documento = ORM::factory('documentos')->where('nur', '=', $id)->and_where('original', '=', 1)->find();
            $tipo = ORM::factory('tipos', $documento->id_tipo);
            $proceso = ORM::factory('procesos', $documento->id_proceso);
            $detalle = array(
                'nur' => $id,
                'fecha' => $documento->fecha_creacion,
                'codigo' => $documento->cite_original,
                'id_documento' => $documento->id,
                'tipo' => $tipo->tipo,
                'proceso' => $proceso->proceso,
                'referencia' => $documento->referencia,
                'remitente' => $documento->nombre_remitente,
                'cargo_remitente' => $documento->cargo_remitente,
                'destinatario' => $documento->nombre_destinatario,
                'cargo_destinatario' => $documento->cargo_destinatario,
                'adjunto' => $documento->adjuntos,
            );
            $archivo = ORM::factory('archivos')
                    ->where('id_documento', '=', $documento->id)
                    ->find_all();
            $oSeg = New Model_Seguimiento();
            $seguimiento = $oSeg->seguimiento($id);

            $user = $this->user;
            //agrupaciones
            $agrupado = ORM::factory('agrupaciones')->where('hijo', '=', $id)->find();
            $this->template->title = 'Seguimiento a la hoja de ruta : ' . $detalle['nur'];
            $this->template->styles = array('media/css/tablas.css' => 'all');
            $this->template->content = View::factory('hojaruta/seguimiento')
                    ->bind('seguimiento', $seguimiento)
                    ->bind('detalle', $detalle)
                    ->bind('archivo', $archivo)
                    ->bind('user', $user)
                    ->bind('agrupado', $agrupado);

            //var_dump($seguimiento);
        } else {
            $this->request->redirect('/route/ver');
        }
    }

    /* ver seguimiento */

    public function action_trace() {
        $id = Arr::get($_GET, 'hr', '');
        if ($id != '') {
            //obtenemos el documento ligado al nur                                     
            $documento = ORM::factory('documentos')->where('nur', '=', $id)->and_where('original', '=', 1)->find();
            $tipo = ORM::factory('tipos', $documento->id_tipo);
            $proceso = ORM::factory('procesos', $documento->id_proceso);
            $detalle = array(
                'nur' => $id,
                'fecha' => $documento->fecha_creacion,
                'codigo' => $documento->cite_original,
                'id_documento' => $documento->id,
                'tipo' => $tipo->tipo,
                'proceso' => $proceso->proceso,
                'referencia' => $documento->referencia,
                'remitente' => $documento->nombre_remitente,
                'cargo_remitente' => $documento->cargo_remitente,
                'destinatario' => $documento->nombre_destinatario,
                'cargo_destinatario' => $documento->cargo_destinatario,
                'adjunto' => $documento->adjuntos,
            );
            $archivo = ORM::factory('archivos')
                    ->where('id_documento', '=', $documento->id)
                    ->find_all();
            //$seguimiento=ORM::factory('seguimiento')->where('nur','=',$id)->find_all();            
            $oSeg = New Model_Seguimiento();
            $seguimiento = $oSeg->seguimiento($id);
            //$f = $oSeg->archivado($id);
            $oficina = $this->user->id_oficina;
            $user = $this->user;
//agrupaciones
            $agrupado = ORM::factory('agrupaciones')->where('hijo', '=', $id)->find();
            //$documento=$documento[0];      
            $this->template->title.=' / Seguimiento ' . $detalle['nur'];
            $this->template->titulo.='Seguimiento a ' . $detalle['nur'];
            $this->template->descripcion = 'Seguimiento del tramite o proceso';
            $this->template->styles = array('media/css/tablas.css' => 'all');
            $this->template->content = View::factory('hojaruta/seguimiento')
                    ->bind('seguimiento', $seguimiento)
                    ->bind('detalle', $detalle)
                    ->bind('archivo', $archivo)
                    // ->bind('f', $f)
                    ->bind('oficina', $oficina)
                    ->bind('user', $user)
                    ->bind('agrupado', $agrupado);
        } else {
            $this->request->redirect('route/view');
        }
    }

    public function action_responder() {
        if ($_GET['id_seg']) {
            $id_seg = Arr::get($_GET, 'id_seg');
            $nur = Arr::get($_GET, 'n');
            $id_tipo = Arr::get($_GET, 'd');
            $seguimiento = ORM::factory('seguimiento', $id_seg);
            if ($seguimiento->loaded()) {
                
                $oficina = ORM::factory('oficinas', $this->user->id_oficina);
                $tipo = ORM::factory('tipos', $id_tipo);
                $oOficina = New Model_Oficinas();

                if($oficina->padre==156||$oficina->id==156){
                    $correlativo = $oOficina->correlativoJuridico(156, $tipo->id, date('Y'));
                }
                else{
                $correlativo = $oOficina->correlativo($this->user->id_oficina, $tipo->id, date('Y'));
                }  
                
                $abre = $oOficina->tipo($tipo->id);
                $sigla = $oOficina->sigla($this->user->id_oficina);


                $oficina = ORM::factory('oficinas', $this->user->id_oficina);
                $abre = $oOficina->tipo($tipo->id);
                $entidad = ORM::factory('entidades')->where('id', '=', $oficina->id_entidad)->find();
                //variables para el cite
                $ofi = $oficina->sigla;
                $cor = $correlativo;
                $ent = $entidad->sigla;
                $mosca = $this->user->mosca;
                $anio = date('Y');//date('y');
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


                $proceso = ORM::factory('documentos')->where('nur', '=', $nur)->and_where('original', '=', 1)->find();

                $documento = ORM::factory('documentos');
                $documento->id_user = $this->user->id;
                $documento->codigo = $codigo;
                $documento->cite_original = $codigo;
                $documento->id_tipo = $id_tipo;
                $documento->nombre_destinatario = $seguimiento->nombre_emisor;
                $documento->cargo_destinatario = $seguimiento->cargo_emisor;
                $documento->nombre_remitente = $this->user->nombre;
                $documento->cargo_remitente = $this->user->cargo;
                $documento->fecha_creacion = date('Y-m-d H:i:s');
                $documento->nur = $nur;
                $documento->id_seguimiento = $id_seg;
                $documento->original = 0; //important !!                
                $documento->id_proceso = $proceso->id;
                $documento->id_oficina = $this->user->id_oficina;
                $documento->id_entidad = $this->user->id_entidad;
                $documento->save();
                if ($documento->id) {
                    $rs = $documento->has('nurs', $nur);
                    $documento->add('nurs', $nur);
                    $_POST = array();
                    $this->request->redirect('documento/edit/' . $documento->id);
                }
            } else {
                $this->templates->content = '<div class="info">Error: no se pudo generar el documento</div>';
            }
        } else {
            $this->template->content = View::factory('');
        }
    }

    public function action_generar_doc() {
        if ($_POST['aceptar']) {
            $nur = Arr::get($_POST, 'nur');
            $id_tipo = Arr::get($_POST, 'documento');
            $tipo = ORM::factory('tipos', $id_tipo);
            $oOficina = New Model_Oficinas();
            $correlativo = $oOficina->correlativo($this->user->id_oficina, $tipo->id, date('Y'));
            $abre = $oOficina->tipo($tipo->id);
            $sigla = $oOficina->sigla($this->user->id_oficina);
            if ($abre != '')
                $abre = $abre . '/';
            $codigo = $abre . $sigla . ' Nº ' . $correlativo . '/' . date('Y');
            //obtenemos el id_proceso del documento original
            $proceso = ORM::factory('documentos')->where('nur', '=', $nur)->and_where('original', '=', 1)->find();
            //generamos el documento
            $documento = ORM::factory('documentos');
            $documento->id_user = $this->user->id;
            $documento->codigo = $codigo;
            $documento->cite_original = $codigo;
            $documento->id_tipo = $id_tipo;
            $documento->fecha_creacion = date('Y-m-d H:i:s');
            $documento->nur = $nur;
            $documento->id_seguimiento = 0;
            $documento->original = 0; //important !!                
            $documento->id_proceso = $proceso->id;
            $documento->id_oficina = $this->user->id_oficina;
            $documento->id_entidad = $this->user->id_entidad;
            $documento->save();
            if ($documento->id) {
                //cazamos al documento con el nur asignado
                $rs = $documento->has('nurs', $nur);
                $documento->add('nurs', $nur);
                $_POST = array();
                $this->request->redirect('document/edit/' . $documento->id);
            }
        } else {
            $this->template->content = View::factory('');
        }
    }

    //asignar nur o nuri
    public function action_asignar($id = '') {
        $auth = Auth::instance();
        if ($auth->logged_in()) {
            //propiedades del documento
            $documento = ORM::factory('documentos')
                    ->where('id', '=', $id)
                    ->and_where('id_user', '=', $this->user->id)
                    ->find();
            if ($documento) {
                if ($_POST) {
                    //generamos el codigo del correlativo
                    $codigo = $this->nuevo(-1);
                    //asignamos
                    $nuri = ORM::factory('asignados');
                    $nuri->codigo = $codigo;
                    $nuri->id_user = $this->user->id;
                    $nuri->fecha_creacion = date('Y-m-d H:i:s');
                    $nuri->tipo_id = -1;
                    $nuri->save();
                    //actualizamos propiedades del docuemtno

                    $documento->id_nuri = $nuri->id;
                    $documento->nuri = $codigo;
                    $documento->id_proceso = $_POST['proceso'];
                    $documento->save();
                    //enviamos al formulario de derivacion
                    $this->request->redirect('route/deriv/?hr=' . $documento->id_nuri);
                }
                $procesos = ORM::factory('procesos')->find_all();
                $arrP = array('' => '');
                foreach ($procesos as $p) {
                    $arrP[$p->id] = $p->proceso;
                }
                $this->template->content = View::factory('nur/create')
                        ->bind('procesos', $arrP)
                        ->bind('documento', $documento);
            } else {
                $mensaje = 'Usted no puede modificar/asignar documentos de otras personas';
                $this->template->content = View::factory('errors/general')
                        ->bind('mensaje', $mensaje);
            }
        } else {
            $this->request->redirect(URL::base() . 'login');
        }
    }

    //correlativo para un NURI -1=nuri / -2 = nur
    public function nuevo($type = -1) {
        $oCorrelativo = ORM::factory('correlativo')
                ->where('id_tipo', '=', $type)
                ->find();
        $oCorrelativo->correlativo = $oCorrelativo->correlativo + 1;
        $oCorrelativo->save();
        $codigo = '000' . $oCorrelativo->correlativo;
        if ($type == -1)
            $tipo = 'I/';
        else
            $tipo = '';
        $codigo = $tipo . date('Y') . '-' . substr($codigo, -4);
        return $codigo;
    }

    //nuris creados por el usuario
    public function action_listar() {
        $oNuri = New Model_Asignados();
        $count = $oNuri->count($auth->get_user());
        //$count2= $oNuri->count2($auth->get_user());           
        if ($count) {
            // echo $oNuri->count2($auth->get_user());
            $pagination = Pagination::factory(array(
                        'total_items' => $count,
                        'current_page' => array('source' => 'query_string', 'key' => 'page'),
                        'items_per_page' => 40,
                        'view' => 'pagination/floating',
            ));
            $result = $oNuri->nuris($auth->get_user(), $pagination->offset, $pagination->items_per_page);
            $page_links = $pagination->render();
            $this->template->title = 'Hojas de Seguimiento';
            $this->template->styles = array('media/css/tablas.css' => 'screen');
            $this->template->content = View::factory('nur/listar')
                    ->bind('result', $result)
                    ->bind('page_links', $page_links);
        } else {
            $this->template->content = View::factory('errors/general');
        }
    }

    public function action_deriv() {
        $nur = Arr::get($_GET, 'hr', 0);
        $documento = ORM::factory('documentos')
                ->where('nur', '=', $nur)
                ->and_where('original', '=', 1)
                ->find();
        if ($documento->loaded()) {
            $session = Session::instance();
            $session->delete('destino');

            $proceso = ORM::factory('procesos', $documento->id_proceso);
            $errors = array();
            $user = $this->user;
            if ($documento->estado == 0) {
                $acciones = $this->acciones();
                $destinatarios = $this->destinatarios($this->user->id, $this->user->superior);
                $id_seguimiento = 0;
                $oficial = 1;
                $hijo = 0;
                $this->template->title.=' / Derivar Correspondencia - ' . $documento->nur;
                $this->template->titulo.='Derivar ' . $documento->nur;
                $this->template->descripcion = ' formulario de derviacion';

                // $this->template->styles = array('media/css/tablas.css' => 'screen', 'media/css/fcbk.css' => 'screen', 'media/css/modal.css' => 'screen',);
                //$this->template->scripts = array('media/js/jquery.fcbkcomplete.min.js',);
                //file:///home/ivan/Descargas/materialadmin/themeforest-10646222-material-admin-bootstrap-admin-html5-app/materialadmin/
                $this->template->scripts = array('static/js/libs/select2/select2.min.js', 'static/js/libs/bootstrap-datepicker/bootstrap-datepicker.js');
                $this->template->styles = array('static/css/theme-1/libs/select2/select2.css' => 'all', 'static/css/theme-1/libs/bootstrap-datepicker/datepicker3.css' => 'screen');

                $this->template->content = View::factory('hojaruta/frm_derivacion')
                        ->bind('documento', $documento)
                        ->bind('acciones', $acciones)
                        ->bind('destinatarios', $destinatarios)
                        ->bind('id_seguimiento', $id_seguimientos)
                        ->bind('oficial', $oficial)
                        ->bind('hijo', $hijo)
                        ->bind('proceso', $proceso)
                        ->bind('errors', $errors)
                        ->bind('user', $user);
            } else {
                //verificamos que la hoja de ruta esta en sus pendientes
                $pendiente = ORM::factory('seguimiento')
                        ->where('nur', '=', $nur)
                        ->and_where('derivado_a', '=', $this->user->id)
                        ->and_where('estado', '=', 2)
                        ->find();
                if ($pendiente->loaded()) {

                    $acciones = $this->acciones();
                    $destinatarios = $this->destinatarios($this->user->id, $this->user->superior);
                    $id_seguimiento = $pendiente->id;
                    $oficial = $pendiente->oficial;
                    $hijo = $pendiente->hijo;

                    $this->template->title.=' / Formulario de Derivación';
                    $this->template->titulo.='Derivar ' . $documento->nur;
                    $this->template->descripcion = ' formulario de derviacion';
                    $this->template->scripts = array('static/js/libs/select2/select2.min.js', 'static/js/libs/bootstrap-datepicker/bootstrap-datepicker.js');
                    $this->template->styles = array('static/css/theme-1/libs/select2/select2.css' => 'all', 'static/css/theme-1/libs/bootstrap-datepicker/datepicker3.css' => 'screen');
                    $this->template->content = View::factory('hojaruta/frm_derivacion')
                            ->bind('documento', $documento)
                            ->bind('acciones', $acciones)
                            ->bind('destinatarios', $destinatarios)
                            ->bind('id_seguimiento', $id_seguimiento)
                            ->bind('oficial', $oficial)
                            ->bind('hijo', $hijo)
                            ->bind('proceso', $proceso)
                            ->bind('user', $user)
                            ->bind('errors', $errors);
                } else {
                    $this->request->redirect('route/trace/?hr=' . $nur);
                }
            }
        } else {
            $this->template->content = 'Hoja de Ruta Inexistente';
        }
    }

    //imprimir
    public function action_print() {

        $this->template->scripts = array('static/js/select2.full.js','static/js/eModal.min.js');
        $this->template->styles = array('static/css/select2.min.css' => 'screen');
        //$this->template->scripts = array('static/scripts/jquery.mockjax.js',"static/src/jquery.autocomplete.js",);

        $this->template->content = View::factory('hojaruta/imprimir');
    }

    public function action_view() {
        $errors = array();
        $results = ORM::factory('seguimiento')
                ->where('derivado_por', '=', $this->user->id)
                ->order_by('fecha_emision', 'DESC')
                ->limit(20)
                ->find_all();
        $this->template->scripts = array('media/js/jquery.tablesorter.min.js');
        $this->template->styles = array('media/css/tablas.css' => 'screen');
        $this->template->titulo .= 'Seguimiento';
        $this->template->descripcion = 'Recientes';
        $this->template->content = View::factory('hojaruta/ver')
                ->bind('results', $results)
                ->bind('errors', $errors);
    }

    /*     * */

    public function acciones() {
        $acciones = array();
        $acc = ORM::factory('acciones')->find_all();
        foreach ($acc as $a) {
            $acciones [$a->id] = $a->accion;
        }
        return $acciones;
    }

    public function destinatarios($id_user, $id_superior) {

        $lista_derivacion = array();
        $oDestino = New Model_Destinatarios();
        //dependientes
        $lista_destinos = $oDestino->dependientes($id_user);
        foreach ($lista_destinos as $l) {
            $lista_derivacion [$l['id']] = $l['oficina'] . ' - ' . Text::limit_words($l['nombre'], 6, '');
        }
        //superior
        $lista_destinos = $oDestino->superior($id_superior);
        foreach ($lista_destinos as $l) {
            $lista_derivacion [$l['id']] = $l['oficina'] . ' - ' . Text::limit_words($l['nombre'], 6, '');
        }

        $lista_destinos = $oDestino->destinos($id_user);
        foreach ($lista_destinos as $l) {
            if (!array_key_exists($l->id, $lista_derivacion))
                $lista_derivacion [$l->id] = $l->oficina . ' - ' . Text::limit_words($l->nombre, 6, '');
        }

        //print_r($lista_destinos);
        //sort($lista_derivacion, true);
        //sort($lista_derivacion);
        asort($lista_derivacion);
        return $lista_derivacion;
    }

    public function action_oficina($id = 0) {

        $oficina = ORM::factory('oficinas', $id);
        if ($oficina->loaded()) {
            $usuarios = ORM::factory('users')->where('id_oficina', '=', $id)->find_all();
            $this->template->styles = array('media/css/tablas.css' => 'all');
            $this->template->scripts = array('media/js/jquery.tablesorter.min.js');
            $this->template->title.=' / ' . $oficina->oficina;
            $this->template->titulo = '<v>' . $oficina->oficina . '</v>';
            $this->template->descripcion = 'Lista de Personal';
            $this->template->content = View::factory('user/personal')
                    ->bind('usuarios', $usuarios);
        } else {
            
        }
    }

}

?>
