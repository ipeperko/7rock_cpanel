"use strict";

var SongboxTools = {

    songboxes: [],
    activeSongBox: [],

    addActiveSongBox: function(id) {

        var exist = false;
        for (var i = 0; i < SongboxTools.activeSongBox.length; i++) {
            if (SongboxTools.activeSongBox[i] == id) {
                exist = true;
                break;
            }
        }

        if (!exist) {
            SongboxTools.activeSongBox.push(id);
        }

        if (SongboxTools.activeSongBox.length > 1) {
            notificationPopup.alert("Izbranih je več boxov! Pazi!!!");
        }
    },

    removeActiveSongBox: function(id) {

        for (var i = 0; i < SongboxTools.activeSongBox.length; i++) {
            if (SongboxTools.activeSongBox[i] == id) {
                SongboxTools.activeSongBox.splice(i, 1);
                break;
            }
        }
    },

    // @direction: "left" / "right"
    switchActiveSongBox: function(direction) {

        var activeBoxes = SongboxTools.activeSongBox;
        var songboxes = SongboxTools.songboxes;

        if (activeBoxes.length == 0) {

            if (songboxes.length > 0) {
                activeBoxes.push(songboxes[0].id);
            }

        } else if (activeBoxes.length > 1) {
            console.log("Cannot shift active box if more than 1 selected");
            return;
        } else {

            if (!direction) {
                console.error("No direction for active box shift");
                return;
            }

            var activeId = activeBoxes[0];

            if (direction == "right") {

                for (var i = 0; i < songboxes.length; i++) {

                    if (songboxes[i].id == activeId) {

                        if (i === songboxes.length - 1) {
                            activeId = songboxes[0].id;
                            break;
                        } else {
                            activeId = songboxes[i + 1].id;
                            break;
                        }

                    }
                }

            } else if (direction == "left") {

                for (var i = 0; i < songboxes.length; i++) {

                    if (songboxes[i].id == activeId) {

                        if (i === 0) {
                            activeId = songboxes[songboxes.length - 1].id;
                            break;
                        } else {
                            activeId = songboxes[i - 1].id;
                            break;
                        }

                    }
                }

            } else {
                console.error("No direction for active box shift");
                return;
            }

            activeBoxes[0] = activeId;

        }


        for (var i = 0; i < songboxes.length; i++) {

            var id = songboxes[i].id;
            var $box = $(SongboxTools.findSongboxWidgetById(id));

            if (!$box) {
                continue;
            }

            if (id == activeBoxes[0]) {
                $box.songboxWidget("setActive", true);
            } else {
                $box.songboxWidget("setActive", false);
            }
        }

    },

    getData: function() {
        ajaxPost({
            urlParams: "q=songboxdata",
            onSuccess: SongboxTools.songboxesInit,
            successMsg: 'Pridobil boxe'
        });
    },

    findSongboxWidgetById: function(id) {

        console.log("Find songbox " + id);

        var boxItem = null;

        $(".songbox_wrapper").each(function(i) {

            if (!$(this).songboxWidget) {
                console.log("NO WIDGET!!!");
            } else {
                if (id == $(this).songboxWidget('getId')) {
                    boxItem = this;
                    return false;
                }
            }
        });

        if (!boxItem) {
            console.log("Box not found!!!");
        }

        return boxItem;
    },

    findSongboxById: function(id) {
        console.log("Find songbox data " + id);

        for (var i = 0; i < SongboxTools.songboxes.length; i++) {
            var _id = SongboxTools.songboxes[i].id;
            if (_id == id) {
                return SongboxTools.songboxes[i];
            }
        }
        return null;
    },

    isSongInBox: function(songId, boxId) {

        var box = SongboxTools.findSongboxById(boxId);
        if (!box) {
            console.error("No box id " + boxId);
            return;
        }

        for (var i = 0; i < box.songs.length; i++) {

            if (box.songs[i] == songId) {
                return true;
            }
        }

        return false;
    },

    songboxesInit: function(data) {

        SongboxTools.songboxes = data.songboxes;
        console.log(SongboxTools.songboxes);

        // Clear all existing data
        $("#playlists-body #pl_selectable").empty();

        // Add box widget
        for (var i = 0; i < SongboxTools.songboxes.length; i++) {
            var box = SongboxTools.songboxes[i];
            var $f = $("<li>");
            $f.songboxWidget({ id: box.id, raw_id: i });
            $f.appendTo($("#playlists-body #pl_selectable"));
            $f.songboxWidget("setName", box.name);
            $f.songboxWidget("setOwner", box.owner);
        }

        // Set count values
        for (var i = 0; i < SongboxTools.songboxes.length; i++) {
            var box = SongboxTools.songboxes[i];
            var id = box.id;
            var boxWidget = SongboxTools.findSongboxWidgetById(id);
            $(boxWidget).songboxWidget("setCount", box.songs.length);

        }

        // Add + button
        $("<li>", {
                class: "add-box-button"
            })
            .html('<i class="fa fa-plus-circle" aria-hidden="true"></i> Dodaj box')
            .click(addNewBoxPopup)
            .appendTo("#playlists-body #pl_selectable");


        // Colorize main table
        SongboxTools.colorizeSongs();

        window.onkeyup = function(e) {
            var key = e.keyCode ? e.keyCode : e.which;

            if (key === 49 && e.ctrlKey === true) {
                SongboxTools.switchActiveSongBox("left");
            } else if (key === 50 && e.ctrlKey === true) {
                SongboxTools.switchActiveSongBox("right");
            }
        }
    },

    addSongsToBox: function(boxId, songIds) {

        ajaxPost({
            urlParams: "q=addtosongbox",
            data: {
                songbox_id: boxId,
                song_ids: songIds
            },
            successMsg: "Dodal komade v box " + boxId,
            onSuccess: function() {

                var box = SongboxTools.findSongboxById(boxId);

                for (var i = 0; i < songIds.length; i++) {
                    box.songs.push(songIds[i]);
                }

                var $box = $(SongboxTools.findSongboxWidgetById(boxId));
                $box.songboxWidget("setCount", box.songs.length);

                tabelaKomadi().selectNone();
                SongboxTools.colorizeSongs();
            },
        });
    },

    removeSongsFromBox: function(boxId, songIds) {

        ajaxPost({
            urlParams: "q=removefromsongbox",
            data: {
                songbox_id: boxId,
                song_ids: songIds
            },
            successMsg: "Izbrisal komade iz boxa " + boxId,
            onSuccess: function() {

                if (boxId === null) {

                    tabelaKomadi().loadSongList();
                    SongboxTools.songboxesInit();

                } else {

                    var box = SongboxTools.findSongboxById(boxId);

                    for (var i = 0; i < songIds.length; i++) {
                        SongboxTools._deleteFromArray(box.songs, songIds[i]);
                    }

                    var $box = $(SongboxTools.findSongboxWidgetById(boxId));
                    $box.songboxWidget("setCount", box.songs.length);

                }

                tabelaKomadi().selectNone();
                SongboxTools.colorizeSongs();
            },
        });
    },

    _deleteFromArray: function(arr, val) {

        for (var i = 0; i < arr.length; i++) {
            if (arr[i] == val) {
                arr.splice(i, 1);
            }
        }

    },

    colorizeSongs: function() {

        var songboxes = SongboxTools.songboxes;
        var tbl = tabelaKomadi();

        // Reset colors in main table        
        for (var i = 0; i < tbl.records.length; i++) {
            //tbl.records[i].style = "background-color: #C2F5B4";
            if (tbl.records[i].style) {
                delete tbl.records[i].style;
            }
            if (tbl.records[i].box) {
                delete tbl.records[i].box;
            }
            if (tbl.records[i].info) {
                delete tbl.records[i].info;
            }
        }

        // Colorize songs
        for (var i = 0; i < songboxes.length; i++) {

            var box = songboxes[i];
            var boxId = box.id;
            var boxName = box.name;
            var $box = $(SongboxTools.findSongboxWidgetById(boxId));
            var color = $box.songboxWidget("getColor");

            console.log("Colorizing songs in box " + boxId + " color:" + color);

            // Colorize song by song 
            for (var j = 0; j < songboxes[i].songs.length; j++) {

                var songId = songboxes[i].songs[j];
                //console.log(tbl.get(songId));                
                var rec = tbl.get(songId);

                if (rec.box) {
                    tbl.set(songId, { box: tbl.get(songId).box + "," + boxName });
                    tbl.set(songId, { info: '<i class="fa fa-bullhorn" aria-hidden="true"></i>' });

                    if (rec.style) {
                        delete rec.style;
                    }
                    rec.style = 'color: blue;'

                } else {
                    rec.style = "background-color:" + color + ";";
                    tbl.set(songId, { box: boxName });
                }
            }
        }

        tbl.refresh();

    },
};

function addNewBoxPopup() {

    if (!w2ui.foo) {
        $().w2form({
            name: 'foo',
            style: 'border: 0px; background-color: transparent;',
            formHTML: '<div class="w2ui-page page-0">' +
                '    <div class="w2ui-field">' +
                '        <label>Ime boxa:</label>' +
                '        <div>' +
                '           <input name="ime" type="text" maxlength="100" style="width: 250px"/>' +
                '        </div>' +
                '    </div>' +

                '</div>' +
                '<div class="w2ui-buttons">' +
                '    <button class="btn" name="ok">Ok</button>' +
                '</div>',
            fields: [
                { field: 'ime', type: 'text', required: true },
            ],
            record: {
                ime: 'Nov box',
            },
            actions: {
                "ok": function() { this.validate(); },
            },
            validate: function() {

                ajaxPost({
                    urlParams: "q=songboxadd",
                    data: {
                        name: this.record.ime,
                    },
                    successMsg: "Dodal box " + this.record.ime,
                    onSuccess: function() {
                        SongboxTools.getData();
                    },
                });

                w2popup.close();
            }
        });
    }
    $().w2popup('open', {
        title: 'Ustvari nov box',
        body: '<div id="form" style="width: 100%; height: 100%;"></div>',
        style: 'padding: 15px 0px 0px 0px',
        width: 500,
        height: 300,
        showMax: true,
        onToggle: function(event) {
            $(w2ui.foo.box).hide();
            event.onComplete = function() {
                $(w2ui.foo.box).show();
                w2ui.foo.resize();
            }
        },
        onOpen: function(event) {
            event.onComplete = function() {
                // specifying an onOpen handler instead is equivalent to specifying an onBeforeOpen handler, which would make this code execute too early and hence not deliver.
                $('#w2ui-popup #form').w2render('foo');
            }
        }
    });
}

function editBoxPopup(boxId, boxName) {

    if (!w2ui.boxedit) {
        $().w2form({
            boxId: boxId,
            boxName: boxName,
            name: 'boxedit',
            style: 'border: 0px; background-color: transparent;',
            formHTML: '<div class="w2ui-page page-0">' +
                '    <div class="w2ui-field">' +
                '        <label>Ime boxa:</label>' +
                '        <div>' +
                '           <input name="ime" type="text" maxlength="100" style="width: 250px"/>' +
                '        </div>' +
                '    </div>' +
                '    <div class="w2ui-field">' +
                '       <label>Briši box' +
                '       </label>' +
                '       <div>    <input id="check_delete_songbox" name="check_delete_songbox" type="checkbox"> ' +
                '           <span style="color:red;" hidden id="delete_songbox_warning"> PAZI, BOX BO IZBRISAN!!! </span>   </div>' +
                '    </div>' +
                '</div>' +
                '<div class="w2ui-buttons">' +
                '    <button class="btn" name="ok">Ok</button>' +
                '</div>',


            fields: [
                { field: 'ime', type: 'text', required: false },
                { field: 'check_delete_songbox', type: 'checkbox', required: false },
            ],
            record: {
                ime: boxName,
                check_delete_songbox: false
            },
            actions: {
                "ok": function() { this.validate(); },
                "check_delete_songbox": function() {
                    console.log("Checkkkkkk");
                }
            },
            onChange: function(event) {

                if (event.target === 'check_delete_songbox') {
                    console.log(event.value_new);

                    if (event.value_new === true) {
                        $("#delete_songbox_warning").show();
                    } else {
                        $("#delete_songbox_warning").hide();
                    }

                }
            },
            validate: function() {

                if (document.getElementById("check_delete_songbox").checked) {
                    console.log("Deleting box " + this.boxId);

                    ajaxPost({
                        urlParams: "q=songboxdelete",
                        data: {
                            songbox_id: this.boxId
                        },
                        successMsg: "Izbrisal box " + this.record.ime,
                        onSuccess: function() {
                            SongboxTools.getData();
                        },
                    });

                } else {

                    if (this.boxName && this.boxName === this.record.ime) {
                        console.log("No changes " + this.boxName + " vs " + this.record.ime);
                    } else {
                        console.log("Changing name " + this.boxName + " -> " + this.record.ime);

                        ajaxPost({
                            urlParams: "q=songboxrename",
                            data: {
                                name: this.record.ime,
                                songbox_id: this.boxId
                            },
                            successMsg: "Spremenil ime boxa " + this.record.ime,
                            onSuccess: function() {
                                SongboxTools.getData();
                            },
                        });
                    }

                }

                w2popup.close();
            }
        });
    } else {

        w2ui.boxedit.record.ime = boxName;
        w2ui.boxedit.record.check_delete_songbox = false;
        w2ui.boxedit.boxId = boxId;
        w2ui.boxedit.boxName = boxName;
        console.log(w2ui.boxedit.record.ime);

    }
    $().w2popup('open', {
        title: 'Uredi box ' + boxName + ' (#' + boxId + ')',
        body: '<div id="form" style="width: 100%; height: 100%;"></div>',
        style: 'padding: 15px 0px 0px 0px',
        width: 500,
        height: 300,
        showMax: true,
        onToggle: function(event) {
            $(w2ui.boxedit.box).hide();
            event.onComplete = function() {
                $(w2ui.boxedit.box).show();
                w2ui.boxedit.resize();
            }
        },
        onOpen: function(event) {
            event.onComplete = function() {
                // specifying an onOpen handler instead is equivalent to specifying an onBeforeOpen handler, which would make this code execute too early and hence not deliver.
                $('#w2ui-popup #form').w2render('boxedit');
            }
        }
    });

}