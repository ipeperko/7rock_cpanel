<?php

include_once 'core/DB.php';

header('Content-Type: application/json');

$db = new DB();

$db->select("song_id AS recid, filename, dir, artist, title, album, year, flags, owner")
        ->from('songs')
        ->order_by("artist", "ASC")
        ->get();

if ($db->num_rows() > 0) {
    $return['total'] = $db->num_rows();
    $return['records'] = $db->result();
    
    $json = json_encode($return);
    echo $json;
}

return json_encode(array( 'status' => 'error'));
        