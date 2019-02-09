<?php

class AdminController
{
    public function login()
    {
    

        $view = new View();    
        $view->render('login', [
            "message" => ""
        ]);
    }

    public function registration()
    {
        $view = new View();
        $view->render('registration',["message"=>""]);
       
    }

    public function register()
    {
        $db = Db::connect();
        $statement = $db->prepare("insert into user (firstname,lastname,email,pass,image) values (:firstname,:lastname,:email,:pass,:image)");
        $statement->bindValue('firstname', Request::post("firstname"));
        $statement->bindValue('lastname', Request::post("lastname"));
        $statement->bindValue('email', Request::post("email"));
        $statement->bindValue('image', Request::post("image"));
        $statement->bindValue('pass', password_hash(Request::post("pass"),PASSWORD_DEFAULT));
        $statement->execute();


        User::createAvatar(Request::post('firstname'),Request::post('lastname'),Request::post('email'));

        Session::getInstance()->logout();
        $view = new View();
        $view->render('login',["message"=>""]);
       
    }

    public function delete($post)
    {

        $db = Db::connect();
        $db->beginTransaction();
        $statement = $db->prepare("delete from comment where post=:post");
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
            "post" => Post::find($postId)
        ]);
       
    }


    public function like($post)
    {

        $db = Db::connect();
        $statement = $db->prepare("insert into likes (post,user) values (:post,:user)");
        $statement->bindValue('post', $post);
        $statement->bindValue('user', Session::getInstance()->getUser()->id);
        $statement->execute();
        
        $this->index();
       
    }


    public function authorize()
    {
//ne dostaju kontrole
        $db = Db::connect();
        $statement = $db->prepare("select id, concat(firstname, ' ', lastname) as name, pass from user where email=:email");
        $statement->bindValue('email', Request::post("email"));
        $statement->execute();


        if($statement->rowCount()>0){
            $user = $statement->fetch();
            if(password_verify(Request::post("password"), $user->pass)){
              
                unset($user->pass);
                
                Session::getInstance()->login($user);

                $this->index();
            }else{
                $view = new View();
                $view->render('login',["message"=>"Neispravna kombinacija korisničko ime i lozinka"]);
            }
        }else{
            $view = new View();
            $view->render('login',["message"=>"Neispravan email"]);
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
            "posts" => $posts
        ]);
    }

    function bulkinsert()
    {
        $db = Db::connect();
        for($i=0;$i<20;$i++){

            $statement = $db->prepare("insert into post (content,user) values ('DDDD $i',1)");
            $statement->execute();

            $id = $db->lastInsertId();

            for($j=0;$j<20;$j++){

                $statement = $db->prepare("insert into comment (content,user,post) values ('CCCCC $i',1,$id)");
                $statement->execute();
            }
        }
    }
}