<?php

defined('BASEPATH') OR exit('No direct script access allowed');

include_once 'core/DB.php';
include_once 'core/login_model.php';

class Controller {
    
    public $debug = FALSE;
    protected $response_format = 'json';
    
    protected $username = null;
    protected $permission_level = 1;
    protected $db = null;   
    protected $requests = array(); // q=...
    

    public function __construct() {
        
        $this->db = new DB();

        if ($_REQUEST['debug'] && $_REQUEST['debug'] === 'yes') {
            $this->db->debug = TRUE;
            $this->debug = TRUE;
        } else {
            if (!is_valid_session()) {
                $this->responseError("Session not valid");
            }
        }

        $this->username = $_REQUEST["username"];
        if (!$this->username) {
            $this->responseError("No username");
        }
        
        $login_model = new LoginModel();        
        $user_data = $login_model->getUserDataByUsername($this->username);

        if ($user_data === NULL) {
            $this->responseError("No user data");
        }
        if ($user_data['permission_level'] !== NULL) {
            $this->permission_level = $user_data['permission_level'];
        }

        $q = $_REQUEST["q"];
        $this->requests = explode(",", $q);

        if (!$q || $q == "") {
            $this->responseError("Wrong request");
        }
    }
    
    public function processRequests() {
        
        $body = file_get_contents("php://input");
        $v = json_decode($body, true);


        if ($this->requested('ping')) {            
            $this->response([ ping => "ok" ]);
            exit();                        
        }
        if ($this->requested('songlistw2ui')) {            
            $this->getSonglistw2ui();
            exit();                        
        }
        if ($this->requested('songboxdata')) {     
            $this->getSongboxData();        
            exit();
        }
        
        
        if ($this->permission_level < 5) {
            $this->responseError("Premalo pravic");
            exit();
        }
        
        
        if ($this->requested('remove_songs')) {            
            $this->removeSongs($v);
            exit();                        
        }
        if ($this->requested('songboxadd')) {     
            $this->songboxAdd($v);        
            exit();
        }
        if ($this->requested('songboxrename')) {     
            $this->songboxRename($v);        
            exit();
        }
        if ($this->requested('songboxdelete')) {     
            $this->songboxDelete($v);        
            exit();
        }
        if ($this->requested('addtosongbox')) {     
            $this->addToSongbox($v);        
            exit();
        }
        if ($this->requested('removefromsongbox')) {     
            $this->removeFromSongbox($v);        
            exit();
        }
        
        if ($return) {
            $return['status'] = 'success';
            $this->response($return);
        }
        
        $this->responseError();                
    }

    // ---------------------------------------------------------------------
    // Song boxes
    // ---------------------------------------------------------------------    
        
    protected function getSongboxData() {
        
        $this->db->select('id, name, owner, timestamp')
                ->from('songboxes')->order_by("name", "ASC")->get();
                
        if ($this->db->num_rows() > 0) {
            $return['songboxes'] = $this->db->result();                       
            
            foreach ($return['songboxes'] as &$box) {
                
                $boxid = $box['id'];                
                $box['songs'] = array();
                                
                $this->db->select("song_id")
                    ->from('songbox_data')
                    ->where('songbox_id', $boxid)
                    ->order_by("song_id", "ASC")->get();  
                

                if ($this->db->num_rows() > 0) {
                    
                    foreach ($this->db->result() as $song) {
                        $box['songs'][] = $song['song_id'];
                    }                                                            
                }                 
            }
                        
        } else {
            $return['songboxes'] = array();
        }      
        
        $return['status'] = 'success';
        $this->response($return);
    }
    
    protected function songboxAdd($data) {
        
        if (!$data || $data['name'] === NULL) {
            $this->responseError("No data");
        }        
        
        $set = array(
            "name" => $data['name'],
            "owner" => $this->username
        );
                
        $this->db->insert("songboxes", $set);
        
        $this->response();  
    }
    
    protected function songboxRename($data) {
        
        if (!$data || $data['songbox_id'] === NULL || $data['name'] === NULL) {
            $this->responseError("No data");
        }        
        
        $set['name'] = $data['name'];
        $where['id'] = $data['songbox_id'];
        $this->db->update("songboxes", $set, $where);                
        
        $this->response();  
    }

    protected function songboxDelete($data) {
        
        if (!$data || $data['songbox_id'] === NULL) {
            $this->responseError("No data");
        }        
        
        $q = "DELETE FROM songboxes WHERE id='" . $data['songbox_id'] . "'";
        $this->db->query($q);  
        $q = "DELETE FROM songbox_data WHERE songbox_id='" . $data['songbox_id'] . "'";
        $this->db->query($q);         
        
        $this->response();  
    }
    
    protected function addToSongbox($data) {
        
        if (!$data || $data['songbox_id'] === NULL || $data['song_ids'] === NULL) {
            $this->responseError("No data");
        }
        
        foreach ($data['song_ids'] as $song_id) {

            $set['songbox_id'] = $data['songbox_id'];
            $set['song_id'] = $song_id;
            $set['username'] = $this->username;
            $r = $this->db->insert("songbox_data", $set);
            
        }
        
        $this->response();       
    }
    
    protected function removeFromSongbox($data) {
        
        if (!$data || $data['song_ids'] === NULL) {
            $this->responseError("No data");
        }
            
        
        foreach ($data['song_ids'] as $song_id) {
            
            if ($data['songbox_id'] !== NULL) {
                $q = "DELETE FROM songbox_data WHERE songbox_id='" . $data['songbox_id'] . "' AND song_id='" . $song_id . "'";
            } else {
                $q = "DELETE FROM songbox_data WHERE song_id='" . $song_id . "'";
            }
            
            $this->db->query($q);            
        }
        
        $this->response();       
    }
    
    // ---------------------------------------------------------------------
    // Songs
    // ---------------------------------------------------------------------    
    protected function getSonglistw2ui() {               
        
        $this->db->select("song_id AS recid, filename, dir, artist, title, album, year, owner, uploaded")
                ->from('songs')
                ->order_by("artist", "ASC")
                ->order_by("album", "ASC")
                ->get();
                
        if ($this->db->num_rows() > 0) {
            $return['status'] = 'success';
            $return['total'] = $this->db->num_rows();
            $return['records'] = $this->db->result();
            $this->response($return);
        }        
        $this->responseError();
    }
    
    protected function removeSongs($data) {
        
        if (!$data || !$data['songs']) {
            $this->responseError("No song array");
        }
        if ($this->permission_level < 10) {
            $this->responseError("Nimaš pravic za brisanje komadov!");
        }

                        
        foreach ($data['songs'] as $id) {          
            
            $this->db->select()->from("songs")->where('song_id', $id)->get();
            
            if ($this->db->num_rows() > 0) {
                
                $dir = $this->db->result()[0]['dir'];
                $filename = $this->db->result()[0]['filename'];
                
                $fullname = dflt_upload_dir();
                if (substr($fullname, -1) !== "/") $fullname .= "/";
                $fullname .= $dir;
                if (substr($fullname, -1) !== "/") $fullname .= "/";
                $fullname .= $filename;                        
                unlink($fullname);                
            }
                        
            $query = "DELETE FROM songs WHERE song_id=" . $id;
            $this->db->query($query);            

            $query = "DELETE FROM songbox_data WHERE song_id=" . $id;
            $this->db->query($query);            
        }
        
        $this->response();
    }
    
    // ---------------------------------------------------------------------
    // Tools
    // ---------------------------------------------------------------------    
    
    // @return : bool
    protected function requested($r) {

        foreach ($this->requests as $val) {
            if ($r == $val) {
                return TRUE;
            }
        }
        return FALSE;
    }
    
    protected function responseError($msg = NULL) {
        $this->response($msg, 400);
    }

    protected function response($data_ = NULL, $response_code = 200) {

        http_response_code($response_code);           

        if (is_string($data_)) {
            $data['status'] = $data_;
        } else {
            $data = &$data_;
        }

        if ($data['status'] === NULL) {
            if ($response_code === 200) {
                $data['status'] = 'success';
            } else {
                $data['status'] = 'error';
            }
        }

        if ($this->response_format == 'json') {
            
            header('Content-Type: application/json; charset=utf-8');

            try {
                $encoded = json_encode($data);
                echo $encoded;
            } catch (Exception $ex) {
                echo $ex;
            }
        
        } else {

            echo $data;
        }

        flush();
        exit;
    }
    
    protected function RESPONSE_DEPRECATED() {
        
        $this->responseError("Deprecated function!");
        
    }

}