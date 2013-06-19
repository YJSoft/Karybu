<?php
    /**
     * @class  pointAdminView
     * @author Arnia (dev@karybu.org)
     * @brief The admin view class of the point module
     **/

    class pointAdminView extends point {

        /**
         * @brief Initialization
         **/
        function init() {
            // Get teh configuration information
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');
            // Set the configuration variable
            Context::set('config', $config);				

			//Security
			$security = new Security();			
			$security->encodeHTML('config.point_name','config.level_icon');
			$security->encodeHTML('module_info..');			

			// Set the template path
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief Default configurations
         **/
        function dispPointAdminConfig() {
            // Get the list of level icons
            $level_icon_list = FileHandler::readDir("./modules/point/icons");
            Context::set('level_icon_list', $level_icon_list);
            // Get the list of groups
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups();
            $selected_group_list = array();
            if(count($group_list)) {
                foreach($group_list as $key => $val) {
                    $selected_group_list[$key] = $val;
                }
            }
            Context::set('group_list', $selected_group_list);
			//Security
			$security = new Security();			
			$security->encodeHTML('group_list..title','group_list..description');			

			// Set the template
            $this->setTemplateFile('config');
        }

        /**
         * @brief Set per-module scores
         **/
        function dispPointAdminModuleConfig() {
            // Get a list of mid
            $oModuleModel = &getModel('module');
			$columnList = array('module_srl', 'mid', 'browser_title');
            $mid_list = $oModuleModel->getMidList(null, $columnList);
            Context::set('mid_list', $mid_list);

            Context::set('module_config', $oModuleModel->getModulePartConfigs('point'));
			//Security
			$security = new Security();			
			$security->encodeHTML('mid_list..browser_title','mid_list..mid');			

			// Set the template
            $this->setTemplateFile('module_config');
        }

        /**
         * @brief Configure the functional act
         **/
        function dispPointAdminActConfig() {
            // Set the template
            $this->setTemplateFile('action_config');
        }
        function getGrid(){
            global $lang;
            $grid = new \Karybu\Grid\Backend();
            $grid->setShowOrderNumberColumn(true);
            $grid->setAllowMassSelect(false);
            $grid->addColumn('email_address', 'text', array(
                'index'=>'email_address',
                'header'=>$lang->email
            ));
            $grid->addColumn('nick_name', 'member', array(
                'index'=>'nick_name',
                'header'=>$lang->nick_name
            ));
            $grid->addColumn('point', 'point', array(
                'index'=>'point',
                'header'=>$lang->point
            ));
            $grid->addColumn('level', 'number', array(
                'index'=>'level',
                'header'=>$lang->level,
                'sortable'=>false
            ));
            return $grid;
        }
        /**
         * @brief Get a list of member points
         **/
        function dispPointAdminPointList() {
            $oPointModel = &getModel('point');
            $args = new stdClass();
            $grid = $this->getGrid();
            $sort_index = Context::get('sort_index');
            $sort_order = Context::get('sort_order');
            $grid->setSortIndex($sort_index);
            $grid->setSortOrder($sort_order);
            $args->sort_index = $grid->getSortIndex();
            $args->sort_order = $grid->getSortOrder();
            $args->list_count = 20;
            $args->page_count = 20;
            $args->page = Context::get('page');

			$oMemberModel = &getModel('member');
			$memberConfig = $oMemberModel->getMemberConfig();

			Context::set('identifier', $memberConfig->identifier);

			$columnList = array('member.member_srl', 'member.user_id', 'member.email_address', 'member.nick_name', 'point.point');
            $output = $oPointModel->getMemberList($args, $columnList);
            // context::set for writing into a template 
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('member_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);
            // Create a member model object
            $oMemberModel = &getModel('member');
            // Get a list of groups
            $this->group_list = $oMemberModel->getGroups();
            Context::set('group_list', $this->group_list);
			//Security
            $grid->setRows($output->data);
            Context::set('grid', $grid);
			$security = new Security();			
			$security->encodeHTML('group_list..title','group_list..description');
			$security->encodeHTML('member_list..');			

			// Set the template
            $this->setTemplateFile('member_list');
        }
    }
?>
