(function() {

    function getColor(level) {
        switch (level) {
            case 'warning':
            case 'notice':
                return '#B23B13';
            case 'error':
                return 'red';
            default:
                return 'black';
        }
    }

    function getMethod(level) {
        switch (level) {
            case 'warning':
            case 'notice':
                return 'warn';
            case 'error':
                return 'error';
            default:
                return 'log';
        }
    }

    function getStyle(color, bold) {
        var style = 'color:' + color;
        if (bold) {
           style += ';font-weight:bold';
        }
        return style;
    }


    if (window.phpMessages && window.console) {
        console.group("PHP error messages");
        window.phpMessages.forEach(function (msg) {
            var level = msg.level;
            var message =  '%c' + msg.type + ':' + ' %c' + msg.message + ' %cin %c' + msg.file;
            var params = [
                getStyle(getColor(level)),
                getStyle('blue', true),
                getStyle('grey'),
                getStyle('green')
            ];
            if (msg.line) {
               message += ' %cat line %c' + msg.line;
               params.push(getStyle('grey'));
               params.push(getStyle('green'));
            }
            params.unshift(message);
            console[getMethod(level)].apply(console, params);
        });
        console.groupEnd();
    }
})();
