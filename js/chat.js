var href = document.location.href;
var poll = href + '/poll';
var chat = $('#chat');
var form = $('#form');
var message = $('#message');

message.focus();
chat.jScrollPane();
chat[0].scrollTo(chat.data('jScrollPaneMaxScroll'));

$.ajaxSetup({cache: false});

setInterval(function() {
    $.get(poll, function(data) {
        if (data.length == 0) return;
        var scroll = chat.data('jScrollPanePosition') == chat.data('jScrollPaneMaxScroll');
        chat.append(data).jScrollPane();
        if (scroll) chat[0].scrollTo(chat.data('jScrollPaneMaxScroll'));
    }, 'text');
}, 1500);

form.submit(function() {
    $.post(href, { message: message.val() });
    message.val('').focus();
    return false;
});

$('.smiley').click(function() {
    message.val($.trim(message.val().rtrim() + ' ' + $(this).attr('title')) + ' ').focus();
    return false;
});

$('.smiley').qtip({
    show: 'mouseover',
    hide: 'mouseout',
    position: {
        corner: {
            target: 'topRight',
            tooltip: 'bottomLeft'
        }
    }
});