<?php

use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;

return [
    'extensions_path'        => APPPATH . '../vendor/gedmo/doctrine-extensions/lib/Gedmo/',
    'migrations_path'        => APPPATH . '../data/Migrations/',
    'proxy_dir'              => APPPATH . '../data/Proxy',
    'proxy_namespace'        => 'Proxy',
    'mappings_path'          => APPPATH . 'classes/model/entity',
    'mappings_driver'        => 'annotation',
    // mappings between Kohaha database types and Doctrine database drivers
    // @see http://kohanaframework.org/3.3/guide/database/config#connection-settings
    // @see http://www.doctrine-project.org/docs/dbal/2.4/en/reference/configuration.html#connection-details
    'type_driver_mapping'    => [
        'pdo'        => 'pdo_mysql',
        'mysql'      => 'pdo_mysql',
        'PDO'        => 'pdo_mysql',
        'MySQL'      => 'pdo_mysql',
        'mysqli'     => 'mysqli',
        'PDO_MySQL'  => 'pdo_mysql',
        'PDO_MySQLi' => 'mysqli',
    ],
    'console_commands'       => [
        // Migrations Commands
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\VersionCommand()
    ],
    'console_helpers'        => [
        //'question' => new \Symfony\Component\Console\Helper\QuestionHelper(),
        'dialog' => new \Symfony\Component\Console\Helper\DialogHelper(),
    ],
    'configuration'          => APPPATH . 'config/doctrine.xml',
    'debug'                  => TRUE,
    'default_database_group' => 'default',
    'cache_implementation'   => 'ArrayCache',
    'cache_namespace'        => NULL,
    'enabled_extensions'     => [
        // 		'string' => array(
        //     		'GroupConcat'   =>  DoctrineExtensions\Query\Mysql\GroupConcat',
        //     		'StringAgg'     =>  'DoctrineExtensions\Query\PostgreSql\StringAgg',
        //        )
        'filters' => [
            'soft-deleteable' => SoftDeleteableFilter::class
        ],
    ],
];
