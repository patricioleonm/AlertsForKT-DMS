<?php

class PatoLeonDeleteAlertsTrigger {

	var $sNamespace = "PatoLeon.Alerts.Trigger.Delete";
	var $aInfo = null;
	
	function setInfo($aInfo){
		$this->aInfo = $aInfo;
	}

	function postValidate(){
		$oDoc = $this->aInfo['document'];
		$docId = $oDoc->getId();
		
		$sql = "DELETE FROM PatoLeonAlerts WHERE DocumentId = ". $docId;
		
		$res = DBUtil::runQuery($sql);
	}
}
?>