<?php

defined('SYSPATH') or die('no tiene acceso');

//descripcion del modelo productos
class Model_Users extends ORM {

    protected $_table_names_plural = false;
    //un usuario genera varios documentos 
    protected $_has_many = array(
        'documentos' => array(
            'model' => 'documentos',
            'foreign_key' => 'id_user'
        ),
        'tipo' => array(
            'model' => 'tipos',
            'through' => 'usertipo',
            'foreign_key' => 'id_user',
            'far_key' => 'id_tipo',
        ),
        'destinos' => array(
            'model' => 'destinos',
            'through' => 'destinatarios',
            'foreign_key' => 'id_usuario',
            'fair_key' => 'id_destino'
        ),
    );
    // un usuario (funcionario) pertenece a una oficina
    protected $_belongs_to = array(
        'oficina' => array(
            'model' => 'oficinas',
            'foreign_key' => 'id_oficina'
        ),
    );

    //propiedades de un usuario
    public function destinatarios($id) {
        $sql = "SELECT x.id as id_destinatario,u.id,u.nombre,u.cargo,o.oficina,u.username,u.genero FROM
              (select * from destinatarios where id_usuario='$id') AS x
              INNER JOIN users u
              ON x.id_destino=u.id
              INNER JOIN oficinas o ON u.id_oficina=o.id";
        return db::query(Database::SELECT, $sql)->execute();
    }

    //cantidad de usuarios conectados
    public function conectados() {
        $sql = "SELECT COUNT(*) as cantidad FROM (
                SELECT DISTINCT(user_id),session,(unix_timestamp()-last_active)/60 as minutos,from_unixtime(last_active) AS last_active  FROM dbsigec.sesiones) AS x
                WHERE x.minutos < 10";
        return db::query(Database::SELECT, $sql)->execute();
    }
    //usuarios conectados
    public function usuariosconectados() {
        $sql = "SELECT c.last_active,ROUND(minutos,2) AS segundos,nombre,cargo,id FROM (SELECT *  FROM (
                SELECT DISTINCT(user_id),session,(unix_timestamp()-last_active)/60 as minutos,from_unixtime(last_active) AS last_active  FROM dbsigec.sesiones) AS x
                WHERE x.minutos < 10 )
                AS c INNER JOIN users u ON c.user_id=u.id order by last_active DESC";
        return db::query(Database::SELECT, $sql)->execute();
    }
    //usuarios conectados
    public function actividades() {
        $f1=date('Y-m-d')." 00:00:00";
        $f2=date('Y-m-d H:i:s');
        $sql = "SELECT * FROM vitacora where fecha_hora BETWEEN '$f1' and '$f2' 
                ORDER BY fecha_hora DESC LIMIT 10";
        return db::query(Database::SELECT, $sql)->execute();
    }

    //propiedades de un usuario
    public function property($id) {
        $sql = "SELECT u.id, u.dependencia, u.nombre,u.cargo,o.nombre, u.genero, u.id_oficina 
            FROM users u INNER JOIN oficinas o ON u.id_oficina=o.id
            WHERE u.id='$id'";
        return db::query(Database::SELECT, $sql)->execute();
    }

    /* Administrador */

    //lista de usuarios excepto quien lo 
    public function usuarios($id) {
        $sql = "SELECT u.id, u.id_oficina, o.oficina, u.username, u.nombre, u.last_login,u.mosca,u.cargo,u.email,u.logins, u.fecha_creacion,u.genero,n.nivel FROM users u
            INNER JOIN oficinas o ON u.id_oficina=o.id
            INNER JOIN niveles n ON u.nivel=n.id
            WHERE u.id <> '$id'";
        return $this->_db->query(Database::SELECT, $sql, TRUE);
    }

    public function todo($id) {
        
    }

}

?>
