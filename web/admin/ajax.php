<?php
require_once("../include/db_info.inc.php");
if(!(isset($_SESSION[$OJ_NAME.'_'.'administrator'])||isset($_SESSION[$OJ_NAME.'_'.'contest_creator']))){
  echo "<a href='../loginpage.php'>Please Login First!</a>";
  exit(1);
}
if($_SERVER['REQUEST_METHOD']=="POST"){
	$m=$_POST["m"];	
	if($m=="problem_add_source"){
		$pid=intval($_POST['pid']);
		$new_source=($_POST['ns']);	
		$sql= "update problem set source=concat(source,' ',?) where problem_id=?";		
		echo pdo_query($sql,$new_source,$pid);
	}
	if($m=="problem_update_time"){
		$pid=intval($_POST['pid']);
		$time=intval($_POST['t']);	
		$sql= "update problem set time_limit=? where problem_id=?";		
		echo pdo_query($sql,$time,$pid);
	}
	if($m=="problem_get_title"){
		$pid=intval($_POST['pid']); 
		$sql= "select title from problem where problem_id=?";		
		echo pdo_query($sql,$pid)[0][0];
	}
        if($m=="user_update_nick"){
                $user_id=$_POST['user_id'];
                $nick=$_POST['nick'];
                $sql= "update users set nick=? where user_id=?";
                echo pdo_query($sql,$nick,$user_id);
		$sql= "update solution set nick=? where user_id=?";
                pdo_query($sql,$nick,$user_id);
        }
	if($m=="user_update_school"){
                $user_id=$_POST['user_id'];
                $school=$_POST['school'];
                $sql= "update users set school=? where user_id=?";
                echo pdo_query($sql,$school,$user_id);
        }
}
