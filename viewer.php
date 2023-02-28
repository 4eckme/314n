<?php
mb_internal_encoding("UTF-8");
mb_regex_encoding("UTF-8");
class Viewer
{
    private function __construct() { }
    
    static function message($message) {
        
        echo json_encode(array(
                'clear'=>0,
                'message'=>'<div class="message">'.$message.'</div>',
                'path'=>''
        ));
    }
    
    static function error($error) {
        
        echo json_encode(array(
                'clear'=>0,
                'message'=>'<div class="error">'.$error.'</div>',
                'path'=>''
        ));
    }
    
    static function content($content, $path) {
        //$content = mb_convert_encoding($content, 'utf-8', 'ASCII');
        $content = mb_ereg_replace('  ', ' ', $content);
        $path = mb_ereg_replace(' ', '&nbsp;', $path);
        
        echo json_encode(array(
                'clear'=>1,
                'message'=>'<div id="loading"></div><div class="content" style="display:none">'.$content.'</div>',
                'path'=> $path
        ));
    }
    
    static function edit($post_id, $content) {
        echo json_encode(array(
                'clear'=>0,
                'edit'=>1,
                'edittext'=>  $content,
                'path'=>'POST&nbsp;'.$post_id
        ));
    }
}

?>
