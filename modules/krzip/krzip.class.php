<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/* Copyright (C) DAOL Project <http://www.daolcms.org> */
/**
 * @class   krzip
 * @author  NAVER (developers@xpressengine.com)
 * @Adaptor DAOL Project (developer@daolcms.org)
 * @brief   Krzip module high class.
 */

if(!function_exists('lcfirst')) {
	function lcfirst($text) {
		return strtolower(substr($text, 0, 1)) . substr($text, 1);
	}
}

class krzip extends ModuleObject {
	public static $sequence_id = 0;
	
	public static $default_config = array('api_handler' => 0);
	
	public static $api_list = array('daumapi', 'epostapi');
	
	public static $epostapi_host = 'http://biz.epost.go.kr/KpostPortal/openapi';
	
	function moduleInstall() {
		return new BaseObject();
	}
	
	function moduleUninstall() {
		return new BaseObject();
	}
	
	function checkUpdate() {
		return FALSE;
	}
	
	function moduleUpdate() {
		return new BaseObject();
	}
}

/* End of file krzip.class.php */
/* Location: ./modules/krzip/krzip.class.php */
