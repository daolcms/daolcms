<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/* Copyright (C) DAOL Project <http://www.daolcms.org> */
/**
 * @class  krzipAdminView
 * @author NAVER (developers@xpressengine.com)
 * @Adaptor DAOL Project (developer@daolcms.org)
 * @brief  Krzip module admin view class.
 */

class krzipAdminView extends krzip {
	function init(){
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile(lcfirst(str_replace('dispKrzipAdmin', '', $this->act)));
	}

	function dispKrzipAdminConfig(){
		$oKrzipModel = getModel('krzip');
		$module_config = $oKrzipModel->getConfig();
		Context::set('module_config', $module_config);
	}
}

/* End of file krzip.admin.view.php */
/* Location: ./modules/krzip/krzip.admin.view.php */
