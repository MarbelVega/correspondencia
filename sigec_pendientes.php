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

                body{
                    font-family: arial;
                }
                cite{
                    font-family: sans-serif;
                    font-size: 9px;
                }
                table{ border-collapse: collapse; }
                td{
                    font-size: 10px;
                }
                th{
                    font-size: 12px;
                    font-weight: bold;
                    background-color: #eeeeee; 
                }
                .row0 td{background: #efefef;}
            }
            .row0 td{background: #efefef;}
            table{ border-collapse: collapse; }
            td,th{
                font-size: 10px;
            }
            th{
                font-weight: bold;
                background-color: #efefef; 
            }
        </style>
        <script language="Javascript">
            function imprimir() {
                print();
            }
        </script>
    </head>
    <body onLoad="imprimir();"> 
        <?php
        if (isset($_GET['idu'])) {
            $idu = base64_decode($_GET['idu']);
            //conexion a la base de datos
            //$dbh = new PDO('mysql:host=localhost;port=3306;dbname=paperwork', 'root', 'r0salinda', array(PDO::ATTR_PERSISTENT => false));
            $dbh = new PDO('mysql:host=localhost;port=3306;dbname=dbsigec', 'root', 'vertrigo', array(PDO::ATTR_PERSISTENT => false));
            
            $sql = "SELECT s.id, s.padre,s.hijo,s.id_seguimiento,s.nur, s.nombre_emisor,s.cargo_emisor,
            s.de_oficina,s.fecha_emision as fecha,DATE_FORMAT(s.fecha_recepcion,'%d/%m/%Y %H:%i:%s') as fecha_recepcion, 
            a.accion, s.oficial, s.hijo, s.proveido,s.adjuntos,s.archivos
             , d.codigo,d.cite_original, d.nombre_destinatario,d.nombre_destinatario, 
             d.cargo_destinatario,d.referencia,d.id as id_doc,s.prioridad,DATEDIFF(NOW(),s.fecha_recepcion)AS 'dias_recepcion'
               FROM 
                (SELECT *
                FROM seguimiento WHERE derivado_a='$idu' and estado='2') as s 
                INNER JOIN documentos as d ON s.nur=d.nur
                INNER JOIN acciones a ON s.accion=a.id
                WHERE d.original='1' ORDER BY s.fecha_recepcion DESC";
            $stmt = $dbh->prepare($sql);
            $stmt->execute();
            ?>
            <button onclick="window.print();">Imprimir</button>
            <table border=1>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>HOJA DE RUTA / CITE</th>                        
                        <th>REMITENTE</th>
                        <th>REFERENCIA</th>
                        <th>RECHA DE RECEPCION</th>
                        <th>DIAS</th>
                    </tr>
                </thead>
                <?php
                $i = 1;
                while ($rs = $stmt->fetch(PDO::FETCH_OBJ)) {
                    ?>
                    <tr class="row<?php echo $i % 2; ?>">
                        <td><?php echo $i; ?></td>
                        <td><b><?php echo $rs->nur; ?></b><br/><br/><cite><?php echo utf8_encode($rs->cite_original); ?></cite></td>
                        <td><?php echo utf8_encode($rs->nombre_emisor); ?><br/><b><?php echo utf8_encode($rs->cargo_emisor); ?></b></td>                        
                        <td><?php echo utf8_encode($rs->referencia); ?></td>
                        <td><?php echo $rs->fecha_recepcion; ?></td>
                        <td><?php echo $rs->dias_recepcion; ?></td>
                    </tr>
                    <?php
                    $i++;
                }
                ?>
            </table>
<?php } ?>
    </body>
</html>
