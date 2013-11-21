<?php
    /**
     * @class  boardAdminController
     * @author zero (zero@nzeo.com)
     * @brief  board module admin controller class
     **/

    class boardAdminController extends board {

        /**
         * @brief initialization
         **/
        function init() {
        }

        /**
         * @brief insert borad module
         **/
        function procBoardAdminInsertBoard($args = null) {
            // igenerate module model/controller object
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');

            // setup the board module infortmation
            $args = Context::getRequestVars();
            $args->module = 'board';
            $args->mid = $args->board_name;
			if(is_array($args->use_status)) $args->use_status = implode('|@|', $args->use_status);
            unset($args->board_name);

            // setup other variables
            if($args->use_category!='Y') $args->use_category = 'N';
            if($args->except_notice!='Y') $args->except_notice = 'N';
            if($args->use_anonymous!='Y') $args->use_anonymous= 'N';
            if($args->consultation!='Y') $args->consultation = 'N';
            if(!in_array($args->order_target,$this->order_target)) $args->order_target = 'list_order';
            if(!in_array($args->order_type,array('asc','desc'))) $args->order_type = 'asc';

            // if there is an existed module
            if($args->module_srl) {
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
                if($module_info->module_srl != $args->module_srl) unset($args->module_srl);
            }

            // insert/update the board module based on module_srl
            if(!$args->module_srl) {
                $output = $oModuleController->insertModule($args);
                $msg_code = 'success_registed';
            } else {
                $output = $oModuleController->updateModule($args);
                $msg_code = 'success_updated';
            }

            if(!$output->toBool()) return $output;

            $this->setMessage($msg_code);
			if (Context::get('success_return_url')){
				$this->setRedirectUrl(Context::get('success_return_url'));
			}else{
				$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispBoardAdminBoardInfo', 'module_srl', $output->get('module_srl')));
			}
        }

        /**
         * @brief delete the board module
         **/
        function procBoardAdminDeleteBoard() {
            $module_srl = Context::get('module_srl');

            // get the current module
            $oModuleController = &getController('module');
            $output = $oModuleController->deleteModule($module_srl);
            if(!$output->toBool()) return $output;

            $this->add('module','board');
            $this->add('page',Context::get('page'));
            $this->setMessage('success_deleted');
        }

        /**
         * @brief insert board list config 
         **/
        function procBoardAdminInsertListConfig() {
            $module_srl = Context::get('module_srl');
            $list = explode(',',Context::get('list'));
            if(!count($list)) return new Object(-1, 'msg_invalid_request');

            $list_arr = array();
            foreach($list as $val) {
                $val = trim($val);
                if(!$val) continue;
                if(substr($val,0,10)=='extra_vars') $val = substr($val,10);
                $list_arr[] = $val;
            }

            $oModuleController = &getController('module');
            $oModuleController->insertModulePartConfig('board', $module_srl, $list_arr);

			$this->setRedirectUrl(Context::get('success_return_url'));
        }
    }
?>
