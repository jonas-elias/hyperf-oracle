<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'strict_param' => true,
        'declare_strict_types' => true,
        'phpdoc_no_empty_return' => true,
        'array_syntax' => ['syntax' => 'short'],
        'phpdoc_align' => true,
        'phpdoc_separation' => true,
        'return_type_declaration' => ['space_before' => 'none'],
        'fully_qualified_strict_types' => true,
        'phpdoc_param_order' => true,
        'phpdoc_indent' => true,
    ])
    ->setFinder($finder);
