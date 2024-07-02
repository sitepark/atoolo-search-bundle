<?php

declare(strict_types=1);

$finder = (new \PhpCsFixer\Finder())
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/test');

return (new \PhpCsFixer\Config())
    ->setCacheFile('var/cache/php-cs-fixer')
    ->setFinder($finder)
    ->setRules(['@PER-CS' => true]);
