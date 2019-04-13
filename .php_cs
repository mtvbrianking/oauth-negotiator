<?php

// https://mlocati.github.io/php-cs-fixer-configurator/

$rules = [
    '@PSR2' => true,
];

$excludes = [
    'docs',
    'example',
    'logs',
    'tests',
    'vendor',
];

return PhpCsFixer\Config::create()
    ->setRules($rules)
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->exclude($excludes)
            ->in(__DIR__.'/src')
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
    );
