<?php

use \Doctrine\ORM\EntityManager;
use \Doctrine\Common\EventManager;
use \Doctrine\DBAL\Event\Listeners\MysqlSessionInit;
use \Doctrine\ORM\Mapping\Driver\YamlDriver;
use \Doctrine\ORM\Mapping\Driver\XmlDriver;
use \Doctrine\ORM\Mapping\Driver\PHPDriver;
use Doctrine\ORM\Tools\Setup;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Gedmo\Timestampable\TimestampableListener;

class Doctrine_ORM
{

    /**
     * @var array
     */
    private static $doctrineConfig;

    /**
     * @var array
     */
    private static $databaseConfig;

    /**
     * @var EventManager
     */
    private $evm;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var  array  doctrine instances
     */
    public static $instances = array();

    /**
     * @var  string  default database group
     */
    public static $default = 'default';

    /**
     * Creates a singleton doctrine instance of the given database group
     *
     *     $doctrine = Doctrine_ORM::instance();
     *
     *
     * @param   string  $database_group   database group
     * @return  Doctrine_ORM
     * @uses    Kohana::$config
     */
    public static function instance($database_group = 'default')
    {
        if ($database_group === NULL) {
            // Use the default type
            $database_group = Doctrine_ORM::$default;
        }

        if (!isset(Doctrine_ORM::$instances[$database_group])) {
            Doctrine_ORM::$instances[$database_group] = new Doctrine_ORM($database_group);
        }

        return Doctrine_ORM::$instances[$database_group];
    }

    /**
     * set Kohana database configuration
     *
     * @param array $doctrine_config
     */
    public static function setConfig($doctrine_config)
    {
        self::$doctrineConfig = $doctrine_config;
    }

    /**
     * Doctrine_ORM constructor.
     * @param string $database_group
     * @throws Kohana_Database_Exception
     */
    public function __construct($database_group = 'default')
    {
        // if config was not set by init.php, load it
        if (self::$doctrineConfig === NULL) {
            self::$doctrineConfig = Kohana::$config->load('doctrine');
        }

        $isDevMode = self::$doctrineConfig['debug'];
        $config    = Setup::createConfiguration($isDevMode);

        // proxy configuration
        $config->setProxyDir(self::$doctrineConfig['proxy_dir']);
        $config->setProxyNamespace(self::$doctrineConfig['proxy_namespace']);
        $config->setAutoGenerateProxyClasses($isDevMode);

        // Extensions
        foreach (self::$doctrineConfig->get('enabled_extensions', []) as $type => $map) {
            foreach ($map as $name => $class) {
                if ($type == 'string') {
                    $config->addCustomStringFunction($name, $class);
                } elseif ($type == 'filters') {
                    $config->addFilter($name, $class);
                }
            }
        }

        // caching configuration
        $cache_class          = '\Doctrine\Common\Cache\\' . self::$doctrineConfig['cache_implementation'];
        $cache_implementation = new $cache_class;

        // set namespace on cache
        if ($cache_namespace = self::$doctrineConfig['cache_namespace']) {
            $cache_implementation->setNamespace($cache_namespace);
        }
        $config->setMetadataCacheImpl($cache_implementation);
        $config->setQueryCacheImpl($cache_implementation);
        $config->setResultCacheImpl($cache_implementation);

        // mappings/metadata driver configuration
        $driver_implementation = NULL;
        switch (self::$doctrineConfig['mappings_driver']) {
            case 'php':
                $driver_implementation     = new PHPDriver(array(self::$doctrineConfig['mappings_path']));
                break;
            case 'xml':
                $driver_implementation     = new XmlDriver(array(self::$doctrineConfig['mappings_path']));
                break;
            case 'annotation':
                $useSimpleAnnotationReader = FALSE;
                $driver_implementation     = $config->newDefaultAnnotationDriver(array(self::$doctrineConfig['mappings_path']), $useSimpleAnnotationReader);
                AnnotationRegistry::registerLoader('class_exists');
                break;
            default:
            case 'yaml':
                $driver_implementation     = new YamlDriver(array(self::$doctrineConfig['mappings_path']));
                break;
        }
        $config->setMetadataDriverImpl($driver_implementation);

        // load config if not defined
        if (self::$databaseConfig === NULL) {
            self::$databaseConfig = Kohana::$config->load('database');
        }

        // get $database_group config
        $db_config = Arr::GET(self::$databaseConfig, $database_group, array());

        // verify that the database group exists
        if (empty($db_config)) {
            throw new Kohana_Database_Exception('database-group "' . $database_group . '" doesn\'t exists');
        }

        if (strtolower($db_config['type']) == 'pdo') {
            $pdo               = new PDO($db_config['connection']['dsn'], $db_config['connection']['username'], $db_config['connection']['password'], array(PDO::ATTR_PERSISTENT => $db_config['connection']['persistent'])
            );
            $connectionOptions = array(
                'pdo'    => $pdo,
                'dbname' => null
            );
        } else {
            // database configuration
            $connectionOptions = array(
                'driver'   => self::$doctrineConfig['type_driver_mapping'][$db_config['type']],
                'host'     => $db_config['connection']['hostname'],
                'port'     => $db_config['connection']['port'],
                'dbname'   => $db_config['connection']['database'],
                'user'     => $db_config['connection']['username'],
                'password' => $db_config['connection']['password'],
                'charset'  => $db_config['charset'],
            );
        }

        // create Entity Manager
        $this->evm = new EventManager();
        $this->em  = EntityManager::create($connectionOptions, $config, $this->evm);

        // specify the charset for MySQL/PDO
        $driverName = $this->em->getConnection()->getDriver()->getName();
        if ($driverName == 'pdo_mysql') {
            $this->em->getEventManager()->addEventSubscriber(new MysqlSessionInit($db_config['charset'], 'utf8_unicode_ci'));
        } else if ($driverName == 'pdo_pgsql') {
            $this->em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('bytea', 'text');
        }

        $this->em->getEventManager()->addEventSubscriber(new TimestampableListener());

        //fix enum
        $conn = $this->em->getConnection();
        $conn->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    /**
     * check current em and if no connection
     * recreate
     * @param type $em
     * @return type
     */
    protected function checkEMConnection($em)
    {
        if (!$em->isOpen()) {
            $connection = $em->getConnection();
            $config     = $em->getConfiguration();

            return $em->create(
                $connection, $config
            );
        }
    }

    /**
     * reconnect if needed
     */
    public function reconnectEm()
    {
        $newEm = $this->checkEMConnection($this->em);
        if ($newEm) {
            $this->em = $newEm;
        }
    }

    /**
     * get EntityManager
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }

    /**
     * get EventManager
     *
     * @return \Doctrine\Common\EventManager
     */
    public function getEventManager()
    {
        return $this->evm;
    }

}
