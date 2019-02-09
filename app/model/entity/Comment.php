<?php
class Comment
{
    private $id;
    private $content;
    private $post;
    private $comment;
    private $dateCreated;
    public function __construct($id, $content, $post, $comment, $dateCreated)
    {
        $this->setId($id);
        $this->setContent($content);
        $this->setPost($post);
        $this->setComment($comment);
        $date = date_create($dateCreated);
        date_timezone_set($date, timezone_open('Europe/Zagreb'));
        $date = date_format($date, 'd.m.Y. H:i');
        $this->setDateCreated($date);
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
    public static function all($id)
    {
        $list = [];
        $id = intval($id);
        $db = Db::connect();
        $statement = $db->prepare("SELECT * FROM comment where postId = :id order by dateCreated desc;");
        $statement->bindValue('id', $id);
        $statement->execute();
        foreach ($statement->fetchAll() as $comment) {
            $list[] = new Comment($comment->id, $comment->content, $comment->post, $comment->comment, $comment->dateCreated);
        }
        return $list;
    }
    public static function count($id)
    {
        $id = intval($id);
        $db = Db::connect();
        $statement = $db->prepare("SELECT count(*) as total from comment where postId = :id;");
        $statement->bindValue('id', $id);
        $statement->execute();
        $comments = $statement->fetch();
        return $comments->total;
    }
}