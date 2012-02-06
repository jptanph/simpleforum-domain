<?php

class Model_class
{
	private $_SIMPLEFORUM_POST;
	private $_SIMPLEFORUM_QUESTIONS;
	private $_SIMPLEFORUM_REGISTRATION;
	private $_sPrefix;
	private $oInput;
	private $_oSql;
	
	public function __construct()
	{
		$this->_oSql = new Sql_helper();
		$this->_oInput = new Input_class();
		$this->_sPrefix = 'simpleforum_';
		$this->_FORUM_POST = $this->_sPrefix . 'post';		
		$this->_FORUM_QUESTIONS = $this->_sPrefix . 'questions';		
		$this->_FORUM_REGISTRATION = $this->_sPrefix . 'registration';		
	}
	
	public function get_post($sWhere,$sLimit)
	{
		$sSql = "SELECT * FROM $this->_FORUM_POST $sWhere ORDER BY date_posted DESC $sLimit";
		return $this->_oSql->execQuery($sSql,true);
	}
	
	public function get_count($sWhere)
	{
		$sSql = "SELECT * FROM $this->_FORUM_POST $sWhere";
		return $this->_oSql->execQuery($sSql,true);		
	}
	
	public function update_Views($iIdx)
	{
		$sSql = "UPDATE $this->_FORUM_POST SET views = views + 1 WHERE idx = $iIdx";
		return $this->_oSql->execQuery($sSql);		
	}
	
	public function insert_post($bShowSmiley)
	{
		$sSql = "INSERT INTO $this->_FORUM_POST
			(full_name,password,subject,message,show_smiley,date_posted)
			VALUES
			('" . getVar('name') . "',PASSWORD('" . getVar('password') . "'), '" . $this->_oInput->filter_data(getVar('subject')) . "', '" . $this->_oInput->filter_data(getVar('message')) . "','$bShowSmiley',UNIX_TIMESTAMP(NOW()))";
		return $this->_oSql->execQuery($sSql,false);	
	}
	
	public function get_views($sLimit)
	{
		$sSql = " SELECT * FROM $this->_FORUM_POST WHERE idx = " . getVar('idx') . " OR parent_post_idx = " . getVar('idx') . " ORDER BY date_posted ASC $sLimit";
		return $this->_oSql->execQuery($sSql,true);	
	}
	
	public function get_reply_count($iIdx)
	{
		$sSql = " SELECT * FROM $this->_FORUM_POST WHERE idx = $iIdx OR parent_post_idx = $iIdx";
		return $this->_oSql->execQuery($sSql,true);		
	}
	
	public function save_reply()
	{
		$sSql = "INSERT INTO $this->_FORUM_POST 
		 (parent_post_idx,full_name,password,message,date_posted)
		 VALUES
		 ('" . getVar('parent_idx'). "','" . getVar('name'). "',PASSWORD('" . getVar('password'). "'),'" . $this->_oInput->filter_data(getVar('message')) . "',UNIX_TIMESTAMP(NOW()))";
		return $this->_oSql->execQuery($sSql,false);	
	}
	
	public function last_reply($iIdx)
	{
		$sSql = " SELECT *,DATEDIFF(NOW(),FROM_UNIXTIME(date_posted)) AS date_range,
				DATE_FORMAT(FROM_UNIXTIME(date_posted),'%h:%i:%s') AS date_time,
				DATE_FORMAT(FROM_UNIXTIME(date_posted),'%Y-%m-%d %h:%i:%s') as date_posted,
				user_idx as user_idx
				
				FROM $this->_FORUM_POST WHERE idx = $iIdx OR parent_post_idx = $iIdx ORDER BY date_posted DESC LIMIT 1";
		return $this->_oSql->execQuery($sSql,true);	
	}
	
	public function get_replies($iIdx)
	{
		$sSql = " SELECT * FROM $this->_FORUM_POST WHERE idx = $iIdx OR parent_post_idx = $iIdx";
		return $this->_oSql->execQuery($sSql,true);	
	}
	
	public function get_post_info()
	{
		$sSql = " SELECT * FROM $this->_FORUM_POST WHERE idx = " . getVar('idx');
		return $this->_oSql->execQuery($sSql,true);		
	}
	
	public function delete_reply()
	{
		$sSql = " DELETE FROM $this->_FORUM_POST WHERE idx = " . getVar('idx') . " AND password = PASSWORD('" . getVar('password') . "')";
		return $this->_oSql->execQuery($sSql,false);	
	}
	
	public function check_login()
	{
		$sSql = " SELECT idx,username FROM $this->_FORUM_REGISTRATION WHERE username = '" . getVar('username') . "' AND password = PASSWORD('" . getVar('password') . "')";
		return $this->_oSql->execQuery($sSql,true);		
	}
	
	public function get_questions()
	{
		$sSql = " SELECT * FROM $this->_FORUM_QUESTIONS";
		return $this->_oSql->execQuery($sSql,true);			
	}
	
	public function insert_registration()
	{
		$sSql = "INSERT INTO $this->_FORUM_REGISTRATION
			(question_idx,full_name,username,password,email,answer,date_registered)
			VALUES
			(" . getVar('question_idx') . ",
			'" . getVar('name') . "',
			'" . getVar('username') . "',
			PASSWORD('" . getVar('password') . "'),
			'" . getVar('email') . "',
			'" . getVar('answer') . "',
			UNIX_TIMESTAMP(NOW())
		)";
		return $this->_oSql->execQuery($sSql,false);				
	}
	
	public function save_user_reply()
	{
		$sSql = "INSERT INTO $this->_FORUM_POST 
		 (parent_post_idx,user_idx,message,date_posted)
		 VALUES
		 (" . getVar('parent_idx'). "," . getVar('idx'). ",'" . $this->_oInput->filter_data(getVar('message')) . "',UNIX_TIMESTAMP(NOW()))";
		return $this->_oSql->execQuery($sSql,false);	
	}
	
	public function delete_user_reply()
	{
		$sSql = " DELETE FROM $this->_FORUM_POST WHERE idx = " . getVar('idx');
		return $this->_oSql->execQuery($sSql,false);		
	}
	
	public function get_user_info($iIdx)
	{
		$sSql = "SELECT * FROM $this->_FORUM_REGISTRATION WHERE idx = $iIdx";
		return $this->_oSql->execQuery($sSql,true);		
	}
}
