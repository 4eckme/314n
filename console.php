<?php

require_once 'inc/viewer.php';
require_once 'inc/executer.php';

class Console
{
    private $command;
    private $rules;
    private $error;
    
    private function init_rules() {
        $this->rules = array(
                 'HELP' => array(
                    'args' => array(),
                    'session' => array(0=>array(), 1=>array())
                 ),
                'DONATE' => array(
                    'args' => array(),
                    'session' => array(0=>array(), 1=>array())
                 ),
                'REGISTER' => array(
                    'args' => array('u'=>'username', 'p'=>'password'),
                    'session' => array(0=>array('user_id'), 1=>array())
                 ),
                'LOGIN' => array(
                    'args' => array('u'=>'username', 'p'=>'password'),
                    'session' => array(0=>array('user_id'), 1=>array())
                ),
                'LOGOUT' => array(
                    'args' => array(),
                    'session' => array(0=>array(), 1=>array('user_id'))
                ),
                'INVITES' => array(
                    'args' => array(),
                    'session' => array(0=>array(), 1=>array('user_id'))
                ),
                'NEWTOPIC' => array(
                    'args' => array('t'=>'title', 'c'=>'content'),
                    'session' => array(0=>array(), 1=>array('user_id', 'board_id'))
                ),
                'TOPIC' => array(
                    'args' => array('n'=>'number'),
                    'session' => array(0=>array(), 1=>array('user_id'))
                ),
                'REPLY' => array(
                    'args' => array('m'=>'message'),
                    'session' => array(0=>array(), 1=>array('user_id', 'board_id', 'topic_id'))
                ),
                'DELETE' => array(
                    'args' => array('p'=>'post'),
                    'session' => array(0=>array(), 1=>array('user_id'))
                ),
                'EDIT' => array(
                    'args' => array('p'=>'post'),
                    'session' => array(0=>array(), 1=>array('user_id'))
                ),
                'TIMEZONE' => array(
                    'args' => array('u'=>'UTC'),
                    'session' => array(0=>array(), 1=>array('user_id'))
                ),
                'FIRST' => array(
                    'args' => array(),
                    'session' => array(0=>array(), 1=>array('user_id', 'page'))
                ),
                'LAST' => array(
                    'args' => array(),
                    'session' => array(0=>array(), 1=>array('user_id', 'page'))
                ),
                'NEXT' => array(
                    'args' => array(),
                    'session' => array(0=>array(), 1=>array('user_id', 'page'))
                ),
                'PREV' => array(
                    'args' => array(),
                    'session' => array(0=>array(), 1=>array('user_id', 'page'))
                ),
                'PAGE' => array(
                    'args' => array('p'=>'page'),
                    'session' => array(0=>array(), 1=>array('user_id', 'page'))
                ),
                'BOARDS' => array(
                    'args' => array(),
                    'session' => array(0=>array(), 1=>array('user_id'))
                ),
                'BOARD' => array(
                    'args' => array('n'=>'number'),
                    'session' => array(0=>array(), 1=>array('user_id'))
                ),
                'RVT' => array(
                    'args' => array(),
                    'session' => array(0=>array(), 1=>array('user_id'))
                ),
                'REFRESH' => array(
                    'args' => array(),
                    'session' => array(0=>array(), 1=>array('user_id', 'view'))
                ),
        );
    }
    
    private function check_command() {
        if (!array_key_exists($this->command['command'], $this->rules)) {
            $this->error = 'unidentified command "'.$this->command['command'].'"';
            return false;
        }

        foreach ($this->rules[$this->command['command']]['args'] as $key => $name) {
            if (!array_key_exists($key, $this->command['args'])) {
                $this->error = 'the argument "'.$name.'" must be specified';
                return false;
            }
        }
        
        foreach ($this->rules[$this->command['command']]['session'][0] as $variable) {
            if ($_SESSION[$variable]) {
                $this->error = '"'.$this->command['command'].'" is not a valid command or is not available in the current context';
                return false;
            }
        }
        
        foreach ($this->rules[$this->command['command']]['session'][1] as $variable) {
            if (!$_SESSION[$variable]) {
                $this->error = '"'.$this->command['command'].'" is not a valid command or is not available in the current context';
                return false;
            }
        }
        
    }
    
    public function __construct() {
        $this->init_rules();
        session_start();
        
        if (@$_SESSION['edit']) {
            $input = (!isset($_POST['input_i']) || (isset($_POST['multiline']) && $_POST['multiline']==1))
              ? $_POST['input']
              : $_POST['input_i'];

            $newcontent = str_replace('  ', ' ', htmlspecialchars(trim(addslashes((string)$input))));
            
            $executer = new Executer();
            $executer->edit_post($_SESSION['edit'], $newcontent);
            
            unset($_SESSION['edit']);
            exit;
        }
    }
    
    public function parse($input) {
        $input = str_replace('  ', ' ', htmlspecialchars(trim(addslashes((string)$input))));
        list($command) = explode(' ', $input);
        $command = mb_strtoupper($command);
        $argstr = substr($input, strlen($command));
        
        $keys = array();
        preg_match_all('/ -[a-zA-Z] /', $argstr, $keys);
        $values = (preg_split('/ -[a-zA-Z] /', substr($argstr, 4)));

        $this->command = array('command'=>$command, 'args'=>array());
        for ($i = 0; $i < count($keys[0]); $i++) {
            $key = $keys[0][$i][2];
            if (strlen($values[$i]))
                $this->command['args'][$key] = $values[$i];
        }
    }
    
    public function execute() {
        $this->check_command();        
        if (!$this->error) {
            $executer = new Executer();
            $executer->execute($this->command);
        } else {
            Viewer::error($this->error);
        }
    }
}

    $console = new Console();
    $input = (!isset($_POST['input_i']) || (isset($_POST['multiline']) && $_POST['multiline']==1))
      ? $_POST['input']
      : $_POST['input_i'];
    $console->parse($input);
    $console->execute();

?>
