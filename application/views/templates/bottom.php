<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title></title>
<?php foreach($scripts as $file) { echo HTML::script($file, NULL, TRUE), "\n"; }?>
<script type="text/javascript">
$(document).ready(function(){
var options1 = {
format: '%A, %d de %B de %Y - %I:%M:%S %p'    }
 $('#jclock1').jclock(options1);
});
</script>
<style>
body{
	font-family:Arial, Helvetica, sans-serif;
	font-size:10px;
	background-color:#E7EAEE;
	color:#666;
}
a{
	text-decoration:none;
	font-weight:bold;
	color: #0060BF; 
	}
	
</style>
</head>
<body background="media/images/misc/fade.gif"  >
<div style="float:left;"><b>Copyright &copy; <a  href="mailto:adm_christian_vega@outlook.com" title="Powered by ">Sistemas</a> </b> - Mutual de Servicios al Policia</div>
<div id="jclock1"style="float:right;"></div>
</body>
</html>