<?php
class pageMobile extends pageView {
	function _getArticleContent(){
		$oTemplate = &TemplateHandler::getInstance();

		$oDocumentModel = getModel('document');
		$oDocument = $oDocumentModel->getDocument(0, true);

		if($this->module_info->mdocument_srl){
			$document_srl = $this->module_info->mdocument_srl;
			$oDocument->setDocument($document_srl);
			Context::set('document_srl', $document_srl);
		}
		Context::set('oDocument', $oDocument);

		if($this->module_info->mskin){
			$templatePath = (sprintf($this->module_path . 'm.skins/%s', $this->module_info->skin));
		} else {
			$templatePath = ($this->module_path . 'm.skins/default');
		}

		$page_content = $oTemplate->compile($templatePath, 'mobile');

		return $page_content;
	}
}
