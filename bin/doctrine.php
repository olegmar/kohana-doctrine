<?php

use Doctrine\ORM\Tools\Console\ConsoleRunner;

require_once __DIR__ . '/../../../../init.php';

// turn off caching
Kohana::$caching = FALSE;

// restore PHP handler for CLI display
restore_error_handler();
restore_exception_handler();

// use "default" if no "--env="
$database_group = 'default';

// hack to get --env
$argv2 = $argv;
foreach ($argv as $pos => $arg) {
    if (strpos($arg, '--env') !== false) {
        $parts          = explode('=', $arg);
        Kohana::$environment = $parts[1];
        $database_group = Kohana::$environment;
        unset($argv2[$pos]);
    }
}

$input = new Doctrine_ReadWriteArgvInput($argv2);
if (!$input->hasOption('configuration')) {
    $input->setOption('configuration', Kohana::$config->load('doctrine')->get('configuration'));
}

$doctrine_orm = new Doctrine_ORM($database_group);

$helperSet = ConsoleRunner::createHelperSet($doctrine_orm->getEntityManager());

// Adding own helpers
/** @var $helper Symfony\Component\Console\Helper\HelperInterface */
foreach (Kohana::$config->load('doctrine')->get('console_helpers', []) as $alias => $helper) {
    $helperSet->set($helper, $alias);
}

ConsoleRunner::run($helperSet, Kohana::$config->load('doctrine')->get('console_commands', []));
