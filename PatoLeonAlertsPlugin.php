<?php

require_once(KT_LIB_DIR . '/plugins/plugin.inc.php');
require_once(KT_LIB_DIR . '/plugins/pluginregistry.inc.php');
require_once(KT_LIB_DIR . '/templating/templating.inc.php');
require_once(KT_LIB_DIR . '/actions/documentaction.inc.php');
require_once(KT_DIR . '/plugins/PatoLeon.Alerts/PatoLeonAlertsAction.php');

class PatoLeonAlertsPlugin extends KTPlugin{
	var $sNamespace='PatoLeon.Alerts.Plugin';	
	
	var $iVersion = 0;
    var $autoRegister = false;
	var $createSQL = true;
	
	function PatoLeonAlertsPlugin($sFilename = null){
	    $res = parent::KTPlugin($sFilename);
        $this->sFriendlyName = _kt('Pato Leon - Alerts Plugin');
        $this->dir = dirname(__FILE__) . DIRECTORY_SEPARATOR;        
		$this->sSQLDir = $this->dir . 'sql' . DIRECTORY_SEPARATOR;
        return $res;
	}

	function setup(){
		$oConfig =& KTConfig::getSingleton();
		//register templates        
        $oTemplating =& KTTemplating::getSingleton();
        $oTemplating->addLocation('PatoLeon.Alerts.Plugin','/plugins/PatoLeon.Alerts/templates');

		//register documentAction
		$this->registerAction('documentaction', 'PatoLeonAlertsAction', 'PatoLeon.Alerts.Action');
		
		//register  documentTrigger
		$this->registerTrigger('delete', 'postValidate', 'PatoLeonDeleteAlertsTrigger','PatoLeon.Alerts.Trigger.Delete', 'PatoLeonAlertsDeleteTrigger.php');
		
		//register documentNotification
		$this->registerNotificationHandler('PatoLeonAlertsNotification', 'PatoLeonAlerts/Notification', 'PatoLeonAlertsNotification.php');
		
		//add alert task
		require_once(KT_DIR.'/plugins/ktcore/scheduler/scheduler.php');
		$oScheduler = new scheduler('Pato Leon Alerts');
		
		$sPath = 'plugins/PatoLeon.Alerts/scheduler.php';
		$oScheduler->setScriptPath($sPath);		
		
		$oScheduler->setFrequency('daily');
		$oScheduler->setAsSystemTask('true');
		
		$iTime = date('Y-m-d').' 00:00:59';
		$oScheduler->setFirstRunTime($iTime);
		
		$oScheduler->registerTask();
	}
}

$oRegistry =& KTPluginRegistry::getSingleton();
$oRegistry->registerPlugin('PatoLeonAlertsPlugin', 'PatoLeon.Alerts.Plugin', __FILE__);
?>
