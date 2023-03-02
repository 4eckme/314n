<?php 
    session_start(); 
    require_once 'head.php';
?>
<br>Welcome to...
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&lt;-.&nbsp;(`-')_&nbsp;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\(&nbsp;OO)&nbsp;)<br>
&nbsp;.----.&nbsp;&nbsp;.--.&nbsp;&nbsp;&nbsp;.---.&nbsp;,--./&nbsp;,--/&nbsp;<br>
\_.-,&nbsp;&nbsp;|/_&nbsp;&nbsp;|&nbsp;&nbsp;/&nbsp;.&nbsp;&nbsp;|&nbsp;|&nbsp;&nbsp;&nbsp;\&nbsp;|&nbsp;&nbsp;|&nbsp;<br>
&nbsp;&nbsp;|_&nbsp;&nbsp;<&nbsp;&nbsp;|&nbsp;&nbsp;|&nbsp;/&nbsp;/|&nbsp;&nbsp;|&nbsp;|&nbsp;&nbsp;.&nbsp;'|&nbsp;&nbsp;|)<br>
.-.&nbsp;\&nbsp;&nbsp;|&nbsp;|&nbsp;&nbsp;|/&nbsp;'-'&nbsp;&nbsp;|||&nbsp;&nbsp;|\&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;<br>
\&nbsp;`-'&nbsp;&nbsp;/&nbsp;|&nbsp;&nbsp;|`---|&nbsp;&nbsp;|'|&nbsp;&nbsp;|&nbsp;\&nbsp;&nbsp;&nbsp;|&nbsp;<br>
&nbsp;`---''&nbsp;&nbsp;`--'&nbsp;&nbsp;&nbsp;&nbsp;`--'&nbsp;`--'&nbsp;&nbsp;`--'&nbsp;<br>

<br><br>Type "HELP" to get started.<br><br><br>

<?php
  require_once 'executer.php';
  $executer = new Executer();
  $executer->check_login();
  $path = '';
  $edittext = '';
  require_once 'butt.php';
?>


