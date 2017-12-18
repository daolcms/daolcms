<?php
/**
 * set the include of the class file and other environment configurations
 *
 * @file    config/config.inc.php
 * @author  NAVER (developers@xpressengine.com)
 * @Adaptor DAOL Project (developer@daolcms.org)
 */

//php 5.4 이상에서 WARNING 표시 문제 수정
if(version_compare(PHP_VERSION, '5.4.0', '<')) {
	@error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_WARNING);
} else {
	@error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_WARNING ^ E_STRICT);
}

if(!defined('__XE__')) exit();

/**
 * Display the version info of DAOL CMS.
 */
define('__DAOL_VERSION__', '1.0.4.0-test2');

/**
 * Display the version info of Based XE.
 * @deprecated this value will never changed, use __DAOL_VERSION__ instead.
 */
define('__XE_VERSION__', '1.5.4.7');

/**
 * Minimum version of PHP usable by DAOL CMS.
 */
define('__DAOL_MIN_PHP_VERSION__', '5.3.0');

/**
 * @deprecated __ZBXE_VERSION__ is deprecated and will be removed. Use __DAOL_VERSION__ instead.
 */
define('__ZBXE_VERSION__', __XE_VERSION__);

/**
 * The base path to where you installed DAOL CMS
 */
define('_DAOL_PATH_', str_replace('config/config.inc.php', '', str_replace('\\', '/', __FILE__)));
define('_XE_PATH_', _DAOL_PATH_);

// Set can use other method instead cookie to store session id(for file upload)
ini_set('session.use_only_cookies', 0);

// Include package setting file(official site, download site, etc)
if(file_exists(_DAOL_PATH_ . 'config/package.inc.php')) {
	require _DAOL_PATH_ . 'config/package.inc.php';
} else {
	/**
	 * Package type
	 */
	define('_XE_PACKAGE_', 'XE');

	/**
	 * Location
	 */
	define('_XE_LOCATION_', 'en');

	/**
	 * Location site
	 */
	define('_DAOL_LOCATION_SITE_', 'http://www.daolcms.org/');
	define('_XE_LOCATION_SITE_', 'http://www.xpressengine.com/');

	/**
	 * Download server
	 */
	define('_DAOL_DOWNLOAD_SERVER_', 'http://download.daolcms.org/');
	define('_XE_DOWNLOAD_SERVER_', 'http://download.xpressengine.com/');
}

/*
 * user configuration files can override the default settings
 * save the following information into config/config.user.inc.php(without heading *)
 * <?php
 * define('__DEBUG__', 0);
 * define('__DEBUG_OUTPUT__', 0);
 * define('__DEBUG_PROTECT__', 1);
 * define('__DEBUG_PROTECT_IP__', '127.0.0.1');
 * define('__DEBUG_DB_OUTPUT__', 0);
 * define('__LOG_SLOW_QUERY__', 0);
 * define('__OB_GZHANDLER_ENABLE__', 1);
 * define('__ENABLE_PHPUNIT_TEST__', 0);
 * define('__PROXY_SERVER__', 'http://domain:port/path');
 */
if(file_exists(_DAOL_PATH_ . 'config/config.user.inc.php')) {
	require _DAOL_PATH_ . 'config/config.user.inc.php';
}

if(!defined('__DEBUG__')) {
	/**
	 * output debug message(bit value)
	 *
	 * <pre>
	 * 0: generate debug messages/not display
	 * 1: display messages through debugPrint() function
	 * 2: output execute time, Request/Response info
	 * 4: output DB query history
	 * </pre>
	 */
	define('__DEBUG__', 0);
}

if(!defined('__DEBUG_OUTPUT__')) {
	/**
	 * output location of debug message
	 *
	 * <pre>
	 * 0: connect to the files/_debug_message.php and output
	 * 1: HTML output as a comment on the bottom (when response method is the HTML)
	 * 2: Firebug console output (PHP 4 & 5. Firebug/FirePHP plug-in required)
	 * </pre>
	 */
	define('__DEBUG_OUTPUT__', 0);
}

if(!defined('__DEBUG_PROTECT__')) {
	/**
	 * output comments of the firePHP console and browser
	 *
	 * <pre>
	 * 0: No limit (not recommended)
	 * 1: Allow only specified IP addresses
	 * </pre>
	 */
	define('__DEBUG_PROTECT__', 1);
}

if(!defined('__DEBUG_PROTECT_IP__')) {
	/**
	 * Set a ip address to allow debug
	 */
	define('__DEBUG_PROTECT_IP__', '127.0.0.1');
}

if(!defined('__DEBUG_DB_OUTPUT__')) {
	/**
	 * DB error message definition
	 *
	 * <pre>
	 * 0: No output
	 * 1: files/_debug_db_query.php connected to the output
	 * </pre>
	 */
	define('__DEBUG_DB_OUTPUT__', 0);
}

if(!defined('__LOG_SLOW_QUERY__')) {
	/**
	 * Query log for only timeout query among DB queries
	 *
	 * <pre>
	 * 0: Do not leave a log
	 * = 0: leave a log when the slow query takes over specified seconds
	 * Log file is saved as ./files/_db_slow_query.php file
	 * </pre>
	 */
	define('__LOG_SLOW_QUERY__', 0);
}

if(!defined('__DEBUG_QUERY__')) {
	/**
	 * Leave DB query information
	 *
	 * <pre>
	 * 0: Do not add information about query
	 * 1: Comment the XML Query ID
	 * </pre>
	 */
	define('__DEBUG_QUERY__', 0);
}

if(!defined('__OB_GZHANDLER_ENABLE__')) {
	/**
	 * option to enable/disable a compression feature using ob_gzhandler
	 *
	 * <pre>
	 * 0: Disabled
	 * 1: Enabled
	 * Only particular servers may have a problem in IE browser when sending a compression
	 * </pre>
	 */
	define('__OB_GZHANDLER_ENABLE__', 1);
}

if(!defined('__ENABLE_PHPUNIT_TEST__')) {
	/**
	 * decide to use/not use the php unit test (Path/tests/index.php)
	 *
	 * <pre>
	 * 0: Disabled
	 * 1: Enabled
	 * </pre>
	 */
	define('__ENABLE_PHPUNIT_TEST__', 0);
}

if(!defined('__PROXY_SERVER__')) {
	/**
	 * __PROXY_SERVER__ has server information to request to the external through the target server
	 * FileHandler:: getRemoteResource uses the constant
	 */
	define('__PROXY_SERVER__', null);
}

if(!defined('__ERROR_LOG__')) {
	/**
	 * __ERROR_LOG__ is function of print PHP error log. It print over the warning error.
	 *
	 * 0: Disabled
	 * 1: Enabled
	 */
	define('__ERROR_LOG__', 0);
}

if(!defined('__DISABLE_DEFAULT_CSS__')) {
	/**
	 * XE의 기본 CSS 스타일을 로드하지 않도록 합니다.
	 *
	 * CSS 적용이 해제됨에 따라 버튼, 팝업 메뉴 및 일부 중요 메시지 등이 표시되지 않을 수 있습니다.
	 * common/css/xe.css
	 * common/css/mobile.css
	 *
	 * 0: CSS 적용 (기존과 동일)
	 * 1: 사용하지 않음
	 */
	define('__DISABLE_DEFAULT_CSS__', 0);
}

// Require specific files when using Firebug console output
if(__DEBUG_OUTPUT__ == 2) {
	require _DAOL_PATH_ . 'libs/FirePHPCore/FirePHP.class.php';
}

// Set Timezone as server time
date_default_timezone_set(@date_default_timezone_get());

if(!defined('__XE_LOADED_CLASS__')) {
	// Require a function-defined-file for simple use
	require(_DAOL_PATH_ . 'config/func.inc.php');

	if(__DEBUG__) define('__StartTime__', getMicroTime());

	// include the class files
	//TODO When _autoload() can be used for PHP5 based applications, it will be removed.
	if(__DEBUG__) define('__ClassLoadStartTime__', getMicroTime());
	require(_DAOL_PATH_ . 'classes/object/BaseObject.class.php');
	require(_DAOL_PATH_ . 'classes/extravar/Extravar.class.php');
	require(_DAOL_PATH_ . 'classes/handler/Handler.class.php');
	require(_DAOL_PATH_ . 'classes/xml/XmlParser.class.php');
	require(_DAOL_PATH_ . 'classes/xml/XmlGenerator.class.php');
	require(_DAOL_PATH_ . 'classes/xml/XmlJsFilter.class.php');
	require(_DAOL_PATH_ . 'classes/xml/XmlLangParser.class.php');
	require(_DAOL_PATH_ . 'classes/cache/CacheHandler.class.php');
	require(_DAOL_PATH_ . 'classes/context/Context.class.php');
	require(_DAOL_PATH_ . 'classes/db/DB.class.php');
	require(_DAOL_PATH_ . 'classes/file/FileHandler.class.php');
	require(_DAOL_PATH_ . 'classes/widget/WidgetHandler.class.php');
	require(_DAOL_PATH_ . 'classes/editor/EditorHandler.class.php');
	require(_DAOL_PATH_ . 'classes/module/ModuleObject.class.php');
	require(_DAOL_PATH_ . 'classes/module/ModuleHandler.class.php');
	require(_DAOL_PATH_ . 'classes/display/DisplayHandler.class.php');
	require(_DAOL_PATH_ . 'classes/template/TemplateHandler.class.php');
	require(_DAOL_PATH_ . 'classes/mail/Mail.class.php');
	require(_DAOL_PATH_ . 'classes/page/PageHandler.class.php');
	require(_DAOL_PATH_ . 'classes/mobile/Mobile.class.php');
	require(_DAOL_PATH_ . 'classes/validator/Validator.class.php');
	require(_DAOL_PATH_ . 'classes/frontendfile/FrontEndFileHandler.class.php');
	require(_DAOL_PATH_ . 'classes/security/Password.class.php');
	require(_DAOL_PATH_ . 'classes/security/Security.class.php');
	require(_DAOL_PATH_ . 'classes/security/IpFilter.class.php');
	if(__DEBUG__) $GLOBALS['__elapsed_class_load__'] = getMicroTime() - __ClassLoadStartTime__;
}
if(version_compare(PHP_VERSION, '7.2', '<')){
	class_alias('BaseObject', 'Object', true);
}
