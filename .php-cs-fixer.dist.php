<?php
declare(strict_types=1);

// PSR-12. Sin views/: el fixer rompe la sangría
$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude(['vendor', 'storage', 'views'])
    ->notPath(['public/dashboard.php']);

return (new PhpCsFixer\Config())
    ->setRules(['@PSR12' => true])
    ->setFinder($finder);
