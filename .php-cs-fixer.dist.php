<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR2' => true,
        'array_syntax' => [
            'syntax' => 'short',
        ],
        'list_syntax' => [
            'syntax' => 'short',
        ],
        'concat_space' => [
            'spacing' => 'one',
        ],
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => null,
        ],
        'blank_line_before_statement' => [
            'statements' => [
                'declare',
            ],
        ],
        'ordered_imports' => [
            'imports_order' => [
                'class', 'function', 'const',
            ],
            'sort_algorithm' => 'alpha',
        ],
        'yoda_style' => [
            'always_move_variable' => false,
            'equal' => false,
            'identical' => false,
        ],
        'phpdoc_align' => [
            'align' => 'left',
        ],
        'multiline_whitespace_before_semicolons' => [
            'strategy' => 'no_multi_line',
        ],
        'constant_case' => [
            'case' => 'lower',
        ],
        'class_attributes_separation' => true,
        'combine_consecutive_unsets' => true,
        'declare_strict_types' => true,
        'linebreak_after_opening_tag' => true,
        'lowercase_static_reference' => true,
        'no_useless_else' => true,
        'no_unused_imports' => true,
        'not_operator_with_successor_space' => true,
        'not_operator_with_space' => false,
        'ordered_class_elements' => true,
        'php_unit_strict' => true,
        'phpdoc_separation' => true,
        'single_quote' => true,
        'standardize_not_equals' => true,
        'multiline_comment_opening_closing' => true,
        'single_line_empty_body' => true,
        'strict_param' => true,
        'return_type_declaration' => ['space_before' => 'none'],
        'fully_qualified_strict_types' => true,
        'phpdoc_param_order' => true,
        'phpdoc_indent' => true,
    ])
    ->setFinder($finder);
