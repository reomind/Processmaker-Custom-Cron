<?php
/*
* save this file under <pluginName>/cron/ folder.
*/
use Illuminate\Foundation\Http\Kernel;

require_once __DIR__ . '/../../../../../gulliver/system/class.g.php';
require_once __DIR__ . '/../../../../../bootstrap/autoload.php';
require_once __DIR__ . '/../../../../../bootstrap/app.php';

use ProcessMaker\Core\System;
use ProcessMaker\Plugins\PluginRegistry;

register_shutdown_function(
    create_function(
        '',
        '
        if (class_exists("Propel")) {
            Propel::close();
        }
        '
    )
);

ini_set('memory_limit', '512M');

try {
    $osIsLinux = strtoupper(substr(PHP_OS, 0, 3)) != 'WIN';
    define('PATH_SEP', ($osIsLinux) ? '/' : '\\');
    $cronName = pathinfo($_SERVER['SCRIPT_FILENAME'], PATHINFO_FILENAME);
    $arrayPathToCron = [];
    $flagPathToCron = false;

    //Path to CRON by $_SERVER['SCRIPT_FILENAME']
    $arrayAux = explode(PATH_SEP, str_replace('engine' . PATH_SEP . 'bin', '', realpath($_SERVER['SCRIPT_FILENAME'])));

    array_pop($arrayAux);
    array_pop($arrayAux);

    $arrayPathToCron = $arrayAux;
    $flagPathToCron = true;

    $pathHome = implode(PATH_SEP, $arrayPathToCron) . PATH_SEP;

    array_pop($arrayPathToCron);

    $pathTrunk = implode(PATH_SEP, $arrayPathToCron) . PATH_SEP;

    array_pop($arrayPathToCron);

    $pathOutTrunk = implode(PATH_SEP, $arrayPathToCron) . PATH_SEP;

    $osIsLinux = strtoupper(substr(PHP_OS, 0, 3)) != 'WIN';

    $workspace = 'workflow';
    $dateSystem = date('Y-m-d H:i:s');
    $sNow = date('Y-m-d H:i:s'); //$date
    //Defines constants

    define('PATH_HOME', $pathHome);
    define('PATH_TRUNK', $pathTrunk);
    define('PATH_OUTTRUNK', $pathOutTrunk);

    define('PATH_CLASSES', PATH_HOME . 'engine' . PATH_SEP . 'classes' . PATH_SEP);

    define('SYS_LANG', 'en');

    require_once PATH_HOME . 'engine' . PATH_SEP . 'config' . PATH_SEP . 'paths.php';
    require_once PATH_TRUNK . 'framework' . PATH_SEP . 'src' . PATH_SEP . 'Maveriks' . PATH_SEP . 'Util' . PATH_SEP . 'ClassLoader.php';

    //Class Loader - /ProcessMaker/BusinessModel
    $classLoader = \Maveriks\Util\ClassLoader::getInstance();
    $classLoader->add(PATH_TRUNK . 'framework' . PATH_SEP . 'src' . PATH_SEP, 'Maveriks');
    $classLoader->add(PATH_TRUNK . 'workflow' . PATH_SEP . 'engine' . PATH_SEP . 'src' . PATH_SEP, 'ProcessMaker');
    $classLoader->add(PATH_TRUNK . 'workflow' . PATH_SEP . 'engine' . PATH_SEP . 'src' . PATH_SEP);

    //Add vendors to autoloader
    //$classLoader->add(PATH_TRUNK . 'vendor' . PATH_SEP . 'luracast' . PATH_SEP . 'restler' . PATH_SEP . 'vendor', 'Luracast');
    //$classLoader->add(PATH_TRUNK . 'vendor' . PATH_SEP . 'bshaffer' . PATH_SEP . 'oauth2-server-php' . PATH_SEP . 'src' . PATH_SEP, 'OAuth2');
    $classLoader->addClass('Bootstrap', PATH_TRUNK . 'gulliver' . PATH_SEP . 'system' . PATH_SEP . 'class.bootstrap.php');

    $classLoader->addModelClassPath(PATH_TRUNK . 'workflow' . PATH_SEP . 'engine' . PATH_SEP . 'classes' . PATH_SEP . 'model' . PATH_SEP);

    $arraySystemConfiguration = System::getSystemConfiguration('', '', $workspace);

    $e_all = (defined('E_DEPRECATED')) ? E_ALL & ~E_DEPRECATED : E_ALL;
    $e_all = (defined('E_STRICT')) ? $e_all & ~E_STRICT : $e_all;
    $e_all = ($arraySystemConfiguration['debug']) ? $e_all : $e_all & ~E_NOTICE;

    app()->useStoragePath(realpath(PATH_DATA));
    app()->make(Kernel::class)->bootstrap();
    restore_error_handler();
    //Do not change any of these settings directly, use env.ini instead
    ini_set('display_errors', $arraySystemConfiguration['debug']);
    ini_set('error_reporting', $e_all);
    ini_set('short_open_tag', 'On');
    ini_set('default_charset', 'UTF-8');
    //ini_set('memory_limit',    $arraySystemConfiguration['memory_limit']);
    ini_set('soap.wsdl_cache_enabled', $arraySystemConfiguration['wsdl_cache']);
    ini_set('date.timezone', $arraySystemConfiguration['time_zone']);

    define('DEBUG_SQL_LOG', $arraySystemConfiguration['debug_sql']);
    define('DEBUG_TIME_LOG', $arraySystemConfiguration['debug_time']);
    define('DEBUG_CALENDAR_LOG', $arraySystemConfiguration['debug_calendar']);
    define('MEMCACHED_ENABLED', $arraySystemConfiguration['memcached']);
    define('MEMCACHED_SERVER', $arraySystemConfiguration['memcached_server']);

    //require_once(PATH_GULLIVER . PATH_SEP . 'class.bootstrap.php');
    //define('PATH_GULLIVER_HOME', PATH_TRUNK . 'gulliver' . PATH_SEP);

    spl_autoload_register(['Bootstrap', 'autoloadClass']);

    //Set variables
    /*----------------------------------********---------------------------------*/
    $dateInit = null;
    $dateFinish = null;
    /*----------------------------------********---------------------------------*/

    $argvx = '';

    for ($i = 8; $i <= count($argv) - 1; $i++) {
        /*----------------------------------********---------------------------------*/
        if (strpos($argv[$i], '+init-date') !== false) {
            $dateInit = substr($argv[$i], 10);
        } else if (strpos($argv[$i], '+finish-date') !== false) {
            $dateFinish = substr($argv[$i], 12);
        } else {
            /*----------------------------------********---------------------------------*/
            $argvx = $argvx . (($argvx != '') ? ' ' : '') . $argv[$i];
            /*----------------------------------********---------------------------------*/
        }
        /*----------------------------------********---------------------------------*/
    }
    global $sObject;
    $sObject = $workspace;

    //Workflow
    saveLog('main', 'action', 'checking folder ' . PATH_DB . $workspace);

    if (is_dir(PATH_DB . $workspace) && file_exists(PATH_DB . $workspace . PATH_SEP . 'db.php')) {
        define('SYS_SYS', $workspace);
        config(["system.workspace" => $workspace]);

        include_once PATH_HOME . 'engine' . PATH_SEP . 'config' . PATH_SEP . 'paths_installed.php';
        include_once PATH_HOME . 'engine' . PATH_SEP . 'config' . PATH_SEP . 'paths.php';

        //PM Paths DATA
        define('PATH_DATA_SITE', PATH_DATA . 'sites/' . config("system.workspace") . '/');
        define('PATH_DOCUMENT', PATH_DATA_SITE . 'files/');
        define('PATH_DATA_MAILTEMPLATES', PATH_DATA_SITE . 'mailTemplates/');
        define('PATH_DATA_PUBLIC', PATH_DATA_SITE . 'public/');
        define('PATH_DATA_REPORTS', PATH_DATA_SITE . 'reports/');
        define('PATH_DYNAFORM', PATH_DATA_SITE . 'xmlForms/');
        define('PATH_IMAGES_ENVIRONMENT_FILES', PATH_DATA_SITE . 'usersFiles' . PATH_SEP);
        define('PATH_IMAGES_ENVIRONMENT_USERS', PATH_DATA_SITE . 'usersPhotographies' . PATH_SEP);

        if (is_file(PATH_DATA_SITE . PATH_SEP . '.server_info')) {
            $SERVER_INFO = file_get_contents(PATH_DATA_SITE . PATH_SEP . '.server_info');
            $SERVER_INFO = unserialize($SERVER_INFO);

            define('SERVER_NAME', $SERVER_INFO['SERVER_NAME']);
            define('SERVER_PORT', $SERVER_INFO['SERVER_PORT']);
            define('REQUEST_SCHEME', $SERVER_INFO['REQUEST_SCHEME']);
        } else {
            eprintln('WARNING! No server info found!', 'red');
        }

        //DB
        $phpCode = '';

        $fileDb = fopen(PATH_DB . $workspace . PATH_SEP . 'db.php', 'r');

        if ($fileDb) {
            while (!feof($fileDb)) {
                $buffer = fgets($fileDb, 4096); //Read a line

                $phpCode .= preg_replace('/define\s*\(\s*[\x22\x27](.*)[\x22\x27]\s*,\s*(\x22.*\x22|\x27.*\x27)\s*\)\s*;/i', '$$1 = $2;', $buffer);
            }

            fclose($fileDb);
        }

        $phpCode = str_replace(['<?php', '<?', '?>'], ['', '', ''], $phpCode);

        eval($phpCode);

        $dsn = $DB_ADAPTER . '://' . $DB_USER . ':' . $DB_PASS . '@' . $DB_HOST . '/' . $DB_NAME;
        $dsnRbac = $DB_ADAPTER . '://' . $DB_RBAC_USER . ':' . $DB_RBAC_PASS . '@' . $DB_RBAC_HOST . '/' . $DB_RBAC_NAME;
        $dsnRp = $DB_ADAPTER . '://' . $DB_REPORT_USER . ':' . $DB_REPORT_PASS . '@' . $DB_REPORT_HOST . '/' . $DB_REPORT_NAME;

        switch ($DB_ADAPTER) {
            case 'mysql':
                $dsn .= '?encoding=utf8';
                $dsnRbac .= '?encoding=utf8';
                break;
            case 'mssql':
                //$dsn .= '?sendStringAsUnicode=false';
                //$dsnRbac .= '?sendStringAsUnicode=false';
                break;
            default:
                break;
        }

        $pro = [];
        $pro['datasources']['workflow']['connection'] = $dsn;
        $pro['datasources']['workflow']['adapter'] = $DB_ADAPTER;
        $pro['datasources']['rbac']['connection'] = $dsnRbac;
        $pro['datasources']['rbac']['adapter'] = $DB_ADAPTER;
        $pro['datasources']['rp']['connection'] = $dsnRp;
        $pro['datasources']['rp']['adapter'] = $DB_ADAPTER;
        //$pro['datasources']['dbarray']['connection'] = 'dbarray://user:pass@localhost/pm_os';
        //$pro['datasources']['dbarray']['adapter']    = 'dbarray';

        $oFile = fopen(PATH_CORE . 'config' . PATH_SEP . '_databases_.php', 'w');
        fwrite($oFile, '<?php global $pro; return $pro; ?>');
        fclose($oFile);

        Propel::init(PATH_CORE . 'config' . PATH_SEP . '_databases_.php');
        //Creole::registerDriver('dbarray', 'creole.contrib.DBArrayConnection');

        //Enable RBAC
        $rbac = &RBAC::getSingleton(PATH_DATA, session_id());
        $rbac->sSystem = 'PROCESSMAKER';

        if (!defined('DB_ADAPTER')) {
            define('DB_ADAPTER', $DB_ADAPTER);
        }

        //Set Time Zone
        $systemUtcTimeZone = false;

        /*----------------------------------********---------------------------------*/
        if (PMLicensedFeatures::getSingleton()->verifyfeature('oq3S29xemxEZXJpZEIzN01qenJUaStSekY4cTdJVm5vbWtVM0d4S2lJSS9qUT0=')) {
            $systemUtcTimeZone = (int) ($arraySystemConfiguration['system_utc_time_zone']) == 1;
        }
        /*----------------------------------********---------------------------------*/

        ini_set('date.timezone', ($systemUtcTimeZone) ? 'UTC' : $arraySystemConfiguration['time_zone']); //Set Time Zone

        define('TIME_ZONE', ini_get('date.timezone'));

        //Processing
        eprintln('Processing workspace: ' . $workspace, 'green');

        // We load plugins' pmFunctions
        $oPluginRegistry = PluginRegistry::loadSingleton();
        $oPluginRegistry->init();

        try {
            processWorkspace();
        } catch (Exception $e) {
            $token = strtotime("now");
            PMException::registerErrorLog($e, $token);
            G::outRes(G::LoadTranslation("ID_EXCEPTION_LOG_INTERFAZ", array($token)) . "\n");

            eprintln('Problem in workspace: ' . $workspace . ' it was omitted.', 'red');
        }

        eprintln();
    }

    if (file_exists(PATH_CORE . 'config' . PATH_SEP . '_databases_.php')) {
        unlink(PATH_CORE . 'config' . PATH_SEP . '_databases_.php');
    }
} catch (Exception $e) {
    $token = strtotime("now");
    PMException::registerErrorLog($e, $token);
    G::outRes(G::LoadTranslation("ID_EXCEPTION_LOG_INTERFAZ", array($token)) . "\n");
}

//Functions
function processWorkspace()
{
    try {
        global $sObject;
        global $sLastExecution;

        doSomeThing();
    } catch (Exception $oError) {
        saveLog("main", "error", "Error processing workspace : " . $oError->getMessage() . "\n");
    }
}

function doSomeThing()
{
    echo SYS_SYS;
}

function saveLog($sSource, $sType, $sDescription)
{
    try {
        global $sObject;
        global $isDebug;

        if ($isDebug) {
            print date("H:i:s") . " ($sSource) $sType $sDescription <br />\n";
        }

        G::verifyPath(PATH_DATA . "log" . PATH_SEP, true);
        G::log("| $sObject | " . $sSource . " | $sType | " . $sDescription, PATH_DATA);
    } catch (Exception $e) {
        //CONTINUE
    }
}

/*----------------------------------********---------------------------------*/

/*----------------------------------********---------------------------------*/
