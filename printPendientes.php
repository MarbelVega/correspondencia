
<html>
    <head><title>Reporte</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <style>
            @media print{
                button{
                    display: none;
                }
                body{
                    margin: 0px;
                }
            }
            body{
                font-family: arial;
            }
            cite{
                font-family: sans-serif;
                font-size: 9px;
            }
            td,th{
                font-size: 10px;
            }
        </style>
    </head>
<body>
<?php
    if(isset($_GET['idu']))
    {
    $idu=base64_decode($_GET['idu']);
    //conexion a la base de datos
    //$dbh = new PDO('mysql:host=localhost;port=3306;dbname=paperwork', 'root', 'limadecasi', array( PDO::ATTR_PERSISTENT => false));
    $dbh = new PDO('mysql:host=localhost;port=3306;dbname=dbsigec', 'root', 'vertrigo', array( PDO::ATTR_PERSISTENT => false));
    $sql="SELECT  s.id, s.padre,s.hijo,s.id_seguimiento,s.nur, s.nombre_emisor,UPPER(s.cargo_emisor) as cargo_emisor,s.de_oficina,s.fecha_emision as fecha,s.fecha_recepcion as fecha2, a.accion, s.oficial, s.hijo, s.proveido,s.adjuntos,s.archivos
             , d.codigo, CONCAT(d.nombre_destinatario,' \n ', d.cargo_destinatario) as nombre_destinatario,d.referencia,d.id as id_doc,s.prioridad
                FROM 
                (SELECT *
                FROM seguimiento WHERE derivado_a='$idu' and estado='2') as s 
                INNER JOIN documentos as d ON s.nur=d.nur
                INNER JOIN acciones a ON s.accion=a.id
                WHERE d.original='1' ORDER BY s.fecha_emision DESC";
    $stmt = $dbh->prepare($sql);        
    $stmt->execute();
    echo '<button onclick="window.print()">Imprimir</button>';
    echo '<table border=1>';
        echo '<tr>';
        echo '<th>HOJA DE RUTA</th>';
        echo '<th>CITE</th>';echo '<th>REMITENTE</th>';
        echo '<th>REFERENCIA</th>';
        echo '<th>DIAS</th>';
        echo '</tr>';
    while($rs=$stmt->fetch()) 
    {
        echo '<tr>';
        echo '<td>'.$rs[4].'<br><cite>'.$rs[8].'</cite></td>';

echo '<td>'.utf8_encode($rs[16]).'</td>';        echo '<td>'.utf8_encode($rs[7]).'<br><cite>'.utf8_encode($rs[5]).'</cite>/<cite>'.utf8_encode($rs[6]).'</cite></td>';
        echo '<td>'.utf8_encode($rs[21]).'<br>Acci&oacute;n:<cite>'.utf8_encode($rs[10]).'</cite></td>';
        echo '<td>'.$rs[24].' d&iacute;as</td>';
        echo '</tr>';
    }
    echo '</table>';
    }    
?>
</body>
</html>
<?php
header('content-type: text/html; charset=utf-8');
?>