<script>
    $(function () {
        $('a.documento').click(function () {
            var codigo = $(this).attr('alt');
            var left = screen.availWidth;
            var top = screen.availHeight;
            left = (left - 600) / 2;
            top = (top - 500) / 2;
            var r = window.showModalDialog("" + codigo, "", "center:0;dialogWidth:700px;dialogHeight:500px;scroll=yes;resizable=yes;status=yes;" + "dialogLeft:" + left + "px;dialogTop:" + top + "px");
            if (r[0] != null) {
            }
        });
        if ($('#hijo').val() > 0)
        {
            $('#agrupado').show();
        }
        $('html, body').animate({
            scrollTop: $("#scroll").offset().top
        }, 1000);
    });
</script>

<?php if (sizeof($seguimiento) > 0) { ?>
    <div class="card card-underline" >
        <div class=" card-head">
            <header><i class="fa fa-tags"></i> Hoja de Ruta : <?php echo $detalle['nur'] ?></header>
            <div class="toolss pull-right">
                <a  href="/print/seguimiento/?hr=<?php echo $detalle['nur'];?>" target="_blank" class="btn btn-sm btn-primary"><i class="md md-print"></i> Imprimir</a>
            </div>
        </div>
        <div class=" card-body" >
            <div class="row">
                <div class="col-lg-2 col-md-2">
                    <span class=" opacity-50">Referencia: </span>
                </div>
                <div class="col-lg-10 col-md-10">
                    <span class="text-medium text-primary-dark"><?php echo $detalle['referencia']; ?></span>
                </div>

            </div>
            <div class="row">
                <div class="col-lg-2 col-md-2">
                    <span class=" opacity-50">Documento Original: </span>
                </div>
                <div class="col-lg-5 col-md-5">
                    <span class="text-medium"><a href="/document/detalle/<?php echo $detalle['id_documento']; ?>" ><?php echo $detalle['codigo']; ?></a></span>
                </div>
                <div class="col-lg-2 col-md-2">
                    <span class=" opacity-50">Proceso: </span>
                </div>
                <div class="col-lg-3 col-md-3">
                    <?php echo $detalle['proceso']; ?>
                </div>
            </div>


            <div class="row">
                <div class="col-lg-2 col-md-2">
                    <span class=" opacity-50">Destinatario: </span>
                </div>
                <div class="col-lg-5 col-md-5">
                    <span class="text-medium"><?php echo $detalle['destinatario']; ?> /  <?php echo $detalle['cargo_destinatario']; ?></span>
                </div>
                <div class="col-lg-2 col-md-2">
                    <span class=" opacity-50">Tipo Documento: </span>
                </div>
                <div class="col-lg-3 col-md-3">
                    <span><?php echo $detalle['tipo'] ?></span>
                </div>
            </div>


            <div class="row">
                <div class="col-lg-2 col-md-2">
                    <span class=" opacity-50">Remitente: </span>
                </div>
                <div class="col-lg-5 col-md-5">
                    <span class="text-medium"><?php echo $detalle['remitente']; ?> /  <?php echo $detalle['cargo_remitente']; ?></span>
                </div>
                <div class="col-lg-2 col-md-2">
                    <span class=" opacity-50">Fecha: </span>
                </div>
                <div class="col-lg-3 col-md-3">
                    <span class=" opacity-50"> 
                        <?php
                        echo Date::fecha($detalle['fecha']) . ' ' . date('H:i:s', strtotime($detalle['fecha']));
                        ;
                        ?>
                    </span>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-2 col-md-2">
                    <span class=" opacity-50">Archivos adjuntos: </span>
                </div>
                <div class="col-lg-10 col-md-10">
                    <span class="text-medium">
                        <?php foreach ($archivo as $a): ?>
                            <a href="/download/?file=<?php echo $a->id; ?>"     title="Descargar adjunto">    
                                <span class=" badge">
                                    <?php echo substr($a->nombre_archivo, 13); ?>    
                                </span>
                            </a> 
                        <?php endforeach; ?>
                    </span>
                </div>

            </div>            
        </div>
    </div>

    <!--  Seguimiento -->
    <div class="card card-underline" >
        <div class=" card-head">
            <header><i class="fa fa-bookmark-o"></i> Seguimiento del proceso</header>
            <div class="tools">
                <?php if (isset($agrupado->id)): ?>
                    <div id="padre" style="text-align: center;" >
                        <span class="text-xl text-primary-dark">
                            <i class="fa fa-folder-o"></i>
                            <a href="/route/trace/?hr=<?php echo $agrupado->padre; ?>" ><span class=" text-primary-dark"><?php echo $agrupado->padre; ?></span></a>
                        </span>                        
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class=" card-body" >
            <?php if (isset($agrupado->id)): ?>
                <div role="alert" class="alert alert-callout alert-warning">
                    <strong>Una copia pertenece a la Hoja de Ruta principal: </strong> <a href="/route/trace/?hr=<?php echo $agrupado->padre; ?>" style="color: #275592; font-weight: bold;  "><?php echo $agrupado->padre; ?></a>
                </div>                
            <?php endif; ?>
            <ul class="timeline collapse-lg">
                <?php
                $count = 0;
                $hijo = 0;
                foreach ($seguimiento as $s):
                    ?>
                    <li class="timeline-inverted">
                        <div class="timeline-circ circ-xl style-<?php
                        if ($s->oficial > 0)
                            echo 'primary-dark';
                        else
                            echo $s->color
                            ?>"><span class="fa fa-leaf"></span></div>
                        <div class="timeline-entry">
                            <div class="card style-<?php echo $s->color ?>">
                                <div class="card-body small-padding">
                                    <div class="col-lg-5 col-md-5">
                                        <?php
                                        $pasos = "";
                                        if ($s->oficial > 0) {
                                            if ($s->id_estado == 1) {
                                                $pasos = 'fa fa-flag fa-2x';
                                                $scrolltop = 'scroll';
                                            } else
                                                $pasos = 'fa fa-paw';
                                        }
                                        ?>
                                        <span class="text-warning text-xl stick-top-right"><i class="<?php echo $pasos ?>"></i></span>
                                        <span class="text-medium">
                                            <p>
                                                <?php if (file_exists(DOCROOT . 'static/fotos/' . $s->u1 . '.jpg')): ?>
                                                    <img class="img-circle img-responsive pull-left width-1 " width="110" src="/static/fotos/<?php echo $s->u1 ?>.jpg" alt="" />
                                                    <?php
                                                else:
                                                    ?>
                                                    <img class="img-circle img-responsive pull-left width-1 " width="110" src="/static/fotos/<?php echo $s->s1 . '.jpg' ?>" alt="" />
                                                <?php endif; ?>

                                                <span class="text-medium"><a href="/route/oficina/<?php echo $s->id_de_oficina ?>" ><?php echo $s->de_oficina; ?></a>
                                                    <br/> <a href="/users/profile/" class="text-primary-dark"><?php echo $s->nombre_emisor; ?></a></span><br>
                                                <span class="opacity-75">
                                                    <?php echo $s->cargo_emisor; ?>
                                                </span>
                                            </p>
                                            <span class="opacity-50 pull-right text-light"><i class="fa fa-arrow-up"></i> 
                                                <?php
                                                echo Date::fecha_medium($s->fecha_emision)
                                                . ' - ' . $s->hora_emision;
                                                ?>
                                            </span>
                                        </span>
                                    </div>
                                    <div class="col-lg-5 col-md-5">

                                        <?php
                                        $pasos = "";
                                        if (($s->oficial > 0) && ($s->id_estado == 6)) {
                                            $pasos = 'fa fa-flag fa-2x';
                                            $scrolltop = 'scroll';
                                        }
                                        $pasos = "";
                                        if (($s->oficial > 0) && ($s->id_estado == 2)) {
                                            $pasos = 'fa fa-flag fa-2x';
                                            $scrolltop = 'scroll';
                                        }

                                        if (($s->oficial > 0) && ($s->id_estado == 4)) {
                                            $pasos = 'fa fa-paw';
                                        }
                                        if (($s->oficial > 0) && ($s->id_estado == 10)) {
                                            $pasos = 'fa fa-flag fa-2x';
                                            $scrolltop = 'scroll';
                                        }
                                        ?>
                                        <span class="text-warning text-xl stick-top-right"><i class="<?php echo $pasos ?>" id="<?php echo $scrolltop ?>"></i></span>
                                        <span class="text-medium">
                                            <p>
                                                <?php if (file_exists(DOCROOT . 'static/fotos/' . $s->u2 . '.jpg')): ?>
                                                    <img class="img-circle img-responsive pull-left width-1 " width="110" src="/static/fotos/<?php echo $s->u2 ?>.jpg" alt="" />
                                                    <?php
                                                else:
                                                    ?>
                                                    <img class="img-circle img-responsive pull-left width-1 " width="110" src="/static/fotos/<?php echo $s->s2 . '.jpg' ?>" alt="" />
                                                <?php endif; ?>
                                                <span class="text-medium"><a href="/route/oficina/<?php echo $s->id_a_oficina ?>" ><?php echo $s->a_oficina; ?></a>
                                                    <br/> <a href="/users/profile/" class="text-primary-dark"><?php echo $s->nombre_receptor; ?></a></span><br>
                                                <span class="opacity-75">
                                                    <?php echo $s->cargo_receptor; ?>
                                                </span>
                                            </p>
                                            <span class="opacity-50 pull-right text-light">Enviado: 
                                                <?php
                                                echo Date::fecha_medium($s->fecha_recepcion)
                                                . ' - ' . $s->hora_recepcion;
                                                ?>
                                            </span>
                                        </span>
                                    </div>
                                    <div class="col-lg-2 col-md-2">
                                        <span class=" badge  style-<?php
                                        if ($s->oficial > 0)
                                            echo 'info';
                                        else
                                            'default-dark';
                                        ?> text-medium">
                                              <?php echo $s->estado; ?>    
                                        </span>
                                        <span class=" text-xs">
                                            <?php
                                            if (($s->oficial == 1) && ($s->id_estado == 10)) {
                                                //obtenemos donde se archivo
                                                $mSeguimiento = new Model_Seguimiento();
                                                $archivado = $mSeguimiento->hrArchivada($s->nur, $s->derivado_a);
                                                if ($archivado) {
                                                    if ($user->id_oficina == $s->id_a_oficina) {
                                                        echo '<a href="/correspondence/folder/' . $archivado['id'] . '"><div class="archivado"></div></a>';
                                                        echo '<div class="nomfol"><i class="fa fa-text-o"></i>' . $archivado['carpeta'] . '</div>';
                                                        echo '<div class="obs"><b>OBS: </b>' . $archivado['observaciones'] . '</div>';
                                                    } else {
                                                        echo '<div class="nomfol">' . $archivado['carpeta'] . '</div>';
                                                        echo '<div class="obs"><b>OBS: </b>' . $archivado['observaciones'] . '</div>';
                                                    }
                                                    $count++;
                                                }
                                            }
                                            ?>
                                            <br/>
                                            <!-- TODO:ADJUNTO DL SEGUIMENTO DEL PROGRESO -->
                                            <!-- Adjunto:                       
                                            <br/>
                                            <?php foreach (json_decode($s->adjuntos) as $k => $a): ?>
                                                <a href="/vista/?doc=<?php echo $a; ?>&id_seg=<?php echo $s->id; ?>" target="_blank" ><?php echo $a; ?></a><br/>                   
                                                <br/>
                                            <?php endforeach; ?>
                                            <?php
                                            $documentos = ORM::factory('documentos')->where('id_seguimiento', '=', $s->id)->find_all();
                                            foreach ($documentos as $d):
                                                ?>
                                                <a  href="/vista/?doc=<?php echo $d->cite_original; ?>&id_seg=<?php echo $s->id; ?>" target="_blank" ><?php echo $d->codigo; ?></a><br/>
                                            <?php endforeach;?> -->
                                            
                                            
                                            

                                        </span>

                                    </div>

                                    <div class="row">
                                        <div class="col-lg-12 col-md-12">
                                            <span class=" text-medium opacity-75 text-light"><i class="md md-message"></i><?php echo $s->proveido ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>            

                    <?php
                    $hijo+=$s->hijo;
                endforeach;
                ?></ul>
            <hr/>
            <div style="text-align:center;">
                <?php if ($hijo > 0): ?>
                    <div role="alert" class="alert alert-callout alert-warning">
                        <input type="hidden" id="hijo" value="1" name="hijo"/>
                        <strong>Agrupado con: </strong> 
                        <?php
                        $hijos = ORM::factory('agrupaciones')->where('padre', '=', $detalle['nur'])->find_all();
                        foreach ($hijos as $h):
                            ?>
                            <a href="/route/trace/?hr=<?php echo $h->hijo; ?>" style="color:#1C4781; font-size: 14px; text-decoration: underline;  " ><?php echo $h->hijo; ?></a>
                        <?php endforeach; ?>

                    </div>                
                <?php else: ?>
                    <input type="hidden" id="hijo" value="0" name="hijo"/>
                <?php endif; ?>
            </div>

            <div class="alert alert-info" style="text-align:center;">
                <p><span style="float: left; margin-right: .3em;" class=""></span>    
                    &larr;<a onclick="javascript:history.back();
                            return false;" href="javascript:;" style="" > Regresar<a/></p>    
            </div>
            <?php
        }
        else {
            ?>
            <!-- mostrar mensajes -->
            <div class="alert alert-info">
                <p><span class=""></span>    
                    <strong>Mensaje: </strong> Hoja de ruta aun no derivada. &larr;<a onclick="javascript:history.back();
                            return false;" href="#" style="" > Regresar</a></p>    
            </div>
            <br/>
        <?php } ?>
        <?php ?>
    </div>
</div>
    <a  href="/print/seguimiento/?hr=<?php echo $detalle['nur'];?>" target="_blank"class="btn btn-sm btn-primary"><i class="md md-print"></i> Imprimir</a>