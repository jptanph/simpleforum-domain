<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/environment.php');
require_once('/model/Model_class.php');

class Index
{
	private $_oModel;
	private $_iLimit;
	private $_iLimitReply;
	
	public function __construct()
	{
		$this->_oModel = new Model_class();
		$this->_iLimit = 15;
		$this->_iLimitReply = 3;
		$this->_requestHandler();
	}

	private function _requestHandler()
	{
		switch(getVar('request'))
		{
			case'post':
				$this->_execGetPost();
			break;
			
			case'savepost':
				$this->_execSavePost();
			break;
			
			case'savereply':
				$this->_execSaveReply();
			break;

			case'saveuserreply':
				$this->_execSaveUserReply();
			break;			
			
			case'login':
				$this->_execLogin();
			break;
			
			case'registrationform':
				$this->_execRegistrationInfo();
			break;	
			
			case'saveregistration':
				$this->_execSaveRegistration();
			break;
			
			case'deletereply':
				$this->_execDeleteReply();
			break;

			case'deleteuserreply':
				$this->_execDeleteUserReply();
			break;
			
			case'viewuserpost':
				$this->_execViewUserPost();
			break;
			
			case'updateuserpost':
				$this->_execUpdateUserPost();
			break;
			case'view':
				$this->_execViewPost();
			break;
			
			case'saveuserpost':
				$this->_execSaveUserPost();
			break;
			
			case'recoveraccount':
				$this->_execRecoverAccount();
			break;
		}
	}
	
	private function _execGetPost()
	{	
		$aData = array();
		$iOffset = (getVar('page') - 1) * $this->_iLimit ;
		$sLimit = " LIMIT $iOffset, $this->_iLimit";

		$sWhere = (getVar('keyword')=='') ? ' WHERE parent_post_idx = 0 ' : " WHERE CONCAT(subject, ' ' ,full_name) LIKE '%" . trim(getVar('keyword')) . "%' AND parent_post_idx = 0";
		$aResult = $this->_oModel->get_post($sWhere,$sLimit);
		$aCount = $this->_oModel->get_count($sWhere);
		foreach($aResult as $rows)
		{
			$aReply = $this->_execLastReply($rows['idx']);
			$aReplyCount = $this->_oModel->get_reply_count($rows['idx']);
			$iReplyCount = count($aReplyCount);
			$aData['list'][]= array(	
				'idx' => $rows['idx'],
				'full_name' => ($rows['user_idx']==0) ? $rows['full_name'] : $this->_execUserInfo($rows['user_idx']),
				'password' => $rows['password'],
				'subject' => $rows['subject'],
				'message' => $rows['message'],
				'views' => $rows['views'],
				'total_reply' =>(int)( count($this->_oModel->get_replies($rows['idx'])) - 1 ),
				'date_posted' => date('Y-m-d H:i:s',$rows['date_posted']),
				'last_reply_name' => ( $aReply['user_idx']=='0') ? $aReply['last_reply_name'] : $this->_execUserInfo($aReply['user_idx']),
				'last_reply_date' => $aReply['last_reply_date'],
				'last_page' => ceil(($iReplyCount/$this->_iLimitReply)),
				'pagination' =>($iReplyCount >= 10)  ? '[' . pageLinksDrawer(1,$iReplyCount,$this->_iLimitReply,'','') . ']': '',
				'more' => ($iReplyCount >= 10) ? 'inew' : ''
			);
		}
		$aData['total_record'] = count($aCount);
		$aData['limit'] = $this->_iLimit;
		echo $this->_execJsonp($aData);
	}
	
	private function _execLastReply($iIdx)
	{
		$aData = array();
		$sDatePost = '';
		$aResult = $this->_oModel->last_reply($iIdx);
		
		if($aResult[0]['date_range']=='1')
		{
			$sDatePost = 'Yesterday ' . $aResult[0]['date_time'];		
		}
		elseif($aResult[0]['date_range']=='0')
		{
			$sDatePost = 'Today ' . $aResult[0]['date_time'];		
		}
		else
		{
			$sDatePost = $aResult[0]['date_posted'];
		}
		
		$aData['last_reply_name'] = $aResult[0]['full_name'];
		$aData['user_idx'] = $aResult[0]['user_idx'];
		$aData['last_reply_date'] = $sDatePost;
		
		return $aData;
	}
	
	private function _execSavePost()
	{	
		$bShowSmiley = getVar('smiley');
		$this->_oModel->insert_post($bShowSmiley);
		$aData = array('status' => 'ok');
		echo $this->_execJsonp($aData);	
	}
	
	private function _execViewPost()
	{	
		$sImagePath = APP_PATH . '/simpleforum/img';
		$iOffset = (getVar('page') - 1) * $this->_iLimitReply;
		$sLimit = " LIMIT $iOffset, $this->_iLimitReply";
		$this->_oModel->update_views(getVar('idx'));
		$aResult = $this->_oModel->get_views($sLimit);
		$aCount = $this->_oModel->get_reply_count(getVar('idx'));
		$aPostInfo = $this->_oModel->get_post_info();
		$iReplyCount = count($aCount);
		$iIncRow = 1;
		$sHasOption = '';
		foreach($aResult as $rows)
		{
			$aReply = $this->_execLastReply($rows['idx']);
			if($rows['user_idx'] == getVar('login_idx'))
			{	
				$sHasOption = "<ul><li class='postdelete'><a href='#none' onclick='Simpleforum.execUserDeleteReply(" . $rows['idx'] . ")'>Delete</a> | </li><li class='postedit'><a href='#none' onclick='Simpleforum.execUserEditPost(" . $rows['idx'] . ")'>Edit</a></li></ul>";
			}
			elseif($rows['user_idx']==0)
			{
				$sHasOption = "<ul><li class='postdelete'><a href='#none' onclick='Simpleforum.execDeletePost(" . $rows['idx'] . ")'>Delete</a> | </li><li class='postedit'><a href='#none' onclick='Simpleforum.execEditPost(" . $rows['idx'] . ")'>Edit</a></li></ul>";
			}
			else
			{
				$sHasOption = "";			
			}
			$aData['list'][]= array(
				'row' => ((getVar('page') == 1 ) ? $iIncRow : $iOffset+$iIncRow),
				'user_type' => (($rows['user_idx']==0) ? 'Guest' : 'User'),
				'idx' => $rows['idx'],
				'full_name' => ($rows['user_idx']==0) ? $rows['full_name'] : $this->_execUserInfo($rows['user_idx']),
				'password' => $rows['password'],
				'subject' => $rows['subject'],
				'message' =>($rows['show_smiley']=='yes') ? str_replace(':)',"<img src='$sImagePath/smile.png'>",$rows['message']) : $rows['message'],
				'views' => $rows['views'],
				'date_posted' => date('Y-m-d H:i:s',$rows['date_posted']),
				'last_reply_date' => $aReply['last_reply_date'],
				'has_option' => $sHasOption				
			);
			$iIncRow++;
		}
		$aData['post_subject'] = $aPostInfo[0]['subject'];
		$aData['total_record'] = count($aCount);
		$aData['limit'] = $this->_iLimitReply;
		echo $this->_execJsonp($aData);
	}
	
	private function _execSaveReply()
	{
		$this->_oModel->save_reply();
		$aCount = $this->_oModel->get_reply_count(getVar('parent_idx'));
		$iReplyCount = count($aCount);		
		$aData = array('status' => 'ok','last_page' => ceil($iReplyCount/$this->_iLimitReply));
		echo $this->_execJsonp($aData);	
	}
	
	private function _execDeleteReply()
	{
		$this->_oModel->delete_reply();
	}
	
	private function _execLogin()
	{	
		$aData = array();
		$aResult = $this->_oModel->check_login();
		if($aResult){
			$aData['idx'] = $aResult[0]['idx'];
			$aData['username'] = $aResult[0]['username'];
		}else{
			$aData['idx'] = '';
			$aData['username'] = '';		
		}
		echo $this->_execJsonp($aData);
	}
	
	public function _execRegistrationInfo()
	{
		$aResult = $this->_oModel->get_questions();
		echo $this->_execJsonp($aResult);
	}
	
	public function _execSaveRegistration()
	{
		$this->_oModel->insert_registration();
	}

	private function _execSaveUserReply()
	{
		$this->_oModel->save_user_reply();
		$aCount = $this->_oModel->get_reply_count(getVar('parent_idx'));
		$iReplyCount = count($aCount);		
		$aData = array('status' => 'ok','last_page' => ceil($iReplyCount/$this->_iLimitReply));
		echo $this->_execJsonp($aData);	
	}
	
	private function _execDeleteUserReply()
	{
		$this->_oModel->delete_user_reply();
		$aCount = $this->_oModel->get_reply_count(getVar('parent_idx'));
		$iReplyCount = count($aCount);		
		$aData = array('status' => 'ok','last_page' => ceil($iReplyCount/$this->_iLimitReply),'total_count'=>$iReplyCount);
		echo $this->_execJsonp($aData);			
	}
	
	private function _execUserInfo($iIdx)
	{
		$aData = array();
		$aResult = $this->_oModel->get_user_info($iIdx);
		
		return ($aResult) ? $aResult[0]['full_name'] : '';
	}
	
	private function _execRecoverAccount()
	{
		$aResult = array();
		if(getVar('type')=='userid')
		{
			$aData = $this->_oModel->get_account_info();																	
			foreach($aData as $rows)
			{
				$aResult['list'][] = array(
					'username' => limitChar($rows['username'],3)
				);
			}
		}
		elseif(getVar('type')=='question')
		{
			//$aData = $this->_oModel->get_question_info();
			$aData = $this->_oModel->get_username_info();
			if($aData)
			{
				$aResult['list'] = 'ok';
				$aResult['question_list'] = $this->_oModel->get_questions();
			}
			else
			{
				$aResult['list'] = 'error';			
			}
		}
		$aResult['type'] = getVar('type'); 
		echo $this->_execJsonp($aResult);			
	}
	
	public function _execViewUserPost()
	{
		$aResult = $this->_oModel->view_user_post();
		$aData['message'] = $aResult[0]['message'];
		echo $this->_execJsonp($aData);	
	}
	
	public function _execSaveUserPost()
	{
		$this->_oModel->insert_user_post();
		$aData = array('status' => 'ok');
		echo $this->_execJsonp($aData);	
	}
	
	public function _execUpdateUserPost()
	{
		$this->_oModel->update_user_post();
		$aData = array('status' => 'ok');
		echo $this->_execJsonp($aData);	
	}
	
	private function _execJsonp($aData)
	{
		return getVar('callback') . '(' . json_encode( $aData) . ')';
 	}
}
$oIndex= new Index();