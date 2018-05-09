<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticableTrait;

//class Admin extends \Moloquent
class Admin extends Eloquent implements Authenticatable {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    use AuthenticableTrait;
    
    public function getAuthIdentifier() {
        return $this->getKey();
    }

    public function getAuthIdentifierName() {
        return $this->getKeyName();
    }

    public function getAuthPassword() {
        return $this->password;
    }

    public function getRememberToken() {
        return $this->{$this->getRememberTokenName()};
    }

    public function getRememberTokenName() {
        return 'remember_token';
    }

    public function setRememberToken($value) {
        $this->{$this->getRememberTokenName()} = $value;
    }

//    use Authenticatable, CanResetPassword;
    protected $fillable = [
        'name', 'email', 'password',
    ];
    
    protected $table = 'admin';

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    

}
