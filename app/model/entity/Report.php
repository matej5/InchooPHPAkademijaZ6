<?php

class Report
{
    public static function checkIfReported($id)
    {
        $db = Db::connect();
        $statement = $db->prepare("select * from report where post = :id and user = :user");
        $statement->bindValue('id', $id);
        $statement->bindValue('user', Session::getInstance()->getUser()->id);
        $statement->execute();

        if ($statement->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public static function checkIfBanned($id)
    {
        $db = Db::connect();
        $statement = $db->prepare("select * from report where post = :id");
        $statement->bindValue('id', $id);
        $statement->execute();

        if ($statement->rowCount() > 4) {
            return true;
        } else {
            return false;
        }
    }

}