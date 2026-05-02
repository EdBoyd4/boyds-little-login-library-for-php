<?php 
    require_once(BOYDS_LOGIN_LIBRARY_CONSTANTS.'/boyds-little-login-library-for-php-db-connection-constants.php');
    require_once(BOYDS_LOGIN_LIBRARY_CONSTANTS.'/boyds-little-login-library-for-php-db-constants.php');

    $userDatabaseConnection = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
    if ($userDatabaseConnection->connect_error) die($userDatabaseConnection->connect_error);

/*     function queryMysql($query)
    {
        global $userDatabaseConnection;
        $result = $userDatabaseConnection->query($query);
        if (!$result) die($userDatabaseConnection->error);
        return $result;
    } */
    
    // function selectByUserName(string $user_name){
    //     global $userDatabaseConnection;
    //     $stmt = $userDatabaseConnection->prepare("SELECT ".UID.", ".UN_.", ".PW_.", ".UR_." FROM users WHERE user_name = ?");
    //     $stmt->bind_param("s", $user_name);
    //     $stmt->execute();
    //     $userInfo = $stmt->get_result();
    //     if(!$userInfo) exit('No rows');
    //     $stmt->close();
    //     return($userInfo);
    // }
	
    function selectByUserName(string $user_name): ?array
    {
        global $userDatabaseConnection;

        $stmt = $userDatabaseConnection->prepare(
            "SELECT " . UID . ", " . UN_ . ", " . PW_ . ", " . UR_ . "
            FROM users
            WHERE user_name = ?
            LIMIT 1"
        );

        if (!$stmt) {
            error_log("Prepare failed: " . $userDatabaseConnection->error);
            return null;
        }

        $stmt->bind_param("s", $user_name);

        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            $stmt->close();
            return null;
        }

        $result = $stmt->get_result();

        if (!$result) {
            error_log("get_result failed");
            $stmt->close();
            return null;
        }

        $user = $result->fetch_assoc(); // array or null

        $stmt->close();

        return $user ?: null;
    }
?>