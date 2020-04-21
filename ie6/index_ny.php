<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>314n</title>
	<meta name="keywords" content="" />
	<meta name="description" content="" />
    
        
        <link id="favicon" rel="shortcut icon" href="f1.png" type="image/x-icon" />
	<link rel="stylesheet" href="css/styles.css" type="text/css" media="screen, projection" />
	<link rel="stylesheet" type="text/css" href="css/scrollbars.css" />
	
        <!--<link rel="stylesheet" type="text/css" href="css/scrollbars-itunes.css" />-->
        <!--[if lt IE 9]><script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
        <!--[if lte IE 6 ]><script type="text/javascript">window.location.href="ie6/index_ru.html";</script><![endif]-->
		
        <script src="js/jquery.min.js"></script>
	<script src="js/jquery.event.drag-2.0.min.js"></script>
	<script src="js/jquery.ba-resize.min.js"></script>
	<script src="js/mousehold.js"></script>
	<script src="js/jquery.mousewheel.js"></script>
	<script src="js/snow.js"></script>
        <script src="js/aplweb.scrollbars2.js"></script>
        
        <script src="js/scroll-startstop.events.jquery.js" type="text/javascript"></script>
        <script src="js/function_up_down.js" type="text/javascript"></script>
	
        <script>
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
		$("#scrollTest").scrollbars();
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
	</script>
</head>

<body>
<div class="border"></div>
<div id="board">
    <div id="wrapper">
    <div class="fone"></div>
    	<div id="scrollTest">
            <div style="width: 100%; position: relative" id="console">
                <div id="content">
<br>Welcome to...
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&lt;-.&nbsp;(`-')_&nbsp;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\(&nbsp;OO)&nbsp;)<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp/&nbsp;,--/&nbsp;<br>
 pd*"*b.  ,pP""Yq.  `7MM   .d*"*bg. <br>
 (O)   j8 6W'    `Wb   MM  6MP    Mb <br>
     ,;j9 8M      M8   MM  YMb    MM <br>
      ,-='    YA.    ,A9   MM   `MbmmdM9 <br>
      Ammmmmmm  `Ybmmd9'  .JMML.      .M' <br>
                                    .d9   <br>
                                                                m"'     <br>

<br><br>Type "HELP" to get started.<br><br><br>

<?php
  require_once 'executer.php';
  $executer = new Executer();
  $executer->check_login();
?>
                </div>
                <a name="end"></a>
                <table class="line"><tr>
                   <td id="path">&gt;&nbsp;</td>
                   <td><textarea id="cmd"></textarea></td>
                </tr></table>
            </div>
    	</div>
    </div>
</div>


</body>
</html>
