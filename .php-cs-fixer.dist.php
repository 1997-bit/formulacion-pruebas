<?php
declare(strict_types=1);

// Estilo del proyecto: PSR-12. Se usa con composer lint (revisar) y composer fix (arreglar).
// Las plantillas (PHP dentro de HTML) quedan fuera: el fixer les rompe la sangría.
$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude(['vendor', 'storage', 'views'])
    ->notPath(['public/paleta.php', 'public/dashboard.php']);

return (new PhpCsFixer\Config())
    ->setRules(['@PSR12' => true])
    ->setFinder($finder);
