<?php	
    /**
     * @class  memberAdminView
     * @author Arnia (dev@karybu.org)
     * member module's admin view class
     **/

    class memberAdminView extends member {

		/**
		 * Group list
		 * 
		 * @var array
		 **/
        var $group_list = NULL;

		/**
		 * Selected member info
		 * 
		 * @var array
		 **/
		var $memberInfo = NULL;

        /**
         * initialization
		 *
		 * @return void
		 **/
        function init() {
            $oMemberModel = &getModel('member');

            // if member_srl exists, set memberInfo            
			$member_srl = Context::get('member_srl');
            if($member_srl) {
                $this->memberInfo = $oMemberModel->getMemberInfoByMemberSrl($member_srl);                if(!$this->memberInfo) Context::set('member_srl','');                else Context::set('member_info',$this->memberInfo);            }

            // retrieve group list            
			$this->group_list = $oMemberModel->getGroups();
            Context::set('group_list', $this->group_list);

			$security = new Security();						
			$security->encodeHTML('group_list..');
			
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * display member list
		 *
		 * @return void
		 **/
        function dispMemberAdminList() {
            $oMemberAdminModel = &getAdminModel('member');
            $oMemberModel = &getModel('member');
            $output = $oMemberAdminModel->getMemberList();

			$filter = Context::get('filter_type');
			global $lang;			
			switch($filter){				
				case 'super_admin' : Context::set('filter_type_title', $lang->cmd_show_super_admin_member);break;
				case 'site_admin' : Context::set('filter_type_title', $lang->cmd_show_site_admin_member);break;
				case 'enable' :  Context::set('filter_type_title', $lang->approval);break;
				case 'disable' : Context::set('filter_type_title', $lang->denied);break;
				default : Context::set('filter_type_title', $lang->cmd_show_all_member);break;			
			}
			// retrieve list of groups for each member
            if($output->data) {
                foreach($output->data as $key => $member)
				{
                    $output->data[$key]->group_list = $oMemberModel->getMemberGroups($member->member_srl,0);
                    $output->data[$key]->member_group = implode(', ', $oMemberModel->getMemberGroups($member->member_srl,0));
                    $massSelectValueFields = array('member_srl', 'email_address', 'user_id', 'user_name', 'nick_name', 'member_group');
                    $massSelectValues = array();
                    foreach ($massSelectValueFields as $field){
                        if (isset($member->$field)){
                            $massSelectValues[] = $member->$field;
                        }
                        else{
                            $massSelectValues[] = '';
                        }
                    }
                    //add status
                    if (isset($member->denied) && $member->denied == 'N'){
                        $massSelectValues[] = $lang->approval;
                    }
                    else{
                        $massSelectValues[] = $lang->denied;
                    }
                    $output->data[$key]->mass_select_value = implode("\t", $massSelectValues);
                    $output->data[$key]->disable_mass_select = ($member->is_admin == 'Y');
                }
            }
			$config = $oMemberModel->getMemberConfig();			
			$memberIdentifiers = array('user_id'=>'user_id', 'user_name'=>'user_name', 'nick_name'=>'nick_name');			
			$usedIdentifiers = array();			

			if (is_array($config->signupForm)){
				foreach($config->signupForm as $signupItem){				
					if (!count($memberIdentifiers)) break;				
					if(in_array($signupItem->name, $memberIdentifiers) && ($signupItem->required || $signupItem->isUse)){					
						unset($memberIdentifiers[$signupItem->name]);					
						$usedIdentifiers[$signupItem->name] = $lang->{$signupItem->name};				
					}			
				}
			}
			Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('member_list', $output->data);
            Context::set('usedIdentifiers', $usedIdentifiers);            
			Context::set('page_navigation', $output->page_navigation);
			
			$security = new Security();
			$security->encodeHTML('member_list..user_name', 'member_list..nick_name', 'member_list..group_list..');

            $grid = new \Karybu\Grid\Backend();
            $grid->addCssClass('_memberList');
            $grid->setMassSelectName('user');
            //email column
			$grid->addColumn('email_address', 'member', array(
                'index' => 'email_address',
                'header'=> $lang->email,
                'masked'=>true,
                'title'=>'Info',
                'member_key'=>'member_srl'
            ));
            $grid->addColumn('user_id', 'text', array(
                'index' => 'user_id',
                'header'=> $lang->user_id
            ));
            $grid->addColumn('user_name', 'text', array(
                'index' => 'user_name',
                'header'=> $lang->user_name
            ));
            $grid->addColumn('nick_name', 'text', array(
                'index' => 'nick_name',
                'header'=> $lang->nick_name
            ));
            $grid->addColumn('regdate', 'date', array(
                'index' => 'regdate',
                'header'=> $lang->signup_date,
                'format'=> 'Y-m-d'
            ));
            $grid->addColumn('last_login', 'date', array(
                'index' => 'last_login',
                'header'=> $lang->last_login,
                'format'=> 'Y-m-d'
            ));
            $grid->addColumn('member_group', 'text', array(
                'index' => 'member_group',
                'header'=> $lang->member_group
            ));
            $grid->addColumn('denied', 'options', array(
                'index'     => 'denied',
                'header'    => $lang->status,
                'show_raw_value' => true,
                'options'   => array(
                    'Y'=>$lang->denied,
                    'N'=>$lang->approval
                )
            ));
            $grid->addColumn('actions', 'action', array(
                'index'         => 'actions',
                'header'        => $lang->actions,
                'wrapper_top'   => '<div class="kActionIcons">',
                'wrapper_bottom'=> '</div>'
            ));
            //view action
            $actionConfig = array(
                'title'=>$lang->cmd_view,
                'url_params'=>array('member_srl'=>'member_srl'),
                'module'=>'admin',
                'act'=>'dispMemberAdminInfo',
                'icon_class' => 'kView'
            );
            $action = new \Karybu\Grid\Action\Action($actionConfig);
            $grid->getColumn('actions')->addAction('view',$action);
            //update action
            $actionConfig = array(
                'title'=>$lang->cmd_update,
                'url_params'=>array('member_srl'=>'member_srl'),
                'module'=>'admin',
                'act'=>'dispMemberAdminInsert',
                'icon_class' => 'kEdit'
            );
            $action = new \Karybu\Grid\Action\Action($actionConfig);
            $grid->getColumn('actions')->addAction('update',$action);

            //delete action
            $actionConfig = array(
                'title'=>$lang->cmd_delete,
                'url_params'=>array('member_srl'=>'member_srl'),
                'module'=>'admin',
                'act'=>'dispMemberAdminDeleteForm',
                'icon_class' => 'kDelete'
            );
            $action = new \Karybu\Grid\Action\Action($actionConfig);
            $grid->getColumn('actions')->addAction('delete',$action);
            $grid->setRows($output->data);
            Context::set('grid', $grid);
			$this->setTemplateFile('member_list');
        }

        /**
         * default configuration for member management
		 *
		 * @return void
         **/
        function dispMemberAdminConfig() 
		{
            $oModuleModel = &getModel('module');
            $oMemberModel = &getModel('member');
            $config = $oMemberModel->getMemberConfig();

			Context::set('config',$config);

            // Get a layout list
            $oLayoutModel = &getModel('layout');
            $layout_list = $oLayoutModel->getLayoutList();

            Context::set('layout_list', $layout_list);

            $mlayout_list = $oLayoutModel->getLayoutList(0, 'M');

            Context::set('mlayout_list', $mlayout_list);

            // list of skins for member module
            $skin_list = $oModuleModel->getSkins($this->module_path);
            Context::set('skin_list', $skin_list);

            // list of skins for member module
            $mskin_list = $oModuleModel->getSkins($this->module_path, 'm.skins');
            Context::set('mskin_list', $mskin_list);

            // retrieve skins of editor
            $oEditorModel = &getModel('editor');
            Context::set('editor_skin_list', $oEditorModel->getEditorSkinList());

            // get an editor
            $option = new stdClass();
            $option->primary_key_name = 'temp_srl';
            $option->content_key_name = 'agreement';
            $option->allow_fileupload = false;
            $option->enable_autosave = false;
            $option->enable_default_component = true;
            $option->enable_component = true;
            $option->resizable = true;
            $option->height = 300;
            $editor = $oEditorModel->getEditor(0, $option);
            Context::set('editor', $editor);

            $signupForm = $config->signupForm;
            foreach($signupForm as $val)
            {
                    if($val->name == 'user_id')
                    {
                            $userIdInfo = $val;
                            break;
                    }
            }

            if($userIdInfo->isUse)
            {
                    // get denied ID list
                    Context::set('useUserID', 1);
                    $denied_list = $oMemberModel->getDeniedIDs();
                    Context::set('deniedIDs', $denied_list);
            }

            // get denied NickName List
            $deniedNickNames = $oMemberModel->getDeniedNickNames();
            Context::set('deniedNickNames', $deniedNickNames);

            $security = new Security();
            $security->encodeHTML('config..');

                        
            //SNS
            $sns_list = $oMemberModel->getSnsList(FALSE);
            $sns_count=0;
            if($sns_list){
                foreach($sns_list as $sns_name => $config) {
                    $sns_count++;		    								
                    $config->path = sprintf('./modules/member/sns/%s', $sns_name);
                }
            }
            Context::set('sns_count', $sns_count);
            Context::set('sns_list', $sns_list);
                        
            $this->setTemplateFile('member_config');
        }

        /**
         * display member information
		 *
		 * @return void
         **/
        function dispMemberAdminInfo() {
            $oMemberModel = &getModel('member');
            $oModuleModel = &getModel('module');
            $member_config = $oModuleModel->getModuleConfig('member');
            Context::set('member_config', $member_config);
			$extendForm = $oMemberModel->getCombineJoinForm($this->memberInfo);            
			Context::set('extend_form_list', $extendForm);			
			$memberInfo = get_object_vars(Context::get('member_info'));			
			if (!is_array($memberInfo['group_list'])) $memberInfo['group_list'] = array();
			Context::set('memberInfo', $memberInfo);			
			
			$disableColumns = array('password', 'find_account_question');			
			Context::set('disableColumns', $disableColumns);			

			$security = new Security();
			$security->encodeHTML('member_config..');
			$security->encodeHTML('extend_form_list...');
			
            $this->setTemplateFile('member_info');
        }

        /**
         * display member insert form
		 *
		 * @return void
         **/
        function dispMemberAdminInsert() {
            // retrieve extend form
            $oMemberModel = &getModel('member');

            $memberInfo = Context::get('member_info');
            if (!is_object($memberInfo)){
                $memberInfo = new stdClass();
            }
            if (isset($this->memberInfo->member_srl)) {
			    $memberInfo->signature = $oMemberModel->getSignature($this->memberInfo->member_srl);
            }
            else {
                $memberInfo->signature = '';
            }
			Context::set('member_info', $memberInfo);
            
			// get an editor for the signature
            if(!empty($memberInfo->member_srl)) {
				$oEditorModel = &getModel('editor');
                $option = new stdClass();
                $option->primary_key_name = 'member_srl';
                $option->content_key_name = 'signature';
                $option->allow_fileupload = false;
                $option->enable_autosave = false;
                $option->enable_default_component = true;
                $option->enable_component = false;
                $option->resizable = false;
                $option->height = 200;
                $editor = $oEditorModel->getEditor($this->memberInfo->member_srl, $option);                
				Context::set('editor', $editor);
            }
			
			$security = new Security();				
			$security->encodeHTML('extend_form_list..');
			$security->encodeHTML('extend_form_list..default_value.');			
			
			$formTags = $this->_getMemberInputTag($memberInfo, true);			
			Context::set('formTags', $formTags);			
			$member_config = $oMemberModel->getMemberConfig();			
			
			global $lang;
            $identifierForm = new stdClass();
			$identifierForm->title = $lang->{$member_config->identifier};			
			$identifierForm->name = $member_config->identifier;			
			$identifierForm->value = isset($memberInfo->{$member_config->identifier}) ? $memberInfo->{$member_config->identifier} : null;
			Context::set('identifierForm', $identifierForm);            
			$this->setTemplateFile('insert_member');
        }

        /**
         * Get tags by the member info type 
		 *
		 * @param object $memberInfo
		 * @param boolean $isAdmin (true : admin, false : not admin)
		 *
		 * @return array
         **/
		function _getMemberInputTag($memberInfo, $isAdmin = false){
            $oMemberModel = &getModel('member');
            $extend_form_list = $oMemberModel->getCombineJoinForm($memberInfo);
			
			if ($memberInfo)
				$memberInfo = get_object_vars($memberInfo);
			$member_config = $oMemberModel->getMemberConfig();
			$formTags = array();
			global $lang;

			foreach($member_config->signupForm as $no=>$formInfo){
				if (!$formInfo->isUse)continue;
				if ($formInfo->name == $member_config->identifier || $formInfo->name == 'password') continue;
				unset($formTag);
				$inputTag = '';
                $formTag = new stdClass();
				$formTag->title = ($formInfo->isDefaultForm) ? $lang->{$formInfo->name} : $formInfo->title;
				if($isAdmin)
				{
					if($formInfo->mustRequired) $formTag->title = $formTag->title.' <em style="color:red">*</em>';
				}
				else
				{
					if ($formInfo->required && $formInfo->name != 'password') $formTag->title = $formTag->title.' <em style="color:red">*</em>';
				}
				$formTag->name = $formInfo->name;

				if($formInfo->isDefaultForm){
					if($formInfo->imageType){
						$formTag->type = 'image';
						if($formInfo->name == 'profile_image'){
							$target = $memberInfo['profile_image'];
							$functionName = 'doDeleteProfileImage';
						}elseif($formInfo->name == 'image_name'){
							$target = $memberInfo['image_name'];
							$functionName = 'doDeleteImageName';
						}elseif($formInfo->name == 'image_mark'){
							$target = $memberInfo['image_mark'];
							$functionName = 'doDeleteImageMark';
						}
						if($target->src){
							$inputTag = sprintf('<p class="a"><input type="hidden" name="__%s_exist" value="true" /><span id="%s"><img src="%s" alt="%s" /> <button type="button" class="text" onclick="%s(%d);return false;">%s</button></span></p>'
												,$formInfo->name
												,$formInfo->name.'tag'
												,$target->src
												,$formInfo->title
												,$functionName
												,$memberInfo['member_srl']
												,$lang->cmd_delete);
						}else{
							$inputTag = sprintf('<input type="hidden" name="__%s_exist" value="false" />', $formInfo->name);
						}
						$inputTag .= sprintf('<p class="a"><input type="file" name="%s" id="%s" value="" /></p><p><span class="desc">%s : %dpx, %s : %dpx</span></p>'
											 ,$formInfo->name
											 ,$formInfo->name
											 ,$lang->{$formInfo->name.'_max_width'}
											 ,$member_config->{$formInfo->name.'_max_width'}
											 ,$lang->{$formInfo->name.'_max_height'}
											 ,$member_config->{$formInfo->name.'_max_height'});
					}//end imageType
					elseif($formInfo->name == 'birthday'){
						$formTag->type = 'date';
						$inputTag = sprintf('<input type="hidden" name="birthday" id="date_birthday" value="%s" /><input type="text" class="inputDate" id="birthday" value="%s" /> <input type="button" value="%s" class="btn dateRemover" />'
								,isset($memberInfo['birthday']) ? $memberInfo['birthday'] : null
								,isset($memberInfo['birthday']) ? zdate($memberInfo['birthday'], 'Y-m-d', false) : false
								,$lang->cmd_delete);
					}elseif($formInfo->name == 'find_account_question'){
						$formTag->type = 'select';
						$inputTag = '<select name="find_account_question" id="find_account_question" style="width:290px; display:block;">%s</select>';
						$optionTag = array();
						foreach($lang->find_account_question_items as $key=>$val){
							if(isset($memberInfo['find_account_question']) && $key == $memberInfo['find_account_question']) {
                                $selected = 'selected="selected"';
                            }
							else {
                                $selected = '';
                            }
							$optionTag[] = sprintf('<option value="%s" %s >%s</option>'
													,$key
													,$selected
													,$val);
						}
						$inputTag = sprintf($inputTag, implode('', $optionTag));
						$inputTag .= '<input type="text" name="find_account_answer" id="find_account_answer" title="'.Context::getLang('find_account_answer').'" value="'.(isset($memberInfo['find_account_answer']) ? $memberInfo['find_account_answer'] : '').'" class="inputText long tall" />';
					}else{
						$formTag->type = 'text';
						$inputTag = sprintf('<input type="text" name="%s" id="%s" value="%s" class="inputText long tall" />'
									,$formInfo->name
									,$formInfo->name
									,isset($memberInfo[$formInfo->name]) ? $memberInfo[$formInfo->name] : null);
					}
				}//end isDefaultForm
				else{
					$extendForm = $extend_form_list[$formInfo->member_join_form_srl];
					$replace = array('column_name' => $extendForm->column_name,
									 'value'		=> $extendForm->value);
					$extentionReplace = array();

					$formTag->type = $extendForm->column_type;
					if($extendForm->column_type == 'text' || $extendForm->column_type == 'homepage' || $extendForm->column_type == 'email_address'){
						$template = '<input type="text" name="%column_name%" id="%column_name%" value="%value%" />';
					}elseif($extendForm->column_type == 'tel'){
						$extentionReplace = array('tel_0' => $extendForm->value[0],
												  'tel_1' => $extendForm->value[1],
												  'tel_2' => $extendForm->value[2]);
						$template = '<input type="text" name="%column_name%[]" value="%tel_0%" size="4" maxlength="4" style="width:30px" />-<input type="text" name="%column_name%[]" value="%tel_1%" size="4" maxlength="4" style="width:30px" />-<input type="text" name="%column_name%[]" value="%tel_2%" size="4" maxlength="4" style="width:30px" />';
					}elseif($extendForm->column_type == 'textarea'){
						$template = '<textarea name="%column_name%" rows="8" cols="42">%value%</textarea>';
					}elseif($extendForm->column_type == 'checkbox'){
						$template = '';
						if($extendForm->default_value){
							$__i = 0;
							foreach($extendForm->default_value as $v){
								$checked = '';
								if(is_array($extendForm->value) && in_array($v, $extendForm->value))$checked = 'checked="checked"';
								$template .= '<input type="checkbox" id="%column_name%'.$__i.'" name="%column_name%[]" value="'.htmlspecialchars($v).'" '.$checked.' /><label for="%column_name%'.$__i.'">'.$v.'</label>';
								$__i++;
							}
						}
					}elseif($extendForm->column_type == 'radio'){
						$template = '';
						if($extendForm->default_value){
							$template = '<ul class="radio">%s</ul>';
							$optionTag = array();
							foreach($extendForm->default_value as $v){
								if($extendForm->value == $v)$checked = 'checked="checked"';
								else $checked = '';
								$optionTag[] = '<li><input type="radio" name="%column_name%" value="'.$v.'" '.$checked.' />'.$v.'</li>';
							}
							$template = sprintf($template, implode('', $optionTag));
						}
					}elseif($extendForm->column_type == 'select'){
						$template = '<select name="'.$formInfo->name.'" id="'.$formInfo->name.'">%s</select>';
						$optionTag = array();
						if($extendForm->default_value){
							foreach($extendForm->default_value as $v){
								if($v == $extendForm->value) $selected = 'selected="selected"';
								else $selected = '';
								$optionTag[] = sprintf('<option value="%s" %s >%s</option>'
														,$v
														,$selected
														,$v);
							}
						}
						$template = sprintf($template, implode('', $optionTag));
					}elseif($extendForm->column_type == 'kr_zip'){
						Context::loadFile(array('./modules/member/tpl/js/krzip_search.js', 'body'), true);
						$extentionReplace = array(
										 'msg_kr_address'       => $lang->msg_kr_address,
										 'msg_kr_address_etc'       => $lang->msg_kr_address_etc,
										 'cmd_search'	=> $lang->cmd_search,
										 'cmd_search_again'	=> $lang->cmd_search_again,
										 'addr_0'	=> $extendForm->value[0],
										 'addr_1'	=> $extendForm->value[1],);
						$replace = array_merge($extentionReplace, $replace);
						$template = <<<EOD
						<div class="krZip">
							<div class="a" id="zone_address_search_%column_name%" >
								<label for="krzip_address1_%column_name%">%msg_kr_address%</label><br />
								<input type="text" id="krzip_address1_%column_name%" value="%addr_0%" />
								<button type="button">%cmd_search%</button>
							</div>
							<div class="a" id="zone_address_list_%column_name%" style="display:none">
								<select name="%column_name%[]" id="address_list_%column_name%"><option value="%addr_0%">%addr_0%</select>
								<button type="button">%cmd_search_again%</button>
							</div>
							<div class="a address2">
								<label for="krzip_address2_%column_name%">%msg_kr_address_etc%</label><br />
								<input type="text" name="%column_name%[]" id="krzip_address2_%column_name%" value="%addr_1%" />
							</div>
						</div>
						<script type="text/javascript">jQuery(function($){ $.krzip('%column_name%') });</script>
EOD;
					}elseif($extendForm->column_type == 'jp_zip'){
						$template = '<input type="text" name="%column_name%" id="%column_name%" value="%value%" />';
					}elseif($extendForm->column_type == 'date'){
						$extentionReplace = array('date' => zdate($extendForm->value, 'Y-m-d'),
												  'cmd_delete' => $lang->cmd_delete);
						$template = '<input type="hidden" name="%column_name%" id="date_%column_name%" value="%value%" /><input type="text" class="inputDate" value="%date%" readonly="readonly" /> <input type="button" value="%cmd_delete%" class="btn dateRemover" />';
					}

					$replace = array_merge($extentionReplace, $replace);
					$inputTag = preg_replace('@%(\w+)%@e', '$replace[$1]', $template);

					if($extendForm->description)
						$inputTag .= '<p style="color:#999;">'.htmlspecialchars($extendForm->description).'</p>';
				}
				$formTag->inputTag = $inputTag;
				$formTags[] = $formTag;
			}
			return $formTags;
		}

        /**
         * display member delete form
		 *
		 * @return void
         **/
        function dispMemberAdminDeleteForm() {
            if(!Context::get('member_srl')) {
                return $this->dispMemberAdminList();
            }
            $this->setTemplateFile('delete_form');
        }

        /**
         * display group list
		 *
		 * @return void
         **/
        function dispMemberAdminGroupList() {
            $oModuleModel = &getModel('module');

            $config = $oModuleModel->getModuleConfig('member');
            Context::set('config', $config);

            $group_srl = Context::get('group_srl');
			
            if($group_srl && $this->group_list[$group_srl]) {
                Context::set('selected_group', $this->group_list[$group_srl]);
				$this->setTemplateFile('group_update_form');
            } else {
                $this->setTemplateFile('group_list');
            }			
			$output = $oModuleModel->getModuleFileBoxList();			
			Context::set('fileBoxList', $output->data);        
		}

        /**
         * Display an admin page for memebr join forms
		 *
		 * @return void
		 **/
        function dispMemberAdminInsertJoinForm() {
            // Get the value of join_form            
			$member_join_form_srl = Context::get('member_join_form_srl');
            if($member_join_form_srl) {
                $oMemberModel = &getModel('member');
                $join_form = $oMemberModel->getJoinForm($member_join_form_srl);

                if(!$join_form) Context::set('member_join_form_srl','',true);
                else {
					Context::set('join_form', $join_form);
					$security = new Security();
					$security->encodeHTML('join_form..');
				}
				
            }
            $this->setTemplateFile('insert_join_form');
        }

        //SNS
        function dispMemberAdminSetupSns(){
            $sns_name = Context::get('sns_name');
            $oMemberModel = &getModel('member');
            $sns = $oMemberModel->getSns($sns_name);
            //$formTags=  $this->snsFormTags($sns);
            
            Context::set('sns', $sns);
			
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('sns_config');
        }
        
    }
?>
