<?php
    /**
     * @class  pollController
     * @author Arnia (developers@xpressengine.com)
     * @brief Controller class for poll module
     **/

    class pollController extends poll {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief after a qeustion is created in the popup window, register the question during the save time
         **/
        function procInsert() {
            $stop_date = Context::get('stop_date');
            if($stop_date < date("Ymd")) $stop_date = date("YmdHis", time()+60*60*24*365);

            $vars = Context::getRequestVars();
            foreach($vars as $key => $val) {
                if(strpos($key,'tidx')) continue;
                if(!preg_match("/^(title|checkcount|item)_/i", $key)) continue;
                if(!trim($val)) continue;

                $tmp_arr = explode('_',$key);

                $poll_index = $tmp_arr[1];

                if(Context::get('is_logged')) {
                    $logged_info = Context::get('logged_info');
                    // Remove the tag if the it is not the top administrator in the session
                    if($logged_info->is_admin != 'Y') $val = htmlspecialchars($val);
                }

                if($tmp_arr[0]=='title') $tmp_args[$poll_index]->title = $val;
                else if($tmp_arr[0]=='checkcount') $tmp_args[$poll_index]->checkcount = $val;
                else if($tmp_arr[0]=='item') $tmp_args[$poll_index]->item[] = $val;
            }

            foreach($tmp_args as $key => $val) {
                if(!$val->checkcount) $val->checkcount = 1;
                if($val->title && count($val->item)) $args->poll[] = $val;
            }

            if(!count($args->poll)) return new Object(-1, 'cmd_null_item');

            $args->stop_date = $stop_date;
            // Configure the variables
            $poll_srl = getNextSequence();

            $logged_info = Context::get('logged_info');
            $member_srl = $logged_info->member_srl?$logged_info->member_srl:0;

            $oDB = &DB::getInstance();
            $oDB->begin();
            // Register the poll
            unset($poll_args);
            $poll_args->poll_srl = $poll_srl;
            $poll_args->member_srl = $member_srl;
            $poll_args->list_order = $poll_srl*-1;
            $poll_args->stop_date = $args->stop_date;
            $poll_args->poll_count = 0;
            $output = executeQuery('poll.insertPoll', $poll_args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }
            // Individual poll registration
            foreach($args->poll as $key => $val) {
                unset($title_args);
                $title_args->poll_srl = $poll_srl;
                $title_args->poll_index_srl = getNextSequence();
                $title_args->title = $val->title;
                $title_args->checkcount = $val->checkcount;
                $title_args->poll_count = 0;
                $title_args->list_order = $title_args->poll_index_srl * -1;
                $title_args->member_srl = $member_srl;
                $title_args->upload_target_srl = $upload_target_srl;
                $output = executeQuery('poll.insertPollTitle', $title_args);
                if(!$output->toBool()) {
                    $oDB->rollback();
                    return $output;
                }
                // Add the individual survey items
                foreach($val->item as $k => $v) {
                    unset($item_args);
                    $item_args->poll_srl = $poll_srl;
                    $item_args->poll_index_srl = $title_args->poll_index_srl;
                    $item_args->title = $v;
                    $item_args->poll_count = 0;
                    $item_args->upload_target_srl = $upload_target_srl;
                    $output = executeQuery('poll.insertPollItem', $item_args);
                    if(!$output->toBool()) {
                        $oDB->rollback();
                        return $output;
                    }
                }
            }

            $oDB->commit();

            $this->add('poll_srl', $poll_srl);
            $this->setMessage('success_registed');
        }

        /**
         * @brief Accept the poll
         **/
        function procPoll() {
            $poll_srl = Context::get('poll_srl'); 
            $poll_srl_indexes = Context::get('poll_srl_indexes'); 
            $tmp_item_srls = explode(',',$poll_srl_indexes);
            for($i=0;$i<count($tmp_item_srls);$i++) {
                $srl = (int)trim($tmp_item_srls[$i]);
                if(!$srl) continue;
                $item_srls[] = $srl;
            }

            // If there is no response item, display an error
            if(!count($item_srls)) return new Object(-1, 'msg_check_poll_item');
            // Make sure is the poll has already been taken
            $oPollModel = &getModel('poll');
            if($oPollModel->isPolled($poll_srl)) return new Object(-1, 'msg_already_poll');

            $oDB = &DB::getInstance();
            $oDB->begin();

            $args->poll_srl = $poll_srl;
            // Update all poll responses related to the post
            $output = executeQuery('poll.updatePoll', $args);
            $output = executeQuery('poll.updatePollTitle', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }
            // Record each polls selected items
            $args->poll_item_srl = implode(',',$item_srls);
            $output = executeQuery('poll.updatePollItems', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }
            // Log the respondent's information
            $log_args->poll_srl = $poll_srl;

            $logged_info = Context::get('logged_info');
            $member_srl = $logged_info->member_srl?$logged_info->member_srl:0;

            $log_args->member_srl = $member_srl;
            $log_args->ipaddress = $_SERVER['REMOTE_ADDR'];
            $output = executeQuery('poll.insertPollLog', $log_args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            $oDB->commit();

            $skin = Context::get('skin'); 
            if(!$skin || !is_dir('./modules/poll/skins/'.$skin)) $skin = 'default';
            // Get tpl
            $tpl = $oPollModel->getPollHtml($poll_srl, '', $skin);

            $this->add('poll_srl', $poll_srl);
            $this->add('tpl',$tpl);
            $this->setMessage('success_poll');

			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispPollAdminConfig');
			$this->setRedirectUrl($returnUrl);
        }

        /**
         * @brief Preview the results
         **/
        function procPollViewResult() {
            $poll_srl = Context::get('poll_srl'); 

            $skin = Context::get('skin'); 
            if(!$skin || !is_dir('./modules/poll/skins/'.$skin)) $skin = 'default';

            $oPollModel = &getModel('poll');
            $tpl = $oPollModel->getPollResultHtml($poll_srl, $skin);

            $this->add('poll_srl', $poll_srl);
            $this->add('tpl',$tpl);
        }

        /**
         * @brief poll list
         **/
        function procPollGetList()
		{
			if(!Context::get('is_logged')) return new Object(-1,'msg_not_permitted');
			$pollSrls = Context::get('poll_srls');
			if($pollSrls) $pollSrlList = explode(',', $pollSrls);

			global $lang;
			if(count($pollSrlList) > 0) {
				$oPollAdminModel = &getAdminModel('poll');
				$args->pollIndexSrlList = $pollSrlList;
				$output = $oPollAdminModel->getPollListWithMember($args);
				$pollList = $output->data;

				if(is_array($pollList))
				{
					foreach($pollList AS $key=>$value)
					{
						if($value->checkcount == 1) $value->checkName = $lang->single_check;
						else $value->checkName = $lang->multi_check;
					}
				}
			}
			else
			{
				$pollList = array();
				$this->setMessage($lang->no_documents);
			}

			$this->add('poll_list', $pollList);
        }

        /**
         * @brief A poll synchronization trigger when a new post is registered
         **/
        function triggerInsertDocumentPoll(&$obj) {
            $this->syncPoll($obj->document_srl, $obj->content);
            return new Object();
        }

        /**
         * @brief A poll synchronization trigger when a new comment is registered
         **/
        function triggerInsertCommentPoll(&$obj) {
            $this->syncPoll($obj->comment_srl, $obj->content);
            return new Object();
        }

        /**
         * @brief A poll synchronization trigger when a post is updated
         **/
        function triggerUpdateDocumentPoll(&$obj) {
            $this->syncPoll($obj->document_srl, $obj->content);
            return new Object();
        }

        /**
         * @brief A poll synchronization trigger when a comment is updated
         **/
        function triggerUpdateCommentPoll(&$obj) {
            $this->syncPoll($obj->comment_srl, $obj->content);
            return new Object();
        }

        /**
         * @brief A poll deletion trigger when a post is removed
         **/
        function triggerDeleteDocumentPoll(&$obj) {
            $document_srl = $obj->document_srl;
            if(!$document_srl) return new Object();
            // Get the poll
            $args->upload_target_srl = $document_srl;
            $output = executeQuery('poll.getPollByTargetSrl', $args);
            if(!$output->data) return new Object();

            $poll_srl = $output->data->poll_srl;
            if(!$poll_srl) return new Object();

            $args->poll_srl = $poll_srl;

            $output = executeQuery('poll.deletePoll', $args);
            if(!$output->toBool()) return $output;

            $output = executeQuery('poll.deletePollItem', $args);
            if(!$output->toBool()) return $output;

            $output = executeQuery('poll.deletePollTitle', $args);
            if(!$output->toBool()) return $output;

            $output = executeQuery('poll.deletePollLog', $args);
            if(!$output->toBool()) return $output;

            return new Object();
        }

        /**
         * @brief A poll deletion trigger when a comment is removed
         **/
        function triggerDeleteCommentPoll(&$obj) {
            $comment_srl = $obj->comment_srl;
            if(!$comment_srl) return new Object();
            // Get the poll
            $args->upload_target_srl = $comment_srl;
            $output = executeQuery('poll.getPollByTargetSrl', $args);
            if(!$output->data) return new Object();

            $poll_srl = $output->data->poll_srl;
            if(!$poll_srl) return new Object();

            $args->poll_srl = $poll_srl;

            $output = executeQuery('poll.deletePoll', $args);
            if(!$output->toBool()) return $output;

            $output = executeQuery('poll.deletePollItem', $args);
            if(!$output->toBool()) return $output;

            $output = executeQuery('poll.deletePollTitle', $args);
            if(!$output->toBool()) return $output;

            $output = executeQuery('poll.deletePollLog', $args);
            if(!$output->toBool()) return $output;

            return new Object();
        }

        /**
         * @brief As post content's poll is obtained, synchronize the poll using the document number
         **/
        function syncPoll($upload_target_srl, $content) {
            $match_cnt = preg_match_all('!<img([^\>]*)poll_srl=(["\']?)([0-9]*)(["\']?)([^\>]*?)\>!is',$content, $matches);
            for($i=0;$i<$match_cnt;$i++) {
                $poll_srl = $matches[3][$i];

                $args = null;
                $args->poll_srl = $poll_srl;
                $output = executeQuery('poll.getPoll', $args);
                $poll = $output->data;

                if($poll->upload_target_srl) continue;

                $args->upload_target_srl = $upload_target_srl;
                $output = executeQuery('poll.updatePollTarget', $args);
                $output = executeQuery('poll.updatePollTitleTarget', $args);
                $output = executeQuery('poll.updatePollItemTarget', $args);
            }
        }
    }
?>
