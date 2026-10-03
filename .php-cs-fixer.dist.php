<?php
declare(strict_types=1);

// PSR-12. Sin views/: el fixer rompe su sangría.
$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude(['vendor', 'storage', 'views'])
    ->notPath(['public/paleta.php', 'public/dashboard.php']);

return (new PhpCsFixer\Config())
    ->setRules(['@PSR12' => true])
    ->setFinder($finder);
