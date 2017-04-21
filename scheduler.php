<?php

	chdir(dirname(__FILE__));
	require_once(realpath('../../config/dmsDefaults.php'));
	require_once(KT_LIB_DIR . '/database/dbutil.inc');
	require_once(KT_DIR . '/plugins/PatoLeon.Alerts/PatoLeonAlertsNotification.php');
	
	global $default;

	$default->log->info("Alerts: started");		
	$sql = "SELECT * FROM PatoLeonAlerts WHERE Date <= '".date('Y-m-d')."' AND Sent = 0";

	$alertsForToday = DBUtil::getResultArray($sql);

	foreach($alertsForToday as $alertForToday){
		$Document = Document::get($alertForToday['DocumentID']);
		
		$users = getUsers(unserialize($alertForToday["Users"]));
	
		foreach($users as $user){
			$oUser = User::get($user);			
			$newPatoLeonNotification = PatoLeonAlertsNotification::newNotificationForDocument($Document, $oUser, $alertForToday['Message']);
		}
		
		$sql = "UPDATE PatoLeonAlerts SET Sent = 1 WHERE AlertID = ".$alertForToday["AlertID"];
		$res = DBUtil::runQuery($sql);
		if(PEAR::isError($res)){
			$default->log->error(sprintf('Alert id %s could not be update to the state sent', $alertForToday["AlertID"]));
		}
	}
	
	function getUsers($allUsers){
		$users = array();
		
		foreach($allUsers["user"] as $user){
			$users[] = $user;
		}
		
	
		foreach($allUsers["group"] as $group){		
			$oGroup = Group::get($group);
			foreach($oGroup->getUsers() as $user){
						$users[] = $user->getId();
			}
		}

		$users = array_unique($users);
		
		return $users;
	}
	
	$default->log->debug("Alerts : finished");
	exit(0);
?>
