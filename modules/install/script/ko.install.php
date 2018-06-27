<?php
	// ko/en/...
	$lang = Context::getLangType();
	$logged_info = Context::get('logged_info');

	$oMenuAdminController = getAdminController('menu');

	function insertMenu($title, $site_srl){
		$menu_args = new stdClass;
		$menu_args->title = $title;
		$menu_args->site_srl = $site_srl;
		$menu_args->menu_srl = getNextSequence();
		$menu_args->listorder = $menu_args->menu_srl * -1;
		$output = executeQuery('menu.insertMenu', $menu_args);
		return array($output, $menu_args->menu_srl);
	}

	function insertMenuItem($url, $name, $menu_srl, $parent_srl = NULL){
		$item_args = new stdClass;
		$item_args->url = $url;
		$item_args->name = $name;
		$item_args->menu_srl = $menu_srl; 
		$item_args->menu_item_srl = getNextSequence();
		$item_args->parent_srl = $parent_srl;
		$item_args->listorder = -1 * $item_args->menu_item_srl;
		$output = executeQuery('menu.insertMenuItem', $item_args);
		return array($output, $item_args->menu_item_srl);
	}

	$outputs = insertMenu('Main Menu', 0);
	if(!$outputs[0]->toBool()) return $outputs[0];
	$menu_srl = $outputs[1];

	$outputs = insertMenuItem('welcome_page', 'Welcome Page', $menu_srl);
	if(!$outputs[0]->toBool()) return $outputs[0];

	$outputs = insertMenuItem('board', 'Board', $menu_srl);
	if(!$outputs[0]->toBool()) return $outputs[0];

	$outputs = insertMenuItem('board', 'Board', $menu_srl, $outputs[1]);
	if(!$outputs[0]->toBool()) return $outputs[0];

	$outputs = insertMenuItem('admin', 'Dashboard', $menu_srl);
	if(!$outputs[0]->toBool()) return $outputs[0];

	// XML 파일을 갱신
	$oMenuAdminController->makeXmlFile($menu_srl);

	// create Layout
	//extra_vars init
	$extra_vars = new stdClass;
	$extra_vars->banner_style = 'y';
	$extra_vars->body_style = 'main';
	$extra_vars->main_menu = $menu_srl;
	$extra_vars->bottom_menu = $menu_srl;
	$extra_vars->menu_name_list = array();
	$extra_vars->menu_name_list[$menu_srl] = 'Main Menu';

	$args = new stdClass;
	$args->site_srl = 0;
	$layout_srl = $args->layout_srl = getNextSequence();
	$args->layout = 'daol_official';
	$args->title = 'welcome_layout';

	$oLayoutAdminController = getAdminController('layout');
	$output = $oLayoutAdminController->insertLayout($args);
	if(!$output->toBool()) return $output;

	// update Layout
	$args->extra_vars = serialize($extra_vars);
	$output = $oLayoutAdminController->updateLayout($args);
	if(!$output->toBool()) return $output;

	// insertPageModule
	$page_args = new stdClass;
	$page_args->layout_srl = $layout_srl;
	$page_args->browser_title = 'Welcome DAOL CMS';
	$page_args->module = 'page';
	$page_args->mid = 'welcome_page';
	$page_args->module_category_srl = 0;
	$page_args->page_caching_interval = 0;
	$page_args->page_type = 'ARTICLE';
	$page_args->skin = 'default';
	
	$oModuleController = getController('module');
	$output = $oModuleController->insertModule($page_args);

	if(!$output->toBool()) return $output;

	$module_srl = $output->get('module_srl');

	// insert PageContents - widget
	$oTemplateHandler = &TemplateHandler::getInstance();

	$oDocumentModel = getModel('document');
	$oDocumentController = getController('document');

	$obj = new stdClass;
	$obj->member_srl = $logged_info->member_srl;
	$obj->user_id = htmlspecialchars_decode($logged_info->user_id);
	$obj->user_name = htmlspecialchars_decode($logged_info->user_name);
	$obj->nick_name = htmlspecialchars_decode($logged_info->nick_name);
	$obj->email_address = $logged_info->email_address;

	$obj->module_srl = $module_srl;
	Context::set('version', __DAOL_VERSION__);
	$obj->title = 'Welcome DAOL CMS';

	$obj->content = $oTemplateHandler->compile('./modules/install/script/welcome_content', 'welcome_content_'.$lang);

	$output = $oDocumentController->insertDocument($obj,true);
	if(!$output->toBool()) return $output;
	
	$document_srl = $output->get('document_srl');

	// save PageWidget
	$oModuleModel = getModel('module');
	$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
	$module_info->document_srl = $document_srl;
	$output = $oModuleController->updateModule($module_info);
	if(!$output->toBool()) return $output;

	// insertFirstModule
	$site_args = new stdClass;
	$site_args->site_srl = 0;
	$site_args->index_module_srl = $module_srl;
	$oModuleController->updateSite($site_args);

?>
