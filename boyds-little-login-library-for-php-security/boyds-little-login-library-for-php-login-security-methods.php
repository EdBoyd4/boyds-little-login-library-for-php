<?php

 require_once BOYDS_LOGIN_LIBRARY_CONSTANTS . '/boyds-little-login-library-for-php-db-constants.php';

function verify_no_existing_user_credentials(){
    if(isset($_SESSION[UN_])){
        $error_msg = "There seems to be an issue with your identity as provided. Please contact a site administrator.";
          return $error_msg;
    }else if(isset($_SESSION[UR_])){
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

// function validate_query($user_info){
//     if (mysqli_num_rows($user_info) == 1){
//         $row = mysqli_fetch_array($user_info);
//         return $row;
//     }else{
//         error_log(http_build_query($user_info));
//     }
// }

function validate_user_password(array $user_info, string $user_password){ 
    if (!empty($user_info['password'])){
        $hashed_password = $user_info['password'];
        if (password_verify($user_password, $hashed_password)){
            return TRUE;
        }else{
            return $error_msg = 'Password Was Not Valid.';
        }
    }else{
        return $error_msg = 'Password Could Not Be Found.';
    }
}

function validate_user_role(array $user_info){
    if(($user_info[UR_] == '0')){
        return TRUE;
    }else{
        if(empty($user_info[UR_])){
            $error_msg = "There seems to be a problem with your access to our database. Please contact a site administrator.";
            return $error_msg;
        }
        if($user_info[UR_] == ''){
            $error_msg = "There seems to be an issue with your access to our database. Please contact a site administrator.";
            return $error_msg;
        }
        if(($user_info[UR_]>= '2')||($user_info[UR_] == '1')){
            $error_msg = "There seems to be a prolem with the amount or type of access you have to our database. Please contact a site administrator.";
            return $error_msg;
        }
    }
}

function set_session_security_variables_and_associated_cookies(array $user_info){
    $_SESSION[UN_] = $user_info['user_name'];
    $_SESSION[UR_] = $user_info['role'];
    $milliseconds = floor(microtime(true) * 1000);
    $_SESSION[LTS] = $milliseconds;
}

// function validate_user_login(array $user_info, string $user_password){
//     // $row = validate_query($user_info);
//     $user_password_correct = validate_user_password($user_info, $user_password);
//     if ($user_password_correct == TRUE){
//         if (validate_user_role($user_info) == TRUE){
//             set_session_security_variables_and_associated_cookies($row);
//             return TRUE;
//         }else{
//             handle_login_error($UserRole);
//         }
//     }else{
//         //---------------------------------------------------------------------------------------------------------
//     }
// }
