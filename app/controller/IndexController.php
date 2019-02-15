<?php

class IndexController
{
    public function index($page = 1)
    {
        $view = new View();
        $posts = Post::all($page);
        $view->render('index', [
            "posts" => $posts,
            "message" => ''
        ]);
    }

    public function byTag()
    {
        $view = new View();
        $posts = Post::byTag($_POST['tag']);
        $view->render('index', [
            "posts" => $posts,
            "message" => ''
        ]);
    }

    public function view($id = 0)
    {
        $view = new View();

        $view->render('view', [
            "post" => Post::find($id),
            "message" => ''
        ]);
    }

    public function newPost()
    {
        $data = $this->_validate($_POST);

        if ($data === false) {
            header('Location: ' . App::config('url'));
        }

        $connection = Db::connect();
        $sql = "insert into post(content, user) values (:content, :user)";
        $stmt = $connection->prepare($sql);
        $stmt->bindValue('content', $data['content']);
        $stmt->bindValue('user', Session::getInstance()->getUser()->id);
        $stmt->execute();
        $postId = $connection->lastInsertId();

        if (!empty($data['tags'])) {
            $d = explode(' ', $data['tags']);
            foreach ($d as $rez) {
                $sql = "Insert into tag (content) values (:tag)";
                $stmt = $connection->prepare($sql);
                $stmt->bindValue('tag', $rez);
                $stmt->execute();
                $tagId = $connection->lastInsertId();

                $sql = "insert into post_tag(post, tag) values (:post,:tag)";
                $stmt = $connection->prepare($sql);
                $stmt->bindValue('post', $postId);
                $stmt->bindValue('tag', $tagId);
                $stmt->execute();
            }
        }
        header('Location: ' . App::config('url'));
    }


    /**
     * @param $data
     * @return array|bool
     */
    private
    function _validate($data)
    {
        $required = ['content'];

        //validate required keys
        foreach ($required as $key) {
            if (!isset($data[$key])) {
                return false;
            }

            $data[$key] = trim((string)$data[$key]);
            if (empty($data[$key])) {
                return false;
            }
        }
        return $data;
    }
}