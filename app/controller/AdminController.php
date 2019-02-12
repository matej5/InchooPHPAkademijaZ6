<?php

class AdminController
{
    public function register()
    {
        $db = Db::connect();
        $statement = $db->prepare("insert into user (firstname,lastname,email,pass,image) values (:firstname,:lastname,:email,:pass,:image)");
        $statement->bindValue('firstname', Request::post("firstname"));
        $statement->bindValue('lastname', Request::post("lastname"));
        $statement->bindValue('email', Request::post("email"));
        $statement->bindValue('image', Request::post("image"));
        $statement->bindValue('pass', password_hash(Request::post("pass"), PASSWORD_DEFAULT));
        $statement->execute();

        User::createAvatar(Request::post('firstname'),Request::post('lastname'),Request::post('email'));

        Session::getInstance()->logout();
        $this->index();

    }

    public function delete($post)
    {

        $db = Db::connect();
        $db->beginTransaction();
        $statement = $db->prepare("delete from comment where postId=:post");
        $statement->bindValue('post', $post);
        $statement->execute();

        $statement = $db->prepare("delete from likes where post=:post");
        $statement->bindValue('post', $post);
        $statement->execute();

        $statement = $db->prepare("delete from post where id=:post");
        $statement->bindValue('post', $post);

        $statement->execute();

        $db->commit();

        $this->index();

    }

    public function comment($postId)
    {
        $db = Db::connect();
        $statement = $db->prepare("insert into comment (postId, user, content) values (:postId,:user,:content)");
        $statement->bindValue('postId', $postId);
        $statement->bindValue('user', Session::getInstance()->getUser()->id);
        $statement->bindValue('content', Request::post("content"));
        $statement->execute();

        $view = new View();

        $view->render('view', [
            "post" => Post::find($postId),
            "message" => ''
        ]);
    }


    public function commentComment($commentId)
    {
        $db = Db::connect();
        $statement = $db->prepare("insert into comment (commentId, user, content) values (:commentId,:user,:content)");
        $statement->bindValue('commentId', $commentId);
        $statement->bindValue('user', Session::getInstance()->getUser()->id);
        $statement->bindValue('content', Request::post("content"));
        $statement->execute();

        $this->index();
    }

    public function like($post)
    {
        if(Post::checkIfLiked($post)){
            $this->index();
        }else {
            $db = Db::connect();
            $statement = $db->prepare("insert into likes (post,user) values (:post,:user)");
            $statement->bindValue('post', $post);
            $statement->bindValue('user', Session::getInstance()->getUser()->id);
            $statement->execute();

            $this->index();
        }
    }

    public function authorize()
    {
        if(!empty(Request::post("email")) && !empty(Request::post("password"))) {

            $db = Db::connect();
            $statement = $db->prepare("select id, concat(firstname, ' ', lastname) as name, pass, image from user where email=:email");
            $statement->bindValue('email', Request::post("email"));
            $statement->execute();

            if ($statement->rowCount() > 0) {
                $user = $statement->fetch();
                if (password_verify(Request::post("password"), $user->pass)) {

                    unset($user->pass);

                    if (empty($user->image)) {
                        $n = explode(" ", $user->name);
                        User::createAvatar($n[0], $n[1], Request::post("email"));
                        $db = Db::connect();
                        $statement = $db->prepare("update user set image = :image where id = :id");
                        $statement->bindValue('image', 'avatar.jpeg');
                        $statement->bindValue('id', $user->id);
                        $statement->execute();
                    }

                    Session::getInstance()->login($user);

                    $this->index();
                } else {
                    $posts = Post::all();
                    $view = new View();
                    $view->render('index', ["posts" => $posts, "message" => "Neispravna kombinacija korisničko ime i lozinka"]);
                }
            } else {
                $posts = Post::all();
                $view = new View();
                $view->render('index', ["posts" => $posts, "message" => "Neispravan email"]);
            }
        } else {
            $posts = Post::all();
            $view = new View();
            $view->render('index', ["posts" => $posts, "message" => "Prazna polja"]);
        }
    }

    public function logout()
    {
        Session::getInstance()->logout();
        $this->index();
    }

    public function json()
    {
        $posts = Post::all();
        //print_r($posts);
        echo json_encode($posts);
    }

    public function index()
    {
        $posts = Post::all();
        $view = new View();
        $view->render('index', [
            "posts" => $posts,
            "message" => ''
        ]);
    }

    function bulkinsert()
    {
        $db = Db::connect();
        for ($i = 0; $i < 20; $i++) {

            $statement = $db->prepare("insert into post (content,user) values ('DDDD $i',1)");
            $statement->execute();

            $id = $db->lastInsertId();

            for ($j = 0; $j < 20; $j++) {

                $statement = $db->prepare("insert into comment (content,user,post) values ('CCCCC $i',1,$id)");
                $statement->execute();
            }
        }
    }
}