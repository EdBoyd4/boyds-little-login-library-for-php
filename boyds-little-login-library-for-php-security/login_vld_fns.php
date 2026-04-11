<?php

require_once('login_sessn_cnstnts.php');

function verify_no_existing_user_credentials(){
    if(isset($_SESSION['user_name'])){
        $error_msg = "There seems to be an issue with your identity as provided. Please contact a site administrator.";
          return $error_msg;
    }else if(isset($_SESSION['role'])){
        $error_msg = "There seems to be an issue with the nature of the access you have to our system. Please contact a site administrator.";
          return $error_msg;
    }
    else{
        return TRUE;
    }
}

function verify_entry_of_username_and_password(){
	if(!empty($_POST['username']) && !empty($_POST['password'])){
		return TRUE;
	}elseif((empty($_POST['username']) || empty($_POST['password']))){
        $error_msg = "Please complete all the required fields.";
		return $error_msg;
	}else{
        $error_msg = "Your entry / entries could not be recognized. Please contact a site administrator.";
		return $error_msg;
	}
}

function validate_query($user_info){
    if (mysqli_num_rows($user_info) == 1){
        $row = mysqli_fetch_array($user_info);
        return $row;
    }else{
        error_log(http_build_query($user_info));
    }
}

function validate_user_password($row, $user_password){ 
    if (!empty($row['password'])){
        $hashed_password = $row['password'];
        if (password_verify($user_password, $hashed_password)){
            return TRUE;
        }else{
            return $error_msg = 'Password Was Not Valid.';
        }
    }else{
        return $error_msg = 'Password Could Not Be Found.';
    }
}

function validate_user_role($row){
    if(($row[ROLE] == '0')){
        return TRUE;
    }else{
        if(empty($row[ROLE])){
            $error_msg = "There seems to be a problem with your access to our database. Please contact a site administrator.";
            return $error_msg;
        }
        if($row[ROLE] == ''){
            $error_msg = "There seems to be an issue with your access to our database. Please contact a site administrator.";
            return $error_msg;
        }
        if(($row[ROLE]>= '2')||($row[ROLE] == '1')){
            $error_msg = "There seems to be a prolem with the amount or type of access you have to our database. Please contact a site administrator.";
            return $error_msg;
        }
    }
}

function validate_user_login($user_info, $user_password){
    $row = validate_query($user_info);
    if (validate_user_password($row, $user_password) == TRUE){
        if (validate_user_role($row) == TRUE){
            set_session_security_variables_and_associated_cookies($row);
            return TRUE;
        }else{
            handle_login_error($UserRole);
        }
    }else{

    }
}

function set_session_security_variables_and_associated_cookies($row){
    $_SESSION[UNS] = $row['user_name'];
    $_SESSION[UR] = $row['role'];
    $milliseconds = floor(microtime(true) * 1000);
    $_SESSION[LTS] = $milliseconds;
    setcookie(UNC, $row['user_name'], time() + (60 * 60 * 1));
    setcookie(LTC, $milliseconds, time() + (60 * 60 * 1));
}

?>