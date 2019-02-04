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

    public static function uploadImage($id)
    {
        $target_dir = "app/images/";
        $name = basename($_FILES["fileToUpload"]["name"]);
        $target_file = $target_dir . $name;

        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            $id = intval($id);
            $db = Db::connect();
            $statement = $db->prepare("update post set image = :image where id = :id;");
            $statement->bindValue('id', $id);
            $statement->bindValue('image', $name);
            $statement->execute();
        }
    }
}