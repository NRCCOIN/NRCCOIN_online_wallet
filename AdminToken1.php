<?php

namespace App\Models;

class AdminToken extends \Moloquent {
//class Admin extends Eloquent implements Authenticatable {
    
    protected $fillable = [
        'status','type','admin_id','token', 'ip', 'ua'
    ];
    
    protected $collection = 'admin_tokens';
    protected $hidden = [];

    public function admin()
    {
        return $this->belongsTo("\App\Models\Admin");
    }

    public function scopeActive($query) {
        return $query->where('status', '=', 1);
    }
    
    
    public static function inactive_token($type,$ua = "",$ip = "")
    {
        $ua = $ua == "" ? \Request::server("HTTP_USER_AGENT") : $ua;
        $ip = $ip == "" ? \Request::getClientIp() : $ip;
        $token = self::active()
                ->where("type","=",$type)
                ->where("ua","=",$ua)
                ->where("ip","=",$ip)
                ->get()->first();
        if(!is_null($token))
        {
            $token->status = 0;
            $token->save();
        }
    }
    
    public static function generate_auth_token()
    {
        static $call_cnt = 0;
        if($call_cnt > 10)
            return "";
        ++$call_cnt;
        $token = \General::rand_str(15);
        $user = self::active()->where("type",'=',"auth")->where("token",'=',$token)->first();
        if(isset($user->token))
        {
            return self::generate_auth_token();
        }
        return $token;
    }
    
    public static function save_token($param,$ua = "",$ip = "")
    {
        $ua = $ua == "" ? \Request::server("HTTP_USER_AGENT") : $ua;
        $ip = $ip == "" ? \Request::getClientIp() : $ip;
        
        $token = new Token();
        $token->fill($param);
        $token->ip = $ip;
        $token->ua = $ua;
        $token->status = isset($param['status']) ? $param['status'] : 1;
        $id = $token->save();
        return \General::success_res();
    }
    
    public static function is_active($type,$token,$ua = "",$ip = "")
    {
        $ua = $ua == "" ? \Request::server("HTTP_USER_AGENT") : $ua;
        $ip = $ip == "" ? \Request::getClientIp() : $ip;
        $user = self::active()
                ->where("type",'=',$type)
                ->where("token",'=',$token)
                ->where("ua",'=',$ua)
                ->where("ip",'=',$ip)
                ->first();
        if(isset($user->token))
        {
            return TRUE;
        }
        return FALSE;
    }
    
    public static function get_active_token($token_type)
    {
        $ua = $ua == "" ? \Request::server("HTTP_USER_AGENT") : $ua;
        $ip = $ip == "" ? \Request::getClientIp() : $ip;
        $token = self::active()
                ->where("type","=",$token_type)
                ->where("ua","=",$ua)
                ->where("ip","=",$ip)
                ->first();
        if(!is_null($token))
        {
            $token = $token->toArray();
            return $token['token'];
        }
        return FALSE;
    }

}
