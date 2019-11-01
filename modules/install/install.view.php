<?php

/**
 * @class  installView
 * @author NAVER (developers@xpressengine.com)
 * @brief  View class of install module
 **/
class installView extends install {

	var $install_enable = false;

	/**
	 * @brief Initialization
	 **/
	function init(){
		// Set browser title
		Context::setBrowserTitle(Context::getLang('introduce_title'));
		// Specify the template path
		$this->setTemplatePath($this->module_path . 'tpl');
		// Error occurs if already installed
		if(Context::isInstalled()) return $this->stop('msg_already_installed');
		// Install a controller
		$oInstallController = getController('install');
		$this->install_enable = $oInstallController->checkInstallEnv();
		// If the environment is installable, execute installController::makeDefaultDirectory()
		if($this->install_enable) $oInstallController->makeDefaultDirectory();
	}

	/**
	 * @brief Display license messages
	 **/
	function dispInstallIntroduce(){
		/**
		 * If './config/install.config.php' file is created and has array shown in the example below, DAOLCMS will be installed using config file.
		 * ex )
		 * $install_config = array(
		 * 'db_type' =>'mysql_innodb', //mysql, mysql_innodb, mysqli, sqlite3_pdo, ...
		 * 'db_port' =>'3306',
		 * 'db_hostname' =>'localhost',
		 * 'db_userid' =>'root',
		 * 'db_password' =>'root',
		 * 'db_database' =>'xe_database',
		 * 'db_table_prefix' =>'xe',
		 * 'use_rewrite' =>'N',
		 * 'time_zone' =>'0000', //like +0900(Asia/Seoul)
		 * 'email_address' =>'admin@xe.com', //will be created as admin account
		 * 'password' =>'pass',
		 * 'password2' =>'pass',
		 * 'nick_name' =>'admin',
		 * 'user_id' =>'admin',
		 * 'lang_type' =>'ko',    // en, jp, ...
		 * );
		 */
		$install_config_file = FileHandler::getRealPath('./config/install.config.php');
		if(file_exists($install_config_file)){
			include $install_config_file;
			if(is_array($install_config)){
				foreach($install_config as $k => $v){
					$v = ($k == 'db_table_prefix') ? $v . '_' : $v;
					Context::set($k, $v, true);
				}
				unset($GLOBALS['__DB__']);
				Context::set('install_config', true, true);
				$oInstallController = &getController('install');
				$output = $oInstallController->procInstall();
				if(!$output->toBool()) return $output;
				header("location: ./");
				Context::close();
				exit;
			}
		}

		Context::set('l', Context::getLangType());
		$this->setTemplateFile('introduce');
	}

	/**
	 * @brief Display messages about installation environment
	 **/
	function dispInstallLicenseAgreement(){
		$this->setTemplateFile('license_agreement');

		$lang_type = Context::getLangType();
		Context::set('lang_type', $lang_type);
	}

	/**
	 * @brief Display messages about installation environment
	 **/
	function dispInstallCheckEnv(){
		$this->setTemplateFile('check_env');
	}

	/**
	 * @brief Choose a DB
	 **/
	function dispInstallSelectDB(){
		// Display check_env if it is not installable
		if(!$this->install_enable) return $this->dispInstallCheckEnv();
		// Enter ftp information
		if(ini_get('safe_mode') && !Context::isFTPRegisted()){
			Context::set('progressMenu', '3');
			$this->setTemplateFile('ftp');
		}
		else{
			Context::set('progressMenu', '4');
			$this->setTemplateFile('select_db');
		}
	}

	/**
	 * @brief Display a screen to enter DB and administrator's information
	 **/
	function dispInstallDBForm(){
		// Display check_env if not installable
		if(!$this->install_enable) return $this->dispInstallCheckEnv();
		// Return to the start-up screen if db_type is not specified
		if(!Context::get('db_type')) return $this->dispInstallSelectDB();

		// Output the file, disp_db_info_form.html
		$tpl_filename = sprintf('form.%s', Context::get('db_type'));

		$title = sprintf(Context::getLang('input_dbinfo_by_dbtype'), Context::get('db_type'));
		Context::set('title', $title);

		$error_return_url = getNotEncodedUrl('', 'act', Context::get('act'), 'db_type', Context::get('db_type'));
		if($_SERVER['HTTPS'] == 'on'){
			// Error occured when using https protocol at "ModuleHandler::init() '
			$parsedUrl = parse_url($error_return_url);
			$error_return_url = '';
			if(isset($parsedUrl['path'])) $error_return_url .= $parsedUrl['path'];
			if(isset($parsedUrl['query'])) $error_return_url .= '?' . $parsedUrl['query'];
			if(isset($parsedUrl['fragment'])) $error_return_url .= '?' . $parsedUrl['fragment'];
		}
		Context::set('error_return_url', $error_return_url);

		$this->setTemplateFile($tpl_filename);
	}

	/**
	 * @brief Display a screen to enter DB and administrator's information
	 **/
	function dispInstallConfigForm(){
		// Display check_env if not installable
		if(!$this->install_enable) return $this->dispInstallCheckEnv();

		include _DAOL_PATH_ . 'files/config/tmpDB.config.php';

		Context::set('time_zone', $GLOBALS['time_zone']);
		Context::set('db_type', $db_info->db_type);
		$this->setTemplateFile('config_form');
	}

	/**
	 * @brief Display a screen to enter DB and administrator's information
	 **/
	function dispInstallManagerForm(){
		// Display check_env if not installable
		if(!$this->install_enable){
			return $this->dispInstallCheckEnv();
		}

		Context::loadLang('modules/member/lang');
		$message = Context::getLang('about_password_strength');

		Context::setLang('msg_password_strength', $message['high']);

		$this->setTemplateFile('admin_form');
	}
}
