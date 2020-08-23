<?php

defined('BASEPATH') OR exit('No direct script access allowed');

include_once BASEPATH.'/core/DB.php';
include_once BASEPATH.'/core/password_validator.php';

class LoginModel {
    
    public $debug = FALSE;
    
    private $db;
    
    public function __construct() {        
        
        $this->db = new DB();
        //$this->debug = TRUE;
        //$this->db->debug = TRUE;                
    }
    
    public function getUserDataByUsername($username) {
        
        $this->db->select('*')
                ->from("app_user_data");
        
        $email = filter_var($username, FILTER_SANITIZE_EMAIL);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->db->where("email", $email);
        } else {
            $this->db->where("username", $username);
        }
        
        $this->db->get();
      
        if ($this->db->result()) {            
            return $this->db->result()[0];        
        }
                        
        return NULL;
    }
    
    public function checkLogin($loginData) {
        
        $this->debugmsg("Check login");

        $userRow = $this->getUserDataByUsername((string) $loginData['username']);
        
        if ($userRow === NULL) {
            $data = array(
                'user_id' => 0,
                'status' => 'error/username'
            );            
            $this->logLogin($data);
            return FALSE;
        }
        
        $this->debugmsg("Username ok");

        $password = $this->checkUserPassword($loginData, $userRow);
        if ($password === FALSE) {
            $data = array(
                'user_id' => $userRow['user_id'],
                'status' => 'error/password'
            );
            $this->logLogin($data);
            return FALSE;
        }

        return ['username' => $userRow['username'], 'name' => $userRow['name']];
    }
    
    /**
     * Preveri prejeto geslo, če je pravilno, naredi nov salt ter na novo
     * zaenkriptiraj geslo
     * @param array username => string, password => string
     * @userRow 
     * @return TRUE/FALSE
     */
    public function checkUserPassword($loginData, $userRow) {
        
        //$userName = (string) $loginData['username'];
        $userName = (string) $userRow['username'];
        $password = (string) $loginData['password'];
        
        $salt = $userRow['salt']; 
        $userPass = $userRow['password']; 
        $postPass = hash('SHA256', $password . $salt);
        
        $this->debugmsg("salt: " . $salt);
        $this->debugmsg("userPass: " . $userPass);
        $this->debugmsg("postPass: " . $postPass);
              
        if ($postPass === $userPass) {
            
            $this->debugmsg("Password is ok");
                        
            $newHash = $this->generateNewPassHash($password);
            $this->debugmsg("newHash: ");    
            $this->debugmsg($newHash);    
            
            $update['password'] = $newHash['1'];
            $update['salt'] = $newHash['0'];            
            $update['last_access'] = time();
            $where['username'] = $userName;
            
            $this->db->update("app_user_data", $update, $where);

            return TRUE;
        }
        return FALSE;
    }
    
    public function forgetPassword($username) {

//        $this->debug = TRUE;
//        $this->db->debug = TRUE;
//        ini_set("display_errors", 1);

        $userRow = $this->getUserDataByUsername($username);

        if ($userRow === NULL) {
            $data = array(
                'user_id' => 0,
                'status' => 'error/forgetpassword'
            );
            $this->logLogin($data);
            return FALSE;
        }

        // TODO: password reset (optoinal)
        // Set reset key
        $reset_key = $this->setResetKey($userRow['user_id']);

        // Send reset email
        $send_mail = $this->sendPasswordResetEmail($userRow['email'], $userRow['user_id'], $reset_key);

        if (!$send_mail) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    private function sendPasswordResetEmail($email, $userid, $key) {

        $from = "noreply@7rockradio.com";

        $headers = "From:" . $from . "\r\n" .
                'Reply-To: ' . $from . "\n" .
                'X-Mailer: PHP/' . phpversion();

        $headers = "From:" . $from . "\r\n" .
                'Reply-To: ' . $from . "\n" .
                'Content-type: text/html; charset=utf-8' . "\r\n" .
                "MIME-Version: 1.0\r\n";

        $body = 'Hi, <br/> <br/>' .
                'Click here to reset your password: <br/> ' .
                "https://{$_SERVER['HTTP_HOST']}/reset_password.php" .
                '?k=' . $key .
                '&u=' . $userid .
                ' <br/> <br/>--<br>i13tech tech team';

        $send_mail = mail($email, "Password reset", $body, $headers);

        if (!$send_mail) {
            //If mail couldn't be sent output error. Check your PHP email configuration (if it ever happens)
            return FALSE;
        } else {
            return TRUE;
        }
    }

    private function deleteResetKey($user_id) {

        $tbl = "app_user_reset_key";

        // Delete entries first
        $q = "DELETE FROM " . $tbl . " WHERE user_id=" . $user_id;
        $this->db->query($q);
    }

    private function setResetKey($user_id) {

        $tbl = "app_user_reset_key";

        // Delete entries first
        $this->deleteResetKey($user_id);


        // Generate key
        $length = 30;
        $new_key = base64_encode(mcrypt_create_iv(ceil(0.75 * $length), MCRYPT_DEV_URANDOM));
        $new_key = str_replace('/', '_', $new_key);
        $new_key = str_replace('\/', '_', $new_key);
        $new_key = str_replace('=', '3', $new_key);
        $new_key = str_replace('+', '2', $new_key);


        // Insert new key
        $q = "INSERT INTO " . $tbl . " (user_id, `key`) VALUES ("
                . "'" . $user_id . "',"
                . "'" . $new_key . "')";
        $this->db->query($q);

        return $new_key;
    }

    /**
     * Funkcija naredi nov password hash z novim saltom
     * @param {string} Password
     * @return {array} 0 => salt, 1 => hash
     *
     */
    public function generateNewPassHash($password) {
        $salt = base64_encode(mcrypt_create_iv(ceil(0.75 * 16), MCRYPT_DEV_URANDOM));        
        $hash = hash('SHA256', $password . $salt);
        return array(
            $salt,
            $hash
        );
    }
    
    /*
     * @password: string
     * @username: string
     */
    public function setNewPassword($password, $userName) {
               
        if ($userName === NULL || $userName === '') {
            return FALSE;
        } 
        
        $newHash = $this->generateNewPassHash($password);
        
        $update['password'] = $newHash['1'];
        $update['salt'] = $newHash['0'];
        $where['username'] = $userName;

        $this->db->update("app_user_data", $update, $where);
        
        return TRUE;
    }
    
    public function setNewPasswordResetKey($user_id, $key, $password) {
        
//        $this->debug = TRUE;
//        $this->db->debug = TRUE;
//        ini_set("display_errors", 1);
        
        if (!$user_id) {
            $this->debugmsg("No user id");
            return FALSE;
        }

        // Check reset key
        $this->db->select("user_id, `key`, unix_timestamp(date_modified) AS date_modified")
                ->from("app_user_reset_key")
                ->where("user_id", $user_id)
                ->get();
        
        if (!$this->db->result()) {            
            $this->debugmsg("No result");
            return FALSE;            
        }
        
        $row = $this->db->result()[0];        
        
        if ($row['key'] != $key) {
            $this->debugmsg("Key does not match");
            return FALSE;
        }
        if ((int)$row['date_modified'] + 3600 * 24 < time()) {
            $this->debugmsg("Key has expired");
            return FALSE;
        }
                
        $this->debugmsg("key is ok");
        
        // Set new password
        $newHash = $this->generateNewPassHash($password);
        $update['password'] = $newHash['1'];
        $update['salt'] = $newHash['0'];
        $where['user_id'] = $user_id;
        
        $this->db->update("app_user_data", $update, $where);
        
        $this->deleteResetKey($user_id);
        
        return TRUE;
    }
    
    public function addUser($username, $firstname, $lastname, $email, $password) {
        
        $this->db->debug = TRUE;
        $newHash = $this->generateNewPassHash($password);        
        
        $set = array(
            'username' => $username,
            'name' => $firstname,
            'last_name' => $lastname,
            'email' => $email,
            'password' => $newHash['1'],
            'salt' => $newHash['0']
                
        );
        $this->db->insert('app_user_data', $set);        
    }
    
    private function logLogin($data)
    {        
        $insert['user_id'] = $data['user_id'];
        $insert['status'] = $data['status'];
        $insert['ip'] = $_SERVER['REMOTE_ADDR'];
        $insert['time'] = time();
        
        $this->db->insert("app_log_login", $insert);        
    }    

    private function debugmsg($msg) {
        
        if ($this->debug) {
            print_r($msg);
            echo "<br/>";
        }
    }
}