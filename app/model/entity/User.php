<?php
/**
 * Created by PhpStorm.
 * User: matej
 * Date: 09.02.19.
 * Time: 07:18
 */

class User
{
    private $id;
    private $firstname;
    private $lastname;
    private $email;
    private $image;

    public function __construct($id, $firstname, $lastname, $email, $image)
    {
        $this->setId($id);
        $this->setFirstname($firstname);
        $this->setLastname($lastname);
        $this->setEmail($email);
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
    public static function createAvatar($fn, $ln, $em)
    {
        $siteRoot = BP . 'app/images/';

        $newUserSubfolder = $siteRoot . $em;
        if (!file_exists($newUserSubfolder)) {
            mkdir($newUserSubfolder, 0777, true);
        }
        $fnInt = 0;
        $lnInt = 0;
        $emInt = 0;

        for ($i = 0; $i < strlen($fn) - 1; $i++) {
            $fnInt += ord($fn[$i]);
        }
        for ($i = 0; $i < strlen($ln) - 1; $i++) {
            $lnInt += ord($ln[$i]);
        }
        for ($i = 0; $em[$i] != '@'; $i++) {
            $emInt += ord($em[$i]);
        }

        $fnColor = $fnInt;
        $lnColor = $lnInt;
        $emColor = $emInt;

        while ($fnColor > 235) {
            $fnColor = $fnColor / 2 + 40;
        }
        while ($lnColor > 235) {
            $lnColor = $lnColor / 2 + 40;
        }
        while ($emColor > 235) {
            $emColor = $emColor / 2 + 40;
        }

        $total = ($fnInt + $lnInt + $emInt) * 21;
        $im = imagecreate(420, 420);
        $white = ImageColorAllocate($im, 255, 255, 255);
        $color = ImageColorAllocate($im, $fnColor, $lnColor, $emColor);
        ImageFilledRectangle($im, 0, 0, 420, 420, $white);
        for ($i = 2, $j = 0; $j < 16; $j++) {
            if (pow($i, $j) & $total) {
                switch ($j) {
                    case 0:
                        ImageFilledRectangle($im, 315, 35, 385, 105, $color);
                        ImageFilledRectangle($im, 35, 35, 105, 105, $color);
                        break;

                    case 1:
                        ImageFilledRectangle($im, 105, 35, 175, 105, $color);
                        ImageFilledRectangle($im, 245, 35, 315, 105, $color);
                        break;

                    case 2:
                        ImageFilledRectangle($im, 175, 35, 245, 105, $color);
                        break;

                    case 3:
                        ImageFilledRectangle($im, 315, 105, 385, 175, $color);
                        ImageFilledRectangle($im, 35, 105, 105, 175, $color);
                        break;

                    case 4:
                        ImageFilledRectangle($im, 245, 105, 315, 175, $color);
                        ImageFilledRectangle($im, 105, 105, 175, 175, $color);
                        break;

                    case 5:
                        ImageFilledRectangle($im, 175, 105, 245, 175, $color);
                        break;

                    case 6:
                        ImageFilledRectangle($im, 315, 175, 385, 245, $color);
                        ImageFilledRectangle($im, 35, 175, 105, 245, $color);
                        break;

                    case 7:
                        ImageFilledRectangle($im, 245, 175, 315, 245, $color);
                        ImageFilledRectangle($im, 105, 175, 175, 245, $color);
                        break;

                    case 8:
                        ImageFilledRectangle($im, 175, 175, 245, 245, $color);
                        break;

                    case 9:
                        ImageFilledRectangle($im, 315, 245, 385, 315, $color);
                        ImageFilledRectangle($im, 35, 245, 105, 315, $color);
                        break;

                    case 10:
                        ImageFilledRectangle($im, 245, 245, 315, 315, $color);
                        ImageFilledRectangle($im, 105, 245, 175, 315, $color);
                        break;

                    case 11:
                        ImageFilledRectangle($im, 175, 245, 245, 315, $color);
                        break;

                    case 12:
                        ImageFilledRectangle($im, 315, 315, 385, 385, $color);
                        ImageFilledRectangle($im, 35, 315, 105, 385, $color);
                        break;

                    case 13:
                        ImageFilledRectangle($im, 245, 315, 315, 385, $color);
                        ImageFilledRectangle($im, 105, 315, 175, 385, $color);
                        break;

                    case 14:
                        ImageFilledRectangle($im, 175, 315, 245, 385, $color);
                        break;

                }
            }
        }
        $save = $newUserSubfolder . '/avatar.jpeg';
        imagejpeg($im, $save, 100);   //Saves the image

        imagedestroy($im);
    }

    public static function getData()
    {
        $db = Db::connect();
        $statement = $db->prepare("select * from user where id = :id");
        $statement->bindValue('id', Session::getInstance()->getUser()->id);
        $statement->execute();
        $user = $statement->fetch();

        return new User($user->id, $user->firstname, $user->lastname, $user->email, $user->image);
    }
}