<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  syndicationController
 * @author NAVER (developers@xpressengine.com)
 * @brief syndication module's Controller class
 **/

class syndicationController extends syndication
{
	var $ping_message = '';

	function triggerInsertDocument(&$obj) {
		if($obj->module_srl < 1) return new Object();

		$oSyndicationModel = getModel('syndication');
		$oModuleModel = getModel('module');

		if($oSyndicationModel->isExceptedModules($obj->module_srl)) return new Object();

		$config = $oModuleModel->getModuleConfig('syndication');

		$id = $oSyndicationModel->getID('channel', $obj->module_srl);
		$this->ping($id, 'article');

		return new Object();
	}

	function triggerUpdateDocument(&$obj) {
		if($obj->module_srl < 1) return new Object();

		$oSyndicationModel = getModel('syndication');
		$oModuleModel = getModel('module');

		if($oSyndicationModel->isExceptedModules($obj->module_srl)) return new Object();

		$config = $oModuleModel->getModuleConfig('syndication');

		$id = $oSyndicationModel->getID('channel', $obj->module_srl);
		$this->ping($id, 'article');

		return new Object();
	}

	function triggerDeleteDocument(&$obj) {
		if($obj->module_srl < 1) return new Object();

		$oSyndicationModel = getModel('syndication');
		$oModuleModel = getModel('module');

		if($oSyndicationModel->isExceptedModules($obj->module_srl)) return new Object();

		$this->insertLog($obj->module_srl, $obj->document_srl, $obj->title, $obj->content);

		$id = $oSyndicationModel->getID('channel', $obj->module_srl);
		$this->ping($id, 'deleted');

		return new Object();
	}

	function triggerDeleteModule(&$obj) {
		$oSyndicationModel = getModel('syndication');
		$oModuleModel = getModel('module');

		if($oSyndicationModel->isExceptedModules($obj->module_srl)) return new Object();

		$output = executeQuery('syndication.getExceptModule', $obj);
		if($output->data->count) return new Object();
		

		$id = $oSyndicationModel->getID('site', $obj->module_srl);
		$this->ping($id, 'deleted');

		return new Object();
	}

	function triggerMoveDocumentToTrash(&$obj) {
		$document_srl = $obj->document_srl;
		$module_srl = $obj->module_srl;

		$oSyndicationModel = getModel('syndication');
		$oModuleModel = getModel('module');

		if($oSyndicationModel->isExceptedModules($module_srl)) return new Object();

		$this->insertLog($obj->module_srl, $obj->document_srl, '', '');

		$id = $oSyndicationModel->getID('channel', $module_srl);
		$this->ping($id, 'deleted');

		return new Object();
	}

	function triggerRestoreTrash(&$obj) {
		$document_srl = $obj->document_srl;
		$module_srl = $obj->module_srl;

		$oSyndicationModel = getModel('syndication');
		$oModuleModel = getModel('module');

		if($oSyndicationModel->isExceptedModules($module_srl)) return new Object();

		$config = $oModuleModel->getModuleConfig('syndication');

		// 신디케이션 삭제 로그 제거
		$this->deleteLog($module_srl, $document_srl);

		$id = $oSyndicationModel->getID('article', $module_srl.'-'.$document_srl);
		$this->ping($id, 'article');

		return new Object();
	}

	function insertLog($module_srl, $document_srl, $title = null, $summary = null)
	{
		$args = new stdClass;
		$args->module_srl = $module_srl;
		$args->document_srl = $document_srl;
		$args->title = $title;
		$args->summary = $summary;
		$output = executeQuery('syndication.insertLog', $args);
	}

	function deleteLog($module_srl, $document_srl)
	{
		$args = new stdClass;
		$args->module_srl = $module_srl;
		$args->document_srl = $document_srl;
		$output = executeQuery('syndication.deleteLog', $args);
	}

	function ping($id, $type, $page=1) {
		$this->ping_message = '';

		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('syndication');

		if(!$config->syndication_token)
		{
			$this->ping_message = 'Syndication Token empty';
			return false;
		}

		if(substr($config->site_url,-1)!='/')
		{
			$config->site_url .= '/';
		}

		$ping_url = 'https://apis.naver.com/crawl/nsyndi/v2';
		$ping_header = array();
		$ping_header['Host'] = 'apis.naver.com';
		$ping_header['Pragma'] = 'no-cache';
		$ping_header['Accept'] = '*/*';
		$ping_header['Authorization'] = sprintf("Bearer %s", $config->syndication_token);

		$request_config = array();
		$request_config['ssl_verify_peer'] = false;

		$ping_body = sprintf('http://%s?module=syndication&act=getSyndicationList&id=%s&type=%s&page=%s&syndication_password=%s', $config->site_url, $id, $type, $page, $config->syndication_password);

		$buff = FileHandler::getRemoteResource($ping_url, null, 10, 'POST', 'application/x-www-form-urlencoded', $ping_header, array(), array('ping_url'=>$ping_body), $request_config);

		$xml = new XmlParser();
		$xmlDoc= $xml->parse($buff);
		if($xmlDoc->result->error_code->boddy != '000')
		{
			if(!$buff)
			{
				$this->ping_message = 'Socket connection error. Check your Server Environment.';
			}
			else
			{
				$this->ping_message = $xmlDoc->result->message->body;
			}
			return false;
		}
		return true;
	}
}
