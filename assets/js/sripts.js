function tabelaKomadi() { return w2ui['komadi']; }

function tabs() { return w2ui['tabs']; }

$(document).ready(function() {

    initLayout();
    initTopToolbar();
    initMainSongTable(SongboxTools.getData);

});

function initLayout() {

    var pstyle = 'border: 1px solid #dfdfdf; padding-top: 0px;';

    $('#layout').w2layout({
        name: 'layout',
        padding: 4,
        panels: [
            { type: 'top', size: 35, resizable: false, style: pstyle },
            { type: 'left', size: '50%', resizable: true, },
            { type: 'main', content: '' },
        ]
    });

    var $mainLayout = $("#layout_layout_panel_main .w2ui-panel-content");

    var $tabs = $("<div>")
        .appendTo($mainLayout)
        .w2tabs({
            name: 'tabs',
            active: 'boxes',
            tabs: [
                { id: 'uploader', caption: 'Upload' },
                { id: 'boxes', caption: 'Boxi' },
            ],
            onClick: function(event) {
                console.log(event);
                this.changeTabContent(event.target);
            },
            changeTabContent: function(itemname) {

                $(".main-tab-item").hide();

                if (itemname === 'uploader') {
                    $("#uploader-body").show();
                } else if (itemname === 'boxes') {
                    $("#playlists-body").show();
                }
            }
        });

    var $tabContainer = $("<div>", {
        class: 'main-tab-container'
    }).appendTo($mainLayout);

    $("#uploader-body").prependTo($tabContainer);
    $("#playlists-body").prependTo($tabContainer);
    tabs().changeTabContent(tabs().active);

    // ping server (session)
    window.setInterval(function() { ajaxPost({ urlParams: "q=ping", hideSuccessMsg: true }); }, 10000);
}

function ping() {
    ajaxPost({ urlParams: "q=ping" });
}

function initTopToolbar() {

    $("<div>")
        .appendTo("#layout_layout_panel_top .w2ui-panel-content")
        .w2toolbar({
            name: 'topToolbar',
            items: [{
                    type: 'html',
                    id: 'brand_img',
                    html: '<div class="topbar-brand" style="width:40px;">  <img src="assets/images/pink_panther.png"/> </div>'
                },
                {
                    type: 'html',
                    id: 'brand',
                    html: '<h3> 7Panel </h3>'
                },
                { type: 'break' },
                {
                    type: 'html',
                    id: 'topbarPlayer',
                    html: '<div><audio id="audioplayer" controls="controls" name="media"> <source src="" type="audio/mpeg"> </audio></div>'
                },
                {
                    type: 'html',
                    id: 'topbarCurrentSong',
                    html: '<div id="topbar-current-song">Current song</div>'
                },
                { type: 'spacer' },
                { type: 'break' },
                {
                    type: 'html',
                    id: 'username',
                    html: '<div style="margin-right: 10px;"> <i class="fa fa-user fa-lg" ></i> <a href="logout.php" class="topbar-username">User</a></div>'
                },
            ]
        });

    $(".topbar-username").html(gl_name);
}

function initMainSongTable(loadedCallback) {

    var komadi = $("<div>", {
            id: 'tabela-komadi',
            css: { height: '100%' }
        })
        .appendTo("#layout_layout_panel_left .w2ui-panel-content");

    var tbl = createStdTable(komadi, 'komadi');

    tbl.toolbar.add(
        [
            { type: 'break', id: 'break1' },
            {
                type: 'button',
                id: 'dodaj',
                caption: 'Dodaj v izbran box',
                img: 'fa fa-cart-plus fa-lg my-tbl-icon',
                onClick: function(itemname, object) {

                    object.preventDefault();

                    var selection = tbl.getSelection();

                    if (selection.length < 1) {
                        notificationPopup.error("Nimaš izbranih komadov! Akcija ni izvedena!");
                        return;
                    }
                    if (SongboxTools.activeSongBox.length < 1) {
                        notificationPopup.error("Nimaš izbranih boxov! Akcija ni izvedena!");
                        return;
                    }

                    // Check if all items in same containers
                    for (var i = 1; i < selection.length; i++) {
                        var it0 = tbl.get(selection[0]);
                        var iti = tbl.get(selection[i]);
                        if (it0.box !== iti.box) {
                            notificationPopup.error("Komadi so v razlicnih boxih! Akcija ni uspela!");
                            return;
                        }
                    }

                    // Add items to all selected boxes
                    for (var i = 0; i < SongboxTools.activeSongBox.length; i++) {
                        var boxId = SongboxTools.activeSongBox[i];
                        SongboxTools.addSongsToBox(boxId, selection);
                    }
                }
            },
            { type: 'break', id: 'break2' },
            {
                type: 'button',
                id: 'remove',
                caption: 'Briši iz vseh boxov',
                img: 'fa fa-eraser fa-lg my-tbl-icon',
                onClick: function(itemname, object) {

                    object.preventDefault();

                    var selection = tbl.getSelection();

                    if (selection.length < 1) {
                        notificationPopup.error("Nimaš izbranih komadov! Akcija ni izvedena!");
                        return;
                    }

                    SongboxTools.removeSongsFromBox(null, selection);
                }
            }
        ]
    );

    tbl.onSongListLoaded = loadedCallback;

    tbl.dblClick = function(recid, ev) {

        if (SongboxTools.activeSongBox.length < 1) {
            notificationPopup.error("Nimaš izbranih boxov! Akcija ni izvedena!");
            return;
        }

        for (var i = 0; i < SongboxTools.activeSongBox.length; i++) {

            var boxId = SongboxTools.activeSongBox[i];
            var isInBox = SongboxTools.isSongInBox(recid, boxId);

            if (isInBox) {
                console.log("Remove song " + recid + " from box " + boxId);
                SongboxTools.removeSongsFromBox(boxId, [recid]);
            } else {
                console.log("Add song " + recid + " to box " + boxId);
                SongboxTools.addSongsToBox(boxId, [recid]);
            }

        }

    };

    tbl.loadSongList();
}

// Tools

function ajaxPost(options) {

    var url = 'api.php' + '?username=' + gl_username + '&' + options.urlParams;
    var json = JSON.stringify(options.data);
    if (json) {
        console.log(json);
    }

    $.ajax({
        type: "POST",
        dataType: "json",
        url: url,
        data: json,

        success: function(data) {

            //console.log(data);

            // callback                     
            if (options.onSuccess && options.onSuccess.constructor === Function) {
                options.onSuccess(data);
            } else if (options.onSuccess && options.onSuccess.constructor === Array) {

                for (var i = 0; i < options.onSuccess.length; i++) {
                    if (options.onSuccess[i] && options.onSuccess[i].constructor === Function) {
                        options.onSuccess[i](data);
                    }
                }
            }

            //setCommIconNormal();            
            if (!options.hideSuccessMsg) { notificationPopup.success(options.successMsg); }

        },
        error: function(data) {

            if (data.responseJSON && data.responseJSON.status == "Session not valid") {
                window.location.href = "login.php"
            }

            console.error(data);
            notificationPopup.error("Zgodila se je napaka");
            //setCommIconError();
        }
    });
}

function setPlayerSong(mp3, play, song_str) {

    var audio = document.getElementById('audioplayer');
    var mp3 = 'uploads/' + mp3;
    $("audio > source").attr('src', mp3).trigger("load");
    $("#topbar-current-song").html(song_str);

    audio.load();
    if (play) {
        audio.play();
    }
}