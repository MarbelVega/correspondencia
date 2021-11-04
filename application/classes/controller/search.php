<?php

defined('SYSPATH') or die('Acceso denegado');

class Controller_Search extends Controller_DefaultTemplate {

    protected $user;
    protected $menus;

    public function before() {
        $auth = Auth::instance();
        //si el usuario esta logeado entocnes mostramos el menu
        if ($auth->logged_in()) {
            //menu top de acuerdo al nivel
            $session = Session::instance();
            $this->user = $session->get('auth_user');
            $oNivel = New Model_niveles();
            $this->menus = $oNivel->menus($this->user->nivel);
            parent::before();
            $this->template->titulo = '<v>Busqueda </v> /';
            $this->template->username = $this->user->nombre;
            if ($this->user->theme != null) {
                $this->template->theme = $this->user->theme;
            }
        } else {
            $url = substr($_SERVER['REQUEST_URI'], 1);
            $this->request->redirect('/login?url=' . $url);
        }
    }

    public function after() {
        $this->template->menutop = View::factory('templates/menutop')->bind('menus', $this->menus)->set('controller', 'search');
        $oSM = New Model_menus();
        $submenus = $oSM->submenus('search');
        $this->template->submenu = View::factory('templates/submenu')->bind('smenus', $submenus)->set('titulo', 'Busqueda');
        parent::after();
    }

    //listar nuris generados por el usuario logeado
    public function action_index() {
        if (isset($_GET['q'])) {
            $text = strtoupper(trim(Arr::get($_GET, 'q', '')));
            if ($text != '') {
                //  $entidad = $this->user->id_entidad;
                // if ($this->user->prioridad == 1)
                $entidad = 0;
                $oDocumento = New Model_Documentos();
                $count = $oDocumento->contarHR($text, $entidad);
                $count = $count[0]['count'];
                // Creamos una instancia de paginacion + configuracion
                $pagination = Pagination::factory(array(
                            'total_items' => $count,
                            'current_page' => array('source' => 'query_string', 'key' => 'page'),
                            'items_per_page' => 10,
                            'view' => 'pagination/floating',
                ));
                $results = $oDocumento->buscarHR($text, $pagination->offset, $pagination->items_per_page, $entidad);
                // Render the pagination links
                $page_links = $pagination->render();
                //tipos para los tabs       
                //vitacora

                /*CONSULTA VITACORA_ABEN*/
                $this->save($this->user->id_entidad, $this->user->id, $this->user->nombre.', realizó una busqueda encontrando <b>' . $count . '</b> resultados para <b>\'' . $text . '\'</b>');
                /*FIN CONSULTA*/
                $this->template->title = ' Resultados de la busqueda';
                $this->template->titulo .= 'Resultados';
                $descripcion = '<b>' . $count . '</b> hojas de ruta encontrados para <b>\'' . $text . '\'</b>';
                $this->template->styles = array('media/css/tablas.css' => 'all');
                $this->template->scripts = array('media/js/jquery.tablesorter.min.js');
                $this->template->content = View::factory('busqueda/result')
                        ->bind('descripcion', $descripcion)
                        ->bind('results', $results)
                        ->bind('page_links', $page_links)
                        ->bind('count', $count)
                        ->bind('name', $text);
            } else {
                $this->request->redirect('search/advanced');
            }
        } else {
            $this->request->redirect('login');
        }
    }

    public function action_documentos() {
        $this->template->titulo.="Busqueda basica";
        $this->template->descripcion.="Busqueda rapida";
        $this->template->styles = array('media/css/search.css' => 'screen');
        $this->template->scripts = array('media/js/scriptsearch.js');
        $this->template->content = View::factory('busqueda/documentos');
    }

    public function action_advanced() {
        $count=0;
        $result=array();
         $page_links ="";
        $mensajes = array();
        if (isset($_GET['buscar'])) {

            $start = new DateTime($_GET['start']);
            $f1 = $start->format('Y-m-d') . " 00:00:00";

            $end = new DateTime($_GET['end']);
            $f2 = $end->format('Y-m-d') . " 23:59:59";

            $where = " WHERE ";
            $where.=" d.fecha_creacion BETWEEN '$f1' AND '$f2' AND ";
            if ($_GET['tipo'] > 0) {
                $where.="id_tipo = '" . $_GET['tipo'] . "' AND ";
            }
            if (trim($_GET['cite_original']) != '') {
                $where.="d.cite_original like '%" . $_GET['cite_original'] . "%' AND ";
            }
            if (trim($_GET['referencia']) != '') {
                $where.="d.referencia like '%" . $_GET['referencia'] . "%' AND ";
            }
            if (trim($_GET['nur']) != '') {
                $where.="d.nur like '%" . $_GET['nur'] . "%' AND ";
            }
            if (trim($_GET['destinatario']) != '') {
                $where.="d.nombre_destinatario like '%" . $_GET['destinatario'] . "%' AND ";
            }
            if (trim($_GET['remitente']) != '') {
                $where.="d.nombre_remitente like '%" . $_GET['remitente'] . "%' AND ";
            }
            if (trim($_GET['entidad']) != '') {
                $where.="d.institucion_remitente like '%" . $_GET['entidad'] . "%' AND ";
            }
            $where = substr($where, 0, -4);
            $oDocumento = New Model_Documentos();
            $count = $oDocumento->contar2($where);
            $count = $count[0]['count'];            
            if ($count > 0) {
                //echo $count;
                // Creamos una instancia de paginacion + configuracion
                $pagination = Pagination::factory(array(
                            'total_items' => $count,
                            'current_page' => array('source' => 'query_string', 'key' => 'page'),
                            'items_per_page' => 10,
                            'view' => 'pagination/floating',
                ));
                
                $result = $oDocumento->search($where, $pagination->offset, $pagination->items_per_page);
                // Render the pagination links
                $page_links = $pagination->render();
            }
            
            
            /*CONSULTA  VITACORA_ABEN*/
            $this->save($this->user->id_entidad, $this->user->id, $this->user->nombre.', realizó una busqueda encontrando <b>' . $count . '</b> resultados para <b>' . $where . '</b>');
            /*FIN CONSULTA*/
           // echo $where;

            /*
              $text = $_GET['texto'];
              $where = " WHERE ";
              $campos = New ArrayIterator($_GET['campo']);
              foreach ($campos as $c) {
              $where.="d." . $c . " LIKE '%$text%' OR ";
              }
              $where = substr($where, 0, -3);

              $oDocumento = New Model_Documentos();
              $count = $oDocumento->contar2($where);
              $count = $count[0]['count'];
              if ($count > 0) {
              // Creamos una instancia de paginacion + configuracion
              $pagination = Pagination::factory(array(
              'total_items' => $count,
              'current_page' => array('source' => 'query_string', 'key' => 'page'),
              'items_per_page' => 15,
              'view' => 'pagination/floating',
              ));
              $results = $oDocumento->search($where, $pagination->offset, $pagination->items_per_page);
              // Render the pagination links
              $page_links = $pagination->render();
              //tipos para los tabs
              $this->save($this->user->id_entidad, $this->user->id, 'Realizó una busqueda encontrando <b>' . $count . '</b> resultados para <b>\'' . $text . '\'</b>');
              $this->template->title = ' Resultados de la busqueda';
              $this->template->titulo .=' Busqueda avanzada';
              $this->template->descripcion = '<b>' . $count . '</b> resultados encontrados para <b>\'' . $text . '\'</b>';
              $this->template->styles = array('media/css/tablas.css' => 'screen');
              $this->template->scripts = array('media/js/tablesort.min.js');
              $this->template->content = View::factory('busqueda/result')
              ->bind('results', $results)
              ->bind('page_links', $page_links)
              ->bind('count', $count)
              ->bind('name', $text);
              } else {
              $mensajes['Sin exito!: '] = "No se encontro ningun resultado para <b>'$text'</b>.";
              $this->template->title .='| formulario de busqueda';
              $this->template->titulo .=' Busqueda avanzada';
              $this->template->descripcion .='Realizar busqueda bajo criterios';
              $this->template->content = View::factory('busqueda/form_advanced')
              ->bind('mensajes', $mensajes);
              }
             * 
             */
        }


        $oTipos = ORM::factory('tipos')->find_all();
        $tipos = array(0 => "Todos los tipos");
        foreach ($oTipos as $t) {
            $tipos[$t->id] = $t->tipo;
        }

        $this->template->styles = array(
            'static/css/theme-3/libs/bootstrap-datepicker/datepicker3.css' => 'screen',
            'static/css/theme-3/libs/select2/select2.css' => 'screen',
        );
        $this->template->scripts = array(
            //'media/jqwidgets/scripts/demos.js',
            'static/js/libs/bootstrap-datepicker/bootstrap-datepicker.js',
            'static/js/libs/select2/select2.min.js'
        );


        $this->template->title .=' / Busqueda avanzada';
        $this->template->titulo .=' Busqueda avanzada';
        $this->template->descripcion .='Realizar busqueda bajo criterios';
        $this->template->content = View::factory('busqueda/form_advanced')
                ->bind('page_links',  $page_links )
                ->bind('result', $result)
                ->bind('tipos', $tipos)
                ->bind('count', $count)
                ->bind('mensajes', $mensajes);
    }

    public function action_advanced_old() {
        $mensajes = array();
        if (isset($_GET['buscar'])) {
            $text = $_GET['texto'];
            $where = " WHERE ";
            $campos = New ArrayIterator($_GET['campo']);
            foreach ($campos as $c) {
                $where.="d." . $c . " LIKE '%$text%' OR ";
            }
            $where = substr($where, 0, -3);

            $oDocumento = New Model_Documentos();
            $count = $oDocumento->contar2($where);
            $count = $count[0]['count'];
            if ($count > 0) {
                // Creamos una instancia de paginacion + configuracion
                $pagination = Pagination::factory(array(
                            'total_items' => $count,
                            'current_page' => array('source' => 'query_string', 'key' => 'page'),
                            'items_per_page' => 15,
                            'view' => 'pagination/floating',
                ));
                $results = $oDocumento->search($where, $pagination->offset, $pagination->items_per_page);
                // Render the pagination links
                $page_links = $pagination->render();
                //tipos para los tabs       
                $this->save($this->user->id_entidad, $this->user->id, 'Realizó una busqueda encontrando <b>' . $count . '</b> resultados para <b>\'' . $text . '\'</b>');
                $this->template->title = ' Resultados de la busqueda';
                $this->template->titulo .=' Busqueda avanzada';
                $this->template->descripcion = '<b>' . $count . '</b> resultados encontrados para <b>\'' . $text . '\'</b>';
                $this->template->styles = array('media/css/tablas.css' => 'screen');
                $this->template->scripts = array('media/js/tablesort.min.js');
                $this->template->content = View::factory('busqueda/result')
                        ->bind('results', $results)
                        ->bind('page_links', $page_links)
                        ->bind('count', $count)
                        ->bind('name', $text);
            } else {
                $mensajes['Sin exito!: '] = "No se encontro ningun resultado para <b>'$text'</b>.";
                $this->template->title .='| formulario de busqueda';
                $this->template->titulo .=' Busqueda avanzada';
                $this->template->descripcion .='Realizar busqueda bajo criterios';
                $this->template->content = View::factory('busqueda/form_avanzada')
                        ->bind('mensajes', $mensajes);
            }
        } else {
            $this->template->title .=' / Busqueda avanzada';
            $this->template->titulo .=' Busqueda avanzada';
            $this->template->descripcion .='Realizar busqueda bajo criterios';
            $this->template->content = View::factory('busqueda/form_avanzada')
                    ->bind('mensajes', $mensajes);
        }
    }

}

?>
