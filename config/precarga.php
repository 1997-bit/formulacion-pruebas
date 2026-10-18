<?php

declare(strict_types=1);

// opcache.preload, #123. Compila core/, models/ y services/ al iniciar PHP
foreach (['core', 'models', 'services'] as $carpeta) {
    foreach (glob(dirname(__DIR__) . "/{$carpeta}/*.php") ?: [] as $archivo) {
        opcache_compile_file($archivo);
    }
}
