<?php
require_once(KT_LIB_DIR . '/plugins/plugin.inc.php');
require_once(KT_LIB_DIR . '/actions/documentaction.inc.php');
require_once(KT_LIB_DIR . "/templating/templating.inc.php");
require_once(KT_LIB_DIR . '/database/dbutil.inc');

class PatoLeonAlertsAction extends KTDocumentAction{

	var $sName = "PatoLeon.Alerts.Action";
	var $_sShowPermission = 'docustore.permissions.alerts';	
	//var $sPermissionName = 'docustore.permissions.alerts';
	
	function getDisplayName() {
		return _kt("Alerts");
	}
	
    function predispatch() {
        $this->persistParams(array('alertID'));
		$alertID = $_REQUEST["alertID"];		
	}
	
	function do_main(){	
		$oTemplating =& KTTemplating::getSingleton();
		$oTemplate = $oTemplating->loadTemplate('PatoLeonAlerts');
		$docId = $this->oDocument->getId();
		
		$this->aBreadcrumbs[] = array(
            'name' => _kt("Alerts"),
            'url' => $_SERVER["PHP_SELF"]."&fDocumentId=".$this->oDocument->getId(),
        );
		
        $this->oPage->setBreadcrumbDetails(_kt("Add an Alert on this Document"));
	   
		$aData = array();
		$aData["action"] = "save";
		$aData["alertID"] = null;
		$aData["fDocumentId"] = $docId;
		
		
		if($_POST["action"] == 'save'){//--------------------------save post
			//get date from field_date or by calc 
			$date = ($_POST["date_type"] == "date") ? $_POST["alert_date"] : $this->daysCalc($_POST['period'], $_POST['length']);
			
			//generator user
			$Users = array(
				"user"  => array($this->oUser->getId()),
			);
			
			//sql sentence
			$sql = "INSERT INTO PatoLeonAlerts (DocumentID, Users, Date, Message)Values(?,?,?,?)";
			
			//sql data
			$aParams = array(
				$docId,
				serialize($Users),
				$date,
				htmlspecialchars($_POST["comment"])
			);
						
			//try to execute sql sentence
			$res = DBUtil::runQuery(array($sql, $aParams));
			if (PEAR::isError($res)) {
				$this->addErrorMessage(sprintf(_kt("The alert could not be added: %s"), $res->getMessage()));				
			}else{
				$this->AddInfoMessage(_kt("The alert has been added"));
			}
		}elseif($_GET["action"] == "edit"){ //------------------edit alert
			$sql = "SELECT AlertID, DATE_FORMAT( Date, '%Y-%m-%d' ) AS Date, Message FROM PatoLeonAlerts WHERE  AlertID = ".$_GET["alertID"];
			
			$res = DBUtil::getOneResult($sql);
			if (PEAR::isError($res)) {
				$this->addErrorMessage(sprintf(_kt("Alert cannot be found: %s"), $res->getMessage()));				
			}else{		
				$aData["action"] = "update";
				$aData["alertID"] = $_GET["alertID"];
				$aData["updateData"] = $res;
				$aData["edit"] = true;
			}
			
		}elseif($_POST["action"] == "update"){ //---------------------update alert
			$date = ($_POST["date_type"] == "date") ? $_POST["alert_date"] : $this->daysCalc($_POST['period'], $_POST['length']);
			
			//sql data
			$aParams = array(				
				$date,
				htmlspecialchars($_POST["comment"]),
				$_POST["alertID"]
			);			
			
			//try to execute sql sentence
			$sql = "UPDATE PatoLeonAlerts SET Date = ?, Message = ? WHERE AlertID = ?";
			
			$res = DBUtil::runQuery(array($sql, $aParams));
			if (PEAR::isError($res)) {
				$this->addErrorMessage(sprintf(_kt("The alert could not be updated: %s"), $res->getMessage()));				
			}else{
				$this->AddInfoMessage(_kt("The alert has been updated"));
			}
			
		}elseif($_GET["action"] == "delete"){ //--------------------------delete alert
			//delete alert from alerts table
			$sql = "DELETE FROM PatoLeonAlerts ".
					"WHERE	AlertID = ".$_GET["alertID"];
			
			$res = DBUtil::runQuery($sql);

			if (PEAR::isError($res)) {
				$this->addErrorMessage(sprintf(_kt("The alert could not be deleted: %s"), $res->getMessage()));				
			}else{
				$this->AddInfoMessage(_kt("The alert has been deleted"));
			}		
		}
		
		//get existing alert list
		$sql = "SELECT AlertID, Users, DATE_FORMAT(Date, '%Y-%m-%d') as Date, datediff(Date, now())  as days, Message, Sent ".
					"FROM `PatoLeonAlerts` ".
					"WHERE DocumentID= ? ".
					"ORDER BY days";
					
		$res = DBUtil::getResultArray(array($sql,array($docId)));
		if (PEAR::isError($res)) {
			$this->addErrorMessage(sprintf(_kt("The alert could not be added: %s"), $res->getMessage()));
			$aData["alerts"] = null;
		}else{
			//assign human readable named instead serialized array to Users field
			for($row = 0; $row < count($res);$row++){
				$res[$row]["Users"] = implode(" ", $this->descriptorToJSON(unserialize($res[$row]["Users"])));				
			}
			$aData["alerts"] = $res;
		}
		
		return $oTemplate->render($aData);
	}
	
	private function daysCalc($number =1, $type="days"){
		return date("Y-m-d", strtotime("+".$number." ".$type));
	}
	
//begin block users/groups administration
	function do_editnotifications() {
        $this->aBreadcrumbs[] = array(
            'name' => _kt("Alerts"),
            'url' => $_SERVER["PHP_SELF"]."&fDocumentId=".$this->oDocument->getId()."&action=editnotifications&alertID=".$_REQUEST["alertID"],
        );
        $this->oPage->setBreadcrumbDetails(_kt("Allocated users and groups"));

        $oForm = $this->form_editnotifications();
        return $oForm->renderPage();
    }
	
    function form_editnotifications() {
        $oForm = new KTForm;
        $oForm->setOptions(array(
            'context' => $this,
            'label' => _kt("Edit State Notifications."),
            'identifier' => 'PatoLeon.Alerts.Action',
            'submit_label' => _kt("Update Notifications"),
            'cancel_action' => 'managenotifications',
            'action' => 'savenotifications',
            'fail_action' => 'editnotifications',
        ));

		$sql = "SELECT Users FROM `PatoLeonAlerts` WHERE AlertID =".$_REQUEST["alertID"];
				
		$res = DBUtil::getOneResult($sql);

		if (PEAR::isError($res)) {
			$this->addErrorMessage(sprintf(_kt("The alert could not be added: %s"), $res->getMessage()).$sql);			
		}else{		
			$preval	= unserialize($res["Users"]);
		}
		
        $oForm->setWidgets(array(
            array('ktcore.widgets.descriptorselection', array(
                'label' => _kt("Users to inform"),
                'description' => _kt("Select which users, groups and roles to be notified."),
                'name' => 'users',
                'src' => KTUtil::addQueryStringSelf($this->meldPersistQuery(array('json_action'=> 'notificationusers'), "json")),
                'value' => $this->descriptorToJSON($preval),
            )),
        ));
        $oForm->setValidators(array(
            array('ktcore.validators.array', array(
                'test' => 'users',
                'output' => 'users',
            )),
        ));
        return  $oForm;
    }
	
	
	function do_savenotifications() {

        $oForm = $this->form_editnotifications();
        $res = $oForm->validate();

        if (!empty($res['errors'])) {
            return $oForm->handleError();
        }

        $data = $res['results'];
		
        // now, an annoying problem is that we do *not* have the final set.
        // so we need to get the original, add the new ones, remove the old ones.
        //
        // because its not *really* isolated properly, we need to post-process
        // the data.

        // we need the old one        

        $user_pattern = '|users\[(.*)\]|';
        $group_pattern = '|groups\[(.*)\]|';
        
        $user = KTUtil::arrayGet($aAllowed, 'user', array());
        $group = KTUtil::arrayGet($aAllowed, 'group', array());

        // do a quick overpass
        $newAllowed = array();
        if (!empty($user)) { $newAllowed['user'] = array_combine($user, $user); }
        else { $newAllowed['user'] = array(); }
        if (!empty($group)) { $newAllowed['group'] = array_combine($group, $group); }
        else { $newAllowed['group'] = array(); }

        $added = explode(',', $data['users']['added']);
        $removed = explode(',', $data['users']['removed']);

        foreach ($added as $akey) {
            $matches = array();
            if (preg_match($user_pattern, $akey, $matches)) { $newAllowed['user'][$matches[1]] = $matches[1]; }
            else if (preg_match($group_pattern, $akey, $matches)) { $newAllowed['group'][$matches[1]] = $matches[1]; }
        }

        foreach ($removed as $akey) {
            $matches = array();
            if (preg_match($user_pattern, $akey, $matches)) { unset($newAllowed['user'][$matches[1]]); }
            else if (preg_match($group_pattern, $akey, $matches)) { unset($newAllowed['group'][$matches[1]]); }
        }

        // FIXME check that these are all users.

		$sql = "UPDATE PatoLeonAlerts SET Users = '".serialize($newAllowed)."' WHERE AlertID = ".$_REQUEST["alertID"];
		
		$res = DBUtil::runQuery($sql);
		
        if (PEAR::isError($res)) {
            return $oForm->handleError($res->getMessage());
        }

        $this->successRedirectTo("managenotifications", _kt("Notifications updated."));
    }

    function json_notificationusers() {
        $sFilter = KTUtil::arrayGet($_REQUEST, 'filter', false);
        if ($sFilter == false) {
        	$values = array('off' => _kt('-- Please filter --')); // default
        }
        $sFilter = trim($sFilter);
    	$values = array('off' => _kt('-- Please filter --')); // default

    	if (!empty($sFilter)) {
    	    $allowed = array();
            // Modified Jarrett Jordaan Only notify enabled users
    	    $q = sprintf('name like "%%%s%%" AND disabled = 0', DBUtil::escapeSimple($sFilter));
    	    $aUsers = User::getList($q);
    	    $q = sprintf('name like "%%%s%%"', DBUtil::escapeSimple($sFilter));
        	$aGroups = Group::getList($q);
            
            $empty = true;

            if (!PEAR::isError($aUsers)) {
                $allowed['user'] = $aUsers;
                if (!empty($aUsers)) {
                    $empty = false;
                }
            }

            if (!PEAR::isError($aGroups)) {
                $allowed['group'] = $aGroups;
                if (!empty($aGroups)) {
                    $empty = false;
                }
            }

            if ($empty) {
            	$values = array('off'=>'-- No results --'); // default
            } else {
                $values = $this->descriptorToJSON($allowed);
            }
    	}

    	return $values;
    }
	
   function descriptorToJSON($aAllowed) {
        $values = array();

        foreach (KTUtil::arrayGet($aAllowed,'user',array()) as $oU) {
            if (!is_object($oU)) {
                $iUserId = $oU;
                $oU = User::get($iUserId);
            } else {
                $iUserId = $oU->getId();
            }

            if (PEAR::isError($oU) || ($oU == false)) {
                continue;
            } else {
                $values[sprintf("users[%d]", $iUserId)] = sprintf(_kt('User: %s'), $oU->getName());
            }
        }

        foreach (KTUtil::arrayGet($aAllowed,'group',array()) as $oG) {
            if (!is_object($oG)) {
                $iGroupId = $oG;
                $oG = Group::get($iGroupId);
            } else {
                $iGroupId = $oG->getId();
            }
            if (PEAR::isError($oG) || ($oG == false)) {
                continue;
            } else {
                $values[sprintf("groups[%d]", $iGroupId)] = sprintf(_kt('Group: %s'), $oG->getName());
            }
        }

        return $values;
    }
//end block users/groups administration
}
?>