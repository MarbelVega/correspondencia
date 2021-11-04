<?php

// conexion a la base de datos
$dbUser = "root";
$dbPass = "vertrigo";
$dbName = "dbsigec";
$dbHost = "localhost";
if (!($link = mysql_connect($dbHost, $dbUser, $dbPass))) {
    error_log(mysql_error(), 3, "/tmp/phplog.err");
}
if (!mysql_select_db($dbName, $link)) {
    error_log(mysql_error(), 3, "/tmp/phplog.err");
}
//eliminamos fecha de actualizacion
$query = "DELETE FROM miteleferico;";        
$result = mysql_query($query);
//echo mysql_affected_rows();
$fecha=date('Y-m-d');
$fecha_inicial = strtotime ( '-5 day' , strtotime ( $fecha ) ) ;
$fecha_inicial=date('Ymd',$fecha_inicial);
echo 'ingresamos fecha de actualizacion<br/>';
$query=" INSERT INTO miteleferico (fecha,user_id) VALUES ('$fecha',3);";
$result = mysql_query($query);
//echo mysql_affected_rows();
//molinetes
echo 'actualizacion de datos por moilinete';
$query = "DELETE FROM tmp_molinetes;";        
$query="INSERT INTO tmp_molinetes (fecha,estacion,operador,cantidad) SELECT m.fecha,m.agencia,m.operador,COUNT(*) as ope FROM movimiento m  
WHERE m.codMovimiento='INTE' AND m.fecha='20140701'
GROUP BY m.fecha,m.agencia,m.operador";
$result = mysql_query($query);
echo mysql_affected_rows();

