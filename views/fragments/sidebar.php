<div class="navigation"><button class="menu-toggle" type="button" aria-expanded="false" aria-controls="main-menu"><span>☰</span> Menú</button><nav id="main-menu">
        <?php foreach ([['inicio','Inicio'],['almacenes','Almacenes'],['solicitudes','Solicitudes'],['entregas','Entregas'],['servicios-regionales','Servicios Regionales'],['escuelas','Escuelas'],['movimientos','Movimientos']] as [$rutaMenu,$etiqueta]): ?>
            <a class="<?= $activo===$rutaMenu || ($activo==='servicios' && $rutaMenu==='servicios-regionales') ? 'active' : '' ?>" href="<?= escapar(url($rutaMenu)) ?>"><?= escapar($etiqueta) ?></a>
        <?php endforeach; ?>
    </nav></div>
<main class="content"><div class="page-title"><div><span>Administración de uniformes</span><h2><?= escapar($titulo) ?></h2></div></div>
