<?php 
    session_start(); 
    require_once 'inc/head.php';
    $msg = file_get_contents('inc/welcome.html');
    require_once 'inc/executer.php';
    $executer = new Executer();
    $msg .= $executer->check_login();
    $_SESSION['msg'] = $msg;
    echo $msg;
    $path = '';
    $edittext = '';
    require_once 'inc/butt.php';
?>


