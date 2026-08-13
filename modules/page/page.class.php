<?php

/**
 * @class  page
 * @author NAVER (developers@xpressengine.com)
 * @brief  high class of the module page
 **/
class page extends ModuleObject {

	/**
	 * @brief Implement if additional tasks are necessary when installing
	 **/
	function moduleInstall(){
		// page generated from the cache directory to use
		FileHandler::makeDir('./files/cache/page');

		return new BaseObject();
	}

	/**
	 * Check whether an external page path is safe to execute.
	 *
	 * @param string $path
	 * @return bool
	 **/
	public static function isAllowedExternalPath($path){
		// Remove null bytes and normalize directory separators.
		$path = str_replace("\0", '', (string)$path);
		$path = str_replace('\\', '/', $path);

		// Block user-controlled, sensitive, and executable cache directories.
		if(preg_match('!(?:^|/)files/(?:attach|cache|config|debug|env|member_extra_info|ruleset|site_design|thumbnails)/!i', $path)){
			return false;
		}

		// Resolve symlinks and traversal before checking the effective path again.
		if(!preg_match('!^https?://!i', $path) && file_exists($path)){
			$realpath = realpath($path);
			if($realpath !== false){
				$normalized_realpath = str_replace('\\', '/', $realpath);
				if($normalized_realpath !== $path && !self::isAllowedExternalPath($normalized_realpath)){
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * @brief a method to check if successfully installed
	 **/
	function checkUpdate(){
		$output = executeQuery('page.pageTypeOpageCheck');
		if($output->toBool() && $output->data) return true;

		$output = executeQuery('page.pageTypeNullCheck');
		if($output->toBool() && $output->data) return true;

		return false;
	}

	/**
	 * @brief Execute update
	 **/
	function moduleUpdate(){
		$args = new stdClass;
		// opage module instance update
		$output = executeQueryArray('page.pageTypeOpageCheck');
		if($output->toBool() && count($output->data) > 0){
			foreach($output->data as $val){
				$args->module_srl = $val->module_srl;
				$args->name = 'page_type';
				$args->value = 'OUTSIDE';
				$in_out = executeQuery('page.insertPageType', $args);
			}
			$output = executeQuery('page.updateAllOpage');
			if(!$output->toBool()) return $output;
		}

		// old page module instance update
		$output = executeQueryArray('page.pageTypeNullCheck');
		$skin_update_srls = array();
		if($output->toBool() && $output->data){
			foreach($output->data as $val){
				$args->module_srl = $val->module_srl;
				$args->name = 'page_type';
				$args->value = 'WIDGET';
				$in_out = executeQuery('page.insertPageType', $args);

				$skin_update_srls[] = $val->module_srl;
			}
		}

		if(count($skin_update_srls) > 0){
			$skin_args = new stdClass;
			$skin_args->module_srls = implode(',', $skin_update_srls);
			$skin_args->is_skin_fix = "Y";
			$ouput = executeQuery('page.updateSkinFix', $skin_args);
		}
		return new BaseObject(0, 'success_updated');
	}

	/**
	 * @brief Re-generate the cache file
	 **/
	function recompileCache(){
	}
}
