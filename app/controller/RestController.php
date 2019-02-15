<?php

class RestController
{
    public function posts($page = 1)
    {
        $posts = Post::all($page);
        echo json_encode($posts);
    }

    public function find($id)
    {
        $posts = Post::find($id);
        echo json_encode($posts);
    }

    public function byTag($tag)
    {
        $posts = Post::byTag($tag);
        echo json_encode($posts);
    }

    public function like($id)
    {
        $likes = Like::all($id);
        echo json_encode($likes);
    }

    public function comments($id)
    {
        $comments = Comment::all($id);
        echo json_encode($comments);
    }

    public function commentsComments($id)
    {
        $comments = Comment::all($id);
        echo json_encode($comments);
    }
}