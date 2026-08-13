<?php

/**
 * @class   layoutView
 * @author  NAVER (developers@xpressengine.com)
 * @Adaptor DAOL Project (developer@daolcms.org)
 * admin view class of the layout module
 **/
class layoutView extends layout {

	/**
	 * Initialization
	 * @return void
	 **/
	function init(){
		$this->setTemplatePath($this->module_path . 'tpl');
	}

	/**
	 * Pop-up layout details(conf/info.xml)
	 * @return void
	 **/
	function dispLayoutInfo(){
		// Get the layout information
		$oLayoutModel = getModel('layout');
		$layout_info = $oLayoutModel->getLayoutInfo(Context::get('selected_layout'));
		if(!$layout_info) exit();
		Context::set('layout_info', $layout_info);
		// Set the layout to be pop-up
		$this->setLayoutFile('popup_layout');
		// Set a template file
		$this->setTemplateFile('layout_detail_info');
	}
}
