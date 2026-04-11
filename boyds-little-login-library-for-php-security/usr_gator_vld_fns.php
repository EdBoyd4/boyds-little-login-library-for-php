<?php

// THIS WAS THE PHP BEHIND THE CONFIRMATION SCREEN!!!! -------------------------------

    require_once(NAVIGATION_PREFIX.USER_MONITORING_FUNCTIONS_SYS);
    require_once(NAVIGATION_PREFIX.PRODUCTION_DATABASE_SECURITY_FUNCTIONS_SYS);
	require_once(NAVIGATION_PREFIX.'ClarAssign/secludere/monitoring/usr_sessn_cnstnts.php');

	function verifyNewUserRole(){
        global $pageArea;
		if($_SESSION[UR] != $pageArea){
			killSessionAndRedirectConnection();
		}
	}
    
    function validateAndResetSessionKeysforProductionDatabase(){
        $infoForVerification = validateLoginInformationBasedOnSessionUserName($_SESSION[UNS]);
        $row = mysqli_fetch_array($infoForVerification);
        if (!empty($row[KEY_ASSET_NAME])){
            if($row[KEY_ASSET_NAME] == $_SESSION[UNS]){
                $_SESSION[KEY_ASSET_NAME] = $_SESSION[UNS];
                unset($_SESSION[UNS]);
            }
        }else{
            killSessionAndRedirectConnection();
        }
        if ($row[KEY_ASSET_ROLE] == 0){
            if($row[KEY_ASSET_ROLE] == $_SESSION[UR]){
                $_SESSION[KEY_ASSET_ROLE] = $_SESSION[UR];
                unset($_SESSION[UR]);
                setcookie(USER_NAME_COOKIE, UNC);
                setcookie(UNC, time() + (60 * 60 * -1));
            }
        }else{
            killSessionAndRedirectConnection();
        }
        if (isset($_SESSION[LTS])){
            $_SESSION[SESSION_TIME_INIT] = $_SESSION[LTS];
            unset($_SESSION[LTS]);
            setcookie(LOGIN_TIME_COOKIE, LTC);
            setcookie(LTC, time() + (60 * 60 * -1));
        }else{
            killSessionAndRedirectConnection();
        }
    }

    function verifySessionExistsAndResetConstraintsForProduction(){
        enforceActiveSessionConstraint();
        verifyNewUserRole();
        validateAndResetSessionKeysforProductionDatabase();
    }

?>