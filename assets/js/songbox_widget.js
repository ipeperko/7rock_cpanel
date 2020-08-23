"use strict";

$.widget("7rock.songboxWidget", {

    options: {
        id: 0,
        raw_id: 0
    },

    _create: function() {

        var Self = this;

        Self.element.addClass("songbox_wrapper");

        $($("#template_songbox").html())
            .appendTo(Self.element);

        for (var i = 0; i < SongboxTools.activeSongBox.length; i++) {
            if (SongboxTools.activeSongBox[i] == Self.options.id) {
                Self.element.addClass('selected');
            }
        }

        Self.element.find(".songbox_id").html("(#" + Self.options.id + ")");

        Self.element.find(".songbox_main").click(function() {
            if (Self.element.hasClass("selected")) {
                Self.element.removeClass("selected");
                SongboxTools.removeActiveSongBox(Self.options.id);
            } else {
                Self.element.addClass("selected");
                SongboxTools.addActiveSongBox(Self.options.id);
            }
        });

        Self.element.find(".songbox_footrow").click(function() {
            editBoxPopup(Self.options.id, Self.getName());
        });

        Self._colorizeByRawValue(Self.options.raw_id);
    },

    setActive: function(active) {

        if (active === true) {
            this.element.addClass('selected');
        } else {
            this.element.removeClass('selected');
        }
    },

    getId: function() {
        return this.options.id;
    },
    getName: function() {
        return this.element.find(".songbox_name").html();
    },
    getColor: function() {
        return this.element.css("background-color");
    },

    setName: function(name) {
        this.element.find(".songbox_name").html(name);
    },
    setOwner: function(owner) {
        this.element.find(".songbox_owner").html(owner);
    },
    setCount: function(count) {
        this.element.find(".songbox_count").html("&#931; " + count);
    },
    _colorizeByRawValue: function(val) {

        val += 1;

        var r, g, b;

        // red
        if (val % 6 < 3) r = 102;
        else r = 255;


        // green  
        if (val % 36 < 6) g = 0;
        else if (val % 36 < 12) g = 51;
        else if (val % 36 < 18) g = 102;
        else if (val % 36 < 24) g = 153;
        else if (val % 36 < 30) g = 204;
        else g = 255;

        // blue
        b = (val % 3) * 102;

        var rgb_string = "rgba(" + r + "," + g + "," + b + ", 0.5)";

        this.element.css({
            "background-color": rgb_string
        });
    },
});