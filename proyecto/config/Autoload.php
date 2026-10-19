<?php
// Carga automática de clases desde las carpetas MVC (sin frameworks ni Composer).
spl_autoload_register(function (string $clase): void {
    $raiz = dirname(__DIR__);
    foreach (['config', 'modelos', 'controladores', 'middleware'] as $carpeta) {
        $archivo = "$raiz/$carpeta/$clase.php";
        if (is_file($archivo)) { require_once $archivo; return; }
    }
});
