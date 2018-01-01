<?php

/**
 * adminlogging class
 * Base class of adminlogging module
 *
 * @author  NAVER (developers@xpressengine.com)
 * @package /modules/adminlogging
 * @version 0.1
 */
class adminlogging extends ModuleObject {
	/**
	 * Install adminlogging module
	 * @return BaseObject
	 */
	function moduleInstall() {
		return new BaseObject();
	}

	/**
	 * If update is necessary it returns true
	 * @return bool
	 */
	function checkUpdate() {
		return false;
	}

	/**
	 * Update module
	 * @return BaseObject
	 */
	function moduleUpdate() {
		return new BaseObject();
	}

	/**
	 * Regenerate cache file
	 * @return void
	 */
	function recompileCache() {
	}
}
