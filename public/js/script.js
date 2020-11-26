if (typeof CodeMirror != "undefined" && CodeMirror.colorize) {
    CodeMirror.colorize();
}

if (typeof ClipboardJS != 'undefined') {
    newClipboard('div.code .buttons .copy');
}

function newClipboard(selector) {
    var timer;
    var clipboard = new ClipboardJS(selector,
        {
            text: function(trigger) {
                var text = $(trigger).parents('.buttons').nextAll('.code').text();
                text = text.replace(/\\n/g, "\\r\\n");
                return text;
            }
        }
    );

    clipboard.on('success', function(event) {
        var button = $(event.trigger);
        button.addClass('copied');
        button.text('copied');
        window.clearTimeout(timer);
        timer = window.setTimeout(function() {
            button.removeClass('copied');
            button.text('copy');
        }, 3000);
    });

    clipboard.on('error', function(event) {
        var button = $(event.trigger);
        button.text('Unable to copy, sorry. Please copy manually.');
        window.clearTimeout(timer);
        timer = window.setTimeout(function() {
            button.text('copy');
        }, 3000);
    });
}

$(function ($) {
    $('div.run li a').on('click', function(event) {
        var button = $(this);
        if (button.hasClass('disabled')) {
            event.preventDefault();
            return;
        }

        var demoTabPanel = $('.demoTabPanel');

        var clickedHash = button.get(0).hash;
        $('div.run li a').each(function() {
            var hash = this.hash;
            $(this).toggleClass('inactive', hash !== clickedHash);
            var h = hash.substring(1);
            if (hash === clickedHash) {
                $('.step.' + h, demoTabPanel).show().trigger('show');
                var ifrm = $('.step.' + h + ' iframe', demoTabPanel);
                if (ifrm.data('src') && ifrm.attr('src') !== ifrm.data('src') || h === 'execute') {
                    ifrm.attr('src', ifrm.data('src'));
                }
            } else {
                $('.step.' + h, demoTabPanel).hide();
            }
        });

        event.preventDefault();
    });

    $('div.run li:first a, div.run li:first a').click();
});