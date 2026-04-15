<?php 
    require_once('sec_db_cnx_cnstnts.php');
    require_once('sec_db_cnstnts.php');

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