<?php 
    require_once(BOYDS_LOGIN_LIBRARY_CONSTANTS.'/boyds-little-login-library-for-php-db-connection-constants.php');
    require_once(BOYDS_LOGIN_LIBRARY_CONSTANTS.'/boyds-little-login-library-for-php-db-constants.php');

    $sourceOfPower = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
    if ($sourceOfPower->connect_error) die($sourceOfPower->connect_error);

    function queryMysql($query)
    {
        global $sourceOfPower;
        $result = $sourceOfPower->query($query);
        if (!$result) die($sourceOfPower->error);
        return $result;
    }
    
    function validateUser($user_name){
        global $sourceOfPower;
        $stmt = $sourceOfPower->prepare("SELECT ".UID.", ".UN.", ".PW.", ".ROLE." FROM users WHERE user_name = ?");
        $stmt->bind_param("s", $user_name);
        $stmt->execute();
        $userInfo = $stmt->get_result();
        if(!$userInfo) exit('No rows');
        $stmt->close();
        return($userInfo);
    }
	
?>