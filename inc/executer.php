<?php
require_once realpath(dirname(__FILE__)).'/../config.php';
require_once realpath(dirname(__FILE__)).'/viewer.php';

mb_internal_encoding('UTF-8');

class Executer {
    
    const posts_per_page = 20;
    const topics_per_page = 10;
    
    private $connection;

    
    public function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_DATABASE);
        $this->connection->query("SET NAMES '".DB_CHARSET."'"); 
        $this->connection->query("SET CHARACTER SET '".DB_CHARSET."'");
        $this->connection->query("SET SESSION collation_connection = '".DB_COLLATION."'");
        
        @$user_timezone_row = $this->connection->query('SELECT timezone, banned FROM users WHERE id = "'.$_SESSION['user_id'].'"')->fetch_assoc();
        if ($user_timezone_row)
            $this->connection->query('SET time_zone = "'.$user_timezone_row['timezone'].'"');
        
        if (@$user_timezone_row['banned']) {
            Viewer::error('You suck');
            exit;
        }
        
        $now_row = $this->connection->query('SELECT NOW() as n')->fetch_assoc();
        $this->now = $now_row['n'];
        
        $this->connection->query('
                UPDATE users SET
                  ip = "'.$_SERVER['REMOTE_ADDR'].'",
                  last_activity = NOW()
                WHERE id = "'.$_SESSION['user_id'].'"');
    }
    
    public function check_login() {
        unset($_SESSION['edit']);
        if ($_SESSION['user_id']) {
            $row_name = $this->connection->query(
                    'SELECT name FROM users WHERE id = "'.$_SESSION['user_id'].'"'
            )->fetch_assoc();
            if ($row_name) return '<br>You are logged in as '.$row_name['name'].'<br><br>';
        }
    }

    public function execute($command) {
        $func = $command['command'];
        $args = $command['args'];
        $this->$func($args);
    }

    private function get_user_code($number) {
        $number--;
        $code = strtoupper(base_convert($number, 10, 26));
        for ($i = 0; $i < strlen($code); $i++) {
            $char = $code[$i];
            if ($char >= '0' && $char <= '9') {
                $code[$i] = chr(ord($char)+17);
            } else {
                $code[$i] = chr(ord($char)+10);
            }
        }
        if (strlen($code) > 1)
            $code[0] = chr(ord($code[0])-1);
        
        return $code;
    }
    
    private function get_topics_pages_count ($topic_id) {
        $count_posts = $this->get_topics_posts_count($topic_id);        
        $pages_count = ceil($count_posts / self::posts_per_page);
        if ($pages_count == 0) $pages_count = 1;        
        return $pages_count;
    }
    
    private function get_boards_pages_count ($board_id) {
        $count_row = $this->connection->query('
                SELECT count(*) as c FROM posts WHERE
                board_id = "'.$board_id.'" AND topic_id = 0
        ')->fetch_assoc();
        
        $pages_count = ceil($count_row['c'] / self::topics_per_page);
        if ($pages_count == 0) $pages_count = 1;
        
        return $pages_count;
    }
    
    private function get_topics_posts_count($topic_id) {
        $count_row = $this->connection->query('
                SELECT count(*) as c FROM posts
                WHERE topic_id = '.$topic_id
        )->fetch_assoc();
        
        return $count_row['c'];
    }
    
    private function get_boards_topics_count($board_id) {
        $count_row = $this->connection->query('
                SELECT count(*) as c FROM posts
                WHERE board_id = '.$board_id.'
                AND topic_id = 0
        ')->fetch_assoc();
        
        return $count_row['c'];
    }
    
    private function get_rvt_topics_count() {
        $count_row = $this->connection->query('
                SELECT count(*) as c FROM posts
                INNER JOIN users_posts_rv ON posts.id = users_posts_rv.topic_id
                WHERE posts.topic_id = 0 AND users_posts_rv.user_id = "'.$_SESSION['user_id'].'"
        ')->fetch_assoc();
        
        return $count_row['c'];
    }
    
    private function get_rvt_pages_count() {
        $topics_count = $this->get_rvt_topics_count();
        $pages_count = ceil($topics_count / self::topics_per_page);
        if ($pages_count == 0) $pages_count = 1;        
        return $pages_count;
    }

    private function format_date($date) {
        //return date('d.m.Y H:i', strtotime($date));
        
        $etime = strtotime($this->now) - strtotime($date);
    
        if ($etime < 1) {
            return 'just now';
        }
    
        $a = array( 12 * 30 * 24 * 60 * 60  =>  'year',
                    30 * 24 * 60 * 60       =>  'month',
                    24 * 60 * 60            =>  'day',
                    60 * 60                 =>  'hour',
                    60                      =>  'minute',
                    1                       =>  'second'
                  );
    
        foreach ($a as $secs => $str) {
            $d = $etime / $secs;
            if ($d >= 1) {
               $r = round($d);
                return $r . ' ' . $str . ($r > 1 ? 's' : '').' ago';
            }
        }
    }
    
    private function generate_invite_code() {
        $code = "";
        $chars = "abcdefghijklmnopqrstuvwxyz";
        $length = rand(12, 16);
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[rand(0, 25)];
        }
        
        return $code;
    }
    
    private function update_posts_rv($topic_id) {
        
        $rv_row_exists = $this->connection->query('
                SELECT id FROM users_posts_rv
                WHERE user_id = "'.$_SESSION['user_id'].'"
                AND topic_id = "'.$topic_id.'"
        ')->num_rows;
        
        $last_post_row = $this->connection->query('
                SELECT MAX(id) as max_id FROM posts
                WHERE topic_id = "'.$topic_id.'"
                OR id = "'.$topic_id.'"
        ')->fetch_assoc();
        
        if (!$rv_row_exists) {        
            $this->connection->query('
                    INSERT INTO users_posts_rv (user_id, topic_id, post_id, visited_date) VALUES
                    ("'.$_SESSION['user_id'].'", "'.$topic_id.'", "'.$last_post_row['max_id'].'", NOW())'
            );
        } else {
            $this->connection->query('
                    UPDATE users_posts_rv SET
                      post_id = "'.$last_post_row['max_id'].'",
                      visited_date = NOW()
                    WHERE user_id = "'.$_SESSION['user_id'].'"
                    AND topic_id = "'.$topic_id.'"
            ');
        }   
    }
    
    private function get_topics_news($topic_id) {
     
        $rv_post_row = $this->connection->query('
                SELECT post_id FROM users_posts_rv
                WHERE user_id = "'.$_SESSION['user_id'].'"
                AND topic_id = "'.$topic_id.'"
        ')->fetch_assoc();
        
        if ($rv_post_row) {
               
            $new_posts_count_row = $this->connection->query('
                    SELECT count(*) as c FROM posts
                    WHERE topic_id = "'.$topic_id.'"
                    AND id > "'.$rv_post_row['post_id'].'"
            ')->fetch_assoc();
            
            if ($new_posts_count_row && (int)$new_posts_count_row['c'] > 0)
                return '<span class="mark">+'.(int)$new_posts_count_row['c'].'</span>';  
        }
    }

    private function REGISTER($args) {
        $registered = (bool)($this->connection->query('
                SELECT id
                FROM users
                WHERE name = "'.strtolower($args['u']).'"'
        )->fetch_assoc());
        
        if ($registered) {
            Viewer::message('username already in use');
        } else {
          /*
            $invite = $this->connection->query('
                SELECT * FROM invites
                WHERE invite = "'.$args['i'].'"'
            )->fetch_assoc();
        
            if (!$invite) {
                Viewer::error('Invalid invitation code');
                exit;
            } else {
                $this->connection->query('DELETE FROM invites WHERE id = "'.$invite['id'].'"');
            }
          */
            
            $res = $this->connection->query('
                    INSERT INTO users (name, password)
                    VALUES ("'.$args['u'].'", "'.md5($args['p']).'")'
            );
            
            $user_id = $this->connection->insert_id;
            
            if ($res) {
                $this->connection->query('INSERT INTO invites (user_id, invite) VALUES ("'.$user_id.'", "'.$this->generate_invite_code().'")');
                $this->connection->query('INSERT INTO invites (user_id, invite) VALUES ("'.$user_id.'", "'.$this->generate_invite_code().'")');
                
                Viewer::message('you are now registered');
            }
            else Viewer::error('registration error');
        }
    }
    
    private function LOGIN($args) {
        $user = $this->connection->query('
            SELECT id
            FROM users
            WHERE name = "'.strtolower($args['u']).'"
            AND password = "'.md5($args['p']).'"'
        )->fetch_assoc();
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            Viewer::message('you are logged in');
        } else {
            Viewer::error('incorrect username or password');
        }
    }
    
    private function LOGOUT($args)
    {
        foreach ($_SESSION as $key => $value) {
            unset($_SESSION[$key]);
        }
        Viewer::message('You have been logged out');
    }
    
    private function BOARDS($args)
    {
        $_SESSION['view'] = 'boards';
        
        unset($_SESSION['topic_id']);
        unset($_SESSION['board_id']);
        unset($_SESSION['page']);
        
        $content = '<br>The list of existing boards:<br><br>';
        $res = $this->connection->query('SELECT * FROM boards WHERE public = 1 ORDER BY id');
        while ($row = mysqli_fetch_assoc($res)){
            $content .= '['.$row['id'].'] '.$row['name'].'
                         <span class="pendant">| '.$this->get_boards_topics_count($row['id']).' topics</span><br>';
        }
        Viewer::content($content, 'BOARDS');
    }
    
    private function BOARD($args)
    {
        $_SESSION['view'] = 'board';
        
        $_SESSION['topic_id'] = 0;
        $_SESSION['page'] = (int)$args['p'] ? (int)$args['p'] : 1;
        $board = $this->connection->query(
                'SELECT * FROM boards WHERE id = "'.$args['n'].'"'
        )->fetch_assoc();
        
        if (!$board) {
            Viewer::error('Board '.$args['n'].' does not exist');
        } else {
            $_SESSION['board_id'] = $args['n'];
            
            $head = '<br>['.$board['id'].'] <span class="reverse">'.$board['name'].'</span> ';
            $pages_count = $this->get_boards_pages_count($_SESSION['board_id']);
            $path = 'BOARD '.$args['n'].' '.$_SESSION['page'].'/'.$this->get_boards_pages_count($_SESSION['board_id']);
            
            $topics_res = $this->connection->query('
                SELECT * FROM posts
                WHERE board_id ='.$args['n'].'
                AND topic_id = 0
                ORDER BY bump_date DESC
                LIMIT '.($_SESSION['page']-1)*self::topics_per_page.', '.self::topics_per_page
            );
            
            $this->display_topics($head, $topics_res, $pages_count, $path);
        }  
    }
    
    private function RVT($args)
    {
        unset($_SESSION['topic_id']);
        unset($_SESSION['board_id']);
        $_SESSION['view'] = 'rvt';
        
        $head = '<br><span class="reverse">Revent viewed topics</span> ';
        $pages_count = $this->get_rvt_pages_count();
        
        if ((int)$args['p'] > $pages_count) {
            $_SESSION['page'] = $pages_count;
        } else if ((int)$args['p'] < 1) {
            $_SESSION['page'] = 1;
        } else {
            $_SESSION['page'] = (int)$args['p'];
        }
        
        $path = 'RVT '.$_SESSION['page'].'/'.$pages_count;
        
        $topics_res = $this->connection->query('
                SELECT posts.* FROM posts
                INNER JOIN users_posts_rv ON posts.id = users_posts_rv.topic_id
                WHERE posts.topic_id = 0 AND users_posts_rv.user_id = "'.$_SESSION['user_id'].'"
                ORDER BY visited_date DESC
                LIMIT '.($_SESSION['page']-1)*self::topics_per_page.', '.self::topics_per_page
        );
        
        $this->display_topics($head, $topics_res, $pages_count, $path);
    }
    
    private function NEWTOPIC($args) {
        $res = $this->connection->query('
            INSERT INTO posts (user_id, board_id, title, topic_id, content, creation_date, bump_date) VALUES
            ("'.$_SESSION['user_id'].'", "'.$_SESSION['board_id'].'", "'.$args['t'].'", "0", "'.$args['c'].'", NOW(), NOW())
        ');
        
        $topic_id = $this->connection->insert_id;
        
        if (!$res) {
            Viewer::error('Error creating topic');
        } else {
            $this->connection->query('
                INSERT INTO codes (user_id, topic_id, code)
                VALUES ("'.$_SESSION['user_id'].'", "'.$topic_id.'", "0")
            ');
            $this->TOPIC(array('n'=>$topic_id));
        }
    }

    private function TOPIC($args) {
       
        $_SESSION['view'] = 'topic';
        
        if ((int)$args['n']) $topic_id = (int)$args['n'];
        $page = (int)$args['p'] ? (int)$args['p'] : 1;
        $pages_count = $this->get_topics_pages_count($topic_id);
        $_SESSION['page'] = $page > $pages_count ? $pages_count : $page;
        if ($_SESSION['page'] < 1) $_SESSION['page'] = 1;
        
        $thread = $this->connection->query('
                SELECT posts.*, boards.name as board_name FROM posts
                INNER JOIN boards ON posts.board_id = boards.id
                WHERE posts.id = '.$topic_id.' AND topic_id = 0'
        )->fetch_assoc();   
        
        
        if (!$thread) {
            unset($_SESSION['topic_id']);
            Viewer::error('Topic '.$args['n'].' does not exist');
        } else {
            
            $this->update_posts_rv($thread['id']);
            
            $_SESSION['topic_id'] = $topic_id;
            $_SESSION['board_id'] = $thread['board_id'];
            $posts_res = $this->connection->query('
                SELECT posts_lim.*, code FROM
                (SELECT * FROM posts WHERE topic_id = '.$thread['id'].' 
                    ORDER BY id
                    LIMIT '.(($_SESSION['page']-1)*self::posts_per_page).', '.self::posts_per_page.'
                ) as posts_lim
                LEFT JOIN codes ON posts_lim.user_id = codes.user_id AND codes.topic_id = "'.$_SESSION['topic_id'].'"
                ORDER BY posts_lim.id'
            );
            
            if (!$posts_res) {
                Viewer::error ('Error reading topic');
            } else {
                $content .= '['.$thread['board_id'].'] '.$thread['board_name'].'                             
                             ['.$thread['id'].'] <span class="reverse">'.$thread['title'].'</span>
                             <br><br>'.$this->parse_bb_code($thread['content']).'<br>'.
                             ($thread['changing_date'] == 
                                     '0000-00-00 00:00:00' ? '' : 
                                     '<br><i><span class="pendant">Edited '.$this->format_date($thread['changing_date'])).'</span></i>'.
                            '<br><i><span class="pendant">Posted '.$this->format_date($thread['creation_date']).'</span></i>'.
                            '<br><br>Page '.$_SESSION['page'].'/'.$this->get_topics_pages_count($_SESSION['topic_id']);
                
                
                while ($row = mysqli_fetch_assoc($posts_res)) {
                    
                    $is_op = $row['user_id'] == $thread['user_id'];
                    $user_code = $is_op ? '[OP]' : $this->get_user_code($row['code']);
                    $content.= '<table class="post"><tr>
                                  <td class="post-gt">&gt;</td>
                                  <td class="post-code">'.$user_code.'</td>
                                  <td class="post-content"><div class="postnumber">['.$row['id'].']</div>'.
                                      $this->parse_bb_code($row['content']).'<br>'.
                                      ($row['changing_date'] == 
                                              '0000-00-00 00:00:00' ? '' : 
                                              '<br><i><span class="pendant">Edited '.$this->format_date($row['changing_date'])).'</span></i>'.
                                      '<br><i><span class="pendant">Posted '.$this->format_date($row['creation_date']).'</span><i></td>
                                </tr></table>';
                }
                
                $content .= '<br><br>';
                
                Viewer::content($content, 'TOPIC&nbsp;'.$thread['id'].'&nbsp;'.$_SESSION['page'].'/'.$this->get_topics_pages_count($thread['id']));
            }
        }
    }
    
    private function REPLY($args) {

        $row = $this->connection->query('
                SELECT id FROM codes
                WHERE user_id = '.$_SESSION['user_id'].' AND topic_id = '.$_SESSION['topic_id'].'
                LIMIT 1
        ')->fetch_assoc();
        
        if (!$row) {        
            $code_row = $this->connection->query('
                    SELECT MAX(code) as c FROM codes
                    WHERE topic_id = '.$_SESSION['topic_id']
            )->fetch_assoc();
            $code = (int)$code_row['c'] + 1;
            
            $res = $this->connection->query('
                INSERT INTO codes(user_id, topic_id, code) VALUES
                ("'.$_SESSION['user_id'].'", "'.$_SESSION['topic_id'].'", "'.$code.'")
            ');
            
            if (!$res) Viewer::error('Error creating post');
        }
        
        if (!$row && !$res) {
            Viewer::error('Error creating post');
        } else {
            
            $res_post = $this->connection->query('
                INSERT INTO posts (user_id, board_id, topic_id, content, creation_date)
                VALUES ("'.$_SESSION['user_id'].'", "'.$_SESSION['board_id'].'", "'.$_SESSION['topic_id'].'", "'.$args['m'].'", NOW())
            ');
            
            $this->connection->query('UPDATE posts SET bump_date = NOW() WHERE id = "'.$_SESSION['topic_id'].'"');            
            
            if (!$res_post) Viewer::error('Error creating post');
            else $this->TOPIC(array('n'=>$_SESSION['topic_id'], 'p'=>$_SESSION['page']));
        }
    }
    
    private function PAGE($args) {
        if ($_SESSION['topic_id']) {
            
            $pages_count = $this->get_topics_pages_count($_SESSION['topic_id']);
            
            if ($args['p'] > $pages_count) $_SESSION['page'] = $pages_count;
            else if ($args['p'] < 1) $_SESSION['page'] = 1;
            else $_SESSION['page'] = $args['p'];
            
            $this->TOPIC(array('n'=>$_SESSION['topic_id'], 'p'=>$_SESSION['page']));
            
        } else if ($_SESSION['board_id']) {
            
            $pages_count = $this->get_boards_pages_count($_SESSION['board_id']);
            
            if ($args['p'] > $pages_count) $_SESSION['page'] = $pages_count;
            else if ($args['p'] < 1) $_SESSION['page'] = 1;
            else $_SESSION['page'] = $args['p'];
            
            $this->BOARD(array('n'=>$_SESSION['board_id'], 'p'=>$_SESSION['page']));
            
        } else if ($_SESSION['view'] == 'rvt') {
            
            $this->RVT(array('p'=>$args['p']));
        }
    }
    
    private function NEXT($args) {
        $this->PAGE(array('p'=>$_SESSION['page']+1));
    }
    
    private function PREV($args) {
        $this->PAGE(array('p'=>$_SESSION['page']-1));
    }
    
    private function FIRST($args) {
        $this->PAGE(array('p'=>1));
    }
    
    private function LAST($args) {
        
        if ($_SESSION['topic_id'])
            $last_page = $this->get_topics_pages_count($_SESSION['topic_id']);
        else if ($_SESSION['board_id'])
            $last_page = $this->get_boards_pages_count($_SESSION['board_id']);
        else if ($_SESSION['view'] == 'rvt')
            $last_page = $this->get_rvt_pages_count();
        
        $this->PAGE(array('p'=>$last_page));
    }
    
    private function REFRESH($args) {
        if ($_SESSION['topic_id']) {
            $this->TOPIC(array('n'=>$_SESSION['topic_id'], 'p'=>$_SESSION['page']));
        } else if ($_SESSION['board_id']) {
            $this->BOARD(array('n'=>$_SESSION['board_id'], 'p'=>$_SESSION['page']));
        } else if ($_SESSION['view'] == 'boards') {
            $this->BOARDS(array());
        } else {
            $this->RVT(array('p'=>$_SESSION['page']));
        }
    }
        
    private function DELETE($args) {
        $post = $this->connection->query('
                SELECT id FROM posts
                WHERE id="'.$args['p'].'"
                AND user_id = "'.$_SESSION['user_id'].'"
                AND topic_id != 0
        ')->fetch_assoc();
        
        if (!$post) {
            Viewer::error('Operation is not possible');
        } else {
            $res = $this->connection->query('DELETE FROM posts WHERE id = "'.$args['p'].'"');            
            if (!$res) Viewer::error('Error deleting post');
            else Viewer::message('Post has been deleted');
        }
    }
    
    
    
    public function edit_post($post_id, $newcontent) {
        $res = $this->connection->query('
            UPDATE posts SET
              content = "'.$newcontent.'",
              changing_date = NOW()
            WHERE id = "'.$post_id.'"'
        );
        
        if (!$res) Viewer::error('Error editing post');
        else Viewer::message('Post has been edited');
    }


    private function EDIT($args) {
        $post = $this->connection->query('
                SELECT id, content FROM posts
                WHERE id="'.$args['p'].'"
                AND user_id = "'.$_SESSION['user_id'].'"
        ')->fetch_assoc();
        
        if (!$post) {
            Viewer::error('Operation is not possible');
        } else {
            $_SESSION['edit'] = $post['id'];
            Viewer::edit($post['id'], $post['content']);
        }
    }



    private function TIMEZONE($args) {
        $res = $this->connection->query('SET time_zone = "'.$args['u'].'"');
        if ($res) $res = $this->connection->query('
            UPDATE users SET timezone = "'.$args['u'].'"
            WHERE id = "'.$_SESSION['user_id'].'"'
        );
        
        if (!$res) Viewer::error('Error setting timezone.');
        else Viewer::message('Timezone saved');
    }
    
    private function INVITES ($args) {
        $res = $this->connection->query('SELECT invite FROM invites WHERE user_id = "'.$_SESSION['user_id'].'"');
        if (!$res) {
            Viewer::error('Error reading your invites');
        } else {
            $count_row = $this->connection->query('
                SELECT count(*) as c FROM invites
                WHERE user_id = "'.$_SESSION['user_id'].'"
            ')->fetch_assoc();
            
            if ($count_row['c']) {
                $invites = ''; $num = 0;
                while ($row = mysqli_fetch_assoc($res))
                    $invites .= ++$num.': '.$row['invite'].'<br>';
                Viewer::message($invites);
            } else {
                Viewer::error('You have no invites');
            }
        }
    }
    
    private function DONATE($args) {
        Viewer::message('<div style="padding:2px">Donations are unforced, gratuitous and anonymous. The collected funds will be used to pay for internets, domains and hosting.</div>
		
		<br><div style="padding:2px"><span class="reverse">&nbsp;YANDEX.MONEY&nbsp;</span> 41001746010947</div>
		<div style="padding:2px"><span class="reverse">&nbsp;QIWI&nbsp;</span> qiwi.me/314n</div>
		<div style="padding:2px"><span class="reverse">&nbsp;OTHER&nbsp;</span> z@314n.org</div>
		
		<br><div style="padding:2px">Thanks you!</div>');
    }
    
    private function HELP($args) {
        $message = '
<div style="padding-left:10px"><br><span class="reverse">&nbsp;HELP&nbsp;</span>

<br><br>[] - optional parameter.
<br>&lt;&gt; - required parameter.

<br><br>Before the parameter you should write a key (keys looks like "-k").

<br><br>Commands for guests:

<br><br><div style="padding:2px"><span class="reverse">&nbsp;REGISTER -u &lt;username&gt; -p &lt;password&gt;&nbsp;</span> Registers a user for 314n.</div>
<div style="padding:2px"><span class="reverse">&nbsp;LOGIN -u &lt;username&gt; -p &lt;password&gt;&nbsp;</span> Logs a user onto 314n.</div>

<br><br>Commands for users:

<br><br><div style="padding:2px"><span class="reverse">&nbsp;TIMEZONE -u &lt;UTC&gt;&nbsp;</span> Set your timezone.</div>
<div style="padding:2px"><span class="reverse">&nbsp;BOARDS&nbsp;</span> Displays list of available boards.</div>
<div style="padding:2px"><span class="reverse">&nbsp;BOARD -n &lt;number&gt;&nbsp;</span> Show the topics in a board.</div>
<div style="padding:2px"><span class="reverse">&nbsp;TOPIC -n &lt;number&gt; [-p &lt;page&gt;]&nbsp;</span> Loads a topic.</div>
<div style="padding:2px"><span class="reverse">&nbsp;RVT [-p &lt;page&gt;]</span> Displays list of recent viewed topics.</div>
<div style="padding:2px"><span class="reverse">&nbsp;REPLY -m &lt;message&gt;&nbsp;</span> Replies to the topic.</div>
<div style="padding:2px"><span class="reverse">&nbsp;DELETE -p &lt;post&gt;&nbsp;</span> Delete post.</div>
<div style="padding:2px"><span class="reverse">&nbsp;EDIT -p &lt;post&gt;&nbsp;</span> Edits post.</div>
<div style="padding:2px"><span class="reverse">&nbsp;NEXT&nbsp;</span> Goes to the next page.</div>
<div style="padding:2px"><span class="reverse">&nbsp;PREV&nbsp;</span> Goes to the previous page.</div>
<div style="padding:2px"><span class="reverse">&nbsp;FIRST&nbsp;</span> Go to the first page.</div>
<div style="padding:2px"><span class="reverse">&nbsp;LAST&nbsp;</span> Go to the last page.</div>
<div style="padding:2px"><span class="reverse">&nbsp;PAGE -p &lt;page&gt;&nbsp;</span> Go to page with required number.</div>
<div style="padding:2px"><span class="reverse">&nbsp;NEWTOPIC -t &lt;title&gt; -c &lt;content&gt;&nbsp;</span> Create a new topic.</div> 
<div style="padding:2px"><span class="reverse">&nbsp;LOGOUT&nbsp;</span> Logs out a user who is logged into 314n.</div>
<div style="padding:2px"><span class="reverse">&nbsp;INVITES&nbsp;</span> Displays yours invates.</div>
<div style="padding:2px"><span class="reverse">&nbsp;REFRESH&nbsp;</span> Refresh the page.</div>

<br><br>Commands for all:

<br><br><div style="padding:2px"><span class="reverse">&nbsp;HELP&nbsp;</span> Show the guide.</div>
<div style="padding:2px"><span class="reverse">&nbsp;DONATE&nbsp;</span> Help the project.</div>

<br><br>Download internets:

<br><br><div style="padding:2px"><span class="reverse">&nbsp;WINDOWS&nbsp;</span> //314n.org/ie6/mustdie.zip</div>
<div style="padding:2px"><span class="reverse">&nbsp;OTHER&nbsp;</span> //314n.org/ie6/unix.zip</div>

<br><br>If you want to paste into your replies something other than text, you should use bbcodes.
<br>The following BBCodes are available:

<br><br>[spoiler]hidden text[/spoiler]
<br>[quote]quoted text[/quote]
<br>[youtube]video file url[/youtube]
<br>[br]Text on next line
<!-- <br>[sound]sound file url[/sound] -->
<!-- <br>[b]bolded[/b] -->
<!-- <br>[center]centered text[/center] -->
<br>[img]image url[/img]
<br>[url(=URL)]URL or text[/url]
<!-- <br>[hr]Horizontal line -->
<br>[i]italic[/i]
<br>[s]strikethrough[/s]
<br>[u]underlined[/u]
<!-- <br>[audio]audio file url[/audio] -->
<br>[css=CSS code]formated text[/css]

<br><br>SHIFT+ENTER to drop down to a new line.

<br><br>Do not turn on the light.<br><br></div>';
        
        Viewer::message($message);
    }

    
    private function CREATEINVITES () {
        $res = $this->connection->query('SELECT id FROM users WHERE 1');
        while ($row = mysqli_fetch_assoc($res)) {
            $this->connection->query('
                INSERT INTO invites (user_id, invite)
                VALUES ("'.$row['id'].'", "'.$this->generate_invite_code().'")
            ');
        }
    }
    
    
    function parse_bb_code($bbcode) {
        $bbcode = str_replace('[br]', '<br>', $bbcode);
        
        /* Matching codes */
        $urlmatch = "([a-zA-Z]+[:\/\/]+[A-Za-z0-9\-_]+\\.+[A-Za-z0-9а-яА-Я\.\/%&=\?\-_]+)";

        /* Basically remove HTML tag's functionality */

        /*// Replace "special character" with it's unicode equivilant
        $match["special"] = "/\ /s";
        $replace["special"] = '&#65533;'; */

        /* Bold text */
        $match["b"] = "/\[b\](.*?)\[\/b\]/is";
        $replace["b"] = "<b>$1</b>";
        
        $match["spoiler"] = "/\[spoiler\](.*?)\[\/spoiler\]/is";
        $replace["spoiler"] = "<span class=\"spoiler\">$1</span>";

        /* Italics */
        $match["i"] = "/\[i\](.*?)\[\/i\]/is";
        $replace["i"] = "<i>$1</i>";

        /* Underline */
        $match["u"] = "/\[u\](.*?)\[\/u\]/is";
        $replace["u"] = "<span style=\"text-decoration: underline\">$1</span>";

        /* Strikethrough text */
        $match["s"] = "/\[s\](.*?)\[\/s\]/is";
        $replace["s"] = "<span style=\"text-decoration: line-through;\">$1</span>";

        /* Color (or Colour) */
        $match["color"] = "/\[css=([^\]]*)\](.*?)\[\/css\]/is";
        $replace["color"] = "<span style=\"$1\">$2</span>";

        /* Images */
        $match["img"] = "/\[img\]".$urlmatch."\[\/img\]/is";
        $replace["img"] = "<a href=\"$1\"><img src=\"$1\" /></a>";
        
        $match["youtube"] = "/\[youtube\]([a-zA-Z0-9_\-]+)\[\/youtube\]/is";
        $replace["youtube"] = "<div class=\"youtube\" id=\"$1\" style=\"width:420px; height:236px;\"></div><script src=\"js/youtube.js\" type=\"text/javascript\"></script>";

        /* Links */
        $match["url"] = "/\[url=".$urlmatch."\](.*?)\[\/url\]/is";
        $replace["url"] = "<a href=\"$1\">$2</a>";

        $match["surl"] = "/\[url\]".$urlmatch."\[\/url\]/is";
        $replace["surl"] = "<a href=\"$1\">$1</a>";

        /* Quotes */
        $match["quote"] = "/\[quote\](.*?)\[\/quote\]/ism";
        $replace["quote"] = "<div class=\"quote\">$1</div>";
        
        /* Parse */
        $bbcode = preg_replace($match, $replace, $bbcode);

        /* New line to <br> tag */
        $bbcode=nl2br($bbcode);
   
        /*// Code blocks - Need to specially remove breaks
        function pre_special($matches)
        {
                $prep = preg_replace("/\<br \/\>/","",$matches[1]);
                return " <pre>$prep</pre> ";
        }
        $bbcode = preg_replace_callback("/\[code\](.*?)\[\/code\]/ism","pre_special",$bbcode);


        // Remove <br> tags before quotes and code blocks
        $bbcode=str_replace(" <br />","",$bbcode);
        $bbcode=str_replace(" ","",$bbcode); //Clean up any special characters that got misplaced...

        // Return parsed contents */
        
        return $bbcode;
    }
    
    
    
    private function display_topics($head, $topics_res, $pages_count, $path) {
        
        $content = $head;
        
        $content .= 'Page '.$_SESSION['page'].'/'.$pages_count.'<br><br>';
        
        while ($row = mysqli_fetch_assoc($topics_res)){
            $content .= '
                <table><tr>
                  <td class="postsnumber" style="width:60px">['.$row['id'].']</td>
                  <td>&nbsp;<span class="reverse">&nbsp;'.mb_convert_encoding($row['title'], 'utf-8').'&nbsp;</span>
                      '.$this->get_topics_posts_count($row['id']).' replies 
                      '.$this->get_topics_news($row['id']).'
                      <div class="pm">&nbsp;Made '.$this->format_date($row['creation_date']).' | Bumped '.$this->format_date($row['bump_date']).'</div></td>
                </tr></table>
            ';
        }
            
        Viewer::content($content, $path);
    }
}
?>
