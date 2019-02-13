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

    private $image;

    public function __construct($id, $content, $user, $date, $likes, $comments, $userid, $image)
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
        $this->setImage($image);
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
        count(c.id) as likes, concat(b.email, '/', b.image) as image
        from 
        post a inner join user b on a.user=b.id 
        left join likes c on a.id=c.post 
        where a.date > ADDDATE(now(), INTERVAL -7 DAY) 
        group by a.id, a.content, concat(b.firstname, ' ', b.lastname), a.date 
        order by a.date desc limit 10");
        $statement->execute();
        foreach ($statement->fetchAll() as $post) {

            $statement = $db->prepare("select a.id, a.content, concat(b.firstname, ' ', b.lastname) as user, concat(b.email, '/', b.image) as image, a.date from comment a inner join user b on a.user=b.id where a.postId=:id and a.commentId is null ");
            $statement->bindValue('id', $post->id);
            $statement->execute();
            $comments = $statement->fetchAll();

            $list[] = new Post($post->id, $post->content, $post->user, $post->date, $post->likes, $comments, 0, $post->image);
        }

        return $list;
    }

    public static function find($id)
    {
        $id = intval($id);
        $db = Db::connect();
        $statement = $db->prepare("select 
        a.id, a.content, concat(b.firstname, ' ', b.lastname) as user, a.date, a.user as userid, count(c.id) as likes, concat(b.email,'/', b.image) as image
        from 
        post a inner join user b on a.user=b.id 
        left join likes c on a.id=c.post 
         where a.id=:id");
        $statement->bindValue('id', $id);
        $statement->execute();
        $post = $statement->fetch();

        $statement = $db->prepare("select a.id, a.content, concat(b.firstname, ' ', b.lastname) as user, concat(b.email,'/', b.image) as image, a.date from comment a inner join user b on a.user=b.id where a.postId=:id and a.commentId is null");
        $statement->bindValue('id', $id);
        $statement->execute();
        $comments = $statement->fetchAll();

        return new Post($post->id, $post->content, $post->user, $post->date, $post->likes, $comments, $post->userid, $post->image);
    }


    public static function byTag($tag)
    {
        if(!empty($tag)) {
            $list = [];
            $db = Db::connect();
            $statement = $db->prepare("select 
            a.id as postid, a.content as postcontent, concat(b.firstname, ' ', b.lastname) as user, a.date, 
            count(c.id) as likes, concat(b.email, '/', b.image) as image, d.*, e.*
            from 
            post a inner join user b on a.user=b.id 
            left join likes c on a.id=c.post 
            inner join post_tag d on d.post = a.id
            inner join tag e on e.id = d.tag
            where a.date > ADDDATE(now(), INTERVAL -7 DAY) and e.content = :tag
            group by d.id , a.id, a.content, concat(b.firstname, ' ', b.lastname), a.date
            order by a.date desc limit 10");
            $statement->bindValue('tag',$tag);
            $statement->execute();
            foreach ($statement->fetchAll() as $post) {

                $statement = $db->prepare("select a.id, a.content, concat(b.firstname, ' ', b.lastname) as user, concat(b.email, '/', b.image) as image, a.date from comment a inner join user b on a.user=b.id where a.postId=:id and a.commentId is null ");
                $statement->bindValue('id', $post->id);
                $statement->execute();
                $comments = $statement->fetchAll();

                $list[] = new Post($post->postid, $post->postcontent, $post->user, $post->date, $post->likes, $comments, 0, $post->image);
            }

            return $list;
        }else{
            return self::all();
        }
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