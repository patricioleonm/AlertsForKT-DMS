<?php
class PatoLeonAlertsNotification extends KTNotificationHandler {

	var $notificationType = 'PatoLeonAlerts/Notification';

    function & clearNotificationsForDocument($oDocument) {
		$aNotifications = KTNotification::getList('data_int_1 = ' . $oDocument->getId());
		foreach ($aNotifications as $oNotification) {
			$oNotification->delete();
		}

	}

	function & newNotificationForDocument($oDocument, $oUser, $sComments) {
		$aInfo = array();
		$aInfo['sData1'] = $sComments;;
		$aInfo['iData1'] = $oDocument->getId();		
		$aInfo['sType'] = 'PatoLeonAlerts/Notification';
		$aInfo['dCreationDate'] = getCurrentDateTime();
		$aInfo['iUserId'] = $oUser->getId();
		$aInfo['sLabel'] = $oDocument->getName();

		$oNotification = KTNotification::createFromArray($aInfo);

		$handler = new PatoLeonAlertsNotification();

		if ($oUser->getEmailNotification() && (strlen($oUser->getEmail()) > 0)) {
			$emailContent = $handler->handleNotification($oNotification);
			$emailSubject = sprintf(_kt('Alert Message').': %s', $oDocument->getName());
			$oEmail = new EmailAlert($oUser->getEmail(), $emailSubject, $emailContent);
			$oEmail->send();
		}

		return $oNotification;
	}

	function handleNotification($oKTNotification) {
        $oTemplating =& KTTemplating::getSingleton();
        $oTemplate =& $oTemplating->loadTemplate('PatoLeonAlertsNotification');

		//$oDoc = Document::get($oKTNotification->getIntData1());
		//$isBroken = (PEAR::isError($oDoc) || ($oDoc->getStatusID() != LIVE));

		    $oTemplate->setData(array(
            'context' => $this,
			'document_id' => $oKTNotification->getIntData1(),
			//'state_name' => $oKTNotification->getStrData1(),
			//'actor' => User::get($oKTNotification->getIntData2()),
			'message'=> $oKTNotification->getStrData1(),
			'document_name' => $oKTNotification->getLabel(),
			'notify_id' => $oKTNotification->getId(),
			'url'=>KTUtil::kt_url(),
			//'document' => $oDoc,
			//'is_broken' => $isBroken,
        ));
        return $oTemplate->render();
	}

	function resolveNotification($oKTNotification) {
	    $notify_action = KTUtil::arrayGet($_REQUEST, 'notify_action', null);
		if ($notify_action == 'clear') {
		    $_SESSION['KTInfoMessage'][] = _kt('Alert notification cleared.');
			$oKTNotification->delete();
			exit(redirect(generateControllerLink('dashboard')));
		}

		$params = 'fDocumentId=' . $oKTNotification->getIntData1();
		$url = generateControllerLink('viewDocument', $params);
		//$oKTNotification->delete(); // clear the alert.
		exit(redirect($url));
	}
}
?>