<?php
$menu = array();
$id = 0;
foreach ($menus as $m):
    $menu[$m->id][$m->id_submenu]['id'] = $m->id_submenu;
    $menu[$m->id][$m->id_submenu]['submenu'] = $m->submenu;
    $menu[$m->id][$m->id_submenu]['accion'] = $m->accion;
    $menu[$m->id][$m->id_submenu]['descripcion'] = $m->descripcion;
endforeach;
$sm = 0;
?>
<?php foreach ($menus as $m): ?>    
    <?php if ($id != $m->id): ?>
        <li  >  
            <?php if (count($menu[$m->id]) > 1): ?>
                <a href="javascript:;"  class="<?php if($controller==$m->controlador){ echo "active"; }?>" >
                    <div class="gui-icon"><i class="<?php echo $m->logo ?>"></i></div>
                    <span class="title"><?php echo $m->menu; ?></span>
                </a>
                <ul> 
                    <?php
                    ksort($menu[$m->id]);
                    foreach ($menu[$m->id] as $k => $v):
                        ?>
                        <li>
                            <a href="/<?php echo $m->controlador; ?>/<?php echo $menu[$m->id][$k]['accion']; ?>"><span class="title"><?php echo $menu[$m->id][$k]['submenu']; ?></span></a>        
                        </li>    
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <a href="/<?php echo $m->controlador; ?>" class="<?php if($controller==$m->controlador){ echo "active"; }?>" >
                    <div class="gui-icon"><i class="<?php echo $m->logo;?>"></i></div>
                    <span class="title"><?php echo $m->menu; ?></span>
                </a>
            <?php endif; ?>
        </li>
        <?php
        $id = $m->id;
    endif;
    ?>  
<?php endforeach; ?>
