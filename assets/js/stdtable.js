function createStdTable($wrapper, name) {

    $wrapper.w2grid({
        name: name,

        show: {
            toolbar: true,
            footer: true,
            selectColumn: true,
            toolbarDelete: true,
        },

        multiSelect: true,

        searches: [
            { field: 'recid', caption: 'ID ', type: 'int' },
            { field: 'artist', caption: 'Izvajalec', type: 'text' },
            { field: 'title', caption: 'Naslov', type: 'text' },
            { field: 'album', caption: 'Album', type: 'text' },
            { field: 'owner', caption: 'Uporabnik', type: 'text' },
            { field: 'uploaded', caption: 'Naložil', type: 'text' },
            { field: 'box', caption: 'Box', type: 'text' },
        ],
        columns: [
            { field: 'recid', caption: 'ID', size: '40px', sortable: true, attr: 'align=center', hidden: true },
            {
                field: 'playrec',
                caption: '',
                size: '30px',
                render: function() {
                    return '<i class="table_play_btn fa fa-play fa-lg" ></i>';
                },
                attr: 'align=center',
                style: 'cursor: pointer;',
                editable: false
            },
            { field: 'info', caption: 'Info', size: '20px', sortable: true, hidden: false },
            { field: 'filename', caption: 'File', size: '100%', hidden: true },
            { field: 'dir', caption: 'Directory', size: '100%', hidden: true },
            { field: 'artist', caption: 'Izvajalec', size: '30%', sortable: true },
            { field: 'title', caption: 'Naslov', size: '30%', sortable: true },
            { field: 'album', caption: 'Album', size: '80px', sortable: true },
            { field: 'year', caption: 'Leto', size: '40px', sortable: true, attr: 'align=center' },
            { field: 'owner', caption: 'Uporabnik', size: '40px', sortable: true, hidden: false },
            { field: 'uploaded', caption: 'Naložil', size: '130px', sortable: true, hidden: false },
            { field: 'box', caption: 'Box', size: '80px', sortable: true, hidden: false },
        ],

        onReload: function() {
            this.loadSongList();
        },

        onDelete: function(event) {
            var selection = this.getSelection();
            event.onComplete = function() {

                ajaxPost({
                    urlParams: "q=remove_songs",
                    data: {
                        songs: selection
                    },
                    successMsg: "Izbrisal komade",
                    onSuccess: function() {
                        SongboxTools.getData();
                    }
                });
            };
        },

        onClick: function(recid, ev) {

            var preventDefault = false;

            if (ev.column === 1) { // player
                preventDefault = true;
                var item = this.get(ev.recid);
                var mp3 = item.dir + '/' + item.filename;
                var str = item.artist + " - " + item.title;
                setPlayerSong(mp3, true, str);
            } else if (ev.originalEvent.type !== 'click') {
                preventDefault = true;
            }

            if (preventDefault) {
                ev.preventDefault();
                return;
            }

        },

        onSongListLoaded: function() {},

        loadSongList: function() {

            console.log("Loading song list");
            var Self = this;

            this.load('api.php?q=songlistw2ui&username=' + gl_username, function(data) {

                console.log(data);
                notificationPopup.success("Pridobil komade");

                Self.onSongListLoaded();

                if (data && data.records && data.records.length > 0) {
                    var song_str = data.records[0].artist + " - " + data.records[0].title;
                    setPlayerSong(data.records[0].dir + '/' + data.records[0].filename, false, song_str);
                }
            });
        },
    });

    return w2ui[name];
}