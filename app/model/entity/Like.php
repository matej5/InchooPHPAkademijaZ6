<?php
/**
 * Created by PhpStorm.
 * User: matej
 * Date: 11.02.19.
 * Time: 15:01
 */

class Like
{
    private $id;
    private $user;

    public function __construct($id, $user)
    {
        $this->setId($id);
        $this->setUser($user);
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

    public static function all($post)
    {
        $db = Db::connect();
        $list = [];
        $statement = $db->prepare("select * from likes where post = :post");
        $statement->bindValue('post', $post);
        $statement->execute();

        foreach ($statement->fetchAll() as $like) {
            $list[] = new Like($like->id, $like->user);
        }

        return $list;
    }
}