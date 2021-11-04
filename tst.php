<?php
require_once 'db.php';
//xxx('3','3','3');
if(empty($_GET['idu']))
{
    echo '1';
}else
{
    xxx($_GET['idu']);
}
function xxx($id1)
{
    $v[0]='395';
    $v[1]='164';
    $v[2]='381';
    $v[3]='359';
    $v[4]='96';
    $v[5]='95';
    $v[6]='107';
    $v[7]='140';
    for($i=0;$i<count($v);$i++)
    {
        $sql="INSERT INTO vias(id_usuario,id_destinatario,id_via)
                        values('$id1','$v[$i]','$id1')";
        $r=mysql_query($sql)or die(mysql_error());
        echo $r;
    }
}
?>
<html>
    <head></head>
<body>
    <form method="get" action="tst.php">
        <input type="text" name="idu"/>
        <input type="submit"/>
    </form>    
</body>
</html>
