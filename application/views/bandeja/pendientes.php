<script type="text/javascript">
 
    $(function () {
        $("div#entrada .bandeja").each(function () {
            var t = $(this).text().toLowerCase(); //all row text
            $("<table class='indexColumn'></table>")
                    .hide().text(t).appendTo(this);
        });//each tr
        $("#FilterTextBox").keyup(function () {
            var s = $(this).val().toLowerCase().split(" ");
            //show all rows.
            $("div#entrada .bandeja:hidden").show();
            $.each(s, function () {
                $("div#entrada .bandeja .indexColumn:not(:contains('"
                        + this + "'))").parent().hide();
            });//each
        });//key up.      

//archivo y pendientes
        $('.sel').bind('click', function () {
            var count = $('input:checked').length;
            if (count < 1) {
                $('#opciones').addClass('oculto');
                $('#sup-group,#sup-archive').removeClass('badge');
                $('#sup-group,#sup-archive').removeClass('style-default');
                $('#group,#archive').removeClass('btn-primary-dark').addClass('btn-default ').removeClass('animated').removeClass('bounceIn');
                $('#group,#archive').attr('title', '');

                $('#sup-group,#sup-archive').text('');
            }
            else
            {
                $('#sup-group,#sup-archive').addClass('badge');
                $('#sup-group,#sup-archive').addClass('style-default');
                $('#sup-group,#sup-archive').text(count);
                var nurs = '';
                $('input:checked').each(function () {
                    if ($(this).is(':checked'))
                    {
                        nurs = nurs + "\n " + $(this).attr('rel');
                    }
                });
                $('#group,#archive').removeClass('btn-defaul').addClass('btn-primary-dark').addClass('animated').addClass('bounceIn');
                $('#group').attr('title', 'Agrupar: \n' + nurs)
                $('#archive').attr('title', 'Archivar: \n' + nurs)
                //$('#seleciones').html(nurs);
                //$('#opciones').removeClass('oculto');
            }
        });

//modal

        //esto se se refresca la pagina
        var count = $('input:checked').length;
        if (count == 0) {
            $('#opciones').addClass('oculto');
        }
        else
        {
            var nurs = '';
            $('input:checked').each(function () {
                if ($(this).is(':checked'))
                {
                    nurs = nurs + "" + $(this).attr('rel');
                }
            });
            $('#seleciones').html(nurs);
            $('#opciones').removeClass('oculto');

        }
        $('a#archive').click(function () {
            $('#accion').val('0');
            $('form#doa').submit();
        });
        $('a#group').click(function () {
            $('#accion').val('1');
            var count = $('input:checked').length;
            if (count > 1)
                $('form#doa').submit();
            else
            {
                alert('Para poder agrupar debe de seleccionar por lo menos 2 hojas de ruta');
                return false;
            }
        });

        $('#tipoCorr').change(function () {
            var tipo = $(this).val();
            if (tipo != '')
            {
                $('.bandeja').hide();
                $('.' + tipo).fadeIn();
            }
            else
                $('.bandeja').show();
        });
        var copia = $('.tipo0').size();
        var oficial = $('.tipo1').size();
//alert(copia+':'+oficial);
        $('a.link2').click(function () {
            $this = $(this);
            var criterio = $this.attr('id');
            if ($this.is('.asc'))
            {
                $this.removeClass('asc');
                $this.addClass('desc');
                var sortdir = -1;
            }
            else
            {

                $this.addClass('asc');
                $this.removeClass('desc');
                var sortdir = 1;
            }
            $(this).siblings().removeClass('asc');
            $(this).siblings().removeClass('desc');
            //sort
            var nurs = $('div.bandeja').get();
            nurs.sort(function (a, b) {
                var val1 = $(a).attr('' + criterio).toUpperCase();
                var val2 = $(b).attr('' + criterio).toUpperCase();
                return (val1 < val2) ? -sortdir : (val1 > val2) ? sortdir : 0;
            });
            $.each(nurs, function (index, row) {
                $('form#doa').append(row);
            });
            return false;
        });
        $('#FilterTextBox').focus();
    });
</script>
<style>
    sup.badge{top: 0.1cm !important;}
</style>
<?php if (sizeof($entrada) > 0) { ?>
    <div style=";position: fixed; z-index: 10; margin-top: -24px; padding: 2px; background: #fff; width: 100%;" class="col-lg-11 col-md-12">
        <div class="row">
            <div class="col-lg-4 col-md-4">
                <h3><i class="fa fa-clock-o"></i> Correspondencia Pendiente <!---cpeee--></h3>
            </div>
            <div class="col-lg-2 col-md-2"><i class="fa fa-filter"></i> Filtrar:          
                <input type="text" id="FilterTextBox" name="FilterTextBox" class="form-control" size="15" />
            </div>
            <div class="col-lg-6">
                <div class="btn-group ">
                    <button class="btn ink-reaction btn-sm btn-default" type="button"><i class="fa fa-sort-alpha-asc"></i> Ordenar por</button>
                    <button data-toggle="dropdown" class="btn ink-reaction btn-sm btn-default dropdown-toggle" type="button" aria-expanded="false"><i class="fa fa-caret-down"></i></button>
                    <ul role="menu" class="dropdown-menu dropdown-menu-right">
                        <li><a href="#" class="link2" id="hojaruta">Hoja Ruta</a></li>
                        <li><a href="#" class="link2" id="fecha">Fecha</a></li>
                        <li><a href="#" class="link2" id="oficina">Oficina</a></li>
                        <li><a href="#" class="link2" id="proceso">Proceso</a></li>
                    </ul>
                </div>
                <a href="javascript:;" class="btn btn-sm btn-default"  data-toggle="title" id="group" title="Permite agrupar 2 o + tramites o precesos en uno solo."><i class="fa fa-link"></i> AGRUPAR 
                    <sup class="badge " id="sup-group"></sup>
                </a>         
                <a href="javascript:;" class="btn btn-sm btn-default" id="archive" title="Permite arhivar 1 o + tramites o procesos." ><i class="fa fa-archive"></i> ARCHIVAR
                    <sup class="badge " id="sup-group"></sup>
                </a>         
                <!--<a href="#" id="print_hr" ><img src="/media/images/excel.png" align="absmiddle"  /><b> Generar Excel</b></a>         -->

                <a href="/print/pendientes/?id=<?php echo time();?>" target="_blank"  class="btn btn-sm btn-default-bright "><i class="fa fa-print"></i> Imprimir</a>

            </div>

        </div>
    </div>
    <div id="entrada" class="card" style="margin-top: 42px; position: relative;">    
        <form action="/bandeja/doa" method="post" id="doa" >
            <?php foreach ($entrada as $s): ?>
                <div class="bandeja tipo<?php echo $s->oficial; ?>" style="display:inline-block; " oficina="<?php echo $s->de_oficina ?>" proceso="<?php echo $s->referencia ?>"  fecha="<?php echo $s->fecha2; ?>" hojaruta="<?php echo $s->nur; ?>">
                    <table class="oficial<?php echo $s->oficial; ?> ">
                        <tr>
                            <td width="118" rowspan="2"  align="center" valign="top" class="nur<?php echo $s->oficial; ?><?php echo $s->prioridad; ?>">
                                <div class="oficial<?php echo $s->oficial; ?> ">
                                    <div class="checkbox checkbox-styled ">
                                        <label>&nbsp;&nbsp;
                                            <input type="checkbox"  name="id_seg[]" value="<?php echo $s->id; ?>" rel="<?php echo $s->nur; ?>"  class="sel">

                                        </label>
                                    </div>

                                </div>				
                            </td>
                            <td valign="top" colspan="3" >
                                <h4 class="text-primary-dark"><a href="/document/detalle/<?php echo $s->id_doc ?>" ><?php echo $s->referencia; ?></a></h4>                                			 				
                            </td>

                        </tr>
                        <tr>
                            <td width="50%" colspan="2" valign="top">
                                <div>
                                    <span class=" text-light"><b><?php echo $s->nombre_emisor; ?> </b> - 
                                        <?php echo $s->cargo_emisor; ?></span><br/>
                                    <span class="oficina opacity-75"><?php echo $s->de_oficina; ?></span>	    
                                </div>
                            </td>            
                            <td class="derecha" valign="top">     
                                <span class="proveido text-accent-light"><i class=" fa fa-comments-o"></i> <?php echo $s->proveido; ?></span>
                                <br/><span class=" text-accent-dark"><?php echo $s->accion; ?></span><br/>                                                                    
                                <?php if ($s->hijo == 1): ?> <a href="/bandeja/agrupado/?hr=<?php echo $s->nur; ?>" class="link agrupado">Agrupado</a><?php endif; ?>
                            </td>            
                        </tr>
                        <tr>
                            <td width="88">
                                <a href="/route/trace/?hr=<?php echo $s->nur; ?>" class="nur<?php echo $s->oficial; ?>"><?php echo $s->nur ?></a>
                            </td>
                            <td colspan="2">
                                <span class=" opacity-75"><?php echo Date::fecha($s->fecha2); ?></span>                
                            </td>
                            <td>                
                                <span class="opciones">                     
                                    <a href="/route/deriv/?hr=<?php echo $s->nur; ?>" class=" btn btn-sm btn-primary-dark" title="Derivar " id_nur="<?php echo $s->nur; ?>" id_seg="<?php echo $s->id; ?>" nuri="<?php echo $s->nur ?>"><i class="fa fa-share"></i> Derivar</a> 

                                    <div class="btn-group">
                                        <button class="btn ink-reaction btn-sm btn-default" type="button">Responder con</button>
                                        <button data-toggle="dropdown" class="btn ink-reaction btn-sm btn-default dropdown-toggle" type="button" aria-expanded="false"><i class="fa fa-caret-down"></i></button>
                                        <ul role="menu" class="dropdown-menu dropdown-menu-right">
                                            <?php foreach ($tipos as $t): ?>

                                                <li><a href="/route/responder/?id_seg=<?php echo $s->id; ?>&d=<?php echo $t['id']; ?>&n=<?php echo $s->nur; ?>"><?php echo $t['tipo'];
                                                //echo ' - '.$t['id'].' - s->id:_'.$s->id; ?>
                                                </a>
                                                </li>                                            
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>

                                </span>
                            </td>
                        </tr>
                        <?php
                        //$dias = floor((($segundos / 3600) / 24));
                        switch ($s->dias) {
                            case 0:
                                $color = "style-success";
                                break;
                            case 1:
                                $color = "style-success";
                                break;
                            case 2:
                                $color = "style-warning";
                                break;
                            default:
                                $color = "style-danger";
                                break;
                        }
                        ?>
                        <sup class="badge pull-2 <?php echo $color; ?> pull-right"><?php echo $s->dias; ?> dias</sup>
                    </table>    

                    <?php // $segundos = (time() - strtotime($s->fecha2)); ?>
                </div> 
            <?php endforeach; ?>
            <?php echo Form::hidden('accion', '', array('id' => 'accion')); ?>          
        </form>        
    </div>

<?php } else { ?>
    <div class="alert alert-info"> 
        <p>
            <i class="fa fa-info-circle"></i> Lista Vacia!, Usted no tiene correspondencia pendiente.</p>
    </div>
<?php } ?>