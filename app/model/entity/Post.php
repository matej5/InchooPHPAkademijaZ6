<?php

class Post
{
    private $id;

    private $content;

    private $user;

    private $date;

    private $likes;

    private $comments;

    private $userid;

    private $userEmail;

    private $userImage;

    public function __construct($id, $content, $user, $date, $likes, $comments, $userid, $userEmail, $userImage)
    {
        $this->setId($id);
        $this->setContent($content);
        $this->setUser($user);
        $d = date_create($date);
        date_timezone_set($d, timezone_open('Europe/Zagreb'));
        $d = date_format($d, 'd.m.Y. H:i');
        $this->setDate($d);
        $this->setLikes($likes);
        $this->setComments($comments);
        $this->setUserid($userid);
        $this->setUserEmail($userEmail);
        $this->setUserImage($userImage);
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function __get($name)
    {
        return isset($this->$name) ? $this->$name : null;
    }

    public function __call($name, $arguments)
    {
        $function = substr($name, 0, 3);
        if ($function === 'set') {
            $this->__set(strtolower(substr($name, 3)), $arguments[0]);
            return $this;
        } else if ($function === 'get') {
            return $this->__get(strtolower(substr($name, 3)));
        }

        return $this;
    }

    public static function all()
    {
        $list = [];
        $db = Db::connect();
        $statement = $db->prepare("select 
        a.id, a.content, concat(b.firstname, ' ', b.lastname) as user, a.date, 
        count(c.id) as likes, b.email, b.image
        from 
        post a inner join user b on a.user=b.id 
        left join likes c on a.id=c.post 
        where a.date > ADDDATE(now(), INTERVAL -7 DAY) 
        group by a.id, a.content, concat(b.firstname, ' ', b.lastname), a.date 
        order by a.date desc limit 10");
        $statement->execute();
        foreach ($statement->fetchAll() as $post) {

            $statement = $db->prepare("select a.id, a.content, concat(b.firstname, ' ', b.lastname) as user, a.date from comment a inner join user b on a.user=b.id where a.postId=:id ");
            $statement->bindValue('id', $post->id);
            $statement->execute();
            $comments = $statement->fetchAll();

            $list[] = new Post($post->id, $post->content, $post->user, $post->date, $post->likes, $comments, 0, $post->email, $post->image);
        }

        return $list;
    }

    /**
     * @param $str
     * @return string|string[]|null
     */
    public static function convertHashtags($str)
    {
        $replace = "/#+[a-zAZ0-9]+/";
        $str = preg_replace($replace, '<a href="#">$0</a>', $str);
        return $str;
    }

    public static function allinone()
    {

        $time = microtime(true);
        $list = [];
        $db = Db::connect();
        $statement = $db->prepare("select 
        a.id, a.content, concat(b.firstname, ' ', b.lastname) as user, a.date,
        d.id as commentid, d.content as commentcontent ,
        concat(e.firstname, ' ', e.lastname) as commentuser,
        count(c.id) as likes
        from 
        post a inner join user b on a.user=b.id 
        left join likes c on a.id=c.post 
        inner join comment d on a.id=d.post
        inner join user e on d.user=e.id
        where a.date > ADDDATE(now(), INTERVAL -7 DAY) 
        group by a.id, a.content, concat(b.firstname, ' ', b.lastname), a.date ,
        d.id , d.content  ,
        concat(e.firstname, ' ', e.lastname) 
        order by a.date desc limit 100");
        $statement->execute();

        foreach ($statement->fetchAll() as $post) {
            $list[] = new Post($post->id, $post->content, $post->user, $post->date, $post->likes, [], 0);
        }
        $time2 = microtime(true);
        echo $time2 - $time;

        return $list;
    }

    public static function find($id)
    {
        $id = intval($id);
        $db = Db::connect();
        $statement = $db->prepare("select 
        a.id, a.content, concat(b.firstname, ' ', b.lastname) as user, a.date, a.user as userid, count(c.id) as likes
        from 
        post a inner join user b on a.user=b.id 
        left join likes c on a.id=c.post 
         where a.id=:id");
        $statement->bindValue('id', $id);
        $statement->execute();
        $post = $statement->fetch();

        $statement = $db->prepare("select a.id, a.content, concat(b.firstname, ' ', b.lastname) as user, a.date from comment a inner join user b on a.user=b.id where a.postId=:id ");
        $statement->bindValue('id', $id);
        $statement->execute();
        $comments = $statement->fetchAll();


        $post->content = Post::convertHashtags($post->content);

        return new Post($post->id, $post->content, $post->user, $post->date, $post->likes, $comments, $post->userid);
    }

    public static function countTime($date)
    {
        $time = strtotime($date);
        $diff = time() - $time;
        if ($diff < 60) {
            return floor($diff) . 's ';
        }
        $diff /= 60;
        if ($diff < 60) {
            return floor($diff) . 'm ';
        }
        $diff /= 60;
        if ($diff < 24) {
            return floor($diff) . 'h ';
        }
        $diff /= 24;
        if ($diff < 30) {
            return floor($diff) . 'd ';
        }
        $diff /= 30;
        if ($diff < 12) {
            return (floor($diff) > 1) ? floor($diff) . ' months ' : floor($diff) . ' month ';
        }
        $diff /= 12.175;
        return (floor($diff) > 1) ? floor($diff) . ' years ' : floor($diff) . ' year ';
    }

    public static function checkIfLiked($post)
    {
        $db = Db::connect();
        $statement = $db->prepare("select post, user from likes where post = :post and user = :user");
        $statement->bindValue('post', $post);
        $statement->bindValue('user', Session::getInstance()->getUser()->id);
        $statement->execute();

        if($statement->rowCount() > 0){
            return true;
        }else {
            return false;
        }
    }
}