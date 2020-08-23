<?php

include_once 'core/session.php';
include_once 'Controller.php';

error_reporting(E_ALL);
ini_set("display_errors", 1);

if (!is_valid_session()) {
    die("Session not valid");
}

$db = new DB();
$NL = "<br/>";
$uploaddir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/";
$lndir = $uploaddir . "0_box_export/";
echo $lndir;
echo $NL;

system("mkdir -p " . $lndir);
system("rm -R " . $lndir . "*");
system("chmod 775 " .$lndir);

function exportBox($boxid, $boxname) {
    
    global $db;
    global $NL;
    global $uploaddir;
    global $lndir;


    $db->select("song_id")
            ->from('songbox_data')
            ->where('songbox_id', $boxid)
            ->order_by("song_id", "ASC")->get();    
    
    if ($db->num_rows() < 1) {
        return;
    }
    
    $songs = $db->result();    
    echo $NL . "BOX " . $boxname  . " (#" . $boxid . ") : " . $NL;
    
    foreach ($songs as $song) {
        
        $db->select("song_id, filename, dir")
                ->from('songs')
                ->where('song_id', $song['song_id'])
                ->get();

        if ($db->result() < 1) {
            continue;
        }
        
        $songdata = $db->result()[0];        
        $cmd = 'ln -s "' . $uploaddir . $songdata['dir']  . "/" . $songdata['filename'] . '" ' .
                '"' . $lndir . $boxname . "/" . $songdata['song_id'] . '-' . $songdata['filename'] . '"';        
        system ($cmd);
            
        echo "[" . $songdata['song_id'] . "] " . $songdata['dir'] . "/" . $songdata['filename'] . $NL;                
    }
}

$db->select('id, name, owner, timestamp')
        ->from('songboxes')->order_by("name", "ASC")->get();

if ($db->num_rows() > 0) {
    $boxes = $db->result();    
    
    foreach ($boxes as &$box) {
        //print_r($box);
        //echo $NL;
        
        $boxid = $box['id'];
        $boxname = $box['name'];
        
        system ("mkdir " . $lndir . $boxname);        
        system("chmod 775 " .$lndir .$boxname);
        exportBox($boxid, $boxname);
    }
} 
else {
    die ("No songboxes");
} 

