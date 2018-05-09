<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticableTrait;

class Users extends Model implements Authenticatable {

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
        'name', 'email', 'password','status', 'mobile', 'wb','dob', 'avatar', 'last_ip','last_ua', 'device_type','device_token',
    ];
    
    protected $table = 'users';

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'remember_token',
    ];
    
    public static function doLogin($param){
//        dd($param);
        if(isset($param['remember']))
        {
            \Cookie::get("remember",1);
            if($param['remember']=='on')
                $param['remember']=1;
            else
                $param['remember']=0;
//            \App\Models\Admin\Settings::set_config(['sanitize_input' => $param['remember']]);
        }
        $user = self::where("email", $param['user_email'])->first();
        $res['data']=$user;
        
//        dd($user->toArray());
        
        $res['flag']=0;
        if (is_null($user)) {
            $res['flag']=0;
            return $res;
        }
//        dd($user->password,$param);
        // if ($user->password != $param['user_pass']) {
        if (!\Hash::check($param['user_pass'], $user->password)) {
            $res['flag']=0;
            return $res;
        }
        if ($user->status == 2) {
            $res['flag'] =4; 
//           
            return $res;
        }
        if(isset($param['remember']) && $param['remember']==1)
        {
            $auth_token = \App\Models\Token::generate_auth_token();
            
//            $token_data = ['user_id' => $user->id,'token' => $auth_token,'type' => 'auth'];
            $token_data = ['user_id' => $user->id,'token' => $auth_token,'type' => \Config::get("constant.AUTH_TOKEN_STATUS")];
            \App\Models\Token::save_token($token_data);
            \Auth::guard("user")->loginUsingId($user->id,true);
        }
        else{
            $auth_token = \App\Models\Token::generate_auth_token();
            
           // $session_data = $user->id;
//            $token_data = ['user_id' => $user->id,'token' => $auth_token,'type' => 'auth'];
            $token_data = ['user_id' => $user->id,'token' => $auth_token,'type' => \Config::get("constant.AUTH_TOKEN_STATUS")];
            \App\Models\Token::save_token($token_data);
           // \Session::put('bit_uid',$session_data);
            \Auth::guard("user")->loginUsingId($user->id);
        }
        
        //$res['data']=$token_data;
        $res['flag']=1;
        return $res;
    }
    
    public static function do_login($param) {
//        dd($param);
        if (($param['type'] == "facebook" || $param['type'] == "google") && (!isset($param['access_token']) || $param['access_token'] == "")) {
            return \General::error_res("access_token_missing");
        }
        if ($param['type'] == "facebook") {
            $res = \App\Models\Services\General::check_facebook_access_token($param['access_token']);
            if ($res['flag'] != 1)
                return $res;
            $param['user_email'] = $res['data']['email'];
        } 
        elseif ($param['type'] == "google") {
            $res = \App\Models\Services\General::check_google_access_token($param['access_token']);
            if ($res['flag'] != 1)
                return $res;
            $param['user_email'] = $res['data']['email'];
        }
        
       $user = self::where("email", $param['user_email'])->first();
       if(is_null($user) && $param['type']='normal'){
          return \General::error_res("User Not Found");
       }
        if($param['type']!='normal'){
            if (is_null($user)) {
                $data = ['email' => $param['user_email'], "password" => \General::rand_str(5), "name" => isset($param['name']) ? $param['name'] : "","mobile" => isset($param['mobile']) ? $param['mobile'] : "","parent_id" => "","device_token" => $param['device_token']];
                if ($param['type'] == "facebook") {
                    $data['fb_status'] = 1;
                } else if ($param['type'] == "google") {
                    $data['g_status'] = 1;
                }


                self::signup($data);

                $user = self::where("email", $param['user_email'])->first();
            }
        }
     
        // if ($param['type'] == "normal" && $param['user_pass'] != $user->password ) {
        if ($param['type'] == "normal" && \Hash::check($param['user_pass'], $user->password )) {
            
            return \General::error_res("invalid_email_password");
        }
        if (($param['type'] == "normal") && $user->status == \Config::get("constant.USER_PENDING_STATUS")) {
            return \General::error_res("email_not_verified");
        }
        if ($user->status == \Config::get("constant.USER_SUSPEND_STATUS")) {
              
            return \General::error_res("account_suspended");
        }
        $user->device_token = $param['device_token'];
        $user->device_type = app("platform");
        $user->save();
        
//        $dead_token_id = \App\Models\Token::find_dead_token_id('auth', $user->id);
        $dead_token_id = \App\Models\Token::find_dead_token_id(\Config::get("constant.AUTH_TOKEN_STATUS"), $user->id);
        $platform = app("platform");
        $token = \App\Models\Token::generate_auth_token();
        if ($token == "")
            return \General::error_res("try_again");

        $data = ["type" => \Config::get("constant.AUTH_TOKEN_STATUS"), "platform" => $platform, "user_id" => $user->id, "token" => $token, "ip" => \Request::getClientIp(), "ua" => \Request::server("HTTP_USER_AGENT")];
//        $data = ["type" => 'auth', "platform" => $platform, "user_id" => $user->id, "token" => $token, "ip" => \Request::getClientIp(), "ua" => \Request::server("HTTP_USER_AGENT")];

        if ($dead_token_id) {
            $data['id'] = $dead_token_id;
        }
        \App\Models\Token::save_token($data);
        $user_data = $user->toArray();
//        $user_data['avatar'] = self::get_image_url($user['id'],$user['avatar']);
        $user_data['auth_token'] = $token;
//        $user_data['mobile'] = \App\Models\Services\General::formate_mobile_no($user_data['mobile']);
        unset($user_data['password']);
        if(!\Request::wantsJson())
        {
            \Auth::guard("user")->loginUsingId($user['id']);
        }
         
        $res = \General::success_res();
        $res['data'] = $user_data;
         
        return $res;
    }
    
    public static function get_image_url($id, $file_name = "") {
        $default_path   = \URL::to("assets/img/userProfile/nobody_m.jpg");
        $file_path      = 'assets/img/userProfile/'. $file_name;
        $file_path1     = 'assets/img/userProfile\\'. $file_name;
        if ($file_name != '' && file_exists(public_path().'\\' . $file_path1))
        {
            $file_url = asset($file_path);
        }
        else
        {
            $file_url = asset($default_path);
        }
        return $file_url;
    }

    
    public static function get_user_list($param){
        
        dd($param);
        $count=self::orderBy('id','desc');
        if(isset($param['search']) && $param['search']!=''){
            $count=self::where('name','like','%'.$param['search'].'%')->orWhere('mobile','like','%'.$param['search'].'%');
        }
        if(isset($param['status']) && $param['status']!=''){
            $count = $count->where('status',$param['status']);
        }
        $count = $count->count();
        $page=$param['crnt'];
        $len=$param['len'];
        $op=$param['opr'];
        $total_page=ceil($count/$len);
        $flag=1;
        
        $start;
        
        if($op!=''){
            if($op=='first'){
                $crnt_page=1;
                $start=($crnt_page-1)*$len;
            }
            
            elseif($op=='prev'){
                $crnt_page=$page-1;
                if($crnt_page<=0){
                    $crnt_page=1;
                }
                $start=($crnt_page-1)*$len;
            }

            elseif($op=='next'){
                $crnt_page=$page+1;
                if($crnt_page>=$total_page){
                    $crnt_page=$total_page;
                }
                $start=($crnt_page-1)*$len;
            }

            else{
                $crnt_page=$total_page;
                $start=($crnt_page-1)*$len;
            }
        }

        else{
            if($page>$total_page){
//                $flag=0;
                $crnt_page=$page-1;
                $start=($crnt_page-1)*$len;
            }
            else{
                $crnt_page=$page;
                $start=($crnt_page-1)*$len;
            }
        }
        
//        dd(config('constant.SP_TYPE'));
        $udata=self::orderBy('id','desc');
        
        if(isset($param['search']) && $param['search']!=''){
            $crnt_page=1;
            $start=($crnt_page-1)*$len;
            $udata=self::where('name','like','%'.$param['search'].'%')->orWhere('mobile','like','%'.$param['search'].'%');
        }
        if(isset($param['status']) && $param['status']!=''){
            $udata = $udata->where('status',$param['status']);
        }
        $udata = $udata->skip($start)->take($len)->get()->toArray();
        
        $res['len']=$len;
        $res['crnt_page']=$crnt_page;
        $res['total_page']=$total_page;
        
        $res['result']=$udata;
        $res['flag']=$flag;
        return $res;
    }
    public static function edit_user($param){
        if(isset($param['id'])){
            $u = self::where('id',$param['id'])->first();
            if(is_null($u)){
                return \General::error_res('no user found');
            }
            if(isset($param['status'])){
                $u->status = $param['status'];
            }
            $u->save();
            $res = \General::success_res('user edited successfully !!');
            $res['data'] = $u;
            return $res;
        }else{
            return \General::error_res('no user found');
        }
    }
    public static function delete_user($param){
        if(isset($param['id'])){
            $u = self::where('id',$param['id'])->first();
            if(is_null($u)){
                return \General::error_res('no user found');
            }
            $u = self::where('id',$param['id'])->delete();
            $res = \General::success_res('user deleted successfully !!');
            return $res;
        }else{
            return \General::error_res('no user found');
        }
    }
    
    public static function signup($param) {
        
        $pidData=\App\Models\Users::where('id',$param['parent_id'])->first();
        if(is_null($pidData)){
            $idData=\App\Models\Users::orderBy('id','ASC')->first();
            $parentId=$idData->id;
        }else{
            $parentId=$param['parent_id'];
        }
        
//        dd($param);
        $user = new Users();
        $user->name    = $param['name'];
        $user->email    = $param['email'];
        // $user->password = $param['password'];
        $user->password = \Hash::make($param['password']);
        $user->mobileno = isset($param['mobile']) ? $param['mobile'] : '';
        $user->parent_id    = $parentId;
        $user->btc_address  = isset($param['user_btcadd'])? $param['user_btcadd']:'';
        $user->status   = \Config::get("constant.USER_PENDING_STATUS");
        $user->coin     = 0;
        $user->balance  = 0;
        $user->binary_balance   = 0;
        $user->last_ip      = \Request::getClientIp();
        $user->last_ua      = \Request::server("HTTP_USER_AGENT");
        $user->device_token = '';
        $user->device_type  = app("platform");
        $user->dob      = isset($param['user_dob']) ? $param['user_dob'] : '';
        $user->save();

//        if(!\Request::wantsJson())
//        {
//            \Auth::guard("user")->loginUsingId($user->id);
//        }
        
        $activation_token = \App\Models\Token::generate_activation_token();
        $user['activation_token'] = $activation_token;
        $data = ['status' => 1, 'type' => \Config::get("constant.ACCOUNT_ACTIVATION_TOKEN_STATUS"), 'platform' => app("platform"), 'user_id' => $user->id, 'token' => $activation_token, "ip" => \Request::getClientIp(), "ua" => \Request::server("HTTP_USER_AGENT")];
        $user_obj = $user;
        $user = $user->toArray();
        \App\Models\Token::save_token($data);

//        $user['auth_token'] = \App\Models\Token::generate_auth_token();
//
//        $data = ['status' => 1, 'type' => \Config::get("constant.AUTH_TOKEN_STATUS"), 'platform' => app("platform"), 'user_id' => $user['id'], 'token' => $user['auth_token'], "ip" => \Request::getClientIp(), "ua" => \Request::server("HTTP_USER_AGENT")];
//        \App\Models\Token::save_token($data);
        
//        unset($user['activation_token']);
        unset($user['password']);
        $user['mail_subject']='Email Verification mail';
        $userm['from']=config('constant.SUPPORT_MAIL');
        $userm['name']=config('constant.PLATFORM_NAME');
        $userm['email']=$user['email'];
//        dd($userm);
//        from(['address'=>$userm['from'],'name'=>$userm['name']])->
        \Mail::send('emails.user.signup_mail', $user, function ($message) use ($userm) {
            $message->to($userm['email'])->subject('New Signup Request');
        });
        
        $res = \General::success_res("Congratulation!! Your signup was successful. Please verify your email.");
        $res['data'] = $user;
        return $res;
    }
    
    public static function is_logged_in($token) {
        
        if (\Request::wantsJson()) {
           
            if ($token == "") {
                return \General::session_expire_res();
            }
//            $already_login = \App\Models\Token::is_active('auth', $token);
            $already_login = \App\Models\Token::is_active(\Config::get("constant.AUTH_TOKEN_STATUS"), $token);
//            dd($already_login);
            if ( $already_login===false){
                 
                return \General::session_expire_res("unauthorise");
            }
            else {
                
                $user = \App\Models\Users::where("id", $already_login)->first()->toArray();
                unset($user['password']);
                $user['auth_token'] = $token;
                app()->instance('logged_in_user', $user);
                return \General::success_res("");
            }
        } else {
           
            if (!\Auth::guard('user')->check()) {
                
                \Auth::guard('user')->logout();
                $validator = \Validator::make([], []);
               
                $validator->errors()->add('attempt', \Lang::get('error.session_expired', []));
                return \General::session_expire_res("unauthorise");
                
                
            } else {
                
                $user_data = \Auth::guard('user')->user();
                unset($user_data->google2fa_secret);
                $user=$user_data->toArray();
                
//                $user = [
//                    'id'        => $user_data->id,
//                    'email'     => $user_data->email,
//                    'name'      => $user_data->name,
//                    'gender'    => $user_data->gender,
//                    'dob'       => $user_data->dob,
////                    'user_landno' => $user_data->user_landno,
//                    'mobile'    => $user_data->mobile,
////                    'user_maritalstatus' => $user_data->user_maritalstatus,
////                    'user_occupation' => $user_data->user_occupation,
////                    'user_address1_1' => $user_data->user_address1_1,
////                    'user_address1_2' => $user_data->user_address1_2,
////                    'user_address1_city' => $user_data->user_address1_city,
////                    'user_address1_state' => $user_data->user_address1_state,
////                    'user_address1_pin' => $user_data->user_address1_pin,
////                    'user_address1_country' => $user_data->user_address1_country,
////                    'user_address2_1' => $user_data->user_address2_1,
////                    'user_address2_2' => $user_data->user_address2_2,
////                    'user_address2_city' => $user_data->user_address2_city,
////                    'user_address2_state' => $user_data->user_address2_state,
////                    'user_address2_country' => $user_data->user_address2_country,
////                    'user_address2_pin' => $user_data->user_address2_pin,
////                    'user_typeID' => $user_data->user_typeID,
//                    'last_ip'   => $user_data->last_ip,
//                    'date'      => $user_data->date,
//                    'status'    => $user_data->status,
//                    'fb_status' => $user_data->fb_status,
//                    'g_status'  => $user_data->g_status,
//                    'updated_at'=> $user_data->updated_at,
//                ];

                $ua = \Request::server("HTTP_USER_AGENT");
                $ip = \Request::server("REMOTE_ADDR");

//                $session = \App\Models\Token::active()->where("type", 'auth')->where("ua", $ua)->where("ip", $ip)->where("user_id", $user['id'])->first();
                $session = \App\Models\Token::active()->where("type", \Config::get("constant.AUTH_TOKEN_STATUS"))->where("ua", $ua)->where("ip", $ip)->where("user_id", $user['id'])->first();
                if (is_null($session)) {
                    \Auth::guard('user')->logout();
                    $user['auth_token'] = "";
                } else {
                    $user['auth_token'] = $session['token'];
                }
                app()->instance('logged_in_user', $user);
            }
        }
        return \General::success_res();
    }
    
    public static function update_profile($param) {
        $id = $param['user_id'];
        $user = self::where("id", $id)->first();
        if (is_null($user)) {
            return \General::error_res("invalid_user");
        }
        
        if(isset($param['user_mobileno'])){
            $muser =self::where("id",'!=',$param['user_id'])->where("mobileno",$param['user_mobileno'])->first();
            if (!is_null($muser)) {
                return \General::error_res("Mobile No is Already taken");
            }
        }
        
        
        if (\Input::hasFile('avatar') || (isset($param['avatar']) && $param['avatar'] != "")) {
            $old_avatar = $user->avatar;
            $old_avatar = substr($old_avatar, strrpos($old_avatar, "/") + 1);
            $dir_path = \Config::get('constant.USER_AVATAR_PATH');
            if (!file_exists($dir_path)) {
                mkdir($dir_path, 0777, true);
            }
            if (\Input::hasFile('avatar')) {
                $ext = \Input::file('avatar')->getClientOriginalExtension();
                if(!in_array(strtolower($ext), ["jpg","jpeg","png"]))
                {
                    return \General::error_res("File must be image");
                }
                $fileName = time() . "." . $ext;
//                dd(\Config::get('constant.USER_AVATAR_PATH'));
                \Input::file('avatar')->move(\Config::get('constant.USER_AVATAR_PATH'), $fileName);
            } else if (isset($param['avatar'])) {
                $fileName = time() . ".png";
                $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $param['avatar']));
                file_put_contents($dir_path . '/' . $fileName, $data);
            }
            $user->avatar = $fileName;
            $param['avatar'] = $fileName;
        } else {
            unset($param['avatar']);
        }
//        dd($param);
        

        if (isset($param['user_name']))
            $user->name = $param['user_name'];


        if (isset($param['user_dob']))
            $user->dob = $param['user_dob'];
        
        if (isset($param['user_email']))
            $user->email = $param['user_email'];


        if (isset($param['user_mobileno']))
        {
            $user->mobileno = $param['user_mobileno'];
        }
        
        if (isset($param['password']))
        {
            $user->password = \Hash::make($param['password']);
        }
        
        if (isset($param['user_btcadd']))
        {
            $user->btc_address = $param['user_btcadd'];
        }

        $user->last_ip = \Request::getClientIp();
//        $user->date = date("Y-m-d");

        $user->save();
        $res = \General::success_res("user_profile_updated");
        $user_data = $user->toArray();
        $user_data['avatar'] = self::get_image_url($user_data['id'],$user_data['avatar']);
        unset($user_data['password']);
        
        $user_data['mobile']=$user_data['mobileno'];
        unset($user_data['mobileno']);
        $res['data'] = $user_data;
        return $res;
    }
    
    public static function change_password($param) {
        $logged_in_user = app("logged_in_user");
        $id = $logged_in_user['id'];
        
//        dd($logged_in_user);
        $user = self::where("id", $id)->first();
        if (is_null($user)) {
            return \General::error_res("user_not_found");
        }
        
        if ($user->status == config("constant.USER_SUSPEND_STATUS")) {
            return \General::error_res("account_suspended");
        }
        
        // if($user->password != $param['old_password']){
        if(\Hash::check($param['old_password'],$user->password)){
            return \General::error_res("Wrong Old password.");
        }
        if($param['new_password'] != $param['confirm_password']){
            return \General::error_res("New and Confirm password do not match.");
        }
        
        \Log::info('Old Password : '.$user->password);
        $user->password = \Hash::make($param['new_password']);
        $user->save();
        \Log::info('New Password : '.$user->password);
        return \General::success_res("Password updated successfullly.");
    }
    
     public static function change_user_status($param){
        $user_id = $param['user_id'];
        $status = $param['status'];

        $user = self::where('id',$user_id)->first();
        if(!$user){
            return \General::error_res('no user found');
        }
        
        $user->status = $status;
        $user->save();
        
        if($status != 1){
            Token::where('user_id',$user_id)->where('type',config('constant.AUTH_TOKEN_STATUS'))->delete();
        }
        
        return \General::success_res('status changed successfully');
    }
      
    public static function edit_user_detail($param){
        $user_id = $param['user_id'];
        
        $user = self::where('id',$user_id)->first();
        if(!$user){
            return \General::error_res('no user found.');
        }
        
        $user->name = $param['user_name'];
        $user->email = $param['user_email'];
        $user->btc_address = $param['user_btcadd'];
        if($param['user_password']!=''){
            $user->password = \Hash::make($param['user_password']);
        }
        
        $user->save();
        
        return \General::success_res('user updated successfully');
    }
    
    public static function forget_password($param) {
        $user = self::where("email", $param['email'])->first();
        if (is_null($user)) {
            return \General::error_res("email_not_found");
        }
        if ($user->status == config("constant.USER_SUSPEND_STATUS")) {
            return \General::error_res("account_suspended");
        }
        
        $platform = app("platform");
        $user_detail = $user->toArray();
        
        $forgotpass_token = \App\Models\Token::generate_forgotpass_token();
        $user_detail['forgotpass_token'] = $forgotpass_token;
//        dd(\Config::get("constant.FORGETPASS_TOKEN_STATUS"));
        $data = ['status' => 1, 'type' => \Config::get("constant.FORGETPASS_TOKEN_STATUS"), 'platform' => app("platform"), 'user_id' => $user->id, 'token' => $forgotpass_token, "ip" => \Request::getClientIp(), "ua" => \Request::server("HTTP_USER_AGENT")];
        
        $token = \App\Models\Token::save_token($data);
//        dd($token);
        $user_detail['mail_subject'] = 'Forgot Password';
//        dd($user_detail);
//        echo \View::make("emails.user.forget_password",$user_detail)->render();
//        exit;

        \Mail::send('emails.user.forget_password', $user_detail, function ($message) use ($user_detail) {
            $message->to($user_detail['email'])->subject('Forgot Password');
        });
        
        return \General::success_res("forgot_password_mail_sent");
    }
    
    public static function resend_confirmation($param) {
        $user = self::where("email", $param['email'])->first();
        if (is_null($user)) {
            return \General::error_res("email_not_found");
        }
        if ($user->status == config("constant.USER_SUSPEND_STATUS")) {
            return \General::error_res("account_suspended");
        }
        
        $platform = app("platform");
        $user_detail = $user->toArray();
        
//        $user['activation_token'] = $activation_token;
        $forgotpass_token = \App\Models\Token::generate_forgotpass_token();
        $user_detail['forgotpass_token'] = $forgotpass_token;
//        dd(\Config::get("constant.FORGETPASS_TOKEN_STATUS"));
        $data = ['status' => 1, 'type' => \Config::get("constant.FORGETPASS_TOKEN_STATUS"), 'platform' => app("platform"), 'user_id' => $user->id, 'token' => $forgotpass_token, "ip" => \Request::getClientIp(), "ua" => \Request::server("HTTP_USER_AGENT")];
        
        $token = \App\Models\Token::save_token($data);
//        dd($token);
        $user_detail['mail_subject'] = 'Forgot Password';
//        dd($user_detail);
//        echo \View::make("emails.user.forget_password",$user_detail)->render();
//        exit;

        \Mail::send('emails.user.forget_password', $user_detail, function ($message) use ($user_detail) {
            $message->to($user_detail['email'])->subject('Forgot Password');
        });

        return \General::success_res("forgot_password_mail_sent");
    }
    
     public static function filter_users($param){
        
        $users = self::orderBy('id','desc');
        if(isset($param['name']) && $param['name'] != ''){
            $users = $users->where(function($q)use($param){
               $q->where('name','like','%'.$param['name'].'%')->orWhere('mobileno','like','%'.$param['name'].'%'); 
            });
        }
        if(isset($param['email']) && $param['email'] != ''){
            $users = $users->where('email','like','%'.$param['email'].'%');
        }
        if(isset($param['status']) && $param['status'] != ''){
            $users = $users->where('status',$param['status']);
        }
        $count = $users->count();
        
        $len = $param['itemPerPage'];
        $start = ($param['currentPage']-1) * $len;
        
        $users = $users->skip($start)->take($len)->get()->toArray();
        $res['data'] = $users;
        $res['total_record'] = $count;
        
        return $res;
    }
    
    public static function filter_referral_users($param){
         
        $count=self::where('parent_id',$param['loggerId'])->orderBy('id','asc');
       
        
        if(isset($param['name']) && $param['name'] != ''){  
               $count->where('name','like','%'.$param['name'].'%'); 
        }
        
        $count = $count->count();
//         dd($count);
        $page=$param['crnt'];
        $len=$param['len'];
        $op=  isset($param['opr'])?$param['opr']:'';
        $total_page=ceil($count/$len);
        $flag=1;
        
        $start=0;
        
        if($op!=''){
            if($op=='first'){
                $crnt_page=1;
                $start=($crnt_page-1)*$len;
            }
            
            elseif($op=='prev'){
                $crnt_page=$page-1;
                if($crnt_page<=0){
                    $crnt_page=1;
                }
                $start=($crnt_page-1)*$len;
            }

            elseif($op=='next'){
                $crnt_page=$page+1;
                if($crnt_page>=$total_page){
                    $crnt_page=$total_page;
                }
                $start=($crnt_page-1)*$len;
            }

            else{
                $crnt_page=$total_page;
                $start=($crnt_page-1)*$len;
            }
        }

        else{
            if($page>$total_page){
//                $flag=0;
                
                $crnt_page=$page-1;
                $start=($crnt_page-1)*$len;
            }
            else{
                
                $crnt_page=$page;
                $start=($crnt_page-1)*$len;
            }
        }
        
        
        $tokenReport=self::where('parent_id',$param['loggerId'])->skip($start)->take($len)->orderBy('id','asc');
        
        if(isset($param['name']) && $param['name'] != ''){
               $tokenReport->where('name','like','%'.$param['name'].'%');     
        }
        
        $tokenReport = $tokenReport->get()->toArray();
       
        $res['len']=$len;
        $res['crnt_page']=$crnt_page;
        $res['total_page']=$total_page;
        
        $res['result']=$tokenReport;
        $res['flag']=$flag;
//        dd($res);
        return $res;
     }
      public static function get_user($param){
        $users = self::orderBy('name','asc');
        if(isset($param['name']) && $param['name'] != ''){
            $users = $users->where('name','like','%'.$param['name'].'%')->orWhere('mobileno','like','%'.$param['name'].'%');
        }
        $users = $users->get()->toArray();
        
        return $users;
    }
    public static function manage_coin_balance($param){
        $user_id= $param['user_id'];
        $wType  = $param['wallet_type'];
        $trType = $param['transaction_type'];
        $amount = $param['amount'];
        $user   = self::where('id',$user_id)->first();
        if(!$user){
            return \General::error_res('no user found');
        }
        $coin       = $user->coin;
        $balance    = $user->balance;
        $btc_balance= $user->btc_balance;
        $eth_balance= $user->eth_balance;
        $earn_bal   = $user->earning_balance;
        if($wType == 'c' && $trType == 'd'){
            if($amount > $coin){
                return \General::error_res('User have No Suffecient Balance to Debit.');
            }
        }elseif($wType == 'b' && $trType == 'd'){
            if($amount > $balance){
                return \General::error_res('User have No Suffecient Balance to Debit.');
            }
        }
        elseif($wType == 'btc' && $trType == 'd'){
            if($amount > $btc_balance){
                return \General::error_res('User have No Suffecient Balance to Debit.');
            }
        }
        elseif($wType == 'eth' && $trType == 'd'){
            if($amount > $eth_balance){
                return \General::error_res('User have No Suffecient Balance to Debit.');
            }
        }
        elseif($wType == 'earn' && $trType == 'd'){
            if($amount > $earn_bal){
                return \General::error_res('User have No Suffecient Earning Balance to Debit.');
            }
        }
        
//        $client = new \App\Lib\coin\jsonRPCClient();
//        $r = $client->getnewaddress('testmain');
        
//        dd($param,$user->coin);
        
        return \General::success_res();
        
    }
}
