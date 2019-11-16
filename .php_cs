<?php

$finder = PhpCsFixer\Finder::create()
    ->ignoreDotFiles(false)
    ->in(__DIR__)
    ->exclude([
        '.dependabot',
        '.github',
        'build',
        'db',
    ])
    ->name('.php_cs');

return PhpCsFixer\Config::create()
    ->setFinder($finder)
    ->setRules([
        '@PSR2' => true,
    ]);
