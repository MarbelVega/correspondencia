<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Admin_Config extends Controller_AdminTemplate {

    protected $user;
    protected $menus;

    public function before() {

        parent::before();
    }

    public function after() {
        $this->template->menutop = View::factory('templates/menutop')->bind('menus', $this->menus)->set('controller', 'user');
        $oSM = New Model_menus();

        parent::after();
    }

    public function action_index() {
        $this->template->content = View::factory('admin/config/config');
    }

    public function action_tipos() {


        $this->template->scripts = array(
            //'media/js/select-chain.js', 
            //'static/js/libs/select2/select2.min.js', 
            'static/js/libs/DataTables/extensions/ColVis/js/dataTables.colVis.min.js',
            'static/js/libs/DataTables/jquery.dataTables.min.js',
                //'media/js/jquery.tablesorter.min.js'
        );
        $this->template->styles = array(
            // 'static/css/theme-1/libs/select2/select2.css' => 'all',
            'static/css/theme-1/libs/DataTables/extensions/dataTables.colVis.css' => 'all',
            'static/css/theme-1/libs/DataTables/jquery.dataTables.css' => 'all'
        );
        $tipos = ORM::factory('tipos')->where('activo', '=', 1)->find_all();

        $this->template->content = View::factory('admin/config/tipo_documentos')
                ->bind('tipos', $tipos);
    }

    //nuevo tipo y edicion de tipos
    public function action_tipo($id = '') {
        $tipo_doc = array();
        if ($id != '') {
            $tipo = ORM::factory('tipos')->where('id', '=', $id)->find();
            if ($tipo->loaded()) {
                $tipo_doc = array(
                    'id' => $tipo->id,
                    'tipo' => $tipo->tipo,
                    'plural' => $tipo->plural,
                    'abreviatura' => $tipo->abreviatura,
                    'action' => $tipo->action,
                    'via' => $tipo->via,
                    'cite_tipo' => $tipo->cite_tipo,
                    'cite_propio' => $tipo->cite_propio,
                    'cite' => $tipo->cite,
                    'template' => $tipo->template,
                    'template_via' => $tipo->template_via,
                    'template_via2' => $tipo->template_via2,
                );
            }
        }
        if (isset($_POST['submit'])) {
            $documento = ORM::factory('tipos', $_POST['id']);

            $documento->tipo = $_POST['tipo'];
            $documento->plural = $_POST['plural'];
            $documento->abreviatura = $_POST['abreviatura'];
            $documento->action = $_POST['action'];
            $documento->save();
            var_dump($_POST);
        }
        $this->template->scripts = array('media/js/select-chain.js', 'static/js/libs/select2/select2.min.js');
        $this->template->styles = array('static/css/theme-1/libs/select2/select2.css' => 'all');

        $tipos = ORM::factory('tipos')->find_all();
        $this->template->content = View::factory('admin/config/tipo_config')
                ->bind('tipo', $tipo_doc);
    }

}

?>
