<script type="text/javascript">
    $(function(){                           
        $("#imprime").click(function(){
            window.print();
            return false;
        });
    });
</script>
<style>
    #file-word{ display: none;  }
    td{padding:5px;}    
</style>

<p style="float: right;"><a href="javascript:void(0)" id="imprime" class="uiButton"><img src="/media/images/printer.png" align="absmiddle" alt=""/> Imprimir</a></p><br/>
<div  style="width:100%; height: 100%;  ">  
<table style=" width: 100%;">
    <tbody>
        <tr>
            <td colspan="2" align="center">
                <h2><?php echo strtoupper($tipo);?></h2>
                <h2 style="color: #23599B;"><?php echo $d->cite_original;?></h2>
                <h2><?php echo $d->nur;?></h2>
            </td>
        </tr>
        <tr>
        <td><b>A: </b></td>
        <td colspan="2"> <?php echo $d->nombre_destinatario;?><br/><b><?php echo $d->cargo_destinatario;?></b></td>
    </tr>
    <?php if(trim($d->nombre_via)!=''){ ?>
    <tr> 
        <td><b>VIA: </b><br/> </td>
        <td colspan="2"><?php echo $d->nombre_via;?><br/><b><?php echo $d->cargo_via;?></b></td>
    </tr>
    <?php } ?>
    <tr> 
        <td><b>DE: </b><br/> </td>
        <td><?php echo $d->nombre_remitente;?><br/><b><?php echo $d->cargo_remitente;?></b></td>
    </tr>
    <tr> 
        <td width="100"><b>FECHA DE CREACI&Oacute;N: </b><br/> </td>
        <td colspan="2"><?php echo  Date::fecha($d->fecha_creacion);?></td>
    </tr>
     <?php foreach($archivo as $a): ?>
    <tr> 
        <td><b>ARCHIVO:</b><br/></td>
        <td><?php echo HTML::anchor('/descargar.php?id='.$a->id,substr($a->nombre_archivo,13));?><br/></td>
    </tr>
    <?php ?>   
    <?php endforeach;?>
    <tr> <td><b>REFERENCIA:</b><br/> </td>
         <td colspan="2"><?php echo $d->referencia;?></td>
    </tr>
    
    <tr>  <td colspan="3">
            <div style="overflow:auto; width: 670px;">
           <?php echo $d->contenido;?>
            </div>
        </td>
    </tr>    
    </tbody>
</table>
    <div class="info" style="text-align:center;margin-top: 200px;">
    <p><span style="float: left; margin-right: .3em;" class=""></span>    
      &larr;<a onclick="javascript:history.back(); return false;" href="#" style="font-weight: bold; text-decoration: underline;  " > Regresar<a/></p>    
</div>
</div>