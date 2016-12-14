<?php

require_once __DIR__ . '/../../../../init.php';

// turn off caching
Kohana::$caching = FALSE;

// restore PHP handler for CLI display
restore_error_handler();
restore_exception_handler();

// use "default" if no "--database-group="
$database_group = 'default';

// hack to get --env
$argv2 = $argv;
foreach ($argv as $pos => $arg) {
    if (strpos($arg, '--env') !== false) {
        $parts          = explode('=', $arg);
        Kohana::$environment = $parts[1];
        unset($argv2[$pos]);
    }
}

$input = new Doctrine_ReadWriteArgvInput($argv2);
if (!$input->hasOption('configuration')) {
    $input->setOption('configuration', Kohana::$config->load('doctrine')->get('configuration'));
}

// end: hack to get --database-group and pass it to the Doctrine_ORM constructor
// create a Doctrine_ORM for one database group
$doctrine_orm = new Doctrine_ORM($database_group);

// add console helpers
$helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
    'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($doctrine_orm->getEntityManager()->getConnection()),
    'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($doctrine_orm->getEntityManager())
        ));

// create and run Symfony Console application
$cli = new Symfony\Component\Console\Application('Kohana Doctrine Command Line Interface</info>'
        . PHP_EOL . '<comment>use --database-group to specifify another group from database.php (defaut: default)</comment>'
        . PHP_EOL . '<info>Doctrine', \Doctrine\ORM\Version::VERSION);
$cli->setCatchExceptions(true);

// Register All Doctrine Commands
\Doctrine\ORM\Tools\Console\ConsoleRunner::addCommands($cli);

// Adding own helpers
foreach (Kohana::$config->load('doctrine')->get('console_helpers', array()) as
/** @var $helper Symfony\Component\Console\Helper\HelperInterface */ $alias => $helper) {
    $helperSet->set($helper, $alias);
}

// Set helperSet
$cli->setHelperSet($helperSet);

// Run with helperset and add own commands
\Doctrine\ORM\Tools\Console\ConsoleRunner::run($helperSet, Kohana::$config->load('doctrine')->get('console_commands', array()));
