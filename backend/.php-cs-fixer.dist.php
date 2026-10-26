<?php

declare(strict_types=1);

/*
 * AegisThread backend — php-cs-fixer configuration.
 *
 * Ruleset: @Symfony (the framework's own coding standard) with strict types
 * enforced. The cache lives under var/, which is git-ignored.
 */

$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'declare_strict_types' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setCacheFile(__DIR__.'/var/php-cs-fixer.cache')
;
