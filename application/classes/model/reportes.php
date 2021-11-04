<?php

defined('SYSPATH') or die('no tiene acceso');

//descripcion del modelo productos
class Model_Reportes extends ORM {

    protected $_table_names_plural = false;

    public function a_juridica() {
        $sql = "SELECT DISTINCT s.codigo, s.derivado_por,s.derivado_a 
            FROM seguimientos s, usuarios u
            WHERE derivado_a='direccion.juridica'
            and f_derivacion BETWEEN '2011-10-01' AND '2011-11-28'
            and s.derivado_por=u.id_usuario
            and u.oficina NOT LIKE '%DAJ%'
            ";
        return db::query(Database::SELECT, $sql)->execute();
    }

    public function avanzado() {
        $sql = "";
        return db::query(Database::SELECT, $sql)->execute();
    }

    public function recepcionado($oficina, $id_user, $fecha1, $fecha2, $tipo) {
        /* $sql="SELECT s.nur,s.nombre_receptor,s.cargo_receptor,s.nombre_emisor,s.cargo_emisor,s.fecha_emision, s.fecha_recepcion,s.proveido,d.codigo FROM seguimiento s
          INNER JOIN documentos d ON s.nur=d.nur
          WHERE s.id_de_oficina='$oficina'
          AND s.derivado_a='$id_user'
          AND s.estado BETWEEN '2' and '4'
          and d.original=1
          AND s.fecha_recepcion BETWEEN '$fecha1' AND '$fecha2'"; */
        //echo '---->'.$tipo;
        if ($tipo == 3) {
            $sql = "SELECT d.nur,d.cite_original,DATE_FORMAT(d.fecha_creacion,'%d/%m/%Y %H:%i:%s') as fecha_creacion,d.institucion_remitente,d.nombre_remitente,
                d.cargo_remitente,d.referencia,s.fecha_emision,d.nombre_destinatario,d.cargo_destinatario,s.estado,s.nombre_emisor
FROM documentos d INNER JOIN
(SELECT s.nur,s.nombre_emisor,s.cargo_emisor,e.estado,s.nombre_receptor,DATE_FORMAT(s.fecha_emision,'%d/%m/%Y %H:%i:%s') as fecha_emision,s.fecha_recepcion,s.oficial
 FROM seguimiento s INNER JOIN estados e ON s.estado=e.id
WHERE s.derivado_a='$id_user'  AND s.id_de_oficina='$oficina' 
 AND fecha_emision BETWEEN '$fecha1' AND '$fecha2' 
ORDER BY fecha_emision desc) as s ON d.nur=s.nur
WHERE d.original=1";
        }
        if ($tipo == 1) {
            $sql = "SELECT d.nur,d.cite_original,DATE_FORMAT(d.fecha_creacion,'%d/%m/%Y %H:%i:%s') as fecha_creacion,d.institucion_remitente,d.nombre_remitente,
                d.cargo_remitente,d.referencia,s.fecha_emision,d.nombre_destinatario,d.cargo_destinatario,s.estado,s.nombre_emisor
FROM documentos d INNER JOIN
(SELECT s.nur,s.nombre_emisor,s.cargo_emisor,e.estado,s.nombre_receptor,DATE_FORMAT(s.fecha_emision,'%d/%m/%Y %H:%i:%s') as fecha_emision,s.fecha_recepcion,s.oficial
 FROM seguimiento s INNER JOIN estados e ON s.estado=e.id
WHERE s.derivado_a='$id_user'  AND s.id_de_oficina='$oficina' 
and s.nur like 'E/%' AND fecha_emision BETWEEN '$fecha1' AND '$fecha2' 
ORDER BY fecha_emision desc) as s ON d.nur=s.nur
WHERE d.original=1";
        }
        if ($tipo == 2) {
            $sql = "SELECT d.nur,d.cite_original,DATE_FORMAT(d.fecha_creacion,'%d/%m/%Y %H:%i:%s') as fecha_creacion,d.institucion_remitente,d.nombre_remitente,
                d.cargo_remitente,d.referencia,s.fecha_emision,d.nombre_destinatario,d.cargo_destinatario,s.estado,s.nombre_emisor
FROM documentos d INNER JOIN
(SELECT s.nur,s.nombre_emisor,s.cargo_emisor,e.estado,s.nombre_receptor,DATE_FORMAT(s.fecha_emision,'%d/%m/%Y %H:%i:%s') as fecha_emision,s.fecha_recepcion,s.oficial
 FROM seguimiento s INNER JOIN estados e ON s.estado=e.id
WHERE s.derivado_a='$id_user'  AND s.id_de_oficina='$oficina'
and s.nur NOT LIKE 'E/%' AND fecha_emision BETWEEN '$fecha1' AND '$fecha2' 
ORDER BY fecha_emision desc) as s ON d.nur=s.nur
WHERE d.original=1";
        }
        //echo $sql;
        return db::query(Database::SELECT, $sql)->execute();
    }

    public function recepcionado_all($id_user, $fecha1, $fecha2, $tipo) {
        switch ($tipo) {
            case 1:
                $sql = "SELECT d.nur,d.cite_original,DATE_FORMAT(d.fecha_creacion,'%d/%m/%Y %H:%i:%s') as fecha_creacion,d.institucion_remitente,d.nombre_remitente,
            d.cargo_remitente,d.referencia,s.fecha_emision,d.nombre_destinatario,d.cargo_destinatario,s.estado,s.nombre_emisor
                    FROM documentos d INNER JOIN
                    (SELECT s.nur,s.nombre_emisor,s.cargo_emisor,e.estado,s.nombre_receptor,DATE_FORMAT(s.fecha_emision,'%d/%m/%Y %H:%i:%s') as fecha_emision,s.fecha_recepcion,s.oficial
                     FROM seguimiento s INNER JOIN estados e ON s.estado=e.id
                    WHERE s.derivado_a='$id_user' 
                    and s.nur like 'E/%' AND fecha_emision BETWEEN '$fecha1' AND '$fecha2' 
                    ORDER BY fecha_emision desc ) as s ON d.nur=s.nur
                    WHERE d.original=1";

                break;
            case 2:
                $sql = "SELECT d.nur,d.cite_original,DATE_FORMAT(d.fecha_creacion,'%d/%m/%Y %H:%i:%s') as fecha_creacion,d.institucion_remitente,d.nombre_remitente,
                       d.cargo_remitente,d.referencia,s.fecha_emision,d.nombre_destinatario,d.cargo_destinatario,s.estado,s.nombre_emisor
                    FROM documentos d INNER JOIN
                    (SELECT s.nur,s.nombre_emisor,s.cargo_emisor,e.estado,s.nombre_receptor,DATE_FORMAT(s.fecha_emision,'%d/%m/%Y %H:%i:%s') as fecha_emision,s.fecha_recepcion,s.oficial
                     FROM seguimiento s INNER JOIN estados e ON s.estado=e.id
                    WHERE s.derivado_a='$id_user' 
                    and s.nur NOT LIKE 'E/%' AND fecha_emision BETWEEN '$fecha1' AND '$fecha2' 
                    ORDER BY fecha_emision desc ) as s ON d.nur=s.nur
        WHERE d.original=1";

                break;

            default:
                $sql = "SELECT d.nur,d.cite_original,DATE_FORMAT(d.fecha_creacion,'%d/%m/%Y %H:%i:%s') as fecha_creacion,d.institucion_remitente,d.nombre_remitente,
                                    d.cargo_remitente,d.referencia,s.fecha_emision,d.nombre_destinatario,d.cargo_destinatario,s.estado,s.nombre_emisor
                        FROM documentos d INNER JOIN
                        (SELECT s.nur,s.nombre_emisor,s.cargo_emisor,e.estado,s.nombre_receptor,DATE_FORMAT(s.fecha_emision,'%d/%m/%Y %H:%i:%s') as fecha_emision,s.fecha_recepcion,s.oficial
                         FROM seguimiento s INNER JOIN estados e ON s.estado=e.id
                        WHERE s.derivado_a='$id_user' 
                         AND s.fecha_emision BETWEEN '$fecha1' AND '$fecha2' 
                        ORDER BY s.fecha_emision desc) as s ON d.nur=s.nur
                       WHERE d.original=1";
                break;
        }

        // echo $sql;
        return db::query(Database::SELECT, $sql)->execute();
    }

    public function enviado($oficina, $id_user, $fecha1, $fecha2) {
        $sql = "SELECT s.nur,s.nombre_receptor,s.cargo_receptor,s.nombre_emisor,s.cargo_emisor,s.fecha_emision, s.fecha_recepcion,s.proveido,d.codigo,d.cite_original FROM seguimiento s
        INNER JOIN documentos d ON s.nur=d.nur
        WHERE s.id_a_oficina='$oficina'
        AND s.derivado_por='$id_user'
        AND s.estado BETWEEN '1' and '4'   
        and d.original=1
        AND s.fecha_emision BETWEEN '$fecha1' AND '$fecha2'";
        return db::query(Database::SELECT, $sql)->execute();
    }

    public function enviado_all($id_user, $fecha1, $fecha2) {
        $sql = "SELECT s.nur,s.nombre_receptor,s.cargo_receptor,s.nombre_emisor,s.cargo_emisor,s.fecha_emision, s.fecha_recepcion,s.proveido,d.codigo,d.cite_original FROM seguimiento s
        INNER JOIN documentos d ON s.nur=d.nur";
        print_r($sql);
        return db::query(Database::SELECT, $sql)->execute();
    }

    //personalizado
    public function personal($oficina, $estado, $fecha1, $fecha2) {
        /* $sql="SELECT s.de_oficina,d.institucion_remitente,d.referencia,d.nombre_remitente,d.nombre_destinatario, s.nur,s.nombre_receptor,s.cargo_receptor,s.nombre_emisor,s.cargo_emisor,s.fecha_emision, s.fecha_recepcion,s.proveido,d.codigo,d.cite_original FROM seguimiento s
          INNER JOIN documentos d ON s.nur=d.nur
          WHERE s.id_a_oficina='$oficina'
          AND s.estado ='$estado'
          and d.original='1'
          AND s.fecha_emision BETWEEN '$fecha1' AND '$fecha2'"; */
        $sql = "SELECT s.id, s.padre,s.hijo,s.id_seguimiento,s.nur, s.nombre_emisor,s.cargo_emisor,s.de_oficina,s.fecha_emision as fecha,s.fecha_recepcion as fecha2, c.accion, s.oficial, s.hijo, s.proveido,s.adjuntos,s.archivos
             , d.codigo, d.nombre_destinatario, d.cargo_destinatario, p.proceso,d.referencia,d.id as id_doc,s.prioridad
              FROM seguimiento s
              INNER JOIN documentos d ON s.nur=d.nur
              INNER JOIN acciones c ON s.accion=c.id      
              INNER JOIN procesos p ON p.id=d.id_proceso												
              WHERE s.estado='$estado'
              AND s.id_a_oficina='$oficina'
              AND d.original='1'
              AND s.fecha_emision BETWEEN '$fecha1' AND '$fecha2'
              ORDER BY s.nur=d.nur";
        return db::query(Database::SELECT, $sql)->execute();
    }

    public function v_recepcion($id_user, $fecha1, $fecha2) {
        $sql = "SELECT d.nur,d.cite_original,d.nombre_destinatario,d.cargo_destinatario,d.nombre_remitente,d.cargo_remitente,d.referencia,d.adjuntos,d.fecha_creacion,d.hojas,d.estado
        FROM documentos d
        WHERE id_user='$id_user'
        AND d.original=1
        AND d.fecha_creacion BETWEEN '$fecha1' AND '$fecha2'";
        return db::query(Database::SELECT, $sql)->execute();
    }

    public function report_adec($idu, $idsup, $oficina) {

        if ($idu != '-1') {
            $sql = "SELECT s.nur,s.nombre_emisor,s.nombre_receptor,s.fecha_emision,s.fecha_recepcion,DATEDIFF(s.fecha_recepcion,s.fecha_emision)AS dias_intermedio,DATEDIFF(NOW(),s.fecha_recepcion)AS dias_recepcion 
                FROM seguimiento s
                WHERE s.derivado_por='$idsup'
                AND s.derivado_a='$idu'    
                AND s.estado='2'
                AND s.id_a_oficina='$oficina' 
                ORDER BY dias_recepcion";
        } else {
            $sql = "SELECT s.nur,s.nombre_emisor,s.nombre_receptor,s.fecha_emision,s.fecha_recepcion,DATEDIFF(s.fecha_recepcion,s.fecha_emision)AS dias_intermedio,DATEDIFF(NOW(),s.fecha_recepcion)AS dias_recepcion 
                FROM seguimiento s
                WHERE s.derivado_por='$idsup'
                AND s.estado='2'
                AND s.id_a_oficina='$oficina' 
                ORDER BY dias_recepcion";
        }

        return db::query(Database::SELECT, $sql)->execute();
    }

}

?>
