<?php
/*define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', 'limadecasi');
define('DB_NAME', 'paperwork');*/
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', 'vertrigo');
define('DB_NAME', 'dbsigec');
if(!$GLOBALS['DB']=  mysql_connect(DB_HOST,DB_USER,DB_PASSWORD))
{
    die ('Error');
}
if(!mysql_select_db(DB_NAME,$GLOBALS['DB']))
{
    mysql_close($GLOBALS['DB']);
    die ('Error abriendo bd');
}

//echo $GLOBALS['DB'];
?>