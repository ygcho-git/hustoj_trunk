<?php
require_once(dirname(__FILE__)."/pdo.php");

//if(date('H')<5||date('H')>21||isset($_GET['dark'])) $OJ_CSS="dark.css";
if (isset($_SESSION[$OJ_NAME . '_' . 'OJ_LANG'])) {
	$OJ_LANG=$_SESSION[$OJ_NAME . '_' . 'OJ_LANG'];
} else if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], array("cn", "ug", "en", 'fa', 'ko', 'th'))) {
	$OJ_LANG=$_COOKIE['lang'];
} else if (isset($_GET['lang']) && in_array($_GET['lang'], array("cn", "ug", "en", 'fa', 'ko', 'th'))) {
	$OJ_LANG=$_GET['lang'];
} else if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && strstr($_SERVER['HTTP_ACCEPT_LANGUAGE'], "zh-CN")) {
	$OJ_LANG="cn";
}
require(dirname(__FILE__)."/../lang/$OJ_LANG.php");

$domain=basename($_SERVER["HTTP_HOST"]);

if($OJ_SaaS_ENABLE){
	$DOMAIN="my.hustoj.com";   //   如启用，需要替换为SaaS服务的主域名。
	$OJ_SaaS_CONF=realpath(dirname(__FILE__)."/..")."/SaaS/".basename($_SERVER["HTTP_HOST"]).".php";
	if(file_exists($OJ_SaaS_CONF)){
		require_once($OJ_SaaS_CONF);
	}else{
	//	echo $OJ_SaaS_CONF;
	}
	if($domain==$DOMAIN)  $MSG_REG_INFO.="/初始化MyOJ";
}else{
	$DOMAIN=$domain;
}

if(strpos($_SERVER["HTTP_USER_AGENT"],"MSIE")){  // 360 or IE use bs3 instead
    $OJ_TEMPLATE="bs3";
}
// $OJ_BG="/image/bg".date('H').".jpg";  //每个整点更换壁纸，需要准备bg[0~23].jpg在image目录

if(isset($_SESSION[$OJ_NAME.'_user_id'])&&isset($OJ_LIMIT_TO_1_IP)&& $OJ_LIMIT_TO_1_IP){
        $ip = ($_SERVER['REMOTE_ADDR']);
        if( isset($_SERVER['HTTP_X_FORWARDED_FOR'] )&&!empty( trim( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ){
            $REMOTE_ADDR = $_SERVER['HTTP_X_FORWARDED_FOR'];
            $tmp_ip=explode(',',$REMOTE_ADDR);
            $ip =(htmlentities($tmp_ip[0],ENT_QUOTES,"UTF-8"));
        } else if(isset($_SERVER['HTTP_X_REAL_IP'])&& !empty( trim( $_SERVER['HTTP_X_REAL_IP'] ) ) ){
            $REMOTE_ADDR = $_SERVER['HTTP_X_REAL_IP'];
            $tmp_ip=explode(',',$REMOTE_ADDR);
            $ip =(htmlentities($tmp_ip[0],ENT_QUOTES,"UTF-8"));
        }
        $sql="select ip from loginlog where user_id=? order by time desc";
        $rows=pdo_query($sql,$_SESSION[$OJ_NAME.'_user_id'] );
        $lastip=$rows[0][0];
        if($ip!=$lastip){
                unset($_SESSION[$OJ_NAME.'_'.'user_id']);
                setcookie($OJ_NAME."_user","");
                setcookie($OJ_NAME."_check","");
                session_destroy();
                $view_errors="Logged in another ip address:$lastip, auto logout!";
                require("template/$OJ_TEMPLATE/error.php");
                exit(0);
        }

}



$OJ_LOG_FILE="/var/log/hustoj/{$OJ_NAME}.log";
require_once(dirname(__FILE__) . "/logger.php");

$logger=new Logger(isset($_SESSION[$OJ_NAME . '_' . 'user_id'])?$_SESSION[$OJ_NAME . '_' . 'user_id']:"guest", 
					$OJ_LOG_FILE, 
					$OJ_LOG_DATETIME_FORMAT, 
					$OJ_LOG_ENABLED, 
					$OJ_LOG_PID_ENABLED,
					$OJ_LOG_USER_ENABLED,
					$OJ_LOG_URL_ENABLED,
					$OJ_LOG_URL_HOST_ENABLED,
					$OJ_LOG_URL_PARAM_ENABLED,
					$OJ_LOG_TRACE_ENABLED);
$logger->info();
// these lines can help you make a SaaS platform of HUSTOJ with the help of JudgeHub
// 傻瓜级保姆配置系统
switch($OJ_FRIENDLY_LEVEL) {
	case 9:
	   $OJ_GUEST=true;
	case 8:
	   $OJ_DOWNLOAD=true;
	case 7:
	   $OJ_BBS="discuss3";
	   $OJ_FREE_PRACTICE=true;
	case 6:
	   $OJ_LONG_LOGIN=true; 
	case 5:
	   $OJ_TEST_RUN=true; 
	case 4:
	   $OJ_MAIL=true;
	   $OJ_AUTO_SHARE=true;
	case 3:
	   $OJ_SHOW_DIFF=true; 
	   $OJ_VCODE=false;
	case 2:
	   $OJ_LANG="cn";
	case 1:
	   date_default_timezone_set("PRC");
	   pdo_query("SET time_zone ='+8:00'");
}

// if using EXAM or ON site auto turn off free practice
if(isset($OJ_ON_SITE_CONTEST_ID) || isset($OJ_EXAM_CONTEST_ID)) $OJ_FREE_PRACTICE=false;

