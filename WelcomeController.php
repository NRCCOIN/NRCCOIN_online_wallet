<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Request;
use App\Models\Admin\User;
use Mail;
use Log;
use Input;
use Session;
use DB;

class WelcomeController extends Controller {

    public function __construct() {
//        dd('ff');
    }

//    public function index($id='',$pid='') {
    
    public function getIndex() {
//       return view("site.comming_soon");
        $dt = date('Y-m-d H:i:s');
        $parentId='';
//         if($id=='signup'){
//             $idData=\App\Models\Users::where('id',$pid)->first();
//             if(!is_null($idData)){
//                 $parentId=$pid;
//             }else{
//                 $idData=\App\Models\Users::orderBy('id','ASC')->first();
//                 $parentId=$idData->id;
//             }
//         }
        $upcome_reward =\App\Models\CalenderIco::OrderBy('end_date','desc')->first();
        $date = new \DateTime($upcome_reward->end_date);
        $cnv_rate = \App\Models\Setting::where('name','conversion_rate')->value('val');
//        dd($cnv_rate);
        $date->sub(new \DateInterval('PT5H30M'));
//      ,"assets/js/site/bootstrap.min.js","assets/css/site/bootstrap.min.css", "assets/css/site/common_log.css"
        $view_data = [
            'header' => [
                "title" => config('constant.PLATFORM_NAME'),
                "js" => [],
                "css" => ["assets/css/app.min.css","assets/css/site/reset.min.css","assets/css/site/fonts.css","assets/css/site/style.css","assets/css/site/media.css","assets/css/site/font-awesome.min.css"],
            ],
            'body' => [
                
                'timer_date'=>$date->format('Y-m-d H:i:s'),
                'parent_id'=>$parentId,
                'cnv_rate'=>$cnv_rate,
            ],
            'footer' => [
                "js" => ['assets/js/site/jquery-3.1.1.min.js',"assets/js/site/particles.min.js","assets/js/site/jquery.countdown.min.js","assets/js/site/custom.js","assets/js/site/jquery.form.min.js"],
                "css" => []
            ],
        ];

//        dd(1);
//        return view("site.home", $view_data);
        return view("site.home1", $view_data);
    }

    
    public function getIndex2($id=''){

        $dt         = date('Y-m-d H:i:s');
        $parentId   = '';
        $upcome_reward =\App\Models\CalenderIco::OrderBy('end_date','desc')->first();
        $date = new \DateTime($upcome_reward->end_date);
       
        $date->sub(new \DateInterval('PT5H30M'));
       
        $view_data = [
            'header' => [
                "title" => config('constant.PLATFORM_NAME'),
                "js"    => [],
                "css"   => ["assets/css/app.min.css","assets/css/site/reset.min.css","assets/css/site/fonts.css","assets/css/site/style.css","assets/css/site/media.css","assets/css/site/font-awesome.min.css","assets/css/site/bootstrap.min.css","assets/css/site/common_log.css"],
            ],
            'body' => [
                'testimonial'   => "",
                'timer_date'    => $date->format('Y-m-d H:i:s'),
                'modalId'       => $id,
                'parent_id'     => $parentId,
            ],
            'footer' => [
                "js"    => ['assets/js/site/jquery-3.1.1.min.js',"assets/js/site/particles.min.js","assets/js/site/jquery.countdown.min.js","assets/js/site/custom.js","assets/js/site/jquery.form.min.js","assets/js/site/bootstrap.min.js"],
                "css"   => []
            ],
        ];

        return view("site.welcome", $view_data);
    }
    
    
    public function aboutUs() {
        $view_data = [
            'header' => [
                "title" => config('constant.PLATFORM_NAME'),
                "js" => [],
                "css" => ["assets/css/app.min.css", "assets/css/search.min.css"]
            ],
            'body' => [
            ],
            'footer' => [
                "js" => [],
                "css" => []
            ],
        ];
        return view("site.about", $view_data);
    }
    
    
    
    
        public function faq() {
        $view_data = [
            'header' => [
                "title" => config('constant.PLATFORM_NAME'),
                "js" => [],
                "css" => ["assets/css/app.min.css","assets/css/search.min.css"]
            ],
            'body' => [
            ],
            'footer' => [
                "js" => [],
                "css" => []
            ],
        ];
        return view("site.faq", $view_data);
    }
    public function tnc() {
        $view_data = [
            'header' => [
                "title" => config('constant.PLATFORM_NAME'),
                "js" => [],
                "css" => ["assets/css/app.min.css", "assets/css/search.min.css"]
            ],
            'body' => [
            ],
            'footer' => [
                "js" => [],
                "css" => []
            ],
        ];
        return view("site.tnc", $view_data);
    }

    public function privacyPolicy() {
        $view_data = [
            'header' => [
                "title" => config('constant.PLATFORM_NAME'),
                "js" => [],
                "css" => ["assets/css/app.min.css", "assets/css/search.min.css"]
            ],
            'body' => [
            ],
            'footer' => [
                "js" => [],
                "css" => []
            ],
        ];
        return view("site.privacyPolicy", $view_data);
    }


    public function forgotPassword() {
        $view_data = [
            'header' => [
                "title" => "Forgot Password",
                "active_menu" => "active_login",
                "js" => [""],
                "css" => [""]
            ],
            'body' => [
            ],
            'footer' => [
                "js" => [],
                "css" => []
            ],
        ];
//        return view("site.forgotPassword", $view_data);
        return view("user.forgotpass", $view_data);
    }

    public function getSignup($id = '') {
//        dd('dsfadsf');
        if (\Auth::guard('user')->check()) {
            return \Redirect::to("user/dashboard");
        }
        
        $view_data = [
            'header' => [
                "title" => "SignUp",
                "js" => [],
                "css" => ["assets/css/app.min.css", "assets/css/search.min.css","assets/css/site/reset.min.css","assets/css/site/fonts.css","assets/css/site/style.css","assets/css/site/media.css","assets/css/site/font-awesome.min.css"],
            ],
            'body' => [
                'parent_id' => $id
            ],
            'footer' => [
                "js" => ['assets/js/site/jquery-3.1.1.min.js',"assets/js/site/particles.min.js","assets/js/site/jquery.countdown.min.js","assets/js/site/custom.js"],
                "css" => []
            ],
        ];

        return view("user.signup", $view_data);
    }

    
    public function getLogin() {
//        dd('Hello');
        if (\Auth::guard('user')->check()) {
            return \Redirect::to("user/dashboard");
        }
        
        $view_data = [
            'header' => [
                "title" => "Login",
                "js" => [],
                "css" => [],
            ],
            'body' => [
                'testimonial'=> "",
            ],
            'footer' => [
                "js" => [],
                "css" => []
            ],
        ];
        return view("user.login", $view_data);
    }
    
    public function postForgotPass() {

        $view_data_back = [
            'header' => [
                "title" => "Forgot Password",
                "js" => [],
                "css" => ["assets/css/app.min.css", "assets/css/search.min.css"]
            ],
            'body' => [
            ],
            'footer' => [
                "js" => [],
                "css" => [],
                'flag' => 1,
            ],
        ];
        $validator = \Validator::make(\Input::all(), \Validation::get_rules("user", "forget_pass"));
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            return view('site.forgotpass', $view_data_back)->withErrors($validator);
        }
        $param = \Input::all();
        $res = \App\Models\Users::forget_password($param);
//        dd($param,$res);
        
        return view('site.forgotpass', $view_data_back)->withErrors(['msg'=>$res['msg']]);
    }

    public function postLogin(Request $req) {
       
        $validator = \Validator::make(\Input::all(), \Validation::get_rules("user", "login"));
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            
            $view_data = [
                'header' => [
                    "title" => config('constant.PLATFORM_NAME'),
                    "js" => [],
                    "css" => ["assets/css/app.min.css", "assets/css/search.min.css"]
                ],
                'body' => [
                ],
                'footer' => [
                    "js" => [],
                    "css" => [],
                    'flag' => 1,
                ],
            ];
            return view('site.login', $view_data)->withErrors($validator);
        }
        $param = $req->input();
        $res = Users::doLogin($param);
        if ($res['flag'] == 0) {
            $view_data = [
                'header' => [
                    "title" => config('constant.PLATFORM_NAME'),
                    "js" => [],
                    "css" => ["assets/css/app.min.css", "assets/css/search.min.css"]
                ],
                'body' => [
                ],
                'footer' => [
                    "js" => [],
                    "css" => [],
                    'flag' => 1,
                ],
            ];
            return view('site.login', $view_data)->withErrors('Wrong User Id or Password !!');

//            return view('site.login',$view_data)->withErrors('Wrong User Id or Password !!');
//            return redirect()->back()->withErrors('Wrong User Id or Password !!');;
        }
        return \Redirect::to("user/dashboard");
    }
    
    public function postSignup(Request $req) {
        
        $setting=app('settings');
        if($setting['signup_disable_status']==1){
                \Session::put('auth_msg','New Registration is Disabled temporarily');
                return redirect()->back();
        }
       
        $custome_msg = [
            'email.required' => 'Email Address Require',
            'email.email' => 'Invalid Email Address',
            'password.required' => 'Password Require',
        ];
        $validator = \Validator::make(\Input::all(), \Validation::get_rules("user", "signup"), $custome_msg);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $res = \General::validation_error_res($error[0]);
            $res['data'] = $error;
            \Session::set("msg", json_encode($res));
            return \Redirect::back()->withInput();
        }
        $param = \Input::all();
        $res = \App\Models\Users::signup($param);
        \Session::set("msg", json_encode($res));
        \Session::set("success_sign", 1);
        return redirect('signup');
    }
    
    public function postResetPass() {
        $param = \Input::all();
//        dd($param);
        $view_data_back = [
            'header' => [
                "title" => config('constant.PLATFORM_NAME'),
                "js" => [],
                "css" => ["assets/css/app.min.css", "assets/css/search.min.css"]
            ],
            'body' => [
                'forgorttoken' => $param['forgottoken'],
            ],
            'footer' => [
                "js" => [],
                "css" => [],
                'flag' => 1,
            ],
        ];
        $validator = \Validator::make(\Input::all(), \Validation::get_rules("user", "reset_pass"));
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            return view('site.resetpass', $view_data_back)->withErrors($validator);
        }
        
        if($param['new_pass'] != $param['cnew_pass']){
            return view('site.resetpass', $view_data_back)->withErrors(['msg' => 'New Password and Confirm Password not Matched.' ]);
        }
        
        $user = \App\Models\Token::where('type',3)->where('token',$param['forgottoken'])->first();
        if(!is_Null($user)){
//            dd($param,$user->toArray());
            $userInfo = \App\Models\Users::where('id',$user->user_id)->first();
            if(is_Null($userInfo)){
                return view('site.resetpass', $view_data_back)->withErrors(['msg' => 'User Not Found.' ]);
            }
            $userInfo->password = \Hash::make($param['new_pass']);
            $userInfo->save();
            $user->delete();
        }
//        dd($param,$userInfo->toArray(),$user->toArray());
        return \Redirect('login');
    }
    
    public function postSendInquiryMail() {
        
        $param=Input::all();
       
        $user = \App\Models\Admin\User::first();
       
        $validator = \Validator::make(\Input::all(), \Validation::get_rules("user", "request"));
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $res = \General::validation_error_res($error[0]);
            $res['data'] = $error;
            return $res; 
            
        }
        
        $mailData['name']=$param['name'];
        $mailData['emailFrom']=$param['email'];
        $mailData['emailTo']=$user->email;
        $mailData['mail_subject'] = 'New Meeting Request';
    
        $mail['msg']=$param['message'];
        
        
        \Mail::send('emails.user.meet_request_mail', $mail, function ($message) use ($mailData) {
            $message->from($mailData['emailFrom'],$mailData['name'])->to($mailData['emailTo'])->subject('New Meeting Request');
        });
        
       $res=\General::success_res('Your Request Successfully Sent');
       return \Response::json($res);
    }
    
    public function postNewSubscription() {
        
        $param=Input::all();
       
        $validator = \Validator::make(\Input::all(), \Validation::get_rules("user", "subscription"));
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $res = \General::validation_error_res($error[0]);
            $res['data'] = $error;
            return $res; 
            
        }
        $res=\App\Models\Subscription::where('email',$param['email'])->first();
        
        if(!is_null($res)){
            return \General::success_res('Your are already Subscribed');
        }
            
        $res=\App\Models\Subscription::add_subscription($param);
        if($res['flag']==1){
            return \General::success_res('Your Subscription Request Successfully Sent');
        }else{
            return \General::error_res('Some Error Occur');
        }
        
    }
    
    public function getRoadmap() {
        return view("site.roadmap");
    }
}
