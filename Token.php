<?php 

namespace App\Models;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Token extends Eloquent {
    
    public $table = 'users_token';
    protected $hidden = [];
    protected $fillable = array('type','user_id','token', 'ip', 'ua','status');
    
    
    public function scopeActive($query) {
        return $query->where('status', '=', 1);
    }
    
    public function userInfo(){
        return $this->hasOne('App\Models\Users','id','user_id');
    }
    
    
    public static function inactive_token($type)
    {
        $token = self::active()
                ->where("type","=",$type)
                ->where("ua","=",\Request::server("HTTP_USER_AGENT"))
                ->where("ip","=",\Request::getClientIp())
                ->get()->first();
        if(!is_null($token))
        {
            $token->status = 0;
            $token->save();
        }
    }
    
    public static function generate_activation_token()
    {
        static $call_cnt = 0;
        if($call_cnt > 50)
            return "";
        ++$call_cnt;
        $token = \General::rand_str(15);
        $user = self::active()->where("type",'=',\Config::get("constant.ACCOUNT_ACTIVATION_TOKEN_STATUS"))->where("token",'=',$token)->first();
        if(isset($user->token))
        {
            return self::generate_activation_token();
        }
        return $token;
    }
    
    public static function generate_forgotpass_token()
    {
        static $call_cnt = 0;
        if($call_cnt > 50)
            return "";
        ++$call_cnt;
        $token = \General::rand_str(15);
        $user = self::active()->where("type",'=',\Config::get("constant.FORGETPASS_TOKEN_STATUS"))->where("token",'=',$token)->first();
        if(isset($user->token))
        {
            return self::generate_activation_token();
        }
        return $token;
    }
    
    public static function generate_auth_token()
    {
        static $call_cnt = 0;
        if($call_cnt > 10)
            return "";
        ++$call_cnt;
        $token = \General::rand_str(15);
        $user = self::active()->where("type",'=',\Config::get("constant.AUTH_TOKEN_STATUS"))->where("token",'=',$token)->first();
        if(isset($user->token))
        {
            return self::generate_auth_token();
        }
        return $token;
    }
    
    public static function find_dead_token_id($token_type,$user_id)
    {
        $token = self::where("type",$token_type)
                ->where("ua","=",\Request::server("HTTP_USER_AGENT"))
                ->where("ip","=",\Request::getClientIp())
//                ->where("platform","=",app("platform"))
                ->where("user_id",$user_id)->first();
        if(is_null($token))
        {
            return FALSE;
        }
        return $token->id;
    }
    
    public static function save_token($param)
    {
        
        $token = new Token();
        $token->fill($param);
        $token->ip = \Request::getClientIp();
        $token->ua = \Request::server("HTTP_USER_AGENT");
        $token->status = isset($param['status']) ? $param['status'] : 1;
        $id = $token->save();
//        dd($token->toArray());
        $res = \General::success_res();
        $res['data'] = $token->toArray();
//        return \General::success_res();
        return $res;
    }
    public static function delete_token()
    {
//        $token = self::where('user_id', \Auth::guard('user')->user()->id)->delete();
        if(\Auth::guard('user')->user()){
//            dd(\Auth::guard('user'));
            $token = self::where('user_id', \Auth::guard('user')->user()->id)->first();
            if($token){
                $token->delete();
            }
        }
        
        return \General::success_res();
    }
    
    public static function is_active($type,$token)
    {
        $user = self::active()->where("type",'=',$type)->where("token",'=',$token)->first();
        if(isset($user->token))
        {
            return $user->user_id;
        }
        return FALSE;
    }
    
    public static function get_active_token($token_type)
    {
        $token = self::active()
                ->where("type","=",$token_type)
                ->where("ua","=",\Request::server("HTTP_USER_AGENT"))
                ->where("ip","=",\Request::getClientIp())
                ->first();
        if(!is_null($token))
        {
            $token = $token->toArray();
            return $token['token'];
        }
        return FALSE;
    }
    
    public static function delete_active_token($token_type)
    {
            if($token_type='auth'){
                $token_type=0;
            }
            $token = self::where('status', 1)->where("type","=",$token_type)->delete();
            
            return \General::success_res();
    }
        
}
