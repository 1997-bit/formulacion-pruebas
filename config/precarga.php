<?php

declare(strict_types=1);

// opcache.preload (#123): compila core/, models/ y services/ una vez, al iniciar PHP. No ejecuta nada.
foreach (['core', 'models', 'services'] as $carpeta) {
    foreach (glob(dirname(__DIR__) . "/{$carpeta}/*.php") ?: [] as $archivo) {
        opcache_compile_file($archivo);
    }
}
