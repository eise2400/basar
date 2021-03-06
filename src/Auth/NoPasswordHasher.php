<?php

namespace App\Auth;

use Cake\Auth\AbstractPasswordHasher;

class NoPasswordHasher extends AbstractPasswordHasher
{

    public function hash($password)
    {
        return $password;
    }

    public function check($password, $hashedPassword)
    {
        return $password === $hashedPassword;
    }
}