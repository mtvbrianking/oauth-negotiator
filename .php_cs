<?php

// https://mlocati.github.io/php-cs-fixer-configurator/

$rules = [
    '@PSR2' => true,

    // phpdocs
    'phpdoc_types' => true,
    'phpdoc_indent' => true,
    'phpdoc_to_comment' => true,
    'phpdoc_trim' => true,
    'phpdoc_align' => true,
    'phpdoc_summary' => true,
    'phpdoc_separation' => true,
    'phpdoc_scalar' => true,
    'phpdoc_order' => true,
    'phpdoc_inline_tag' => true,
    'phpdoc_return_self_reference' => true,
    'phpdoc_var_without_name' => true,
    'phpdoc_var_annotation_correct_order' => true,
    'phpdoc_trim_consecutive_blank_line_separation' => true,
    'phpdoc_add_missing_param_annotation' => [
        'only_untyped' => false,
    ],
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
