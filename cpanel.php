<?php

include_once 'core/session.php';

if (!is_valid_session()) {
    header('Location: login.php'); 
    die();    
}

$VER=5;

?>


<script type="text/javascript">        
    var gl_username = "<?php echo $_SESSION['username']; ?>";
    var gl_name = "<?php echo $_SESSION['name']; ?>";
</script>



<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <link href = "bower_components/w2ui/w2ui-1.4.3.min.css" rel = "stylesheet">
    <link href = "bower_components/font-awesome/css/font-awesome.min.css" rel = "stylesheet">
    <link href = "bower_components/jquery-toast-plugin/dist/jquery.toast.min.css" rel = "stylesheet">
    <link href = "assets/css/style.css?ver=<?php echo $VER; ?>" rel = "stylesheet">
</head>
<body>
    
    <div id="layout" style="width: 100%; height: 100%;">        
    </div>

    <div id="uploader-body" class="main-tab-item">
        <div>
            <!--<h2>Uploader</h2>-->
        </div>
        
        <div class="instructions-wrapper instructions-wrapper-warning">
            <h5>Navodila za nalaganje komadov:</h5>
            <ul>
                <li>Preveri, če so <b>komadi že naloženi</b>!</li>
                <li>Preveri bitrate (<b>44.1kHz</b>) in kvaliteto (<b>vsaj 128kbps</b>)!</li>

                <li>Odpri komade s <b>programom za tagganje</b> (kjer imaš nastavljeno kodiranje <b>UTF-8</b>)!</li>
                <li>Tudi če taggov ni treba popravljati, komade shrani (<b>save</b>), da se zagotovo zapiše/prepiše ustrezno kodiranje UTF-8!</li>
                
                <li>Preveri tage: <b>Artist</b>, <b>Title</b>, <b>Album</b>, <b>Year</b>!</li>
                <li>Pazi na <b>velike in male črke</b> (npr. Inmate in ne INMATE ali kaj drugega)!</li>
                <li>Nedovoljeni znaki v tagih: <b>?</b> <b>/</b> </li>                
            </ul>
        </div>

        <div id="dragndropimage" class="uploadimage-dragndrop">
            <div class="uploadimage-text">Povleci mp3 komade sem notri</div>
            <div class="uploadimage-input">
                <input type="file" multiple="multiple" name="uploadFiles" id="upload-input" />
            </div>
        </div>

        <div id="upload-liveuploads" data-bind="template: { name: 'template-uploads' }"></div>

        <div id="error-wrapper"></div>

        <script type="text/html" id="template-uploads">
            <div data-bind="visible: showTotalProgress()">
                <div>
                    <span data-bind="text: uploadSpeedFormatted()"></span>
                    <span data-bind="text: timeRemainingFormatted()" style="float: right;"></span>
                </div>
                <div class="uploadimage-totalprogress">
                    <div class="uploadimage-totalprogressbar" style="width: 0%;" data-bind="style: { width: totalProgress() + '%' }"></div>
                </div>
            </div>
            <div data-bind="foreach: uploads">
                <div class="uploadimage-upload" data-bind="css: { 'uploadimage-uploadcompleted': uploadCompleted(), 'uploadimage-uploaderror': uploadError() }">
                    <div class="uploadimage-fileinfo">
                        <strong data-bind="text: fileName"></strong>                        
                        <span data-bind="text: fileSizeFormated"></span>
                        <strong data-bind="text: info"></strong>
                        <span class="uploadimage-progresspct" data-bind="visible: uploadProgress() < 100"><span data-bind="text: uploadSpeedFormatted()"></span></span>
                    </div>
                    <div class="uploadimage-progress">
                        <div class="uploadimage-progressbar" style="width: 0%;" data-bind="style: { width: uploadProgress() + '%' }"></div>
                    </div>
                </div>
            </div>
        </script>
      
    </div>

    <div id="playlists-body" class="main-tab-item">
        <ol id="pl_selectable">
            
        </ol>
             
        <div class="instructions-wrapper">
            <h5>Dodajanje komadov v boxe in brisanje iz boxov</h5>
            <ul>
                <li>Dvoklik na komad doda komad v izbran box (ali več izbranih boxov), če ga tam še ni.</li>
                <li>Dvoklik na komad briše komad iz izbranega boxa (ali več izbranih boxov), če je že tam.</li>
                <li>Dodajanje več komadov naenkrat se doda s klikom na "Dodaj v izbran box" (izbran mora biti vsaj 1 komad in vsaj 1 box).</li>
                <li>Brisanje več komadov iz boxov se izvede s klikom na "Briši iz vseh boxov" 
                    (pri tem bodo komadi brisani iz vseh boxov, ne glede na trenutno izbrane boxe).</li>              
            </ul>
            
            <h5>Razne finte</h5>
            <ul>
                <li>Ctrl+1, Ctrl+2 premika trenutno izbran box levo/desno. 
                    Ni kompatibilno z vsemi brskalniki (recimo Chrome uporablja ti kombinaciji tipk za prehajanje med zavihki). </li>
            </ul>
        </div>        
    </div>
    
    
<script src = "bower_components/jquery/dist/jquery.js"></script>  
<script src = "bower_components/jquery-ui/jquery-ui.min.js"></script>  
<script type="text/javascript" data-main="html5Upload/scripts/main.js" src="html5Upload/scripts/require.js"></script>
<script src = "bower_components/w2ui/w2ui-1.4.3.min.js"></script>  
<script src = "bower_components/jquery-toast-plugin/dist/jquery.toast.min.js"></script>  
<script src = "assets/js/stdtable.js?ver=<?php echo $VER; ?>"></script> 
<script src = "assets/js/sripts.js?ver=<?php echo $VER; ?>"></script> 
<script src = "assets/js/songbox_tools.js?ver=<?php echo $VER; ?>"></script> 
<script src = "assets/js/songbox_widget.js?ver=<?php echo $VER; ?>"></script> 
<script src = "assets/js/notificationpopup.js?ver=<?php echo $VER; ?>"></script> 
    
</body>
    
</html>

<script type="text/html" id="template_songbox">
    
    <div class="songbox_main">
    
        <div class="songbox_name">
            Name
        </div>
        
        <div class="songbox_id">
            id
        </div>
        <div class="songbox_owner">
            Owner
        </div>
        <div class="songbox_count">
            0
        </div>
        <!--
        <div class="songbox_x">
            <i class="fa fa-times"></i>
        </div>
        -->

    </div>
    
    <div class="clear">&nbsp;</div> 
    <div class="songbox_footrow">UREDI</div>
</script>