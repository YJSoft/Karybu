<?php
    /**
     * Admin view class of addon modules
     * @author Arnia (dev@karybu.org)
     **/
    class addonAdminView extends addon {

        /**
         * Initialization
		 *
		 * @return void
         **/
        function init() {
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * Add Management main page (showing the list)
		 *
		 * @return Object
         **/
        function dispAddonAdminIndex() {
			$oAdminModel = &getAdminModel('admin');

            // Add to the list settings
            $oAddonModel = &getAdminModel('addon');
            $addon_list = $oAddonModel->getAddonListForSuperAdmin();

			$security = new Security($addon_list);
			$addon_list = $security->encodeHTML('..', '..author..');

			foreach($addon_list as $no => $addon_info)
			{
                if (!empty($addon_info->description)) {
				    $addon_list[$no]->description = nl2br(trim($addon_info->description));
                }
                else {
                    $addon_list[$no]->description = '';
                }
			}

            Context::set('addon_list', $addon_list);
			Context::set('addon_count', count($addon_list));
            // Template specifies the path and file
            $this->setTemplateFile('addon_list');
        }

        /**
         * Display setup page
		 *
		 * @return Object
         **/
        function dispAddonAdminSetup() {
            $site_module_info = Context::get('site_module_info');
            // Wanted to add the requested
            $selected_addon = Context::get('selected_addon');
            // Wanted to add the requested information
            $oAddonModel = &getAdminModel('addon');
            $addon_info = $oAddonModel->getAddonInfoXml($selected_addon, $site_module_info->site_srl, 'site');
            Context::set('addon_info', $addon_info);
            // Get a mid list
            $oModuleModel = &getModel('module');
            $oModuleAdminModel = &getAdminModel('module');
            $args = new stdClass();
            if(!empty($site_module_info->site_srl)) {
                $args->site_srl = $site_module_info->site_srl;
            }
            else {
                $args->site_srl = null;
            }
			$columnList = array('module_srl', 'module_category_srl', 'mid', 'browser_title');
            $mid_list = $oModuleModel->getMidList($args, $columnList);
            // module_category and module combination
            if(empty($site_module_info->site_srl)) {
                // Get a list of module categories
                $module_categories = $oModuleModel->getModuleCategories();

                if(is_array($mid_list)) {
                    foreach($mid_list as $module_srl => $module) {
                        $module_categories[$module->module_category_srl]->list[$module_srl] = $module;
                    }
                }
            }
            else {
                $module_categories = array();
                $module_categories[0] = new stdClass();
                $module_categories[0]->list = $mid_list;
            }

            Context::set('mid_list',$module_categories);

            // Template specifies the path and file
            $this->setTemplateFile('setup_addon');

			if(Context::get('module') != 'admin')
			{
				$this->setLayoutPath('./common/tpl');
				$this->setLayoutFile('popup_layout');
			}

			$security = new Security();
			$security->encodeHTML('addon_info.', 'addon_info.author..', 'mid_list....');
        }

        /**
         * Display information
		 *
		 * @return Object
         **/
        function dispAddonAdminInfo() {
            $site_module_info = Context::get('site_module_info');
            // Wanted to add the requested
            $selected_addon = Context::get('selected_addon');
            // Wanted to add the requested information
            $oAddonModel = &getAdminModel('addon');
            $site_srl = null;
            if (!empty($site_module_info->site_srl)) {
                $site_srl = $site_module_info->site_srl;
            }
            $addon_info = $oAddonModel->getAddonInfoXml($selected_addon, $site_srl);
            Context::set('addon_info', $addon_info);
            // Set the layout to be pop-up
            $this->setLayoutFile('popup_layout');
            // Template specifies the path and file
            $this->setTemplateFile('addon_info');

			$security = new Security();
			$security->encodeHTML('addon_info.', 'addon_info.author..');
        }
    }
?>
