<?php

class AdminController
{
    public function register()
    {
        $db = Db::connect();
        $statement = $db->prepare("insert into user (firstname,lastname,email,pass,image, status) values (:firstname,:lastname,:email,:pass,:image,:status)");
        $statement->bindValue('firstname', Request::post("firstname"));
        $statement->bindValue('lastname', Request::post("lastname"));
        $statement->bindValue('email', Request::post("email"));
        $statement->bindValue('image', 'avatar.jpeg');
        $statement->bindValue('status', 2);
        $statement->bindValue('pass', password_hash(Request::post("pass"), PASSWORD_DEFAULT));
        $statement->execute();

        User::createAvatar(Request::post('firstname'), Request::post('lastname'), Request::post('email'));

        Session::getInstance()->logout();
        $this->index();

    }

    public static function report($id)
    {
        if(!Session::getInstance()->isLoggedIn()){
            header('Location: ' . App::config('url') . 'Index/index');
        }elseif (Report::checkIfReported($id)) {
            header('Location: ' . App::config('url') . 'Index/index');
        }else {
            $db = Db::connect();
            $statement = $db->prepare("insert into report (user, post) values(:user, :post)");
            $statement->bindValue('post', $id);
            $statement->bindValue('user', Session::getInstance()->getUser()->id);
            $statement->execute();

            header('Location: ' . App::config('url') . 'Index/index');
        }
    }

    public function delete($post)
    {

        $db = Db::connect();
        $db->beginTransaction();
        $statement = $db->prepare("SET foreign_key_checks = 0;
                                            delete from comment where postId=:post;
                                            SET foreign_key_checks = 1;");
        $statement->bindValue('post', $post);
        $statement->execute();

        $statement = $db->prepare("SET foreign_key_checks = 0;
                                            delete from report where post=:post;
                                            SET foreign_key_checks = 1;");
        $statement->bindValue('post', $post);
        $statement->execute();

        $statement = $db->prepare("SET foreign_key_checks = 0;
                                            delete from post_tag where post=:post;
                                            SET foreign_key_checks = 1;");
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

    public function profile()
    {
        $user = User::getData();
        $view = new View();
        $view->render('profile', [
            "user" => $user,
            "message" => ''
        ]);
    }

    //todo change pass
    public function changeData()
    {
        $db = Db::connect();
        $statement = $db->prepare("update user set firstname = :firstname, lastname = :lastname, email = :email where id = :id");
        $statement->bindValue('id', Session::getInstance()->getUser()->id);
        $statement->bindValue('firstname', Request::post("firstname"));
        $statement->bindValue('lastname', Request::post("lastname"));
        $statement->bindValue('email', Request::post("email"));
        $statement->execute();

        $this->profile();
    }

    public function uploadImage()
    {

        $target_dir = "app/images/".Session::getInstance()->getUser()->email."/";
        $name = basename($_FILES["fileToUpload"]["name"]);
        $target_file = $target_dir . $name;
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
            $this->profile();
        }

        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            $db = Db::connect();
            $statement = $db->prepare("update user set image = :image where id = :id;");
            $statement->bindValue('id', Session::getInstance()->getUser()->id);
            $statement->bindValue('image', $name);
            $statement->execute();
        }

        $this->profile();
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
        $statement = $db->prepare("select postId from comment where id = :commentId");
        $statement->bindValue('commentId', $commentId);
        $statement->execute();
        $post = $statement->fetch()->postId;

        $db = Db::connect();
        $statement = $db->prepare("insert into comment (postId, commentId, user, content) values (:postId, :commentId,:user,:content)");
        $statement->bindValue('postId', $post);
        $statement->bindValue('commentId', $commentId);
        $statement->bindValue('user', Session::getInstance()->getUser()->id);
        $statement->bindValue('content', Request::post("content"));
        $statement->execute();

        header('Location: '.App::config('url').'Index/index');
    }

    public function like($post)
    {
        if (Post::checkIfLiked($post)) {
            $this->index();
        } else {
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
        if (!empty(Request::post("email")) && !empty(Request::post("password"))) {

            $db = Db::connect();
            $statement = $db->prepare("select id, concat(firstname, ' ', lastname) as name, pass, image, email, status from user where email=:email");
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
}