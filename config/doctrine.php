<?php

use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Timestampable\TimestampableListener;

return [
    'migrations_path' => APPPATH . '../data/Migrations/',
    'proxy_dir' => APPPATH . '../data/Proxy',
    'proxy_namespace' => 'Proxy',
    'mappings_paths' => [
        APPPATH . 'classes/entity',
    ],
    'mappings_driver' => 'annotation',
    // mappings between Kohaha database types and Doctrine database drivers
    // @see http://kohanaframework.org/3.3/guide/database/config#connection-settings
    // @see http://www.doctrine-project.org/docs/dbal/2.4/en/reference/configuration.html#connection-details
    'type_driver_mapping' => [
        'pdo' => 'pdo_mysql',
        'mysql' => 'pdo_mysql',
        'PDO' => 'pdo_mysql',
        'MySQL' => 'pdo_mysql',
        'mysqli' => 'mysqli',
        'PDO_MySQL' => 'pdo_mysql',
        'PDO_MySQLi' => 'mysqli',
    ],
    'console_commands' => [
        // Migrations Commands
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\VersionCommand()
    ],
    'console_helpers' => [
        'question' => new \Symfony\Component\Console\Helper\QuestionHelper(),
    ],
    'string_functions' => [],
    'filters' => [
        'soft-deleteable' => SoftDeleteableFilter::class
    ],
    'subscribers' => [
        SoftDeleteableListener::class,
        TimestampableListener::class,
    ],
    'debug' => true,
    'default_database_group' => 'default',
    'cache_implementation' => 'ArrayCache',
    'cache_namespace' => null,
];
