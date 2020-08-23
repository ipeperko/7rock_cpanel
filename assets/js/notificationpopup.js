var notificationPopup = {

    options: {
        // custom message text
        //text: '',

        // custom toast title
        //heading: '',

        // show/hide transition effects.
        // fade, slide or plain.
        showHideTransition: 'slide',

        // show a close icon
        allowToastClose: true,

        // auto hide after a timeout
        hideAfter: 3000,

        // loader
        loader: false,
        //loaderBg: '#9EC600',

        // stack length
        stack: 5,

        position: 'bottom-right',

        // background color
        //bgColor: '#444',

        // custom text color
        //textColor: '#eee',

        // custom text align
        //textAlign: 'left',

        // callback functions.
        //beforeShow: function () {},
        //afterShown: function () {},
        //beforeHide: function () {},
        //afterHidden: function () {}
    },

    success: function(msg) {

        var opt = notificationPopup.options;
        opt.text = msg;
        opt.heading = "OK";
        opt.bgColor = '#2bc5f4';

        $.toast(opt);
    },

    critical: function(msg) {

        var opt = notificationPopup.options;
        opt.text = msg;
        opt.heading = "Error";
        opt.bgColor = 'rgba(255,51,51,0.9)';

        $.toast(opt);
    },

    alert: function(msg) {

        var opt = notificationPopup.options;
        opt.text = msg;
        opt.heading = "Opozorilo";
        opt.bgColor = 'rgba(220,144,29,0.9)';

        $.toast(opt);
    },

    error: function(msg) {
        this.critical(msg);
    }
};