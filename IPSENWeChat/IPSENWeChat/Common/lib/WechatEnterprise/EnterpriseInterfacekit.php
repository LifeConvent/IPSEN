<?php

require_once __ROOT__."/Application/Common/lib/CURL/CURL.php";
require_once __ROOT__."/Application/Common/lib/LogMaster/LogMaster.php";
// 导入CURL类库
// import('Common.lib.CURL.CURL', APP_PATH, '.php');

// 导入LogMaster类库
// import('Common.lib.LogMaster.LogMaster', APP_PATH, '.php');

define("encodingAesKey","oRKL8RLbnVADmUfWhQ7mIOYD7MrR5TmorXZjXD8gYGx");
define("token","WechatIncubator");
define("corpId","wx72d997ae150ccf6f");
define("corpsecret","t41APDYcYAM1qSrQJmrPUk1GJAMpIPA14yF2UHn0J2_Dl2PnK4AdLACkpN1j9JnE");

define("logFile","wechat_log");

class EnterpriseInterfacekit
{
	private $logMaster;
	public function EnterpriseInterfacekit(){
		$this->logMaster = new LogMaster();
		$this->logMaster->tellMe(logFile, "Initialized EnterpriseInterfacekit\n");
	}
	
	// 获取 access_token
	public function getAccessToken(){
		
		// 此处应加入过期判断
		// ToDo:
		
		$access_token_url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken";
		$param['corpid'] = corpId;
		$param['corpsecret'] = corpsecret;
		
		$this->logMaster->tellMe(logFile, "corpId\n".corpId);
		$this->logMaster->tellMe(logFile, "corpsecret\n".corpsecret);
		
		$sender = new CURL();
		$result = $sender->sendHTTP($access_token_url, $param, 'GET', array("Content-type: text/html; charset=utf-8"));
		// $result = '{"access_token":"r9OV1eRMkBRLM2PBanGhI77wEoL_prh_Mei-x-S5IHzLhD22y5P57W0kDV-HNsKX","expires_in":7200}';
		$arr = json_decode($result);
		
		session('access_token', $arr->{'access_token'});
		session('expires_in', $arr->{'expires_in'});
		
		$this->logMaster->tellMe(logFile, "access_token\n".session('access_token'));
		$this->logMaster->tellMe(logFile, "expires_in\n".session('expires_in'));
	}
	
	/*
	public function getUserID(){
		
		$code = I('get.code', '');
		$state = I('get.state');
		
		if(!empty($code)){
			session('code', $code);
			session('state', $state);
			
			$this->logMaster->tellMe(logFile, "code\n".$code);
			$this->logMaster->tellMe(logFile, "state\n".$state);
			
			$this->getAccessToken();
			
			if(session('?access_token') && session('?code'))
			{
				$userid_url = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo";
				$param['access_token'] = session('access_token');
				$param['code'] = session('code');
				
				$sender = new CURL();
				$result = $sender->sendHTTP($userid_url, $param, 'GET', array("Content-type: text/html; charset=utf-8"));
				$arr = json_decode($result);
			
				session('UserId', $arr->{'UserId'});
				session('DeviceId', $arr->{'DeviceId'});
				
				$this->logMaster->tellMe(logFile, "UserId\n".session('UserId'));
				$this->logMaster->tellMe(logFile, "DeviceId\n".session('DeviceId'));
				
				$this->getOpenID();
				
			}
		}
		
	}
	*/
	
	public function getOpenID(){
		
		$access_token = session('access_token'); 
		// $access_token = 'r9OV1eRMkBRLM2PBanGhI77wEoL_prh_Mei-x-S5IHzLhD22y5P57W0kDV-HNsKX';
		
		$convert_to_openid_url = "https://qyapi.weixin.qq.com/cgi-bin/user/convert_to_openid?access_token={$access_token}"; 
		
		$param['userid'] = session('?UserId') ? session('UserId') : I('get.userid');
		// $param['userid'] = 'BYY'; 
		
		$sender = new CURL();
		$result = $sender->sendHTTP($convert_to_openid_url, json_encode($param), 'POST', array("Content-type" => "application/x-www-form-urlencoded", "charset" => "utf-8"), true);
		$arr = json_decode($result);
		
		session('openid', $arr->{'openid'});
		
		$this->logMaster->tellMe(logFile, "errcode\n".$arr->{'errcode'});
		$this->logMaster->tellMe(logFile, "errmsg\n".$arr->{'errmsg'});
		$this->logMaster->tellMe(logFile, "openid\n".session('openid'));
		
	}
	
	public function getAuthorization($destnURL){
		// 拼接跳转地址
		$corpId = corpId;
		$redirect_uri = urlencode($destnURL);
		$state = "getCode";
		$code_url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$corpId}&redirect_uri={$redirect_uri}&response_type=code&scope=SCOPE&state={$state}#wechat_redirect";
		
		$this->logMaster->tellMe(logFile, $code_url);
		
		header('Location:'.$code_url);
		
	}
	
}

