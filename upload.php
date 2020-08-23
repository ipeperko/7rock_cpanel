<?php

include_once 'core/session.php';
include_once 'core/DB.php';

header('Content-Type: text/html; charset=utf-8');

class UploadMP3 {

    public $debug = TRUE;
    public $debugnl = '<br/>';
    
    protected $res_info = NULL;
    protected $mp3_tags = NULL;
    protected $db = NULL;
    protected $upload_dir = '';

    
    public function __construct() {

        $this->upload_dir = dflt_upload_dir();
        $this->setFileValid(0);
        $this->db = new DB();
        //$this->db->debug = TRUE;
        
        if (!is_valid_session()) {
            $this->addError("Session expired");
            $this->response(400);               
        }
    }

    // Note: for > 1 files not tested!!!
    public function processRequest() {
        
        if (!$_FILES) {
            $this->addError("No files");
            $this->response(400);
        }

        foreach ($_FILES as $file) {

            $this->res_info['file'] = $file;
            
            // Validate file            
            if ($file['error'] !== 0) {
                $this->addError("File error code " . $file['error']);
                $this->response(400);
            }
            
            $ok = $this->getMp3Tags($file['tmp_name']);

            if (!$ok) {                
                $this->response(400);
            }            

            $exist = $this->fileEntryExist();

            if ($exist) {                
                $this->response(400);
            }
            
            $dir = $this->initDirectory();
            $filename = $this->getStdFileName();
            
            // Move from '/tmp' to '$dir' and insert entry in database
            $uploaded = move_uploaded_file(
                    $file['tmp_name'], $dir['dir'] . '/' . $filename
            );            

            if (!$uploaded) {
                $this->addError("Datoteka ni nalozena");
                $this->response(500);
            }
            
            $this->insertSong($filename, $dir['subdir']);
            $this->addInfo("Datoteka " . $file['name'] . " nalozena");
            $this->setFileValid(1);
            $this->response();
        }

        $this->addError("??? Kje pa smo jebemti ???");
        $this->response(400);
    }
      
    // @return:  on success: array ( 'dir', 'subdir' )
    protected function initDirectory() {
        
        // Init directory
        $subdir = $this->mp3_tags['artist'];
        //$subdir = $this->znebiSeSumnikov($subdir);
        $dir = $this->upload_dir . '/' . $subdir;

        if (!file_exists($dir)) {
            mkdir($dir);
        }

        if ($this->mp3_tags['album'] && strlen( $this->mp3_tags['album']) > 0) {
            $subdir .= "/" . $this->mp3_tags['album'];
            //$subdir = $this->znebiSeSumnikov($subdir);
            $dir = $this->upload_dir . '/' . $subdir;
            if (!file_exists($dir)) {
                mkdir($dir);
            }
        }        
        
        return array (
            'dir' => $dir,
            'subdir' => $subdir
        );
    } 
    
    protected function getStdFileName() {
        
        $fname = "";
        
        if ($this->mp3_tags['artist']) {
            $fname .= $this->mp3_tags['artist'];
        } else {
            return NULL;
        }
        
        if ($this->mp3_tags['album']) {
            $fname .= " - " . $this->mp3_tags['album'];
        }
        if ($this->mp3_tags['title']) {
            $fname .= " - " . $this->mp3_tags['title'];
        }
        
        $fname .= ".mp3";
        
        return $fname;
    }
    
    // @return: TRUE/FALSE (false on error)
    protected function getMp3Tags($filename) {
        
        $et = 'json';
        
        if ($et === 'xml') {
            $mp3info = exec('mp3info -p "<song><artist>%a</artist><title>%t</title><album>%l</album><year>%y</year></song>" ' . $filename, $mp3response);
            //print_r($mp3info);
            $this->mp3_tags = (array) simplexml_load_string($mp3info);
        }
        elseif ($et === 'json') {
            $mp3info = exec('mp3info -p "{\"artist\":\"%a\", \"title\": \"%t\", \"album\":\"%l\", \"year\":\"%y\"}" ' . $filename, $mp3response);
            //print_r($mp3info);
            $this->mp3_tags = (array)json_decode($mp3info);
            //print_r($this->mp3_tags);
        }

        $ok = TRUE;
        
        if (!$this->mp3_tags) {
            
            $ok = FALSE;
            $this->addError("Ne morem dobiti mp3 tagov");
            
        } else {
            
            if (strlen($this->mp3_tags['artist']) < 1) {
                //echo "No artist!<br/>";
                $ok = FALSE;
                $this->addError("Manjka tag 'Artist'");
            }
            if (strlen($this->mp3_tags['title']) < 1) {
                //echo "No title!<br/>";
                $ok = FALSE;
                $this->addError("Manjka tag 'Title'");
            }
        }
        
        if (!mb_detect_encoding($this->mp3_tags['artist'] , 'UTF-8', TRUE)) {
            $ok = FALSE;
            $this->addError("Artist ni utf8");            
        }
        if (!mb_detect_encoding($this->mp3_tags['title'] , 'UTF-8', TRUE)) {
            $ok = FALSE;
            $this->addError("Title ni utf8");            
        }
        
        return $ok;
    }

    protected function insertSong($filename, $dir) {

        if (!$filename || strlen($filename) < 1) {
            return;
        }
                
        $tags = $this->mp3_tags;

        $set = array(
            'filename' => $filename,
            'dir' => $dir,
            'artist' => $tags['artist'],
            'title' => $tags['title'],
            'album' => $tags['album'],
            'year' => $tags['year'],
            'owner' => $_SESSION['username']
        );

        $this->db->insert('songs', $set);
    }

    protected function fileEntryExist () {
        
        $this->db->select('*')
                ->from('songs')
                ->where('artist', $this->mp3_tags['artist'])
                ->where('title', $this->mp3_tags['title'])
                ->where('album', $this->mp3_tags['album'])
                ->get();
        
        if ($this->db->num_rows() > 0) {
            $this->addError("Datoteka ze obstaja (" .  $this->mp3_tags['artist'] . " " . $this->mp3_tags['title'] . ")");
            return TRUE;
        } else {
            return FALSE;
        }                       
    }

    protected function znebiSeSumnikov($str) {
        
        $str = str_replace('š', "s", $str);
        $str = str_replace('Š', "S", $str);
        $str = str_replace('č', "c", $str);
        $str = str_replace('Č', "C", $str);
        $str = str_replace('ž', "z", $str);
        $str = str_replace('Ž', "Z", $str);
        return $str;
    }    

    // @valid: 0/1
    protected function setFileValid($valid) {
        $this->res_info['valid'] = $valid === 1 ? 1 : 0;
    }

    protected function addInfo($msg) {
        $this->res_info['info'][] = $msg;
    }

    protected function addError($msg) {
        $this->res_info['error'][] = $msg;
    }
        
    protected function response($response_code = 200) {

        http_response_code($response_code);
        if ($this->res_info) {
            $json = json_encode($this->res_info);
            echo $json;
        }
        die();
    }
    
    public function debugmsg($msg) {

        if ($this->debug === TRUE) {
            print_r($msg);
            echo $this->debugnl;
        }
    }
}


if (defined('NO_SESSION_START')) {
    return;
} 

$uploader = new UploadMP3();
$uploader->processRequest();