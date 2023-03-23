<?php
mb_internal_encoding("UTF-8");
mb_regex_encoding("UTF-8");
class Viewer
{
    private function __construct() { }
    
    static function message($message) {
        self::output(array(
            'clear'=>0,
            'message'=>'<div class="message">'.$message.'</div>',
            'path'=>''
        ));
    }
    
    static function error($error) {
        self::output(array(
            'clear'=>0,
            'message'=>'<div class="error">'.$error.'</div>',
            'path'=>''
        ));
    }
    
    static function content($content, $path) {
        $content = mb_ereg_replace('  ', ' ', $content);
        $path = mb_ereg_replace(' ', '&nbsp;', $path);
        
        self::output(array(
            'clear'=>1,
            'message'=>'<div id="loading"></div><div class="content" style="display:none">'.$content.'</div>',
            'path'=> $path
        ));
    }
    
    static function edit($post_id, $content) {
        self::output(array(
            'clear'=>0,
            'edit'=>1,
            'edittext'=>  $content,
            'path'=>'POST&nbsp;'.$post_id
        ));
    }

    static function output($data) {
        if (@$_GET['nojs'] == '1' || @$_POST['nojs'] == '1') {
            require_once 'head.php';
            $msg = ($data['clear']==0 && @$_SESSION['msg'] ? $_SESSION['msg'] : ''). $data['message'];
            $_SESSION['msg'] = $msg;
            echo $msg;
            $path = @$data['path'] ? $data['path'] : '';
            $edittext = isset($data['edittext']) ? $data['edittext'] : '';
            require_once 'butt.php';
        }
        else echo json_encode($data);
    }
}

?>
