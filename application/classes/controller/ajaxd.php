<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Ajaxd extends Controller {

    protected $user;

    public function before() {
        $auth = Auth::instance();
        //si el usuario esta logeado entocnes mostramos el menu
        if ($auth->logged_in()) {
            $session = Session::instance();
            $this->user = $session->get('auth_user');
            parent::before();
        } else {
            $this->request->redirect('/login');
        }
    }

    public function action_documentosjson($user) {
        // $this->view->disable();
        //$user = '150';
        $esql = "SELECT d.id,d.nombre_destinatario,d.cargo_destinatario,d.nombre_remitente,d.cargo_remitente,d.estado,
                d.referencia,d.nur,d.cite_original,DATE_FORMAT(d.fecha_creacion,'%d/%m/%Y %H:%i:%s') as fecha,fecha_creacion,t.tipo ,d.original
                FROM documentos d 
                INNER JOIN tipos t ON d.id_tipo=t.id
                WHERE id_user='$user'
                ORDER BY fecha_creacion DESC";

        $query = "SELECT * FROM ( " . $esql . " ) as d";
        $pagenum = $_GET['pagenum'];
        $pagesize = $_GET['pagesize'];
        $start = $pagenum * $pagesize;

        $query = "SELECT * FROM ( " . $esql . " ) as d LIMIT $start, $pagesize";

        $sql = "SELECT COUNT(*) as found_rows FROM ( " . $esql . " ) as d ";
        $mDocumentos = new Model_Documentos();
        $result = $mDocumentos->ejecutarsql_array($sql);
        $total_rows = $result[0]['found_rows'];
        //filter data
        $filterquery = "";

        // filter data.
        if (isset($_GET['filterscount'])) {
            $filterscount = $_GET['filterscount'];

            if ($filterscount > 0) {
                $where = " WHERE (";
                $tmpdatafield = "";
                $tmpfilteroperator = "";
                for ($i = 0; $i < $filterscount; $i++) {
                    // get the filter's value.
                    $filtervalue = $_GET["filtervalue" . $i];
                    // get the filter's condition.
                    $filtercondition = $_GET["filtercondition" . $i];
                    // get the filter's column.
                    $filterdatafield = $_GET["filterdatafield" . $i];
                    // get the filter's operator.
                    $filteroperator = $_GET["filteroperator" . $i];

                    if ($tmpdatafield == "") {
                        $tmpdatafield = $filterdatafield;
                    } else if ($tmpdatafield <> $filterdatafield) {
                        $where .= ")AND(";
                    } else if ($tmpdatafield == $filterdatafield) {
                        if ($tmpfilteroperator == 0) {
                            $where .= " AND ";
                        } else
                            $where .= " OR ";
                    }

                    // build the "WHERE" clause depending on the filter's condition, value and datafield.
                    switch ($filtercondition) {
                        case "NOT_EMPTY":
                        case "NOT_NULL":
                            $where .= " " . $filterdatafield . " NOT LIKE '" . "" . "'";
                            break;
                        case "EMPTY":
                        case "NULL":
                            $where .= " " . $filterdatafield . " LIKE '" . "" . "'";
                            break;
                        case "CONTAINS_CASE_SENSITIVE":
                            $where .= " BINARY  " . $filterdatafield . " LIKE '%" . $filtervalue . "%'";
                            break;
                        case "CONTAINS":
                            $where .= " " . $filterdatafield . " LIKE '%" . $filtervalue . "%'";
                            break;
                        case "DOES_NOT_CONTAIN_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " NOT LIKE '%" . $filtervalue . "%'";
                            break;
                        case "DOES_NOT_CONTAIN":
                            $where .= " " . $filterdatafield . " NOT LIKE '%" . $filtervalue . "%'";
                            break;
                        case "EQUAL_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " = '" . $filtervalue . "'";
                            break;
                        case "EQUAL":
                            $where .= " " . $filterdatafield . " = '" . $filtervalue . "'";
                            break;
                        case "NOT_EQUAL_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " <> '" . $filtervalue . "'";
                            break;
                        case "NOT_EQUAL":
                            $where .= " " . $filterdatafield . " <> '" . $filtervalue . "'";
                            break;
                        case "GREATER_THAN":
                            $where .= " " . $filterdatafield . " > '" . $filtervalue . "'";
                            break;
                        case "LESS_THAN":
                            $where .= " " . $filterdatafield . " < '" . $filtervalue . "'";
                            break;
                        case "GREATER_THAN_OR_EQUAL":
                            $where .= " " . $filterdatafield . " >= '" . $filtervalue . "'";
                            break;
                        case "LESS_THAN_OR_EQUAL":
                            $where .= " " . $filterdatafield . " <= '" . $filtervalue . "'";
                            break;
                        case "STARTS_WITH_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " LIKE '" . $filtervalue . "%'";
                            break;
                        case "STARTS_WITH":
                            $where .= " " . $filterdatafield . " LIKE '" . $filtervalue . "%'";
                            break;
                        case "ENDS_WITH_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " LIKE '%" . $filtervalue . "'";
                            break;
                        case "ENDS_WITH":
                            $where .= " " . $filterdatafield . " LIKE '%" . $filtervalue . "'";
                            break;
                    }

                    if ($i == $filterscount - 1) {
                        $where .= ")";
                    }

                    $tmpfilteroperator = $filteroperator;
                    $tmpdatafield = $filterdatafield;
                }
                // build the query.
                $query = "SELECT * FROM ( " . $esql . ") as d " . $where;
                $filterquery = $query;
                $result = mysql_query($query) or die("SQL Error 1: " . mysql_error());
                $sql = "SELECT FOUND_ROWS() AS found_rows;";
                $rows = mysql_query($sql);
                $rows = mysql_fetch_assoc($rows);
                $new_total_rows = $rows['found_rows'];
                $query = "SELECT * FROM (" . $esql . ") as d " . $where . " LIMIT $start, $pagesize";
                $total_rows = $new_total_rows;
            }
        }
        //sort data
        if (isset($_GET['sortdatafield'])) {

            $sortfield = $_GET['sortdatafield'];
            $sortorder = $_GET['sortorder'];

            if ($sortorder != '') {
                if ($_GET['filterscount'] == 0) {
                    if ($sortorder == "desc") {
                        $query = "SELECT * FROM (" . $esql . ") as d ORDER BY" . " " . $sortfield . " DESC LIMIT $start, $pagesize";
                    } else if ($sortorder == "asc") {
                        $query = "SELECT * FROM (" . $esql . ") as d ORDER BY" . " " . $sortfield . " ASC LIMIT $start, $pagesize";
                    }
                } else {
                    if ($sortorder == "desc") {
                        $filterquery .= " ORDER BY" . " " . $sortfield . " DESC LIMIT $start, $pagesize";
                    } else if ($sortorder == "asc") {
                        $filterquery .= " ORDER BY" . " " . $sortfield . " ASC LIMIT $start, $pagesize";
                    }
                    $query = $filterquery;
                }
            }
        }
        //ejecucion de consulta        
        $result = $mDocumentos->ejecutarsql_array($query);

        $orders = null;
        foreach ($result as $row) {
            if ($row['estado'] == 1) {
                $link = '<a href="/route/trace/?hr=' . $row['nur'] . '" title="Derivado: Ver seguimiento" class="text-xl text-success"><i class="md md- md-verified-user "></i></a>';
                $link .= '<a href="/print/hr/?code=' . $row['nur'] . '&p=1" title="Imprimir hoja de ruta" class="text-xl text-primary"><i class="md md-print "></i></a>';
            }

            if (strlen($row['nur']) < 2 && $row['estado'] == 0) {
                $link = '<a href="/document/asignar/' . $row['id'] . '" title="Asignar Hoja de Ruta al documento" class="text-xl text-accent-light"><i class="md md-class md-2x"></i></a>';
            }
            if (strlen($row['nur']) > 1 && $row['estado'] == 0) {
                $link = '<a href="/route/deriv/?hr=' . $row['nur'] . '" title="Derivar Hoja de Ruta" class="text-xl text-warning"><i class="md md-play-circle-outline"></i></a>';
            }

            $edit='<a href="/documento/edit/' . $row['id'] . '" class="text-xl text-primary-dark"  title="Editar ' . $row['tipo'] . '"><i class="md md-mode-edit"></i></a><a href="/plantilla/word/' . $row['id'] . '" class="text-xl text-primary-dark"  title="Descargar plantilla ' . $row['tipo'] . '" ><i class="fa fa-file-word-o"></i></a>';
            
            $orders[] = array(
                //'id' => "/documento/edit/" . $row['id'],
                'id' => $row['id'],
                'idd' => $row['id'],
                'nur' => '<a href="/route/deriv/?hr=' . $row['nur'] . '" title="Derivar Hoja de Ruta" class="text-primary-dark">' . $row['nur'] . '</a>',
                'cite_original' => $row['cite_original'],
                'tipo' => $row['tipo'],
                'nombre_destinatario' => $row['nombre_destinatario'],
                'cargo_destinatario' => $row['cargo_destinatario'],
                 'institucion_destinatario' => $row['institucion_destinatario'],
                 'institucion_remitente' => $row['institucion_remitente'],
                'nombre_remitente' => $row['nombre_remitente'],
                'cargo_remitente' => $row['cargo_remitente'],
                'referencia' => $row['referencia'],
                'fecha_creacion' => $row['fecha_creacion'],
                'estado' => $row['estado'],
                'edit' => $edit,
                'link' => $link,
            );
        }
        $data[] = array(
            'TotalRows' => $total_rows,
            'Rows' => $orders
        );
        //echo $query;
        echo json_encode($data);

        // echo $query;
    }

    public function action_ventanillajson($user) {
        // $this->view->disable();
        //$user = '150';
        $esql = "SELECT d.id,CONCAT(d.nombre_destinatario,'<br><b>',d.cargo_destinatario,'</b>') as destinatario
                ,CONCAT(d.nombre_remitente,'<br><b>',d.cargo_remitente,'<br><span class=\"text-primary-dark\">',d.institucion_remitente,'</span></b>') as remitente,d.estado,
                d.referencia,d.nur,d.cite_original,DATE_FORMAT(d.fecha_creacion,'%d/%m/%Y %H:%i:%s') as fecha,fecha_creacion,d.original
                FROM documentos d                
                WHERE id_user='$user'
                ORDER BY fecha_creacion DESC";
        $query = "SELECT * FROM ( " . $esql . " ) as d";
        $pagenum = $_GET['pagenum'];
        $pagesize = $_GET['pagesize'];
        $start = $pagenum * $pagesize;

        $query = "SELECT * FROM ( " . $esql . " ) as d LIMIT $start, $pagesize";

        $sql = "SELECT COUNT(*) as found_rows FROM ( " . $esql . " ) as d ";
        $mDocumentos = new Model_Documentos();
        $result = $mDocumentos->ejecutarsql_array($sql);
        $total_rows = $result[0]['found_rows'];
        //filter data
        $filterquery = "";

        // filter data.
        if (isset($_GET['filterscount'])) {
            $filterscount = $_GET['filterscount'];

            if ($filterscount > 0) {
                $where = " WHERE (";
                $tmpdatafield = "";
                $tmpfilteroperator = "";
                for ($i = 0; $i < $filterscount; $i++) {
                    // get the filter's value.
                    $filtervalue = $_GET["filtervalue" . $i];
                    // get the filter's condition.
                    $filtercondition = $_GET["filtercondition" . $i];
                    // get the filter's column.
                    $filterdatafield = $_GET["filterdatafield" . $i];
                    // get the filter's operator.
                    $filteroperator = $_GET["filteroperator" . $i];

                    if ($tmpdatafield == "") {
                        $tmpdatafield = $filterdatafield;
                    } else if ($tmpdatafield <> $filterdatafield) {
                        $where .= ")AND(";
                    } else if ($tmpdatafield == $filterdatafield) {
                        if ($tmpfilteroperator == 0) {
                            $where .= " AND ";
                        } else
                            $where .= " OR ";
                    }

                    // build the "WHERE" clause depending on the filter's condition, value and datafield.
                    switch ($filtercondition) {
                        case "NOT_EMPTY":
                        case "NOT_NULL":
                            $where .= " " . $filterdatafield . " NOT LIKE '" . "" . "'";
                            break;
                        case "EMPTY":
                        case "NULL":
                            $where .= " " . $filterdatafield . " LIKE '" . "" . "'";
                            break;
                        case "CONTAINS_CASE_SENSITIVE":
                            $where .= " BINARY  " . $filterdatafield . " LIKE '%" . $filtervalue . "%'";
                            break;
                        case "CONTAINS":
                            $where .= " " . $filterdatafield . " LIKE '%" . $filtervalue . "%'";
                            break;
                        case "DOES_NOT_CONTAIN_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " NOT LIKE '%" . $filtervalue . "%'";
                            break;
                        case "DOES_NOT_CONTAIN":
                            $where .= " " . $filterdatafield . " NOT LIKE '%" . $filtervalue . "%'";
                            break;
                        case "EQUAL_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " = '" . $filtervalue . "'";
                            break;
                        case "EQUAL":
                            $where .= " " . $filterdatafield . " = '" . $filtervalue . "'";
                            break;
                        case "NOT_EQUAL_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " <> '" . $filtervalue . "'";
                            break;
                        case "NOT_EQUAL":
                            $where .= " " . $filterdatafield . " <> '" . $filtervalue . "'";
                            break;
                        case "GREATER_THAN":
                            $where .= " " . $filterdatafield . " > '" . $filtervalue . "'";
                            break;
                        case "LESS_THAN":
                            $where .= " " . $filterdatafield . " < '" . $filtervalue . "'";
                            break;
                        case "GREATER_THAN_OR_EQUAL":
                            $where .= " " . $filterdatafield . " >= '" . $filtervalue . "'";
                            break;
                        case "LESS_THAN_OR_EQUAL":
                            $where .= " " . $filterdatafield . " <= '" . $filtervalue . "'";
                            break;
                        case "STARTS_WITH_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " LIKE '" . $filtervalue . "%'";
                            break;
                        case "STARTS_WITH":
                            $where .= " " . $filterdatafield . " LIKE '" . $filtervalue . "%'";
                            break;
                        case "ENDS_WITH_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " LIKE '%" . $filtervalue . "'";
                            break;
                        case "ENDS_WITH":
                            $where .= " " . $filterdatafield . " LIKE '%" . $filtervalue . "'";
                            break;
                    }

                    if ($i == $filterscount - 1) {
                        $where .= ")";
                    }

                    $tmpfilteroperator = $filteroperator;
                    $tmpdatafield = $filterdatafield;
                }
                // build the query.
                $query = "SELECT * FROM ( " . $esql . ") as d " . $where;
                $filterquery = $query;
                $result = mysql_query($query) or die("SQL Error 1: " . mysql_error());
                $sql = "SELECT FOUND_ROWS() AS found_rows;";
                $rows = mysql_query($sql);
                $rows = mysql_fetch_assoc($rows);
                $new_total_rows = $rows['found_rows'];
                $query = "SELECT * FROM (" . $esql . ") as d " . $where . " LIMIT $start, $pagesize";
                $total_rows = $new_total_rows;
            }
        }
        //sort data
        if (isset($_GET['sortdatafield'])) {

            $sortfield = $_GET['sortdatafield'];
            $sortorder = $_GET['sortorder'];

            if ($sortorder != '') {
                if ($_GET['filterscount'] == 0) {
                    if ($sortorder == "desc") {
                        $query = "SELECT * FROM (" . $esql . ") as d ORDER BY" . " " . $sortfield . " DESC LIMIT $start, $pagesize";
                    } else if ($sortorder == "asc") {
                        $query = "SELECT * FROM (" . $esql . ") as d ORDER BY" . " " . $sortfield . " ASC LIMIT $start, $pagesize";
                    }
                } else {
                    if ($sortorder == "desc") {
                        $filterquery .= " ORDER BY" . " " . $sortfield . " DESC LIMIT $start, $pagesize";
                    } else if ($sortorder == "asc") {
                        $filterquery .= " ORDER BY" . " " . $sortfield . " ASC LIMIT $start, $pagesize";
                    }
                    $query = $filterquery;
                }
            }
        }
        //ejecucion de consulta        
        $result = $mDocumentos->ejecutarsql_array($query);

        $orders = null;
        foreach ($result as $row) {
            if ($row['estado'] == 1) {
                $link = '<a href="/route/trace/?hr=' . $row['nur'] . '" title="Derivado: Ver seguimiento" target="_blank" class="text-xl text-success"><i class="md md- md-verified-user "></i></a>';
                $edit='';
            } else {

                $edit='<a href="/ventanilla/edit/' . $row['id'] . '" class="text-xl text-primary-dark"  title="Editar ' . $row['tipo'] . '" ><i class="md md-mode-edit"><i/></a>';
                $link = '<a href="/route/deriv/?hr=' . $row['nur'] . '" title="Derivar Hoja de Ruta" class="text-xl text-warning"><i class="md md-play-circle-outline"></i></a>';
            }

            $orders[] = array(
                //'id' => "/documento/edit/" . $row['id'],
                'id' => $row['id'],
                'idd' => $row['id'],
                'nur' => '<a href="/route/deriv/?hr=' . $row['nur'] . '" title="Derivar Hoja de Ruta" class="text-primary-dark">' . $row['nur'] . '</a>',
                'cite_original' => $row['cite_original'],
                //'tipo' => $row['tipo'],
                'destinatario' => $row['destinatario'],
                //'cargo_destinatario' => $row['cargo_destinatario'],
                // 'institucion_destinatario' => $row['institucion_destinatario'],
                // 'institucion_remitente' => $row['institucion_remitente'],
                'remitente' => $row['remitente'],
                // 'cargo_remitente' => $row['cargo_remitente'],
                'referencia' => $row['referencia'],
                'fecha_creacion' => $row['fecha_creacion'],
                'estado' => $row['estado'],
                //'edit' => '<a href="/ventanilla/edit/' . $row['id'] . '" class="text-xl text-primary-dark"  title="Editar ' . $row['tipo'] . '" ><i class="md md-mode-edit"><i/></a>',
                'edit' => $edit,
                'link' => $link,
            );
        }
        $data[] = array(
            'TotalRows' => $total_rows,
            'Rows' => $orders
        );
        //echo $query;
        echo json_encode($data);

        // echo $query;
    }
    public function action_ventanillajsonp($user) {
        // $this->view->disable();
        //$user = '150';
        $esql = "SELECT d.id,CONCAT(d.nombre_destinatario,'<br><b>',d.cargo_destinatario,'</b>') as destinatario
                ,CONCAT(d.nombre_remitente,'<br><b>',d.cargo_remitente,'<br><span class=\"text-primary-dark\">',d.institucion_remitente,'</span></b>') as remitente,d.estado,
                d.referencia,d.nur,d.cite_original,DATE_FORMAT(d.fecha_creacion,'%d/%m/%Y %H:%i:%s') as fecha,fecha_creacion,d.original
                FROM documentos d                
                WHERE id_user='$user' and estado='0'
                ORDER BY fecha_creacion DESC";
        $query = "SELECT * FROM ( " . $esql . " ) as d";
        $pagenum = $_GET['pagenum'];
        $pagesize = $_GET['pagesize'];
        $start = $pagenum * $pagesize;

        $query = "SELECT * FROM ( " . $esql . " ) as d LIMIT $start, $pagesize";

        $sql = "SELECT COUNT(*) as found_rows FROM ( " . $esql . " ) as d ";
        $mDocumentos = new Model_Documentos();
        $result = $mDocumentos->ejecutarsql_array($sql);
        $total_rows = $result[0]['found_rows'];
        //filter data
        $filterquery = "";

        // filter data.
        if (isset($_GET['filterscount'])) {
            $filterscount = $_GET['filterscount'];

            if ($filterscount > 0) {
                $where = " WHERE (";
                $tmpdatafield = "";
                $tmpfilteroperator = "";
                for ($i = 0; $i < $filterscount; $i++) {
                    // get the filter's value.
                    $filtervalue = $_GET["filtervalue" . $i];
                    // get the filter's condition.
                    $filtercondition = $_GET["filtercondition" . $i];
                    // get the filter's column.
                    $filterdatafield = $_GET["filterdatafield" . $i];
                    // get the filter's operator.
                    $filteroperator = $_GET["filteroperator" . $i];

                    if ($tmpdatafield == "") {
                        $tmpdatafield = $filterdatafield;
                    } else if ($tmpdatafield <> $filterdatafield) {
                        $where .= ")AND(";
                    } else if ($tmpdatafield == $filterdatafield) {
                        if ($tmpfilteroperator == 0) {
                            $where .= " AND ";
                        } else
                            $where .= " OR ";
                    }

                    // build the "WHERE" clause depending on the filter's condition, value and datafield.
                    switch ($filtercondition) {
                        case "NOT_EMPTY":
                        case "NOT_NULL":
                            $where .= " " . $filterdatafield . " NOT LIKE '" . "" . "'";
                            break;
                        case "EMPTY":
                        case "NULL":
                            $where .= " " . $filterdatafield . " LIKE '" . "" . "'";
                            break;
                        case "CONTAINS_CASE_SENSITIVE":
                            $where .= " BINARY  " . $filterdatafield . " LIKE '%" . $filtervalue . "%'";
                            break;
                        case "CONTAINS":
                            $where .= " " . $filterdatafield . " LIKE '%" . $filtervalue . "%'";
                            break;
                        case "DOES_NOT_CONTAIN_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " NOT LIKE '%" . $filtervalue . "%'";
                            break;
                        case "DOES_NOT_CONTAIN":
                            $where .= " " . $filterdatafield . " NOT LIKE '%" . $filtervalue . "%'";
                            break;
                        case "EQUAL_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " = '" . $filtervalue . "'";
                            break;
                        case "EQUAL":
                            $where .= " " . $filterdatafield . " = '" . $filtervalue . "'";
                            break;
                        case "NOT_EQUAL_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " <> '" . $filtervalue . "'";
                            break;
                        case "NOT_EQUAL":
                            $where .= " " . $filterdatafield . " <> '" . $filtervalue . "'";
                            break;
                        case "GREATER_THAN":
                            $where .= " " . $filterdatafield . " > '" . $filtervalue . "'";
                            break;
                        case "LESS_THAN":
                            $where .= " " . $filterdatafield . " < '" . $filtervalue . "'";
                            break;
                        case "GREATER_THAN_OR_EQUAL":
                            $where .= " " . $filterdatafield . " >= '" . $filtervalue . "'";
                            break;
                        case "LESS_THAN_OR_EQUAL":
                            $where .= " " . $filterdatafield . " <= '" . $filtervalue . "'";
                            break;
                        case "STARTS_WITH_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " LIKE '" . $filtervalue . "%'";
                            break;
                        case "STARTS_WITH":
                            $where .= " " . $filterdatafield . " LIKE '" . $filtervalue . "%'";
                            break;
                        case "ENDS_WITH_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " LIKE '%" . $filtervalue . "'";
                            break;
                        case "ENDS_WITH":
                            $where .= " " . $filterdatafield . " LIKE '%" . $filtervalue . "'";
                            break;
                    }

                    if ($i == $filterscount - 1) {
                        $where .= ")";
                    }

                    $tmpfilteroperator = $filteroperator;
                    $tmpdatafield = $filterdatafield;
                }
                // build the query.
                $query = "SELECT * FROM ( " . $esql . ") as d " . $where;
                $filterquery = $query;
                $result = mysql_query($query) or die("SQL Error 1: " . mysql_error());
                $sql = "SELECT FOUND_ROWS() AS found_rows;";
                $rows = mysql_query($sql);
                $rows = mysql_fetch_assoc($rows);
                $new_total_rows = $rows['found_rows'];
                $query = "SELECT * FROM (" . $esql . ") as d " . $where . " LIMIT $start, $pagesize";
                $total_rows = $new_total_rows;
            }
        }
        //sort data
        if (isset($_GET['sortdatafield'])) {

            $sortfield = $_GET['sortdatafield'];
            $sortorder = $_GET['sortorder'];

            if ($sortorder != '') {
                if ($_GET['filterscount'] == 0) {
                    if ($sortorder == "desc") {
                        $query = "SELECT * FROM (" . $esql . ") as d ORDER BY" . " " . $sortfield . " DESC LIMIT $start, $pagesize";
                    } else if ($sortorder == "asc") {
                        $query = "SELECT * FROM (" . $esql . ") as d ORDER BY" . " " . $sortfield . " ASC LIMIT $start, $pagesize";
                    }
                } else {
                    if ($sortorder == "desc") {
                        $filterquery .= " ORDER BY" . " " . $sortfield . " DESC LIMIT $start, $pagesize";
                    } else if ($sortorder == "asc") {
                        $filterquery .= " ORDER BY" . " " . $sortfield . " ASC LIMIT $start, $pagesize";
                    }
                    $query = $filterquery;
                }
            }
        }
        //ejecucion de consulta        
        $result = $mDocumentos->ejecutarsql_array($query);

        $orders = null;
        foreach ($result as $row) {
            if ($row['estado'] == 1) {
                $link = '<a href="/route/trace/?hr=' . $row['nur'] . '" title="Derivado: Ver seguimiento" target="_blank" class="text-xl text-success"><i class="md md- md-verified-user "></i></a>';
                $edit='';
            } else {

                $edit='<a href="/ventanilla/edit/' . $row['id'] . '" class="text-xl text-primary-dark"  title="Editar ' . $row['tipo'] . '" ><i class="md md-mode-edit"><i/></a>';
                $link = '<a href="/route/deriv/?hr=' . $row['nur'] . '" title="Derivar Hoja de Ruta" class="text-xl text-warning"><i class="md md-play-circle-outline"></i></a>';
            }

            $orders[] = array(
                //'id' => "/documento/edit/" . $row['id'],
                'id' => $row['id'],
                'idd' => $row['id'],
                'nur' => '<a href="/route/deriv/?hr=' . $row['nur'] . '" title="Derivar Hoja de Ruta" class="text-primary-dark">' . $row['nur'] . '</a>',
                'cite_original' => $row['cite_original'],
                //'tipo' => $row['tipo'],
                'destinatario' => $row['destinatario'],
                //'cargo_destinatario' => $row['cargo_destinatario'],
                // 'institucion_destinatario' => $row['institucion_destinatario'],
                // 'institucion_remitente' => $row['institucion_remitente'],
                'remitente' => $row['remitente'],
                // 'cargo_remitente' => $row['cargo_remitente'],
                'referencia' => $row['referencia'],
                'fecha_creacion' => $row['fecha_creacion'],
                'estado' => $row['estado'],
                //'edit' => '<a href="/ventanilla/edit/' . $row['id'] . '" class="text-xl text-primary-dark"  title="Editar ' . $row['tipo'] . '" ><i class="md md-mode-edit"><i/></a>',
                'edit' => $edit,
                'link' => $link,
            );
        }
        $data[] = array(
            'TotalRows' => $total_rows,
            'Rows' => $orders
        );
        //echo $query;
        echo json_encode($data);

        // echo $query;
    }

    public function action_historial() {
        $o_reports = New Model_documentos();
        $documentos = $o_reports->historial();
        $result = array();
        foreach ($documentos as $d) {
            $result[] = array($d['fecha'], $d['a']);
        }
        echo json_encode($result, JSON_NUMERIC_CHECK);
    }

    public function action_jsonpendientes() {
        $mSeguimiento = new Model_Seguimiento();
        $pendientes = $mSeguimiento->pendientesTodo();
        $aPendientes = array();
        $aPendientes[0]['suma'] = 0;
        $aPendientes[0]['id'] = 0;
        $aPendientes[0]['oficina'] = '[TODAS LAS OFICINAS DE LA ENTIDAD]';
        $aPendientes[0]['no_recibido'] = 0;
        $aPendientes[0]['pendiente'] = 0;
        foreach ($pendientes as $p) {
            $aPendientes[$p['id']]['suma'] = 1;
            $aPendientes[$p['id']]['id'] = $p['id'];
            $aPendientes[$p['id']]['oficina'] = $p['oficina'];
            $aPendientes[$p['id']]['no_recibido'] = 0;
            $aPendientes[$p['id']]['pendiente'] = 0;
        }
        foreach ($pendientes as $p) {
            $aPendientes[$p['id']][$p['estado']] = $p['cantidad'];
        }
        echo json_encode(array_values($aPendientes));
    }

    public function action_jsonpusers() {
        $mSeguimiento = new Model_Seguimiento();
        $pendientes = $mSeguimiento->pendientesUsersTodo();
        $aPendientes = array();
        foreach ($pendientes as $p) {
            $aPendientes[$p['id']]['suma'] = 1;
            $aPendientes[$p['id']]['id'] = $p['id'];
            $aPendientes[$p['id']]['nombre'] = $p['nombre'];
            $aPendientes[$p['id']]['cargo'] = $p['cargo'];
            $aPendientes[$p['id']]['ultimo_ingreso'] = $p['ultimo_ingreso'];
            $aPendientes[$p['id']]['oficina'] = $p['oficina'];
            $aPendientes[$p['id']]['no_recibido'] = 0;
            $aPendientes[$p['id']]['pendiente'] = 0;
        }
        foreach ($pendientes as $p) {
            $aPendientes[$p['id']][$p['estado']] = $p['cantidad'];
        }
        echo json_encode(array_values($aPendientes));
    }

    public function action_jsonpusersId($id) {
        $mSeguimiento = new Model_Seguimiento();
        $pendientes = $mSeguimiento->jsonpendientesUser($id);
        echo json_encode($pendientes, JSON_NUMERIC_CHECK);
    }

    public function action_jsonpusers_old($id) {
        $mSeguimiento = new Model_Seguimiento();
        if ($id != 0) {
            $pendientes = $mSeguimiento->pendientesUsers($id);
        } else {
            $pendientes = $mSeguimiento->pendientesUsersTodo($id);
        }
        $aPendientes = array();
        foreach ($pendientes as $p) {
            $aPendientes[$p['id']]['suma'] = 1;
            $aPendientes[$p['id']]['id'] = $p['id'];
            $aPendientes[$p['id']]['nombre'] = $p['nombre'];
            $aPendientes[$p['id']]['cargo'] = $p['cargo'];
            $aPendientes[$p['id']]['ultimo_ingreso'] = $p['ultimo_ingreso'];
            $aPendientes[$p['id']]['oficina'] = $p['oficina'];
            $aPendientes[$p['id']]['no_recibido'] = 0;
            $aPendientes[$p['id']]['pendiente'] = 0;
        }
        foreach ($pendientes as $p) {
            $aPendientes[$p['id']][$p['estado']] = $p['cantidad'];
        }
        echo json_encode(array_values($aPendientes));
    }

//documentos generados por oficina    



    public function action_jsondocumentos() {
        $oficina = $_GET['oficina'];
        $oDocumentos = new Model_Documentos();
        $documentos = $oDocumentos->tdocumentos($oficina);
        echo json_encode($documentos);
    }

    public function action_jqwdocumentos() {
        $oficina = $_GET['oficina'];
        $tipo = $_GET['tipo'];
        $query = "SELECT * FROM (
                SELECT d.nombre_destinatario,d.cargo_destinatario,d.nombre_remitente,d.cargo_remitente,
                d.referencia,d.nur,d.cite_original,u.nombre,u.cargo,d.fecha_creacion 
                FROM documentos d INNER JOIN users u ON d.id_user=u.id
                WHERE d.id_oficina='$oficina'
                AND d.id_tipo='$tipo'
                ) as d";


        $pagenum = $_GET['pagenum'];
        $pagesize = $_GET['pagesize'];
        $start = $pagenum * $pagesize;
        $query = "SELECT SQL_CALC_FOUND_ROWS * FROM (
                     SELECT d.nombre_destinatario,d.cargo_destinatario,d.nombre_remitente,d.cargo_remitente,
                d.referencia,d.nur,d.cite_original,u.nombre,u.cargo,d.fecha_creacion 
                FROM documentos d INNER JOIN users u ON d.id_user=u.id
                WHERE d.id_oficina='$oficina'
                AND d.id_tipo='$tipo'
                ) as d LIMIT $start, $pagesize";
        $result = mysql_query($query) or die("SQL Error 1: " . mysql_error());
        $sql = "SELECT FOUND_ROWS() AS found_rows;";
        $rows = mysql_query($sql);
        $rows = mysql_fetch_assoc($rows);
        $total_rows = $rows['found_rows'];
        $filterquery = "";

        // filter data.
        if (isset($_GET['filterscount'])) {
            $filterscount = $_GET['filterscount'];

            if ($filterscount > 0) {
                $where = " WHERE (";
                $tmpdatafield = "";
                $tmpfilteroperator = "";
                for ($i = 0; $i < $filterscount; $i++) {
                    // get the filter's value.
                    $filtervalue = $_GET["filtervalue" . $i];
                    // get the filter's condition.
                    $filtercondition = $_GET["filtercondition" . $i];
                    // get the filter's column.
                    $filterdatafield = $_GET["filterdatafield" . $i];
                    // get the filter's operator.
                    $filteroperator = $_GET["filteroperator" . $i];

                    if ($tmpdatafield == "") {
                        $tmpdatafield = $filterdatafield;
                    } else if ($tmpdatafield <> $filterdatafield) {
                        $where .= ")AND(";
                    } else if ($tmpdatafield == $filterdatafield) {
                        if ($tmpfilteroperator == 0) {
                            $where .= " AND ";
                        } else
                            $where .= " OR ";
                    }

                    // build the "WHERE" clause depending on the filter's condition, value and datafield.
                    switch ($filtercondition) {
                        case "NOT_EMPTY":
                        case "NOT_NULL":
                            $where .= " " . $filterdatafield . " NOT LIKE '" . "" . "'";
                            break;
                        case "EMPTY":
                        case "NULL":
                            $where .= " " . $filterdatafield . " LIKE '" . "" . "'";
                            break;
                        case "CONTAINS_CASE_SENSITIVE":
                            $where .= " BINARY  " . $filterdatafield . " LIKE '%" . $filtervalue . "%'";
                            break;
                        case "CONTAINS":
                            $where .= " " . $filterdatafield . " LIKE '%" . $filtervalue . "%'";
                            break;
                        case "DOES_NOT_CONTAIN_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " NOT LIKE '%" . $filtervalue . "%'";
                            break;
                        case "DOES_NOT_CONTAIN":
                            $where .= " " . $filterdatafield . " NOT LIKE '%" . $filtervalue . "%'";
                            break;
                        case "EQUAL_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " = '" . $filtervalue . "'";
                            break;
                        case "EQUAL":
                            $where .= " " . $filterdatafield . " = '" . $filtervalue . "'";
                            break;
                        case "NOT_EQUAL_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " <> '" . $filtervalue . "'";
                            break;
                        case "NOT_EQUAL":
                            $where .= " " . $filterdatafield . " <> '" . $filtervalue . "'";
                            break;
                        case "GREATER_THAN":
                            $where .= " " . $filterdatafield . " > '" . $filtervalue . "'";
                            break;
                        case "LESS_THAN":
                            $where .= " " . $filterdatafield . " < '" . $filtervalue . "'";
                            break;
                        case "GREATER_THAN_OR_EQUAL":
                            $where .= " " . $filterdatafield . " >= '" . $filtervalue . "'";
                            break;
                        case "LESS_THAN_OR_EQUAL":
                            $where .= " " . $filterdatafield . " <= '" . $filtervalue . "'";
                            break;
                        case "STARTS_WITH_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " LIKE '" . $filtervalue . "%'";
                            break;
                        case "STARTS_WITH":
                            $where .= " " . $filterdatafield . " LIKE '" . $filtervalue . "%'";
                            break;
                        case "ENDS_WITH_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " LIKE '%" . $filtervalue . "'";
                            break;
                        case "ENDS_WITH":
                            $where .= " " . $filterdatafield . " LIKE '%" . $filtervalue . "'";
                            break;
                    }

                    if ($i == $filterscount - 1) {
                        $where .= ")";
                    }

                    $tmpfilteroperator = $filteroperator;
                    $tmpdatafield = $filterdatafield;
                }
                // build the query.
                $query = "SELECT * FROM (
                     SELECT d.nombre_destinatario,d.cargo_destinatario,d.nombre_remitente,d.cargo_remitente,
                d.referencia,d.nur,d.cite_original,u.nombre,u.cargo,d.fecha_creacion 
                FROM documentos d INNER JOIN users u ON d.id_user=u.id
                WHERE d.id_oficina='$oficina'
                AND d.id_tipo='$tipo'
                ) as d " . $where;
                $filterquery = $query;
                $result = mysql_query($query) or die("SQL Error 1: " . mysql_error());
                $sql = "SELECT FOUND_ROWS() AS found_rows;";
                $rows = mysql_query($sql);
                $rows = mysql_fetch_assoc($rows);
                $new_total_rows = $rows['found_rows'];
                $query = "SELECT * FROM (
                    SELECT d.nombre_destinatario,d.cargo_destinatario,d.nombre_remitente,d.cargo_remitente,
                d.referencia,d.nur,d.cite_original,u.nombre,u.cargo,d.fecha_creacion 
                FROM documentos d INNER JOIN users u ON d.id_user=u.id
                WHERE d.id_oficina='$oficina'
                AND d.id_tipo='$tipo'
                ) as d " . $where . " LIMIT $start, $pagesize";
                $total_rows = $new_total_rows;
            }
        }

        if (isset($_GET['sortdatafield'])) {

            $sortfield = $_GET['sortdatafield'];
            $sortorder = $_GET['sortorder'];

            if ($sortorder != '') {
                if ($_GET['filterscount'] == 0) {
                    if ($sortorder == "desc") {
                        $query = "SELECT * FROM (
                     SELECT d.nombre_destinatario,d.cargo_destinatario,d.nombre_remitente,d.cargo_remitente,
                d.referencia,d.nur,d.cite_original,u.nombre,u.cargo,d.fecha_creacion 
                FROM documentos d INNER JOIN users u ON d.id_user=u.id
                WHERE d.id_oficina='$oficina'
                AND d.id_tipo='$tipo'
                ) as d ORDER BY" . " " . $sortfield . " DESC LIMIT $start, $pagesize";
                    } else if ($sortorder == "asc") {
                        $query = "SELECT * FROM (
                     SELECT d.nombre_destinatario,d.cargo_destinatario,d.nombre_remitente,d.cargo_remitente,
                d.referencia,d.nur,d.cite_original,u.nombre,u.cargo,d.fecha_creacion 
                FROM documentos d INNER JOIN users u ON d.id_user=u.id
                WHERE d.id_oficina='$oficina'
                AND d.id_tipo='$tipo'
                ) as d ORDER BY" . " " . $sortfield . " ASC LIMIT $start, $pagesize";
                    }
                } else {
                    if ($sortorder == "desc") {
                        $filterquery .= " ORDER BY" . " " . $sortfield . " DESC LIMIT $start, $pagesize";
                    } else if ($sortorder == "asc") {
                        $filterquery .= " ORDER BY" . " " . $sortfield . " ASC LIMIT $start, $pagesize";
                    }
                    $query = $filterquery;
                }
            }
        }
        //conexion
        $mDocumentos = new Model_Documentos();
        $result = $mDocumentos->ejecutarsql($query);
        // echo json_encode($documentos);
        //$result = mysql_query($query) or die("SQL Error 1: " . mysql_error());
        $orders = null;

        //  echo $query;
        // get data and store in a json array
        foreach ($result as $row) {
            $orders[] = array(
                'nur' => $row['nur'],
                'cite_original' => $row['cite_original'],
                'nombre_destinatario' => $row['nombre_destinatario'],
                'cargo_destinatario' => $row['cargo_destinatario'],
                // 'institucion_destinatario' => $row['institucion_destinatario'],
                // 'institucion_remitente' => $row['institucion_remitente'],
                'nombre_remitente' => $row['nombre_remitente'],
                'cargo_remitente' => $row['cargo_remitente'],
                'referencia' => $row['referencia'],
                'fecha_creacion' => $row['fecha_creacion'],
                // 'estado' => $row['estado'],
                'nombre' => $row['nombre'] . ' / ' . $row['cargo'],
                'cargo' => $row['cargo'],
            );
        }
        $data[] = array(
            'TotalRows' => $total_rows,
            'Rows' => $orders
        );
        echo json_encode($data);
    }

    public function action_vitacora() {
        $query = "SELECT v.id,u.username,u.nombre,u.cargo,v.accion_realizada,v.ip_usuario,v.fecha_hora,u.logins,u.habilitado FROM vitacora v INNER JOIN users u ON v.id_usuario=u.id";


        $pagenum = $_GET['pagenum'];
        $pagesize = $_GET['pagesize'];
        $start = $pagenum * $pagesize;
        $query = "SELECT SQL_CALC_FOUND_ROWS * FROM vitacora v INNER JOIN users u ON v.id_usuario=u.id LIMIT $start, $pagesize";
        $result = mysql_query($query) or die("SQL Error 1: " . mysql_error());
        $sql = "SELECT FOUND_ROWS() AS found_rows;";
        $rows = mysql_query($sql);
        $rows = mysql_fetch_assoc($rows);
        $total_rows = $rows['found_rows'];
        $filterquery = "";

        // filter data.
        if (isset($_GET['filterscount'])) {
            $filterscount = $_GET['filterscount'];

            if ($filterscount > 0) {
                $where = " WHERE (";
                $tmpdatafield = "";
                $tmpfilteroperator = "";
                for ($i = 0; $i < $filterscount; $i++) {
                    // get the filter's value.
                    $filtervalue = $_GET["filtervalue" . $i];
                    // get the filter's condition.
                    $filtercondition = $_GET["filtercondition" . $i];
                    // get the filter's column.
                    $filterdatafield = $_GET["filterdatafield" . $i];
                    // get the filter's operator.
                    $filteroperator = $_GET["filteroperator" . $i];

                    if ($tmpdatafield == "") {
                        $tmpdatafield = $filterdatafield;
                    } else if ($tmpdatafield <> $filterdatafield) {
                        $where .= ")AND(";
                    } else if ($tmpdatafield == $filterdatafield) {
                        if ($tmpfilteroperator == 0) {
                            $where .= " AND ";
                        } else
                            $where .= " OR ";
                    }

                    // build the "WHERE" clause depending on the filter's condition, value and datafield.
                    switch ($filtercondition) {
                        case "NOT_EMPTY":
                        case "NOT_NULL":
                            $where .= " " . $filterdatafield . " NOT LIKE '" . "" . "'";
                            break;
                        case "EMPTY":
                        case "NULL":
                            $where .= " " . $filterdatafield . " LIKE '" . "" . "'";
                            break;
                        case "CONTAINS_CASE_SENSITIVE":
                            $where .= " BINARY  " . $filterdatafield . " LIKE '%" . $filtervalue . "%'";
                            break;
                        case "CONTAINS":
                            $where .= " " . $filterdatafield . " LIKE '%" . $filtervalue . "%'";
                            break;
                        case "DOES_NOT_CONTAIN_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " NOT LIKE '%" . $filtervalue . "%'";
                            break;
                        case "DOES_NOT_CONTAIN":
                            $where .= " " . $filterdatafield . " NOT LIKE '%" . $filtervalue . "%'";
                            break;
                        case "EQUAL_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " = '" . $filtervalue . "'";
                            break;
                        case "EQUAL":
                            $where .= " " . $filterdatafield . " = '" . $filtervalue . "'";
                            break;
                        case "NOT_EQUAL_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " <> '" . $filtervalue . "'";
                            break;
                        case "NOT_EQUAL":
                            $where .= " " . $filterdatafield . " <> '" . $filtervalue . "'";
                            break;
                        case "GREATER_THAN":
                            $where .= " " . $filterdatafield . " > '" . $filtervalue . "'";
                            break;
                        case "LESS_THAN":
                            $where .= " " . $filterdatafield . " < '" . $filtervalue . "'";
                            break;
                        case "GREATER_THAN_OR_EQUAL":
                            $where .= " " . $filterdatafield . " >= '" . $filtervalue . "'";
                            break;
                        case "LESS_THAN_OR_EQUAL":
                            $where .= " " . $filterdatafield . " <= '" . $filtervalue . "'";
                            break;
                        case "STARTS_WITH_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " LIKE '" . $filtervalue . "%'";
                            break;
                        case "STARTS_WITH":
                            $where .= " " . $filterdatafield . " LIKE '" . $filtervalue . "%'";
                            break;
                        case "ENDS_WITH_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " LIKE '%" . $filtervalue . "'";
                            break;
                        case "ENDS_WITH":
                            $where .= " " . $filterdatafield . " LIKE '%" . $filtervalue . "'";
                            break;
                    }

                    if ($i == $filterscount - 1) {
                        $where .= ")";
                    }

                    $tmpfilteroperator = $filteroperator;
                    $tmpdatafield = $filterdatafield;
                }
                // build the query.
                $query = "SELECT v.id,u.username,u.nombre,u.cargo,v.accion_realizada,v.ip_usuario,v.fecha_hora,u.logins,u.habilitado FROM vitacora v INNER JOIN users u ON v.id_usuario=u.id " . $where;
                $filterquery = $query;
                $result = mysql_query($query) or die("SQL Error 1: " . mysql_error());
                $sql = "SELECT FOUND_ROWS() AS found_rows;";
                $rows = mysql_query($sql);
                $rows = mysql_fetch_assoc($rows);
                $new_total_rows = $rows['found_rows'];
                $query = "SELECT v.id,u.username,u.nombre,u.cargo,v.accion_realizada,v.ip_usuario,v.fecha_hora,u.logins,u.habilitado FROM vitacora v INNER JOIN users u ON v.id_usuario=u.id " . $where . " LIMIT $start, $pagesize";
                $total_rows = $new_total_rows;
            }
        }

        if (isset($_GET['sortdatafield'])) {

            $sortfield = $_GET['sortdatafield'];
            $sortorder = $_GET['sortorder'];

            if ($sortorder != '') {
                if ($_GET['filterscount'] == 0) {
                    if ($sortorder == "desc") {
                        $query = "SELECT v.id,u.username,u.nombre,u.cargo,v.accion_realizada,v.ip_usuario,v.fecha_hora,u.logins,u.habilitado FROM vitacora v INNER JOIN users u ON v.id_usuario=u.id ORDER BY" . " " . $sortfield . " DESC LIMIT $start, $pagesize";
                    } else if ($sortorder == "asc") {
                        $query = "SELECT v.id,u.username,u.nombre,u.cargo,v.accion_realizada,v.ip_usuario,v.fecha_hora,u.logins,u.habilitado FROM vitacora v INNER JOIN users u ON v.id_usuario=u.id ORDER BY" . " " . $sortfield . " ASC LIMIT $start, $pagesize";
                    }
                } else {
                    if ($sortorder == "desc") {
                        $filterquery .= " ORDER BY" . " " . $sortfield . " DESC LIMIT $start, $pagesize";
                    } else if ($sortorder == "asc") {
                        $filterquery .= " ORDER BY" . " " . $sortfield . " ASC LIMIT $start, $pagesize";
                    }
                    $query = $filterquery;
                }
            }
        }
        //conexion
        $mDocumentos = new Model_Documentos();
        $result = $mDocumentos->ejecutarsql($query)->as_array();
        // echo json_encode($documentos);
        //$result = mysql_query($query) or die("SQL Error 1: " . mysql_error());
        /*  $orders = null;

          //  echo $query;
          // get data and store in a json array
          foreach ($result as $row) {
          $orders[] = array(
          'nur' => $row['nur'],
          'cite_original' => $row['cite_original'],
          'nombre_destinatario' => $row['nombre_destinatario'],
          'cargo_destinatario' => $row['cargo_destinatario'],
          // 'institucion_destinatario' => $row['institucion_destinatario'],
          // 'institucion_remitente' => $row['institucion_remitente'],
          'nombre_remitente' => $row['nombre_remitente'],
          'cargo_remitente' => $row['cargo_remitente'],
          'referencia' => $row['referencia'],
          'fecha_creacion' => $row['fecha_creacion'],
          // 'estado' => $row['estado'],
          'nombre' => $row['nombre'] . ' / ' . $row['cargo'],
          'cargo' => $row['cargo'],
          );
          } */
        $data[] = array(
            'TotalRows' => $total_rows,
            'Rows' => $result
        );
        echo json_encode($data);
    }

    public function action_explosion() {
        $oficina = $_GET['oficina'];
        $query = "SELECT * FROM (
                SELECT d.nur,d.cite_original,d.nombre_destinatario,d.cargo_destinatario,d.nombre_remitente,d.cargo_remitente,d.referencia,
                d.fecha_creacion,s.a_oficina,s.nombre_receptor,s.cargo_receptor,s.fecha_recepcion,e.estado,DATEDIFF(s.fecha_recepcion,d.fecha_creacion) as d1,DATEDIFF(s.fecha_emision,d.fecha_creacion) as d2 ,DATEDIFF(NOW(),d.fecha_creacion) as d3
                 FROM documentos d INNER JOIN (
                SELECT n.nur FROM nurs n 
                INNER JOIN users u ON n.id_user=u.id
                WHERE u.id_oficina='$oficina' ) AS n ON  d.nur=n.nur
                INNER JOIN seguimiento s ON s.nur=d.nur
                INNER JOIN estados e ON s.estado=e.id
                WHERE s.oficial='1'
                AND s.estado IN (1,2,10)
                AND d.original='1'
                AND d.id_seguimiento='0'
                ) as d";


        $pagenum = $_GET['pagenum'];
        $pagesize = $_GET['pagesize'];
        $start = $pagenum * $pagesize;
        $query = "SELECT SQL_CALC_FOUND_ROWS * FROM (
                SELECT d.nur,d.cite_original,d.nombre_destinatario,d.cargo_destinatario,d.nombre_remitente,d.cargo_remitente,d.referencia,
                d.fecha_creacion,s.a_oficina,s.nombre_receptor,s.cargo_receptor,s.fecha_recepcion,e.estado,DATEDIFF(s.fecha_recepcion,d.fecha_creacion) as d1,DATEDIFF(s.fecha_emision,d.fecha_creacion) as d2 ,DATEDIFF(NOW(),d.fecha_creacion) as d3
                 FROM documentos d INNER JOIN (
                SELECT n.nur FROM nurs n 
                INNER JOIN users u ON n.id_user=u.id
                WHERE u.id_oficina='$oficina' ) AS n ON  d.nur=n.nur
                INNER JOIN seguimiento s ON s.nur=d.nur
                INNER JOIN estados e ON s.estado=e.id
                WHERE s.oficial='1'
                AND s.estado IN (1,2,10)
                AND d.original='1'
                AND d.id_seguimiento='0'
                ) as d LIMIT $start, $pagesize";
        $result = mysql_query($query) or die("SQL Error 1: " . mysql_error());
        $sql = "SELECT FOUND_ROWS() AS found_rows;";
        $rows = mysql_query($sql);
        $rows = mysql_fetch_assoc($rows);
        $total_rows = $rows['found_rows'];
        $filterquery = "";

        // filter data.
        if (isset($_GET['filterscount'])) {
            $filterscount = $_GET['filterscount'];

            if ($filterscount > 0) {
                $where = " WHERE (";
                $tmpdatafield = "";
                $tmpfilteroperator = "";
                for ($i = 0; $i < $filterscount; $i++) {
                    // get the filter's value.
                    $filtervalue = $_GET["filtervalue" . $i];
                    // get the filter's condition.
                    $filtercondition = $_GET["filtercondition" . $i];
                    // get the filter's column.
                    $filterdatafield = $_GET["filterdatafield" . $i];
                    // get the filter's operator.
                    $filteroperator = $_GET["filteroperator" . $i];

                    if ($tmpdatafield == "") {
                        $tmpdatafield = $filterdatafield;
                    } else if ($tmpdatafield <> $filterdatafield) {
                        $where .= ")AND(";
                    } else if ($tmpdatafield == $filterdatafield) {
                        if ($tmpfilteroperator == 0) {
                            $where .= " AND ";
                        } else
                            $where .= " OR ";
                    }

                    // build the "WHERE" clause depending on the filter's condition, value and datafield.
                    switch ($filtercondition) {
                        case "NOT_EMPTY":
                        case "NOT_NULL":
                            $where .= " " . $filterdatafield . " NOT LIKE '" . "" . "'";
                            break;
                        case "EMPTY":
                        case "NULL":
                            $where .= " " . $filterdatafield . " LIKE '" . "" . "'";
                            break;
                        case "CONTAINS_CASE_SENSITIVE":
                            $where .= " BINARY  " . $filterdatafield . " LIKE '%" . $filtervalue . "%'";
                            break;
                        case "CONTAINS":
                            $where .= " " . $filterdatafield . " LIKE '%" . $filtervalue . "%'";
                            break;
                        case "DOES_NOT_CONTAIN_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " NOT LIKE '%" . $filtervalue . "%'";
                            break;
                        case "DOES_NOT_CONTAIN":
                            $where .= " " . $filterdatafield . " NOT LIKE '%" . $filtervalue . "%'";
                            break;
                        case "EQUAL_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " = '" . $filtervalue . "'";
                            break;
                        case "EQUAL":
                            $where .= " " . $filterdatafield . " = '" . $filtervalue . "'";
                            break;
                        case "NOT_EQUAL_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " <> '" . $filtervalue . "'";
                            break;
                        case "NOT_EQUAL":
                            $where .= " " . $filterdatafield . " <> '" . $filtervalue . "'";
                            break;
                        case "GREATER_THAN":
                            $where .= " " . $filterdatafield . " > '" . $filtervalue . "'";
                            break;
                        case "LESS_THAN":
                            $where .= " " . $filterdatafield . " < '" . $filtervalue . "'";
                            break;
                        case "GREATER_THAN_OR_EQUAL":
                            $where .= " " . $filterdatafield . " >= '" . $filtervalue . "'";
                            break;
                        case "LESS_THAN_OR_EQUAL":
                            $where .= " " . $filterdatafield . " <= '" . $filtervalue . "'";
                            break;
                        case "STARTS_WITH_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " LIKE '" . $filtervalue . "%'";
                            break;
                        case "STARTS_WITH":
                            $where .= " " . $filterdatafield . " LIKE '" . $filtervalue . "%'";
                            break;
                        case "ENDS_WITH_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " LIKE '%" . $filtervalue . "'";
                            break;
                        case "ENDS_WITH":
                            $where .= " " . $filterdatafield . " LIKE '%" . $filtervalue . "'";
                            break;
                    }

                    if ($i == $filterscount - 1) {
                        $where .= ")";
                    }

                    $tmpfilteroperator = $filteroperator;
                    $tmpdatafield = $filterdatafield;
                }
                // build the query.
                $query = "SELECT * FROM (
                SELECT d.nur,d.cite_original,d.nombre_destinatario,d.cargo_destinatario,d.nombre_remitente,d.cargo_remitente,d.referencia,
                d.fecha_creacion,s.a_oficina,s.nombre_receptor,s.cargo_receptor,s.fecha_recepcion,e.estado,DATEDIFF(s.fecha_recepcion,d.fecha_creacion) as d1,DATEDIFF(s.fecha_emision,d.fecha_creacion) as d2 ,DATEDIFF(NOW(),d.fecha_creacion) as d3
                 FROM documentos d INNER JOIN (
                SELECT n.nur FROM nurs n 
                INNER JOIN users u ON n.id_user=u.id
                WHERE u.id_oficina='$oficina' ) AS n ON  d.nur=n.nur
                INNER JOIN seguimiento s ON s.nur=d.nur
                INNER JOIN estados e ON s.estado=e.id
                WHERE s.oficial='1'
                AND s.estado IN (1,2,10)
                AND d.original='1'
                AND d.id_seguimiento='0'
                ) as d " . $where;
                $filterquery = $query;
                $result = mysql_query($query) or die("SQL Error 1: " . mysql_error());
                $sql = "SELECT FOUND_ROWS() AS found_rows;";
                $rows = mysql_query($sql);
                $rows = mysql_fetch_assoc($rows);
                $new_total_rows = $rows['found_rows'];
                $query = "SELECT * FROM (
                SELECT d.nur,d.cite_original,d.nombre_destinatario,d.cargo_destinatario,d.nombre_remitente,d.cargo_remitente,d.referencia,
                d.fecha_creacion,s.a_oficina,s.nombre_receptor,s.cargo_receptor,s.fecha_recepcion,e.estado,DATEDIFF(s.fecha_recepcion,d.fecha_creacion) as d1,DATEDIFF(s.fecha_emision,d.fecha_creacion) as d2 ,DATEDIFF(NOW(),d.fecha_creacion) as d3
                 FROM documentos d INNER JOIN (
                SELECT n.nur FROM nurs n 
                INNER JOIN users u ON n.id_user=u.id
                WHERE u.id_oficina='$oficina' ) AS n ON  d.nur=n.nur
                INNER JOIN seguimiento s ON s.nur=d.nur
                INNER JOIN estados e ON s.estado=e.id
                WHERE s.oficial='1'
                AND s.estado IN (1,2,10)
                AND d.original='1'
                AND d.id_seguimiento='0'
                ) as d " . $where . " LIMIT $start, $pagesize";
                $total_rows = $new_total_rows;
            }
        }

        if (isset($_GET['sortdatafield'])) {

            $sortfield = $_GET['sortdatafield'];
            $sortorder = $_GET['sortorder'];

            if ($sortorder != '') {
                if ($_GET['filterscount'] == 0) {
                    if ($sortorder == "desc") {
                        $query = "SELECT * FROM (
                SELECT d.nur,d.cite_original,d.nombre_destinatario,d.cargo_destinatario,d.nombre_remitente,d.cargo_remitente,d.referencia,
                d.fecha_creacion,s.a_oficina,s.nombre_receptor,s.cargo_receptor,s.fecha_recepcion,e.estado,DATEDIFF(s.fecha_recepcion,d.fecha_creacion) as d1,DATEDIFF(s.fecha_emision,d.fecha_creacion) as d2 ,DATEDIFF(NOW(),d.fecha_creacion) as d3
                 FROM documentos d INNER JOIN (
                SELECT n.nur FROM nurs n 
                INNER JOIN users u ON n.id_user=u.id
                WHERE u.id_oficina='$oficina' ) AS n ON  d.nur=n.nur
                INNER JOIN seguimiento s ON s.nur=d.nur
                INNER JOIN estados e ON s.estado=e.id
                WHERE s.oficial='1'
                AND s.estado IN (1,2,10)
                AND d.original='1'
                AND d.id_seguimiento='0'
                ) as d ORDER BY" . " " . $sortfield . " DESC LIMIT $start, $pagesize";
                    } else if ($sortorder == "asc") {
                        $query = "SELECT * FROM (
                SELECT d.nur,d.cite_original,d.nombre_destinatario,d.cargo_destinatario,d.nombre_remitente,d.cargo_remitente,d.referencia,
                d.fecha_creacion,s.a_oficina,s.nombre_receptor,s.cargo_receptor,s.fecha_recepcion,e.estado,DATEDIFF(s.fecha_recepcion,d.fecha_creacion) as d1,DATEDIFF(s.fecha_emision,d.fecha_creacion) as d2 ,DATEDIFF(NOW(),d.fecha_creacion) as d3
                 FROM documentos d INNER JOIN (
                SELECT n.nur FROM nurs n 
                INNER JOIN users u ON n.id_user=u.id
                WHERE u.id_oficina='$oficina' ) AS n ON  d.nur=n.nur
                INNER JOIN seguimiento s ON s.nur=d.nur
                INNER JOIN estados e ON s.estado=e.id
                WHERE s.oficial='1'
                AND s.estado IN (1,2,10)
                AND d.original='1'
                AND d.id_seguimiento='0'
                ) as d ORDER BY" . " " . $sortfield . " ASC LIMIT $start, $pagesize";
                    }
                } else {
                    if ($sortorder == "desc") {
                        $filterquery .= " ORDER BY" . " " . $sortfield . " DESC LIMIT $start, $pagesize";
                    } else if ($sortorder == "asc") {
                        $filterquery .= " ORDER BY" . " " . $sortfield . " ASC LIMIT $start, $pagesize";
                    }
                    $query = $filterquery;
                }
            }
        }
        //conexion
        $mDocumentos = new Model_Documentos();
        $result = $mDocumentos->ejecutarsql($query);
        // echo json_encode($documentos);
        //$result = mysql_query($query) or die("SQL Error 1: " . mysql_error());
        $orders = null;

        //  echo $query;
        // get data and store in a json array
        foreach ($result as $row) {
            $orders[] = array(
                'nur' => $row['nur'],
                'cite_original' => $row['cite_original'],
                'nombre_destinatario' => $row['nombre_destinatario'],
                'cargo_destinatario' => $row['cargo_destinatario'],
                // 'institucion_destinatario' => $row['institucion_destinatario'],
                // 'institucion_remitente' => $row['institucion_remitente'],
                'nombre_remitente' => $row['nombre_remitente'],
                'cargo_remitente' => $row['cargo_remitente'],
                'referencia' => $row['referencia'],
                'fecha_creacion' => $row['fecha_creacion'],
                'estado' => $row['estado'],
                'nombre_receptor' => $row['nombre_receptor'] . ' / ' . $row['cargo_receptor'],
                'cargo_receptor' => $row['cargo_receptor'],
                'fecha_recepcion' => $row['fecha_recepcion'],
                'd1' => $row['d1'],
                'd2' => $row['d2'],
                'd3' => $row['d3'],
            );
        }
        $data[] = array(
            'TotalRows' => $total_rows,
            'Rows' => $orders
        );
        echo json_encode($data);
    }

    public function action_gauges() {

        $oficina = $_GET['oficina'];
        $gestion = $_GET['gestion'];
        $mes = $_GET['mes'];
        $sql_generado = "SELECT COUNT(*) as generada FROM nurs n INNER JOIN users u ON n.id_user=u.id";
        $sql_generado2 = $sql_generado;
        $sql_generado3 = $sql_generado;
        $sql_concluido = "SELECT COUNT(*) as archivada FROM seguimiento s INNER JOIN (
                          SELECT n.nur FROM nurs n INNER JOIN users u ON n.id_user=u.id ";
        $sql_concluido2 = $sql_concluido;
        $sql_concluido3 = $sql_concluido;
        $where = "";
        $where2 = "";
        $where3 = "";
        if ($oficina > 0) {
            $where.=" WHERE u.id_oficina='$oficina' ";
            $where2.=" WHERE u.id_oficina='$oficina' ";
            $where3.=" WHERE u.id_oficina='$oficina' ";
        }
        if ($gestion > 0) {
            if (strlen($where) > 0) {
                $where.=" AND YEAR(n.fecha_creacion)='$gestion'";
                $where2.=" AND YEAR(n.fecha_creacion)='$gestion'";
                $where3.=" AND YEAR(n.fecha_creacion)='$gestion'";
            } else {
                $where.=" WHERE YEAR(n.fecha_creacion)='$gestion'";
                $where2.=" WHERE YEAR(n.fecha_creacion)='$gestion'";
                $where3.=" WHERE YEAR(n.fecha_creacion)='$gestion'";
            }
        }
        if ($mes > 0) {
            if (strlen($where) > 0) {
                $where.=" AND MONTH(n.fecha_creacion)='$mes'";
                $where2.=" AND MONTH(n.fecha_creacion)<='$mes'";
                // $where3.=" AND MONTH(n.fecha_creacion)<='$mes'";
            } else {
                $where.=" WHERE MONTH(n.fecha_creacion)='$mes'";
                $where2.=" WHERE MONTH(n.fecha_creacion)='$mes'";
                // $where3.=" WHERE MONTH(n.fecha_creacion)='$mes'";
            }
        }
        //procesos generados
        $sql_generado.=$where;
        $sql_generado2.=$where2;
        $sql_generado3.=$where3;
        $mSeguimiento = new Model_seguimiento();
        $generados = $mSeguimiento->sql($sql_generado);
        $generados = $generados[0]['generada'];

        $generados2 = $mSeguimiento->sql($sql_generado2);
        $generados2 = $generados2[0]['generada'];

        $generados3 = $mSeguimiento->sql($sql_generado3);
        $generados3 = $generados3[0]['generada'];
        //procesos conluidos
        $sql_concluido.=$where . " ) AS g ON g.nur=s.nur
                WHERE s.estado='10'
                AND s.oficial='1'";
        $sql_concluido2.=$where2 . " ) AS g ON g.nur=s.nur
                WHERE s.estado='10'
                AND s.oficial='1'";
        $sql_concluido3.=$where3 . " ) AS g ON g.nur=s.nur
                WHERE s.estado='10'
                AND s.oficial='1'";
        $concluidos = $mSeguimiento->sql($sql_concluido);
        $concluidos = $concluidos[0]['archivada'];

        $concluidos2 = $mSeguimiento->sql($sql_concluido2);
        $concluidos2 = $concluidos2[0]['archivada'];

        $concluidos3 = $mSeguimiento->sql($sql_concluido3);
        $concluidos3 = $concluidos3[0]['archivada'];
        $g1 = 0;
        if ($generados > 0) {
            $g1 = round(($concluidos / $generados) * 100, 2);
        }
        $g2 = 0;
        if ($generados2 > 0) {
            $g2 = round(($concluidos2 / $generados2) * 100, 2);
        }
        $g3 = 0;
        if ($generados3 > 0) {
            $g3 = round(($concluidos3 / $generados3) * 100, 2);
        }
        $mes = array(
            'generadosMes' => (int) $generados,
            'concluidosMes' => (int) $concluidos,
            'g1' => $g1,
            'generadosAcumulado' => (int) $generados2,
            'concluidosAcumulado' => (int) $concluidos2,
            'g2' => $g2,
            'generadosAnio' => (int) $generados3,
            'concluidosAnio' => (int) $concluidos3,
            'g3' => $g3
        );
        echo json_encode($mes);
    }

    //torta de documentos
    public function action_tDocumentos() {
        $oficina = $_GET['oficina'];
        $gestion = $_GET['gestion'];
        $mes = $_GET['mes'];
        $sql = "SELECT  COUNT(*) as cantidad, UPPER(t.plural) as documento, t.id FROM documentos d INNER JOIN tipos t ON t.id=d.id_tipo"
                . " WHERE t.abreviatura IS NOT NULL";
        $where = "";
        if ($oficina > 0) {
            $where.=" AND d.id_oficina='$oficina' ";
        }
        if ($gestion > 0) {
            $where.=" AND YEAR(d.fecha_creacion)='$gestion'";
        }
        if ($mes > 0) {
            $where.=" AND MONTH(d.fecha_creacion)='$mes'";
        }
        $sql.=$where . " GROUP BY t.id";
        $mSeguimiento = new Model_Seguimiento();
        $documentos = $mSeguimiento->sql($sql);
        echo json_encode($documentos);
    }

    //jqw documentos
    public function action_jDocumentos($id) {
        
    }

    //nuevo cambio

    public function action_jreporte() {

        $pagenum = $_GET['pagenum'];
        $pagesize = $_GET['pagesize'];
        $start = $pagenum * $pagesize;
        $query = "SELECT SQL_CALC_FOUND_ROWS * FROM documentos LIMIT $start, $pagesize";
        $result = mysql_query($query) or die("SQL Error 1: " . mysql_error());
        $sql = "SELECT FOUND_ROWS() AS found_rows;";
        $rows = mysql_query($sql);
        $rows = mysql_fetch_assoc($rows);
        $total_rows = $rows['found_rows'];
        $filterquery = "";

        // filter data.
        if (isset($_GET['filterscount'])) {
            $filterscount = $_GET['filterscount'];

            if ($filterscount > 0) {
                $where = " WHERE (";
                $tmpdatafield = "";
                $tmpfilteroperator = "";
                for ($i = 0; $i < $filterscount; $i++) {
                    // get the filter's value.
                    $filtervalue = $_GET["filtervalue" . $i];
                    // get the filter's condition.
                    $filtercondition = $_GET["filtercondition" . $i];
                    // get the filter's column.
                    $filterdatafield = $_GET["filterdatafield" . $i];
                    // get the filter's operator.
                    $filteroperator = $_GET["filteroperator" . $i];

                    if ($tmpdatafield == "") {
                        $tmpdatafield = $filterdatafield;
                    } else if ($tmpdatafield <> $filterdatafield) {
                        $where .= ")AND(";
                    } else if ($tmpdatafield == $filterdatafield) {
                        if ($tmpfilteroperator == 0) {
                            $where .= " AND ";
                        } else
                            $where .= " OR ";
                    }

                    // build the "WHERE" clause depending on the filter's condition, value and datafield.
                    switch ($filtercondition) {
                        case "NOT_EMPTY":
                        case "NOT_NULL":
                            $where .= " " . $filterdatafield . " NOT LIKE '" . "" . "'";
                            break;
                        case "EMPTY":
                        case "NULL":
                            $where .= " " . $filterdatafield . " LIKE '" . "" . "'";
                            break;
                        case "CONTAINS_CASE_SENSITIVE":
                            $where .= " BINARY  " . $filterdatafield . " LIKE '%" . $filtervalue . "%'";
                            break;
                        case "CONTAINS":
                            $where .= " " . $filterdatafield . " LIKE '%" . $filtervalue . "%'";
                            break;
                        case "DOES_NOT_CONTAIN_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " NOT LIKE '%" . $filtervalue . "%'";
                            break;
                        case "DOES_NOT_CONTAIN":
                            $where .= " " . $filterdatafield . " NOT LIKE '%" . $filtervalue . "%'";
                            break;
                        case "EQUAL_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " = '" . $filtervalue . "'";
                            break;
                        case "EQUAL":
                            $where .= " " . $filterdatafield . " = '" . $filtervalue . "'";
                            break;
                        case "NOT_EQUAL_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " <> '" . $filtervalue . "'";
                            break;
                        case "NOT_EQUAL":
                            $where .= " " . $filterdatafield . " <> '" . $filtervalue . "'";
                            break;
                        case "GREATER_THAN":
                            $where .= " " . $filterdatafield . " > '" . $filtervalue . "'";
                            break;
                        case "LESS_THAN":
                            $where .= " " . $filterdatafield . " < '" . $filtervalue . "'";
                            break;
                        case "GREATER_THAN_OR_EQUAL":
                            $where .= " " . $filterdatafield . " >= '" . $filtervalue . "'";
                            break;
                        case "LESS_THAN_OR_EQUAL":
                            $where .= " " . $filterdatafield . " <= '" . $filtervalue . "'";
                            break;
                        case "STARTS_WITH_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " LIKE '" . $filtervalue . "%'";
                            break;
                        case "STARTS_WITH":
                            $where .= " " . $filterdatafield . " LIKE '" . $filtervalue . "%'";
                            break;
                        case "ENDS_WITH_CASE_SENSITIVE":
                            $where .= " BINARY " . $filterdatafield . " LIKE '%" . $filtervalue . "'";
                            break;
                        case "ENDS_WITH":
                            $where .= " " . $filterdatafield . " LIKE '%" . $filtervalue . "'";
                            break;
                    }

                    if ($i == $filterscount - 1) {
                        $where .= ")";
                    }

                    $tmpfilteroperator = $filteroperator;
                    $tmpdatafield = $filterdatafield;
                }
                // build the query.
                $query = "SELECT id,nur,cite_original,nombre_destinatario,cargo_destinatario,nombre_remitente,cargo_remitente,referencia, fecha_creacion"
                        . ",institucion_remitente,institucion_destinatario FROM documentos " . $where;
                $filterquery = $query;
                $result = mysql_query($query) or die("SQL Error 1: " . mysql_error());
                $sql = "SELECT FOUND_ROWS() AS found_rows;";
                $rows = mysql_query($sql);
                $rows = mysql_fetch_assoc($rows);
                $new_total_rows = $rows['found_rows'];
                $query = "SELECT id,nur,cite_original,nombre_destinatario,cargo_destinatario,nombre_remitente,cargo_remitente,referencia, fecha_creacion"
                        . ",institucion_remitente,institucion_destinatario FROM documentos " . $where . " LIMIT $start, $pagesize";
                $total_rows = $new_total_rows;
            }
        }

        if (isset($_GET['sortdatafield'])) {

            $sortfield = $_GET['sortdatafield'];
            $sortorder = $_GET['sortorder'];

            if ($sortorder != '') {
                if ($_GET['filterscount'] == 0) {
                    if ($sortorder == "desc") {
                        $query = "SELECT id,nur,cite_original,nombre_destinatario,cargo_destinatario,nombre_remitente,cargo_remitente,referencia, fecha_creacion"
                                . ",institucion_remitente,institucion_destinatario FROM documentos ORDER BY" . " " . $sortfield . " DESC LIMIT $start, $pagesize";
                    } else if ($sortorder == "asc") {
                        $query = "SELECT id,nur,cite_original,nombre_destinatario,cargo_destinatario,nombre_remitente,cargo_remitente,referencia,fecha_creacion"
                                . ",institucion_remitente,institucion_destinatario FROM documentos ORDER BY" . " " . $sortfield . " ASC LIMIT $start, $pagesize";
                    }
                } else {
                    if ($sortorder == "desc") {
                        $filterquery .= " ORDER BY" . " " . $sortfield . " DESC LIMIT $start, $pagesize";
                    } else if ($sortorder == "asc") {
                        $filterquery .= " ORDER BY" . " " . $sortfield . " ASC LIMIT $start, $pagesize";
                    }
                    $query = $filterquery;
                }
            }
        }
        //conexion
        $mDocumentos = new Model_Documentos();
        $result = $mDocumentos->ejecutarsql($query);
        // echo json_encode($documentos);
        //$result = mysql_query($query) or die("SQL Error 1: " . mysql_error());
        $orders = null;

        //  echo $query;
        // get data and store in a json array
        foreach ($result as $row) {
            $orders[] = array(
                'nur' => $row['nur'],
                'cite_original' => $row['cite_original'],
                'nombre_destinatario' => $row['nombre_destinatario'],
                'cargo_destinatario' => $row['cargo_destinatario'],
                'institucion_destinatario' => $row['institucion_destinatario'],
                'institucion_remitente' => $row['institucion_remitente'],
                'nombre_remitente' => $row['nombre_remitente'],
                'cargo_remitente' => $row['cargo_remitente'],
                'referencia' => $row['referencia'],
                'fecha_creacion' => $row['fecha_creacion']
            );
        }
        $data[] = array(
            'TotalRows' => $total_rows,
            'Rows' => $orders
        );
        echo json_encode($data);
    }

    public function action_seguimiento($nur) {
        $mDocumentos = new Model_Documentos();
        $seguimiento = $mDocumentos->seguimiento($nur);
        echo json_encode($seguimiento);
    }

//nuevo
    public function action_documentosTipo() {
        $oficina = $this->user->id_oficina;
        $mDocumento = new Model_Documentos();
        $documentos = $mDocumento->documentosTipo($oficina);
        $data = array();
        foreach ($documentos as $d) {
            // $data[$d['documento']][]=(int)$d['cantidad'];
            $data[$d['fecha']][$d['documento']][] = (int) $d['cantidad'];
        }
        $categoria = array();
        foreach ($data as $k => $v) {
            $categoria[] = $k;
        }
        echo json_encode($data);
        echo json_encode($categoria);
        //echo '<br/><b>'.$this->user->id_oficina.'</b>';
    }

    public function action_addUser() {
        $id_user = $_POST['id'];
        $destinos = explode(';', $_POST['destinos']);
        foreach ($destinos as $k => $v) {
            if ($v != '') {
                $destino = ORM::factory('destinatarios');
                $destino->id_usuario = $id_user;
                $destino->id_destino = $v;
                $destino->save();
            }
        }
        echo true;
    }

    public function action_theme() {
        $auth = Auth::instance();
        if ($auth->logged_in()) {
            $usuario = ORM::factory('user', $auth->get_user());
            $usuario->theme = $_POST['theme'];
            $usuario->save();
        }
    }

    //function para saber si alguien le derivo algo   
    public function action_mensaje2() {
        $session = Session::instance();
        $user = $session->get('auth_user');
        $a_enviados = array();
        $date = time() - 17;
        $model = New Model_Seguimiento();
        $enviados = $model->por_recibir($user->id, date('Y-m-d H:i:s', $date));
        $i = 0;
        foreach ($enviados as $e):
            $a_enviados[$i]['hr'] = $e['nur'];
            $a_enviados[$i]['remitente'] = $e['nombre_emisor'];
            $a_enviados[$i]['cargo'] = $e['cargo_emisor'];
            $a_enviados[$i]['de_oficina'] = $e['de_oficina'];
            $i++;
        endforeach;
        echo json_encode($a_enviados);
    }

    public function action_eventos() {
        // $date=date('Y-m-d H:i:s');
        $fecha_inicio = date('Y-01-01', time());
        $fecha_final = date('Y-12-31', time());
        $o_eventos = New Model_Eventos();
        $eventos = $o_eventos->lista($fecha_inicio, $fecha_final);
        $a_eventos = array();
        foreach ($eventos as $e) {
            $id = $e['id'];
            $fecha = strtotime($e['fecha_inicio']);
            $a_eventos[$id]['anio'] = date('Y', $fecha);
            $a_eventos[$id]['mes'] = date('m', $fecha);
            $a_eventos[$id]['dia'] = date('d', $fecha);
            $a_eventos[$id]['hora'] = date('H', $fecha);
            $a_eventos[$id]['minuto'] = date('i', $fecha);
            $a_eventos[$id]['anio2'] = date('Y', $fecha);
            $a_eventos[$id]['mes2'] = date('m', $fecha);
            $a_eventos[$id]['dia2'] = date('d', $fecha);
            $a_eventos[$id]['titulo'] = $e['titulo'];
            $a_eventos[$id]['descripcion'] = $e['descripcion'];
            $a_eventos[$id]['prioridad'] = $e['prioridad'];
            $a_eventos[$id]['frecuencia'] = $e['frecuencia'];
        }
        echo json_encode($a_eventos);
    }

    public function action_derivar() {
        if ($_POST) {
            $tipo = $_POST['tipo'];
            $oficial = $_POST['oficial'];
            $id_seg = $_POST['id_seg'];
            $destino = $_POST['destino'];
            $accion = $_POST['accion'];
            $proveido = $_POST['proveido'];
            $nur = $_POST['nur'];
            $user = $_POST['user'];
            $id_doc = $_POST['document'];
            $hijo = $_POST['hijo'];
            $prioridad = $_POST['urgente'];
            $session = Session::instance();
            if ($session->get('destino')) {
                $usuario = $session->get('destino');
                //verificamos que el usuario no estee en destinatarios ya enviados
                if (array_key_exists($destino, $session->get('destino'))) {
                    $error = array('error' => 'Ya se envio la hoja de ruta' . $nur . ' la usuario.');
                    echo json_encode($error);
                    exit;
                }
                //verificamos que solo se envie un oficial
                if (isset($usuario['oficial'])) {
                    if ($usuario['oficial'] == $oficial) {
                        $error = array('error' => 'Solo puede enviar un oficial');
                        //$session->delete('destino');
                        echo json_encode($error);
                        exit;
                    }
                }
            }
            $usuario[$destino] = $destino;

            if ($oficial > 0)
                $usuario['oficial'] = $oficial;

            $session->set('destino', $usuario);

            $adjunto = $_POST['adjunto'];
            if ($adjunto == 0)
                $adjunto = json_encode(array());
            else
                $adjunto = json_encode($adjunto);

            $destino = ORM::factory('users', $destino);
            $oficina_destino = ORM::factory('oficinas', $destino->id_oficina);
            $remite = ORM::factory('users', $user);
            $oficina_remite = ORM::factory('oficinas', $remite->id_oficina);

            $seguimiento = ORM::factory('seguimiento');
            if (($id_seg > 0) && ($oficial == $tipo)) {
                $seguimiento_actual = ORM::factory('seguimiento')
                        ->where('id', '=', $id_seg)
                        ->find();
                $seguimiento_actual->estado = 4;
                if ($seguimiento_actual->oficial == 1) {
                    $seguimiento_actual->oficial = 2;
                }
                $seguimiento_actual->save();
            } else {
                if (isset($usuario['oficial'])) {
                    $documento = ORM::factory('documentos', $id_doc);
                    $documento->estado = 1;
                    $documento->save();
                }
            }
            $seguimiento->id_seguimiento = $id_seg;
            $seguimiento->nur = $nur;
            $seguimiento->derivado_por = $remite->id;
            $seguimiento->nombre_emisor = $remite->nombre;
            $seguimiento->cargo_emisor = $remite->cargo;
            $seguimiento->fecha_emision = date('Y-m-d H:i:s');
            $seguimiento->derivado_a = $destino->id;
            $seguimiento->nombre_receptor = $destino->nombre;
            $seguimiento->cargo_receptor = $destino->cargo;
            $seguimiento->estado = 1;
            $seguimiento->accion = $accion;
            $seguimiento->oficial = $oficial;
            $seguimiento->hijo = $hijo;
            $seguimiento->proveido = $proveido;
            $seguimiento->adjuntos = $adjunto;
            $seguimiento->de_oficina = $oficina_remite->oficina;
            $seguimiento->a_oficina = $oficina_destino->oficina;
            $seguimiento->id_de_oficina = $oficina_remite->id;
            $seguimiento->id_a_oficina = $oficina_destino->id;
            $seguimiento->prioridad = $prioridad;
            $seguimiento->save();

            $result = array(
                'id' => $seguimiento->id,
                'remite_nombre' => $seguimiento->nombre_emisor,
                'remite_cargo' => $seguimiento->cargo_emisor,
                'de_oficina' => $seguimiento->de_oficina,
                'receptor_nombre' => $seguimiento->nombre_receptor,
                'receptor_cargo' => $seguimiento->cargo_receptor,
                'a_oficina' => $seguimiento->a_oficina,
                'proveido' => $seguimiento->proveido,
                'oficial' => $seguimiento->oficial,
                'id_destino' => $seguimiento->derivado_a,
                'adjunto' => json_decode($seguimiento->adjuntos),
            );
            if (!$oficial)
                $oficial = 'Copia';
            else
                $oficial = 'Oficial';
            //guardamo vitacora
            $this->save($remite->id_entidad, $seguimiento->derivado_por, $seguimiento->nombre_emisor . ' | <b>' . $seguimiento->cargo_emisor . '</b> Deriva la Hoja de Ruta ' . $seguimiento->nur . '(' . $oficial . ') a ' . $seguimiento->nombre_receptor . ' | <b>' . $seguimiento->cargo_receptor . '</b>');

            echo json_encode($result);
        }
    }

    public function action_eliminar() {
        $id_seg = $_POST['id'];
        $id_destino = $_POST['destino'];
        $oficial = $_POST['oficial'];
        $id_doc = $_POST['document'];
        $session = Session::instance();
        $usuario = $session->get('destino');
        unset($usuario[$id_destino]);
        //verificamos si se borro algun oficial
        if ($oficial == 1)
            unset($usuario['oficial']);
        $session->set('destino', $usuario);
        /* quitar el seguimiento */
        $seguimiento = ORM::factory('seguimiento', $id_seg);
        if ($seguimiento->loaded()) {
            if (sizeof($usuario) == 0) {
                if ($seguimiento->id_seguimiento > 0) { //si tienes seguimieto previo el nur debe volver a pendientes
                    $seg_anterior = ORM::factory('seguimiento', $seguimiento->id_seguimiento);
                    if ($seg_anterior->oficial == 2)
                        $seg_anterior->oficial = 1;
                    $seg_anterior->estado = 2;
                    $seg_anterior->save();
                }
                else {        //si no tiene seguimiento, fue la primera derivacion, entonces el documento debe cambiar el estado
                    $documento = ORM::factory('documentos', $id_doc);
                    if ($documento->loaded()) {
                        $documento->estado = 0;
                        $documento->save();
                    }
                }
            }
            //vitacora
            if (!$seguimiento->oficial)
                $oficial = 'Copia';
            else
                $oficial = 'Oficial';
            $this->save(0, $seguimiento->derivado_por, $seguimiento->nombre_emisor . ' | <b>' . $seguimiento->cargo_emisor . '</b> Cancela derivacion de la Hoja de Ruta ' . $seguimiento->nur . '(' . $oficial . ') a ' . $seguimiento->nombre_receptor);
            $seguimiento->delete();
        }

        //si no hay usuarios que derivar verificamos el estado de la derivacion

        echo json_encode($usuario);
    }

    //LISTA DE DOCUMENTOS CREADOS 
    public function action_documentos() {
        $auth = Auth::instance();
        if ($auth->logged_in()) {
            $documentos = ORM::factory('documentos')
                    ->where('id_user', '=', $auth->get_user())
                    ->find_all();
            $doc = array();
            foreach ($documentos as $d) {
                $doc[] = array('key' => $d->codigo, 'value' => $d->codigo);
            }
            echo json_encode($doc);
        }
    }

    //archivar documento
    public function action_archivar() {
        $auth = Auth::instance();
        if ($auth->logged_in()) {
            $oArch = New Model_Archivados();
            $a = $oArch->archivar($_POST['id_nuri'], $auth->get_user(), $_POST['carpeta'], date('Y-m-d H:i:s'));
            if ($a) {
                echo json_encode(array('La hoja de Seguimiento fue almacenada correctamente'));
            } else {
                echo json_encode(array('Hubo un error al guardar en archivos'));
            }
        }
    }

    //imprimir una hoja de seguimiento
    public function action_print_hs() {
        $hs = $_POST['nur'];
        $aNur = array();
        $nur = ORM::factory('nurs')->where('nur', '=', $hs)->count_all();
        if ($nur > 0) {
            $aNur['nur'] = $nur;
        } else {
            $aNur['nur'] = 0;
        }
        echo json_encode($aNur);
    }

    //verificamos el usuario unico
    public function action_username() {
        $username = Arr::get($_POST, 'username', '');
        $myuser = ORM::factory('users', array('username' => $username));
        $res = 0;
        if ($myuser->loaded()) {
            $res = 1;
        }
        echo json_encode(array('result' => $res));
    }

    //verificamos un email unico
    public function action_emailunique() {
        $email = Arr::get($_POST, 'email', '');

        $myuser = new Model_Myuser();
        $res = $myuser->username_unique($email);

        echo json_encode(array('result' => $res));
    }

    public function action_checkOldPass() {
        $oldpass = Arr::get($_POST, 'oldpass', '');

        $myuser = new Model_Myuser();
        $res = $myuser->checkOldPass($oldpass);

        echo json_encode(array('result' => $res));
    }

    /*     * *********************REPORTES*********************** */

    public function action_pOficina() {
        $auth = Auth::instance();
        if ($auth->logged_in()) {
            $session = Session::instance();
            $user = $session->get('auth_user');
            //usuarios que pertences a mi oficina  
            $users = ORM::factory('users')->where('id_oficina', '=', $user->id_oficina)->or_where('superior', '=', $user->id)->find_all();
            $oficiales = array();
            $copias = array();
            $usuarios = array();
            $archivados = array();
            foreach ($users as $u) {
                $oficial = ORM::factory('seguimiento')->where('derivado_a', '=', $u->id)->and_where('estado', '=', 2)->and_where('oficial', '=', 1)->count_all();
                $copia = ORM::factory('seguimiento')->where('derivado_a', '=', $u->id)->and_where('estado', '=', 2)->and_where('oficial', '=', 0)->count_all();
                $archivado = ORM::factory('seguimiento')->where('derivado_a', '=', $u->id)->and_where('estado', '=', 10)->count_all();
                array_push($oficiales, (int) $oficial);
                array_push($copias, (int) $copia);
                array_push($usuarios, $u->nombre);
                array_push($archivados, (int) $archivado);
            }
            $pendientes = array(
                'oficiales' => array_values($oficiales),
                'copias' => array_values($copias),
                'archivados' => array_values($archivados),
                'names' => array_values($usuarios),
            );
            echo json_encode($pendientes);
        }
    }

#******* pendientes por oficinas ***********        

    public function action_pOficinas() {
        $auth = Auth::instance();
        if ($auth->logged_in()) {
            $session = Session::instance();
            $user = $session->get('auth_user');
            //usuarios que pertences a mi oficina  
            $oficina = ORM::factory('oficinas', $user->id_oficina);
            $oficinas = ORM::factory('oficinas')->where('id_entidad', '=', $oficina->id_entidad)->find_all();
            $oficiales = array();
            $copias = array();
            $usuarios = array();
            $archivados = array();
            foreach ($oficinas as $u) {
                $oficial = ORM::factory('seguimiento')->where('id_a_oficina', '=', $u->id)->and_where('estado', '=', 2)->and_where('oficial', '=', 1)->count_all();
                $copia = ORM::factory('seguimiento')->where('id_a_oficina', '=', $u->id)->and_where('estado', '=', 2)->and_where('oficial', '=', 0)->count_all();
                $archivado = ORM::factory('seguimiento')->where('id_a_oficina', '=', $u->id)->and_where('estado', '=', 10)->count_all();
                array_push($oficiales, (int) $oficial);
                array_push($copias, (int) $copia);
                array_push($usuarios, $u->oficina);
                array_push($archivados, (int) $archivado);
            }
            $pendientes = array(
                'oficiales' => array_values($oficiales),
                'copias' => array_values($copias),
                'archivados' => array_values($archivados),
                'names' => array_values($usuarios),
            );
            echo json_encode($pendientes);
        }
    }

    public function action_vercite() {
        if ($_POST) {
            $a_documento = array();
            $cite = trim($_POST['cite']);
            $oficina = trim($_POST['oficina']);
            if ($cite == '' || $cite == 'S/C') {
                echo json_encode($a_documento);
            } else {
                $documento = ORM::factory('documentos')
                        ->where('cite_original', '=', $cite)
                        ->and_where('id_oficina', '=', $oficina)
                        ->find();
                if ($documento->loaded()) {
                    $a_documento['error'] = 'El documento ya fue recpecionado en fecha: ' . $documento->fecha_creacion . ' y tiene HOJA DE RUTA ' . $documento->nur;
                }
                echo json_encode($a_documento);
            }
        }
    }

    public function action_pendientes() {
        $id = $_POST['user'];
        $usuarios = ORM::factory('users')
                ->where('superior', '=', $id)
                ->and_where('habilitado', '=', 1)
                ->find_all();
        $data = array();
        $sql = "SELECT u.id, u.nombre,u.cargo,COUNT(*) as  pendientes FROM users u
    INNER JOIN seguimiento s ON s.derivado_a=u.id
    WHERE s.estado='2'
    AND s.derivado_a IN ( ";
        foreach ($usuarios as $u):
            $data[$u->id] = array(
                'id' => $u->id,
                'nombre' => $u->nombre,
                'pendientes' => 0,
                'cargo' => $u->cargo
            );
            $sql.=$u->id . ", ";
        endforeach;
        $sql = substr($sql, 0, -2);
        $sql.=" ) GROUP BY u.id";
        $mSeg = New Model_Seguimiento();
        $pendientes = $mSeg->pendientes2($sql);
        foreach ($pendientes as $p):
            $data[$p['id']] = array(
                'id' => $p['id'],
                'nombre' => $p['nombre'],
                'pendientes' => $p['pendientes'],
                'cargo' => $p['cargo']
            );
        endforeach;
        echo json_encode($data);
    }

    public function save($entidad, $user, $accion) {
        $vitacora = ORM::factory('vitacora');
        $vitacora->id_usuario = $user;
        $vitacora->id_entidad = $entidad;
        $vitacora->fecha_hora = date('Y-m-d H:i:s');
        $vitacora->accion_realizada = $accion;
        $vitacora->ip_usuario = Request::$client_ip;
        $vitacora->save();
    }

}
