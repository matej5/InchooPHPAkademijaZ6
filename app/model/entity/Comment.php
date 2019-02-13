<?php

class Comment
{
    private $id;
    private $user;
    private $content;
    private $postId;
    private $commentId;
    private $date;
    private $image;

    public function __construct($id, $user, $content, $post, $comment, $date, $image)
    {
        $this->setId($id);
        $this->setUser($user);
        $this->setContent($content);
        $this->setPost($post);
        $this->setComment($comment);
        $d = date_create($date);
        date_timezone_set($d, timezone_open('Europe/Zagreb'));
        $d = date_format($d, 'd.m.Y. H:i');
        $this->setDate($d);
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

    public static function all($id)
    {
        $list = [];
        $id = intval($id);
        $db = Db::connect();
        $statement = $db->prepare("SELECT a.*, concat(b.firstname, ' ', b.lastname) as user, concat(b.email, '/', b.image) as image
        from comment a inner join user b on a.user = b.id
        where a.postId = :id and a.commentId <=> NULL
        group by a.id, a.content, concat(b.firstname, ' ', b.lastname), a.date 
        order by a.date desc;");
        $statement->bindValue('id', $id);
        $statement->execute();
        foreach ($statement->fetchAll() as $comment) {
            $list[] = new Comment($comment->id, $comment->user, $comment->content, $comment->postId, $comment->commentId, $comment->date, $comment->image);
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

    public static function comOfCom($id)
    {
        $list = [];
        $id = intval($id);
        $db = Db::connect();
        $statement = $db->prepare("SELECT a.*, concat(b.firstname, ' ', b.lastname) as user, concat(b.email, '/', b.image) as image
        from comment a inner join user b on a.user = b.id
        where commentId = :id
        group by a.id, a.content, concat(b.firstname, ' ', b.lastname), a.date 
        order by a.date desc;");
        $statement->bindValue('id', $id);
        $statement->execute();
        foreach ($statement->fetchAll() as $comment) {
            $list[] = new Comment($comment->id, $comment->user, $comment->content, $comment->postId, $comment->commentId, $comment->date, $comment->image);
        }
        return $list;
    }

    public static function recursion($id)
    {
        if ($comments = self::comOfCom($id)):
            foreach ($comments as $comment): ?>
                <div class="indent">
                    <p>
                        <img src="/app/images/<?= $comment->image; ?>" class="postImage">
                        <b><?php echo htmlspecialchars($comment->user) ?></b>
                        <?= htmlspecialchars($comment->content) ?> </a> <br/>
                    </p>
                    <cite>
                        <?php echo 'Before ' . Post::countTime($comment->date) ?>
                    </cite>
                    <?php if (Session::getInstance()->isLoggedIn()): ?>
                        <a href="javascript:void(0);" class="comForm">Comment</a>
                        <form method="post"
                              action="<?= App::config('url') ?>admin/commentComment/<?php echo $comment->id ?>"
                              class="form-horizontal showActionComment">
                            <input type="text" name="content">
                        </form>
                    <?php endif; ?>
                    <?php Comment::recursion($comment->id) ?>
                </div>
            <?php endforeach; ?>
            </p>
        <?php endif;
    }
}