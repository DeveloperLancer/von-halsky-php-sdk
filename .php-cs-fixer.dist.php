<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->files()
    ->name('*.php')
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/tools',
    ])
    ->exclude([
        'build',
        'var',
        'vendor',
    ]);

return (new Config())
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache')
    ->setRiskyAllowed(false)
    ->setRules([
        '@PER-CS2.0' => true,
        'array_syntax' => ['syntax' => 'short'],
        'function_declaration' => [
            'closure_fn_spacing' => 'one',
            'closure_function_spacing' => 'one',
        ],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_line_empty_body' => false,
        'trailing_comma_in_multiline' => false,
    ])
    ->setFinder($finder);
