<script type="text/javascript">
    function visible(text)
    {
        //Fade in Background
        $('body').append('<div id="fade"></div>'); //Add the fade layer to bottom of the body tag.
        $('#fade').css({'filter': 'alpha(opacity=80)'}).fadeIn(); //Fade in the fade layer - .css({'filter' : 'alpha(opacity=80)'}) is used to fix the IE Bug on fading transparencies 
        $('#loading').css({'filter': 'alpha(opacity=80)'}).fadeIn().append('<span>').html('<img src="/media/images/load-indicator.gif" align="absmiddle" alt="" /> ' + text);
    }
    function ocultar()
    {
        $('#fade , #loading').fadeOut(function () {
            $('#fade, a.close').remove();  //fade them both out
        });
    }

//adicionar un destinatariov
    function ajaxs(oficial)
    {
        //$('#theTable tbody tr').remove();
        var hijo = $('#hijo').val();
        var destinatario = $('#destino').val();
        var accion = $('#accion').val();
        var accion_texto = $('#accion option:selected').text();
        var proveido = $('#proveido').val();
        var user = $('#user').val();
        var adjunto = $('#adjunto').val();
        var id_seg = $('#id_seg').val();
        var estado = $('#estado').val();
        var document = $('#document').val();
        var adjunto = $('#adjunto').val();
        var tipo = $('#oficial').val();
        var fecha = $('#fecha').val();
        var urgente = $('input:checkbox#urgente').attr('checked');
        if (urgente == undefined)
            urgente = 0;
        else
            urgente = 1;
        if (adjunto == null)
        {
            adjunto = 0;
        }
        var nur = $('#nur').val();
        visible('Derivando...');
        $.ajax({
            type: "POST",
            data: {tipo: tipo, oficial: oficial, fecha: fecha, destino: destinatario, adjunto: adjunto, document: document, nur: nur, accion: accion, proveido: proveido, hijo: hijo, user: user, adjunto:adjunto, id_seg: id_seg, estado: estado, urgente: urgente},
            url: "/ajax/derivar",
            dataType: "json",
            success: function (item)
            {
                ocultar();
                if (item.id)
                {
                    var adjunto = '';
                    $.each(item.adjunto, function (k, v) {
                        adjunto = adjunto + v + "<br/>";
                    });
                    if (item.oficial == "0")
                    {
                        $('#theTable tbody').append('<tr class="oficial0"><td rowspan="2" ><a href="javascript:;" onclick="activar($(this));" class="btn btn-sm btn-danger" title="Cancelar derivación"  id="' + item.id + '" destino="' + item.id_destino + '" oficial="' + item.oficial + '" ><i class="fa fa-trash-o"></i></a></td><td rowspan="2"><b class="label style-default">COPIA</label></td><td>' + item.receptor_nombre + '<br/><b>' + item.receptor_cargo + '</b></td><td>' + accion_texto + '<br/></td><td><b>' + adjunto + '</b></td></tr><tr class="oficial0"><td colspan="3"><b>Proveido: </b>' + item.proveido + '</td></tr>');
                    }
                    else
                    {
                        $('#theTable tbody').append('<tr class="oficial1"><td rowspan="2" ><a href="javascript:;" onclick="activar($(this));" class="btn btn-sm btn-danger" title="Cancelar derivación"  id="' + item.id + '" destino="' + item.id_destino + '" oficial="' + item.oficial + '" ><i class="fa fa-trash-o"></i></a></td><td rowspan="2"><label class="label style-primary-dark">OFICIAL</label></td><td>' + item.receptor_nombre + '<br/><b>' + item.receptor_cargo + '</b></td><td>' + accion_texto + '<br/></td><td><b>' + adjunto + '</b></td></tr><tr class="oficial1"><td colspan="3" ><b>Proveido: </b>' + item.proveido + '</td></tr>');
                    }
                }
                else
                {
                    ocultar();
                    alert(item.error);
                }
            },
            error: function () {
                ocultar();
            }
        });
    }
    function activar(link) {
        var $this = link;
        var id = $this.attr('id');
        //alert(id)
        var destino = $this.attr('destino');
        var oficial = $this.attr('oficial');
        var document = $('#document').val();
        visible('Quitando destinatario...');
        $.ajax({
            type: "POST",
            data: {id: id, destino: destino, oficial: oficial, document: document},
            url: "/ajax/eliminar",
            dataType: "json",
            success: function (item)
            {
                ocultar();
            },
            error: function () {
                ocultar();
            }
        });
        $this.parent('td').parent('tr').next().remove();
        $this.parent('td').parent('tr').remove();
        return false;
        console.log(link.attr('title'));
    }
    $(function () {
        $('body').append(
                $('<div>').attr('id', 'loading').addClass('loading').css({
            position: 'absolute',
            display: 'none',
            top: '48%',
            left: '48%',
            background: '#ffffff'
        })
                );
        $('#fecha-resp').datepicker({autoclose: true, todayHighlight: true});
        $('#imprimir').click(function () {
            visible();
            ocultar();
            return false;
        });
        //  $('#frmDerivar').validate();
        $('#dOficial').bind('click', function () {
            ajaxs(1);
            return false;
        });
        $('#dCopia').bind('click', function () {
            ajaxs(0);
            return false;
        });
        /*      $("#adjunto").fcbkcomplete({
         json_url: "/ajax/documentos",
         addontab: true,
         maxitems: 5,
         height: 5,
         cache: true
         });
         */
        /*    $('table#theTable :checkbox').on("click", function () {
         alert("sadad");
         var $this = $(this);
         var id = $this.attr('id');
         var destino = $this.attr('destino');
         var oficial = $this.attr('oficial');
         var document = $('#document').val();
         visible('Quitando destinatario...');
         $.ajax({
         type: "POST",
         data: {id: id, destino: destino, oficial: oficial, document: document},
         url: "/ajax/eliminar",
         dataType: "json",
         success: function (item)
         {
         ocultar();
         },
         error: function () {
         ocultar();
         }
         });
         $this.parent('td').parent('tr').next().remove();
         $this.parent('td').parent('tr').remove();
         return false;
         //var $this=$(this).find('input:checkbox');
         //  var id=$this.attr('id');       
         // alert('hola');   
         }); */

//eliminar
        $('#eliminar').click(function () {
            var len = 0;
            var destinatarios = [];
            var valor;
            $('#theTable tbody tr').each(function (index, domEle) {
                var checked = $(this).find('input:checkbox').attr('checked');
                if (checked) {
                    valor = $(this).find('input:checkbox').attr('id');
                    destinatarios[len] = valor;
                    len++;
                }
            });
            if (len > 0)
            {
                alert(destinatarios)
            }
            else {
                alert("Seleccione un destinatario por favor.");
            }
        });
$('#fecha').datepicker({autoclose: true, todayHighlight: true, format: "dd/mm/yyyy"});
        $('select').select2();
    });
</script>

<style type="text/css">
    table tr td {padding: 2px;}
    input[type="checkbox"]{cursor:pointer; border: 2px solid #DC5526;  }
    div.loading{border: 5px solid #666; background-color: #fff; padding: 10px;}
</style>
<div style="width: 100%;">

    <div id="derivacion">
        <!-- mostrar errores -->
        <?php if (sizeof($errors) > 0): ?>
            <div class="error">
                <p><span style="float: left; margin-right: .3em;" class=""></span>
                    <?php foreach ($errors as $k => $v): ?>
                        <strong><?= $k ?>: </strong> <?php echo $v; ?></p>
                <?php endforeach; ?>
            </div>
            <br/>
        <?php endif; ?>
        <div class="row">

            <div class="col-lg-12">
                <div class="card card-underline">
                    <div class=" card-head">
                        <header><i class="fa fa-tags"></i> Derivar <!--quique-->: <?php echo $documento->nur; ?></header>
                        <div class="tools">

                            <span class=" opacity-50">Cite original: </span>
                            <span class="text-medium text-primary-dark"><?php echo $documento->cite_original; ?></span>                            
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="/route/derivando/?nur=<?php echo $documento->nur; ?>" method="post"  class="form" id="frmDerivar">
                            <input type="hidden" value="<?php echo $hijo; ?>" name="hijo" id="hijo"/>
                            <input type="hidden" value="<?php echo $documento->nur; ?>" name="nur" id="nur"/>
                            <input type="hidden" value="<?php echo $id_seguimiento; ?>" name="id_seg" id="id_seg"/>
                            <input type="hidden" value="<?php echo $oficial; ?>" name="oficial" id="oficial" />
                            <input type="hidden" value="<?php echo $documento->estado; ?>" name="estado" id="estado"/>
                            <input type="hidden" value="<?php echo $user->id; ?>" name="user" id="user"/>
                            <input type="hidden" value="<?php echo $documento->id; ?>" name="document" id="document"/>

                            <div class="row">                                
                                <div class="col-lg-2 col-md-2">
                                    <span class=" opacity-50">Referencia: </span>
                                </div>
                                <div class="col-lg-10 col-md-10">
                                    <span class="text-medium text-primary-dark"><?php echo $documento->referencia; ?></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-2 col-md-2">
                                    <span class=" opacity-50">Destinatario: </span>
                                </div>
                                <div class="col-lg-10 col-md-10">
                                    <span class="text-medium">
                                        <?php echo $documento->nombre_destinatario; ?>
                                        | <?php echo $documento->cargo_destinatario; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-2 col-md-2">
                                    <span class=" opacity-50">Remitente: </span>
                                </div>
                                <div class="col-lg-10 col-md-10">
                                    <span class="text-medium">
                                        <strong><?php echo $documento->nombre_remitente; ?> | </strong> <?php echo $documento->cargo_remitente; ?>
                                    </span>
                                </div>
                            </div>
                            <hr/>
                            <div class="row">
                                <div class="col-lg-9 col-md-9">
                                    <div class="form-group">
                                        <?php echo Form::select('destino', $destinatarios, Arr::get($_POST, 'destino', NULL), array('id' => 'destino', 'class' => 'form-control required',)); ?>
                                        <?php echo Form::label('derivar', 'Derivar a :'); ?>             
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-3">
                                    
                                        <label>
                                            <input type="checkbox" name="urgente" class="" value="1" > <span>Urgente</span>
                                        </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6 col-md-6">
                                    <div class="form-group">                                     
                                        <?php echo Form::textarea('proveido', Arr::get($_POST, 'proveido', ''), array('COLS' => 12, 'rows' => 2, 'class' => 'required form-control', 'id' => 'proveido')); ?>
                                        <?php echo Form::label('proveido', 'Proveido'); ?>                            
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-3">
                                    <div class="form-group">
                                        <?php echo Form::select('accion', $acciones, Arr::get($_POST, 'accion', NULL), array('class' => 'required', 'id' => 'accion')); ?>
                                        <?php echo Form::label('accion', 'Accion'); ?>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-3">
                                    <div class="form-group control-width-normal">
                                        <div id="fecha-resp" class="input-group date">
                                            <div class="input-group-content">
                                                <input id="fecha" type="text" name="fecha"  class="form-control">
                                                <label for="fecha">Fecha max de respuesta </label>
                                            </div>
                                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>

                                        </div>
                                        <p class="help-block" title="Fecha maxima de respuesta en caso de solicitar un informe o nota interna">No obligatorio</p>
                                    </div>
                                </div>
                            </div>


                            <table style=" width:100%; ">
                                <tr>            
                                    <td colspan="2">    
                                        <?php if ($oficial != 0): ?>                
                                            <a href="#"   id="dOficial" class="btn btn-sm btn-primary-dark"><i class="md md-play-circle-outline"></i> Derivar Oficial</a>
                                        <?php endif; ?>
                                        <a href="#" id="dCopia" class="btn btn-sm btn-default"><i class="md md-play-circle-outline"></i> Derivar Copia</a>                               

                                    </td>
                                    <td align="right">                
                                        <?php if ($documento->estado == 0 && $documento->id_proceso==0): ?>
                                            <a href="/print/hre/?code=<?php echo $documento->nur; ?>&p=1" target="_blank" class="btn btn-sm btn-accent-dark"><i class="fa fa-print"></i> Imprimir Hoja de Ruta</a>                
                                        <?php else:
                                                if ($documento->estado == 0):
                                            ?>
                                            <a href="/print/hr/?code=<?php echo $documento->nur; ?>&p=1" target="_blank" class="btn btn-sm btn-accent-dark"><i class="fa fa-print"></i> Imprimir Hoja de Ruta</a>
                                        <?php 
                                                endif;
                                        endif; ?>
                                    </td>
                                </tr>
                            </table>                          
                            <?php echo Form::hidden('tipo', '', array('id' => 'tipo')); ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <table id="theTable" class="table table-condensed ">
                <thead>
                    <tr>
                        <th>Cancelar</th> 
                        <th>Tipo</th>             
                        <th>Derivado a</th>             
                        <th>Acción</th> 
                        <th>Fecha max respuesta</th> 
                    </tr>
                </thead>  
                <tbody>

                </tbody>
            </table>
        </div>
    </div>
</div>