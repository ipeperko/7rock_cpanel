<?php

define('NO_SESSION_START', TRUE);
include_once 'upload.php';

class CommandLineUploader extends UploadMP3 {
        
    public function printUsage() {
        echo "Ukaz ni pravilen!!!\n";
        echo "TODO: Usage:\n";
    }    
    
    public function welcomeMessage() {
        echo "Uploader ver 1.0\n\n";
    }
    
    public function uploadFolder($srcdir) {
        
        if (!file_exists($srcdir)) {
            $this->onError("Directory '" . $srcdir . "' not exists!");
        }        
        
        if (substr($srcdir, -1) !== "/") {
            $srcdir .= "/";
        }
        
        $files = scandir($srcdir);
        unset($files[0]); // .
        unset($files[1]); // ..
        
        foreach ($files as $k => $srcfile) {
            
            $array = explode('.', $srcfile);
            $extension = end($array);
            
            if ($extension !== 'mp3') {
                unset($files[$k]);
            }
        }
          
        //print_r($files);
        
        foreach ($files as $k => $srcfile) {
            
            echo "\n---- " . $srcfile . " ---- \n";
            
            //$srcfullname = "'" . "$srcdir$srcfile" . "'";
            $srcfullname = "$srcdir$srcfile";
            
            $this->mp3_tags = NULL;
            $ok = $this->getMp3Tags('"' . $srcfullname . '"');            
            if (!$ok) {
                $this->onError($srcfile . " ni pravi mp3!\n");
                continue;
            }
            
            $exist = $this->fileEntryExist();
            if ($exist) {
                $this->onError($srcfile . " ze obstaja!\n");
                continue;
            }

            //print_r($this->mp3_tags);
            //echo "\n";
            
            $dir = $this->initDirectory();
            $filename = $this->getStdFileName();      
            $fullname = $dir['dir'] . '/' . $filename;
            
            //print_r ($srcfullname);
            //echo "\n";
            //print_r ($fullname);
            //continue;
            
            // Copy file
            $cp_cmd = 'cp "' . $srcfullname . '" "' . $fullname . '"';
            //echo "$cp_cmd\n";
            $copied = system($cp_cmd);
            if ($copied === FALSE) {
                $this->onError($srcfile . " ni prekopirana!\n");
                continue;                
            }
            
            $this->insertSong($filename, $dir['subdir']);            
        }                       
    }
    
    private function onError($msg) {        
        echo "\033[01;ERROR : " .$msg . " \033[0m";
    }
}

$uploader = new CommandLineUploader();
$uploader->welcomeMessage();

if (count ($argv) < 2) {
    $uploader->printUsage();
    die();
}

$dir = $argv[1];
$uploader->uploadFolder($dir);

