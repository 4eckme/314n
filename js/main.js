var commands = [];
var command_number;

function animate_favicon () {
    if ($('link[rel$=icon]').attr("href") == 'f1.png') {
        $('link[rel$=icon]').remove();
        $('head').append($('<link rel="shortcut icon" type="image/x-icon"/>').attr( 'href', "f2.png" ) );
    } else {
        $('link[rel$=icon]').remove();
        $('head').append($('<link rel="shortcut icon" type="image/x-icon"/>').attr( 'href', "f1.png" ) );
    }
    
    setTimeout(animate_favicon, 600);
}

setTimeout(animate_favicon, 600);

function loading () {
    str = 'Loading...';
    nextchar = str.charAt($('#loading').html().length);
    if ($('#loading').html().length < 10) {
        $('#loading').html($('#loading').html()+nextchar);
        setTimeout(loading, 40);
    } else {
        $('.content').css('display', 'block');
        nav_down();
    }
}

$(window).resize(function () {  
    $('#console').width($("#scrollTest").width()-50);
});
  
$(document).ready(function() {
    // Check for styled scrollbar support
    var $st = $("#scrollTest")
    , cst = getComputedStyle($st[0], '::-webkit-scrollbar')
    if(!((CSS.supports('scrollbar-width: thin') || (cst.hasOwnProperty('width') && cst.width == '9px')))) {
        console.log('Native scrollbars not supported')
        $('body').removeClass('native-scrollbar')
        $st.scrollbars()
    }
    $('#console').width($("#scrollTest").width()-50);
    $('#cmd').keydown(function (event) {
    
        if (event.keyCode == 38) {
            if (command_number > 0) {
                if (command_number == commands.length)
                    commands.push($('#cmd').val());
                command_number--;
                $('#cmd').val(commands[command_number]);
            }
            
            return false;
        }

        if (event.keyCode == 40) {
            if (command_number < commands.length - 1) {
                command_number++;
                $('#cmd').val(commands[command_number]);
            }
            
            return false;
        }

        if (event.keyCode==13 && !event.shiftKey) {
            commands.push($('#cmd').val());
            command_number = commands.length;
            var command = $('#cmd').val();
            $('#cmd').val('');
            $.ajax({
                type: "POST",
                url: "console.php",
                dataType: "json",
                data: { input: command },
                success: function (response) {
                    if (response.edit) {
                        $('#path').html(response.path+'&nbsp;>&nbsp;');
                        $('#cmd').val(response.edittext);
                    } else {
                        if (response.clear) $('#content').html('');                                
                        $('#content').append(response.message);
                        if (response.path) $('#path').html(response.path+'&nbsp;>&nbsp;');
                        if (response.clear) loading();
                        else nav_down();
                    }
                }
            });
            return false;
        }
    });
});