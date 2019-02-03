<?php

class IndexController
{
    public function index()
    {
        $view = new View();
        $posts = Post::all();
        $view->render('index', [
            "posts" => $posts
        ]);
    }
    public function view($id = 0)
    {
        $view = new View();
        $view->render('view', [
            "post" => Post::find($id),
            "comments" => Comment::all($id)
        ]);
    }

    public function newPost()
    {
        $data = $this->_validate($_POST);
        if ($data === false) {
            header('Location: ' . App::config('url'));
        } else {
            $connection = Db::connect();
            $sql = 'INSERT INTO post (content) VALUES (:content)';
            $stmt = $connection->prepare($sql);
            $stmt->bindValue('content', $data['content']);
            $stmt->execute();
            header('Location: ' . App::config('url'));
        }
    }

    public function newComment($postId)
    {
        $data = $this->_validate($_POST);
        if ($data === false) {
            header('Location: ' . App::config('url'));
        } else {
            $connection = Db::connect();
            $sql = 'INSERT INTO comment (content, postId) VALUES (:content, :postId)';
            $stmt = $connection->prepare($sql);
            $stmt->bindValue('content', $data['content']);
            $stmt->bindValue('postId', $postId);
            $stmt->execute();
            header('Location: ' . App::config('url') . 'Index/view/' . $postId);
        }
    }
    /**
     * @param $data
     * @return array|bool
     */
    private function _validate($data)
    {
        $required = ['content'];
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