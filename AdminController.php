<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Request;
use App\Models\Admin\User;
use App\Models\Serviceprovider;
use Illuminate\Support\Facades\Mail;
use DB;
use PragmaRX\Google2FA\Google2FA as Google2FA;
use \ParagonIE\ConstantTime\Base32;

class AdminController extends Controller {

    private static $bypass_url = ['getIndex', 'getLogin', 'postLogin'];
    private static $logger = '';


    public function __construct() {
        $this->middleware('AdminAuth', ['except' => self::$bypass_url]);
        self::$logger = config('constant.LOGGER');
    }

    public function getIndex() {
        if (!\Auth::guard('admin')->check()) {
//            return \Response::view('errors.401', array(), 401);
            return \Redirect::to('/');
        }

        return \Redirect::to('admin/dashboard');
    }

    public function getDashboard() {

        if (!\Auth::guard('admin')->check()) {
            return \Response::view('errors.401', array(), 401);
        }
        
     
        $users = \App\Models\Users::count();
        $coins = $s = \App\Models\Admin\Settings::get_config('total_btc')['total_btc'];
        $coin   = \App\Models\Admin\Settings::get_config('total_coin')['total_coin'];
        $pendingWithdraw =\App\Models\WithdrowRequest::where('status',0)->count();
        
        $admin = \Auth::guard('admin')->user()->toArray();
//        dd($admin);
                
        if($admin['wallet_address'] == ''){
            $check = 1;
            $c = 0;
            while(!is_null($check)){
                $coin_address = \App\Models\General::generate_coin_address();
                $check = \App\Models\Users::where('coin_address',$coin_address)->first();
                $c++;
                if($c == 10){
                    return \Response::view('errors.404', array('msg'=>'This Link is Temporily Unavailable. Please, Try After Some time.'), 404);
                }
            }
            
            $address_update = User::where('id',$admin['id'])->update(['wallet_address'=>$coin_address]);
            
            $admin = User::where('id',$admin['id'])->first()->toArray();
            
        }
        
        $size = 200;
        $string = $admin['wallet_address'];
        $img = '';
        if($string){
            $renderer = new \BaconQrCode\Renderer\Image\Png();
            $renderer->setWidth($size);
            $renderer->setHeight($size);

            $bacon = new \BaconQrCode\Writer($renderer);
            $data = $bacon->writeString($string, 'utf-8');

            $img = 'data:image/png;base64,'.base64_encode($data);
        }
        
        
       
        $view_data = [
            'header' => [
                "title" => 'Dashboard | Admin Panel ',
                "js"    => [],
                "css"   => [],
            ],
            'body' => [
                'id'    => 'dashboard',
                'lable' => 'Dashboard',
                'users' => $users,
                'admin' => $admin,
                'coins' => $coins,
                'coin'   => $coin,
               
                'pwr'   => $pendingWithdraw,
//                'wallet_address'=> $string,
                'wallet_address'=> $img,
                
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];
       
        return view("admin.dashboard", $view_data);
    }

    
    public function getLogin($sec_token = "") {
//        dd($sec_token);
        $s = \App\Models\Admin\Settings::get_config('login_url_token');
        if ($sec_token != $s['login_url_token']) {
            return \Response::view('errors.404', array(), 404);
        }

        if (\Auth::guard("admin")->check()) {
            return \Redirect::to("admin/dashboard");
        }
        $view_data = [
            'header' => [
                'title' => '',
            ],
            'body'=> [
                'logger' => 'Admin',
                'type' => 'A'
            ]
        ];
        return view('admin.login',$view_data);
    }

    public function postLogin(Request $req) {
        $view_data = [
            'header' => [
                'title' => '',
            ],
            'body'=> [
                'logger' => 'Admin',
                'type' => 'A'
            ]
        ];
        
         $custome_msg = [
            'g-recaptcha-response.required'   => 'Please ensure that you not robot.',
        ];
        
        $validator = \Validator::make(\Input::all(), \Validation::get_rules("admin", "login"));
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            
            return view('admin.login',$view_data)->withErrors($validator);
        }
        $param = $req->input();
        $res = User::doLogin($param);
        if ($res['flag'] == 0) {
            return view('admin.login',$view_data)->withErrors('Wrong User Id or Password !!');
//            return \Redirect::to('/');
        }
        $user = $res['data'];
//        dd($user);
        if($user['google2fa_secret']){
           $check = \General::google_authenticate($user,$param);
           if($check['flag'] != 1){
//                       return $check;
               \Auth::guard('admin')->logout();
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
//                return view('site.login', $view_data)->withErrors('Email Id is Not Verified');
                return view('admin.login', $view_data)->withErrors($check['msg']);
           }
        }
        return \Redirect::to("admin/dashboard");
    }

    public function getLogout() {

        \App\Models\Admin\Token::delete_token();
        \Auth::guard('admin')->logout();
        $s = \App\Models\Admin\Settings::get_config('login_url_token');
        return redirect("admin/login/" . $s['login_url_token']);
    }

    public function getProfile($msg = "") {
        $res = User::getProfile();
        $view_data = [
            'header' => [
                "title" => 'Profile | Admin Panel BusTiket',
                "js" => [],
                "css" => [],
            ],
            'body' => [
                'name' => isset($res['name']) ? $res['name'] : "",
                'email' => isset($res['email']) ? $res['email'] : "",
                'msg' => $msg
            ],
            'footer' => [
                "js" => [],
                "css" => []
            ],
        ];
        return view("admin.profile", $view_data);
    }
    
    public function postChangeAdminPassword() {
        $param = \Input::all();
//        dd($param);
        $res = User::change_admin_password($param);
//        dd($res);
        if (isset($res['flag'])) {
            if ($res['flag'] == 0) {
                return \Redirect::to('admin/profile/' . $res['msg']);
            } else if ($res['flag'] == 1) {
//                return \Redirect::to("admin/dashboard");
                return \Redirect::to("admin/logout");
            }
        }
    }
    
    public function getUserList() {


        $view_data = [
            'header' => [
                'title' => 'Users List',
                'css'=>['assets/css/jquery.ui.css'],
                'js'=>[],
            ],
            'body'=> [
                'id'=>'users_list',
                'lable'=>'Users',
            ],
            'footer'=>[

                'js'=>['jquery.ui.js','ckeditor/ckeditor.js'],
            ],
        ];
        return view('admin.users_list',$view_data);
    }

    public function postUserFilter(){
        $param = \Input::all();
        
        $users = \App\Models\Users::filter_users($param);
 //       dd($users);
        $res = \General::success_res();
        $res['blade'] = view("admin.users_filter", $users)->render();
        $res['total_record'] = $users['total_record'];
//        dd($res['blade']);
        return $res;
    }
    
    public function postUserName(){
        $param = \Input::all();
        $user = \App\Models\Users::get_user($param);
        if(is_null($user)){
            return \General::error_res('no users found');
        }
        $res = \General::success_res();
        $isMobile = isset($param['is_mobile']) ? $param['is_mobile'] : 0;
        $r = [];
        foreach($user as $a){
            $r[] = [
                'key'=>$a['id'],
                'value'=>$isMobile ? $a['name'] . ' - '.$a['mobileno'] : $a['name'],
            ];
            
        }
        
        $res['data'] = $r;
        return \Response::json($res, 200);
    }
    
    public function postUserTransaction(){
        $param = \Input::all();
        
//        dd($param['wallet_type']);
        $rules = [
            'user_id'=>'required',
            'amount'=>'required',
            'wallet_type'=>'required|in:c,b,btc,eth,earn',
            'transaction_type'=>'required|in:c,d',
        ];
        if($param['amount']==0){
            return \General::error_res('Please Enter Valid Amount');
        }
        $validator = \Validator::make(\Input::all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $json = \General::validation_error_res();
            $json['data'] = $error;
            $json['msg'] = $error[0];
            return \Response::json($json, 200);
        }
//        dd($param);
        
        $user =  \App\Models\Users::where('id',$param['user_id'])->first();
        if(!$user){
            return \General::error_res('no user found');
        }
        
        $userCD = \App\Models\Users::manage_coin_balance($param);
        if($userCD['flag'] != 1){
            return $userCD;
        }
        
        
        $btc_balance = $user->btc_balance;
        $eth_balance = $user->eth_balance;
        
        
        $param['reference_id'] = null;
        $param['type']  = 3;
        $param['credit']= $param['transaction_type'] == 'c' ? $param['amount'] : 0;
        $param['debit'] = $param['transaction_type'] == 'd' ? $param['amount'] : 0;
        
        if($param['wallet_type'] == 'c'){
//            dd($param['wallet_type']);
            $trn = \App\Models\CoinTransactions::add_transaction($param);
            
            $admin = \Auth::guard('admin')->user()->toArray();
            
            $data['user_id'] = $param['user_id'];
            $data['admin_id'] = $admin['id'];
            $data['reference_id'] = null;
            $data['type']   = $param['transaction_type'] == 'c' ? 0 : 1;
            $data['credit'] = $param['transaction_type'] == 'c' ? 0 : $param['amount'];
            $data['debit']  = $param['transaction_type'] == 'd' ? 0 : $param['amount'];
            $data['comment']= $param['transaction_type'] == 'c' ? 
                                    $param['amount'].' '.config('constant.COIN_SHORT_NAME').' Reward to User '.$param['user_id'] : $param['amount'].' '.config('constant.COIN_SHORT_NAME').' Penalty to User '.$param['user_id'];
            
            $admin_trn = \App\Models\Admin\AdminCoinTransactions::add_transaction($data);
        }
        else if($param['wallet_type'] == 'btc'){
//            dd($param['wallet_type']);
            if($param['transaction_type']=='c'){
                    $new_balance = $btc_balance + $param['amount'];
                }else if($param['transaction_type'] == 'd'){
                    $new_balance = $btc_balance - $param['amount'];
                }
                $res = \App\Models\Users::where('id',$param['user_id'])->update(['btc_balance'=>$new_balance]);
                $trn = \App\Models\BtcTransactions::add_transaction($param);
            }
        else if($param['wallet_type'] == 'eth'){
//            dd($param['wallet_type']);
            if($param['transaction_type'] == 'c'){
                $new_balance = $eth_balance + $param['amount'];
            }else if($param['transaction_type']=='d'){
                $new_balance = $eth_balance - $param['amount'];
            }
            
            $res = \App\Models\Users::where('id',$param['user_id'])->update(['eth_balance'=>$new_balance]);  
            $trn = \App\Models\EthTransactions::add_transaction($param);
        }
        else if($param['wallet_type'] == 'earn'){
//            dd($param['wallet_type']);
            $trn = \App\Models\EarningHistory::add_transaction($param);
        }
        else if($param['wallet_type'] == 'b'){
//            dd($param['wallet_type']);
            $trn = \App\Models\BalanceTransactions::add_transaction($param);
        }
        
        $res = \General::success_res('your transaction successfully done.');
        
        return $res;
    }
    
    public function getUserDetail($id = '') {
        
        if($id == ''){
            return redirect('admin/user-list');
        }
        
        $user = \App\Models\Users::where('id',$id)->first();
        
        if(!$user){
            return redirect('admin/user-list');
        }
        $user = $user->toArray();
        
        $allCoin = \App\Models\CoinTransactions::where('user_id',$id)->count();
        $allBal  = \App\Models\BalanceTransactions::where('user_id',$id)->count();
        $allBtc  = \App\Models\BtcTransactions::where('user_id',$id)->count();
        $allEth  = \App\Models\EthTransactions::where('user_id',$id)->count();
        $allEarn = \App\Models\EarningHistory::where('user_id',$id)->count();
        
        $withdraw = \App\Models\WithdrowRequest::where('user_id',$id)->count();
        $user['allcoin']    = $allCoin;
        $user['allbalance'] = $allBal;
        $user['allBitcoin'] = $allBtc;
        $user['allEtherium']= $allEth;
        $user['allEarning'] = $allEarn;
        $user['withdraw']   = $withdraw;
        
        $prev = \URL::previous();
        $prev = explode('/', $prev);
        $prev = end($prev);
        $lable = '<a href="'.\URL::to('admin/user-list').'">Users</a> / User Detail / '.$user['name'];
        $id = 'users_list';
        if($prev == 'token-report'){
            $id = 'token_report';
            $lable = '<a href="'.\URL::to('admin/token-report').'">Token Report</a> / User Detail / '.$user['name'];
        }elseif($prev == 'balance-report'){
            $id = 'balance_report';
            $lable = '<a href="'.\URL::to('admin/balance-report').'">Balance Report</a> / User Detail / '.$user['name'];
        }elseif($prev == 'withdrawal-report'){
            $id = 'withdrawal_reports';
            $lable = '<a href="'.\URL::to('admin/withdrawal-report').'">Withdrawal Report</a> / User Detail / '.$user['name'];
        }
        
        
        $view_data = [
            'header' => [
                'title' => 'Users Detail',
                'css'   =>[],
                'js'    =>[],
            ],
            'body'=> [
                'id'    =>$id,
                'lable' =>$lable,
                'user'  =>$user,
            ],
            'footer'=>[
                'js'    =>[],
            ],
        ];
        return view('admin.users_detail',$view_data);
    }
    
    public function postChangeUserStatus(){
        $param = \Input::all();
        $user = \App\Models\Users::change_user_status($param);
        return $user;
    }
    
    public function postEditUserDetail(){
        $param = \Input::all();
        $rules = [
            'user_id'=>'required',
            'user_name'=>'required',
            'user_name'=>'required',
            'user_email'=>'required|email:unique,'.$param['user_id'],
        ];
        
        $validator = \Validator::make(\Input::all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $json = \General::validation_error_res();
            $json['data'] = $error;
            $json['msg'] = $error[0];
            return \Response::json($json, 200);
        }
        
        $user = \App\Models\Users::edit_user_detail($param);
        return $user;
    }
    
    public function postTokenReportFilter() {
       
       $param=\Request::all();
        if(isset($param['user_id']) && $param['user_id'] != ''){
            $param['loggerId']=$param['user_id'];
        }
       $data=\App\Models\CoinTransactions::get_token_report($param);
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
            return view("user.token_report_filter",$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
        
    }
    
    public function postEarningReportFilter() {
        $param=\Request::all();
        if(isset($param['user_id']) && $param['user_id'] != ''){
            $param['loggerId']=$param['user_id'];
        }
        
       $data= \App\Models\EarningHistory::get_earning_history_report($param);
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
            return view("user.earning_report_filter",$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
    }
    public function postBalanceReportFilter() {
        $param=\Request::all();
        if(isset($param['user_id']) && $param['user_id'] != ''){
            $param['loggerId']=$param['user_id'];
        }
        
       $data=  \App\Models\BalanceTransactions::get_balance_transaction_report($param);
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
            return view("user.balance_report_filter",$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
    }
    
    public function postBitcoinReportFilter() {
        $param=\Request::all();
        if(isset($param['user_id']) && $param['user_id'] != ''){
            $param['loggerId']=$param['user_id'];
        }
        
       $data=  \App\Models\BtcTransactions::get_wallet_transaction_report($param);
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
            return view("user.bitcoin_report_filter",$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
    }
    
    
    public function postEtheriumReportFilter() {
        $param=\Request::all();
        if(isset($param['user_id']) && $param['user_id'] != ''){
            $param['loggerId']=$param['user_id'];
        }
        
       $data=  \App\Models\EthTransactions::get_wallet_transaction_report($param);
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
            return view("user.etherium_report_filter",$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
    }
    
    
    
    public function postWithdrawalListFilter() {
        $param=\Request::all();
        if(isset($param['user_id']) && $param['user_id'] != ''){
            $param['loggerId']=$param['user_id'];
        }
       
       $data = \App\Models\WithdrowRequest::get_request_filter($param);
       
       
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
            
           return view("admin.withdrawal_list_filter",$res);
        }
        
        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
       
    }
    
    public function postProsessRequest(){
        $param=\Request::all();
//        dd($param);
        $withdr = \App\Models\WithdrowRequest::process_request($param);
        return $withdr;
    }
    
    public function getEarningReport(){
        $view_data = [
            'header' => [
                'title' => 'Earning Report',
                'css'=>['assets/css/jquery.ui.css',"assets/css/daterangepicker.css"],
                'js'=>["assets/js/moment.min.js","assets/js/daterangepicker.js"],
            ],
            'body'=> [
                'id'=>'earning_report',
                'lable'=>'Earning Report',
            ],
            'footer'=>[
                'js'=>['jquery.ui.js'],
            ],
        ];
        return view('admin.earning_report',$view_data);
    }
    
    public function postEarningFilter() {
        $param=\Request::all();
//        dd($param);

        $data= \App\Models\EarningHistory::filter_earning_report($param);
        $res = \General::success_res();
        $res['blade'] = view("admin.earning_report_filter", $data)->render();
        $res['total_record'] = $data['total_record'];

        return $res;
    }
    
    public function getAdminWithdraw(){
        $view_data = [
            'header' => [
                'title' => 'Admin Withdraw Report',
                'css'   => ['assets/css/jquery.ui.css',"assets/css/daterangepicker.css"],
                'js'    => ["assets/js/moment.min.js","assets/js/daterangepicker.js"],
            ],
            'body'=> [
                'id'    => 'admin_withdraw_report',
                'lable' => 'Admin BTC Withdraw Report',
            ],
            'footer'=>[
                'js'    => ['jquery.ui.js'],
            ],
        ];
        return view('admin.admin_withdraw_report',$view_data);
    }
    
    public function postAdminWithdrawFilter(){
        $param = \Input::all();
        
        $withdraw_list = \App\Models\AdminWithdrow::filter_withdrawal_report($param);

        $res = \General::success_res();
        $res['blade']           = view("admin.admin_withdrawal_report_filter", $withdraw_list)->render();
        $res['total_record']    = $withdraw_list['total_record'];

        return $res;
    }
    
    public function postAdminBtcWithdraw(){
        $param = \Input::all();
        
        $validator = \Validator::make(\Input::all(), \Validation::get_rules('admin','btc_withdraw'));
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $json = \General::validation_error_res();
            $json['data'] = $error;
            $json['msg'] = 'Fill necessary field';
            return \Response::json($json,200);
        }
        
        $res = \App\Models\AdminWithdrow::add_request($param);
        
        return \Response::json($res,200);
//        dd($param,$res);
    }
    
    public function getBalanceReport(){
        $view_data = [
            'header' => [
                'title' => 'Balance Report',
                'css'=>['assets/css/jquery.ui.css',"assets/css/daterangepicker.css"],
                'js'=>["assets/js/moment.min.js","assets/js/daterangepicker.js"],
            ],
            'body'=> [
                'id'=>'balance_report',
                'lable'=>'Balance Report',
            ],
            'footer'=>[
                'js'=>['jquery.ui.js'],
            ],
        ];
        return view('admin.balance_report',$view_data);
    }
    
    public function postBalanceFilter(){
        $param = \Input::all();
        
        $users = \App\Models\BalanceTransactions::filter_balance_report($param);
//        dd($users);
        $res = \General::success_res();
        $res['blade'] = view("admin.balance_report_filter", $users)->render();
        $res['total_record'] = $users['total_record'];
//        dd($res['blade']);
        return $res;
    }
    
    public function getWalletHistory(){
        $view_data = [
            'header' => [
                'title' => config('constant.COIN_SHORT_NAME').' Wallet History',
                'css'   => ['assets/css/jquery.ui.css',"assets/css/daterangepicker.css"],
                'js'    => ["assets/js/moment.min.js","assets/js/daterangepicker.js"],
            ],
            'body'=> [
                'id'    => 'wallet_history',
                'lable' => config('constant.COIN_SHORT_NAME').' Wallet History',
            ],
            'footer'=>[
                'js'=>['jquery.ui.js'],
            ],
        ];
        return view('admin.wallet_history',$view_data);
    }
    
    public function postWalletFilter(){
        $param = \Input::all();
        
        $history = \App\Models\Admin\AdminCoinTransactions::filter_token_report($param);
//        dd($users);
        $res = \General::success_res();
        $res['blade'] = view("admin.wallet_history_filter", $history)->render();
        $res['total_record'] = $history['total_record'];
//        dd($res['blade']);
        return $res;
    }
    
    public function getBtcReport(){
        $view_data = [
            'header' => [
                'title' => 'BTC Report',
                'css'=>['assets/css/jquery.ui.css',"assets/css/daterangepicker.css"],
                'js'=>["assets/js/moment.min.js","assets/js/daterangepicker.js"],
            ],
            'body'=> [
                'id'=>'btc_report',
                'lable'=>'BTC Report',
            ],
            'footer'=>[
                'js'=>['jquery.ui.js'],
            ],
        ];
        return view('admin.btc_report',$view_data);
    }
    
    public function postBtcFilter(){
        $param = \Input::all();
        
        $users = \App\Models\BtcTransactions::filter_token_report($param);
//        dd($users);
        $res = \General::success_res();
        $res['blade'] = view("admin.btc_report_filter", $users)->render();
        $res['total_record'] = $users['total_record'];
//        dd($res['blade']);
        return $res;
    }
    
    public function getTokenReport(){
        $view_data = [
            'header' => [
                'title' => 'Token Report',
                'css'=>['assets/css/jquery.ui.css',"assets/css/daterangepicker.css"],
                'js'=>["assets/js/moment.min.js","assets/js/daterangepicker.js"],
            ],
            'body'=> [
                'id'=>'token_report',
                'lable'=>'Token Report',
            ],
            'footer'=>[
                'js'=>['jquery.ui.js'],
            ],
        ];
        return view('admin.token_report',$view_data);
    }
    public function postTokenFilter(){
        $param = \Input::all();
        
        $users = \App\Models\CoinTransactions::filter_token_report($param);
//        dd($users);
        $res = \General::success_res();
        $res['blade'] = view("admin.token_report_filter", $users)->render();
        $res['total_record'] = $users['total_record'];
//        dd($res['blade']);
        return $res;
    }
    public function getWithdrawalReport(){
        $view_data = [
            'header' => [
                'title' => 'Token Report',
                'css'=>['assets/css/jquery.ui.css',"assets/css/daterangepicker.css"],
                'js'=>["assets/js/moment.min.js","assets/js/daterangepicker.js"],
            ],
            'body'=> [
                'id'=>'withdrawal_reports',
                'lable'=>'Withdrawal Report',
            ],
            'footer'=>[
                'js'=>['jquery.ui.js'],
            ],
        ];
        return view('admin.withdrawal_report',$view_data);
    }
    public function postWithdrawalFilter(){
        $param = \Input::all();
        
        $users = \App\Models\WithdrowRequest::filter_withdrawal_report($param);
//        dd($users);
        $res = \General::success_res();
        $res['blade'] = view("admin.withdrawal_report_filter", $users)->render();
        $res['total_record'] = $users['total_record'];
//        dd($res['blade']);
        return $res;
    }
    
    public function getSettings(){
         $user = \Auth::guard('admin')->user()->toArray();
//        $settings = \App\Models\Setting::get()->toArray();
        $settings = app('settings');
//        dd($settings);
        $view_data = [
            'header' => [
                'title' => 'Token Report',
                'css'=>[],
                'js'=>[],
            ],
            'body'=> [
                'id'=>'settings',
                'lable'=>'Settings',
                'settings'=>$settings,
                 'user'  => $user,
            ],
            'footer'=>[
                'js'=>[],
            ],
        ];
        return view('admin.settings',$view_data);
    }
    
    public function postSaveSettings(){
        $param = \Input::all();
//        dd($param);
        $setting_type = $param['settting_type'];
        
        if($setting_type == 'general'){
            $res = \App\Models\Admin\Settings::edit_general_settings($param);
        }
//        else if($setting_type == 'bitpay'){
//            $res = \App\Models\Admin\Settings::edit_bitpay_settings($param);
//        }
        else if($setting_type == 'gourl'){
            $res = \App\Models\Admin\Settings::edit_gourl_settings($param);
        }
        else if($setting_type == 'password'){
            $res = User::change_admin_password($param);
        }
        else if($setting_type == 'auth'){
            $res =\App\Models\Admin\Settings::auth_settings($param);
        }
        else{
            $res = \General::error_res('setting type is not proper');
        }
        
        return $res;
    }
    
    public function postDisableUser2sf(){
        
        if (!\Auth::guard('admin')->check()) {
            return redirect('');
        }
        
        $param=\Request::all();
       
        $user   = \App\Models\Users::where('id',$param['uid'])->first();
        $user->google2fa_secret = null;
        $user->save();
        
        return \General::success_res(' 2 Factor Auth disabled successfully.');
    }
    
    
    public function postEnable2sf(){
        if (!\Auth::guard('admin')->check()) {
            return redirect('');
        }
        $user   = \Auth::guard('admin')->user();
//        dd($user);
        //generate new secret
        $secret = $this->generateSecret();

        //encrypt and then save secret
        $user->google2fa_secret = \Crypt::encrypt($secret);
        $user->save();

        //generate image for QR barcode
        $google2f = new Google2FA();
//        $imageDataUri = Google2FA::getQRCodeInline(
        $imageDataUri = $google2f->getQRCodeInline(
            \Request::getHttpHost(),
            $user->email,
            $secret,
            200
        );
        $res = \General::success_res();
        $res['data'] = ['image' => $imageDataUri,
            'secret' => $secret];
        
        
        return $res;
    }
    private function generateSecret()
    {
        $randomBytes = random_bytes(10);

        return Base32::encodeUpper($randomBytes) ;
    }
    public function postDisable2sf(){
        if (!\Auth::guard('admin')->check()) {
            return redirect('');
        }
        $user   = \Auth::guard('admin')->user();
        $user->google2fa_secret = null;
        $user->save();
        
        return \General::success_res('authenticator disabled successfully.');
    }
    
    
    public function getManageTicket() {

//        if (!\Auth::guard('user')->check()) {
//            return \Response::view('errors.404', array(), 404);
//        }

//        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'Manage Ticket',
                "js"    => ["assets/js/moment.min.js","assets/js/daterangepicker.js"],
                "css"   => ["assets/css/daterangepicker.css"],
            ],
            'body' => [
                'user'  => '',
                'lable' =>'Manage Ticket',
                'id'    =>'manage_ticket',
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("admin.manage_ticket", $view_data);
    }
    public function getManageInterest() {

        $interest = \App\Models\InterestPerDay::get()->toArray();
        
        $view_data = [
            'header' => [
                "title" => 'Manage Interest',
                "js"    => ["assets/js/moment.min.js","assets/js/daterangepicker.js"],
                "css"   => ["assets/css/daterangepicker.css"],
            ],
            'body' => [
                'user'  => '',
                'lable' =>'Manage Interest',
                'id'    =>'manage_interest',
                'interest' => $interest
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("admin.manage_interest", $view_data);
    }
    
    public function postManageTicketFilter() {
       $param=\Input::all();
       $data= \App\Models\SupportTicket::get_ticket_report($param);
//       dd($data);
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
//            dd($res);
            return view("admin.manage_ticket_filter",$res);
        }
        
        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
    }
    
    public function postChangeTicketStatus(){
      
        $param=\Input::all();
        $rules = [
            'id'=>'required',
            'action'=>'required',
        ];
        
        $validator = \Validator::make(\Input::all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $json = \General::validation_error_res();
            $json['data'] = $error;
            $json['msg'] = 'Fill necessary field';
            return \Response::json($json,200);
        }
        
//        dd($param); 
           $res = \App\Models\SupportTicket::where('id',$param['id'])->update(['status'=>$param['action']]);
       
        
           return \General::success_res('Ticket Updated Successfully');
        
    }
    
    public function getBtcTransactionReport(){
        $view_data = [
            'header' => [
                'title' => 'Balance Report',
                'css'=>['assets/css/jquery.ui.css',"assets/css/daterangepicker.css"],
                'js'=>["assets/js/moment.min.js","assets/js/daterangepicker.js"],
            ],
            'body'=> [
                'id'=>'btc_report',
                'lable'=>'Bitcoin Report',
            ],
            'footer'=>[
                'js'=>['jquery.ui.js'],
            ],
        ];
        
        return view('admin.btc_transaction_report',$view_data);
    }
    
    public function postBtcTransactionFilter(){
        $param = \Input::all();
        
        $users = \App\Models\BtcTransactions::filter_balance_report($param);
//        dd($users);
        $res = \General::success_res();
        $res['blade'] = view("admin.btc_transaction_filter", $users)->render();
        $res['total_record'] = $users['total_record'];
//        dd($res['blade']);
        return $res;
    }
    
    public function getEthTransactionReport(){
        $view_data = [
            'header' => [
                'title' => 'Balance Report',
                'css'=>['assets/css/jquery.ui.css',"assets/css/daterangepicker.css"],
                'js'=>["assets/js/moment.min.js","assets/js/daterangepicker.js"],
            ],
            'body'=> [
                'id'=>'eth_report',
                'lable'=>'Etherium Report',
            ],
            'footer'=>[
                'js'=>['jquery.ui.js'],
            ],
        ];
        
        return view('admin.eth_transaction_report',$view_data);
    }
    
    public function postEthTransactionFilter(){
        $param = \Input::all();
        
        $users = \App\Models\EthTransactions::filter_balance_report($param);
//        dd($users);
        $res = \General::success_res();
        $res['blade'] = view("admin.btc_transaction_filter", $users)->render();
        $res['total_record'] = $users['total_record'];
//        dd($res['blade']);
        return $res;
    }
    
    public function postSaveInterestPerDay(){
        $param = \Input::all();
        $interest = \App\Models\InterestPerDay::save_interest($param);
        
        return $interest;
    }
    
    
     public function getIcoCalendar(){
        $view_data = [
            'header' => [
                'title' => 'Balance Report',
                'css'=>['assets/css/jquery.ui.css',"assets/css/daterangepicker.css"],
                'js'=>["assets/js/moment.min.js","assets/js/daterangepicker.js"],
            ],
            'body'=> [
                'id'=>'ico_calendar',
                'lable'=>'Manage Calendar',
            ],
            'footer'=>[
                'js'=>['jquery.ui.js'],
            ],
        ];
        
        return view('admin.ico_calendar_list',$view_data);
    }
    
    
     public function postIcoCalendarFilter(){
        $param = \Input::all();
        
        $cal = \App\Models\CalenderIco::ico_calendar_report($param);

        $res = \General::success_res();
        $res['blade'] = view("admin.ico_calendar_filter", $cal)->render();
        $res['total_record'] = $cal['total_record'];

        return $res;
    }
    public function getPreSellCalendar(){
        $view_data = [
            'header' => [
                'title' => 'Pre Sell Calender',
                'css'=>['assets/css/jquery.ui.css',"assets/css/daterangepicker.css"],
                'js'=>["assets/js/moment.min.js","assets/js/daterangepicker.js"],
            ],
            'body'=> [
                'id'=>'pre_sell_calendar',
                'lable'=>'Manage Pre Sell Calendar',
            ],
            'footer'=>[
                'js'=>['jquery.ui.js'],
            ],
        ];
        
        return view('admin.pre_sell_calendar_list',$view_data);
    }
     public function postPreSellCalendarFilter(){
        $param = \Input::all();
        
        $cal = \App\Models\PreSellCalender::ico_calendar_report($param);

        $res = \General::success_res();
        $res['blade'] = view("admin.pre_sell_calendar_filter", $cal)->render();
        $res['total_record'] = $cal['total_record'];

        return $res;
    }
    
    
    public function postNewIco(){
        $param = \Input::all();
        $rules = [
            'startd_ico'=>'required',
            'endd_ico'=>'required',
           
            'price'=>'required|numeric',
             'token'=>'required|numeric',
        ];
        
        $validator = \Validator::make(\Input::all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $json = \General::validation_error_res();
            $json['data'] = $error;
            $json['msg'] = $error[0];
            return \Response::json($json, 200);
        }
        
        $ico =\App\Models\CalenderIco::add_new_ico($param);
        return \General::success_res('New Ico Range Added Successfully');
    }
    
    public function postUpdateIco(){
        $param = \Input::all();
        $rules = [
            'startd_ico'=>'required',
            'endd_ico'=>'required',
            'price'=>'required|numeric',
        ];
        
        $validator = \Validator::make(\Input::all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $json = \General::validation_error_res();
            $json['data'] = $error;
            $json['msg'] = $error[0];
            return \Response::json($json, 200);
        }
        
        $ico =\App\Models\CalenderIco::update_ico($param);
        return \General::success_res('Ico Range Updated Successfully');
    }
    public function postNewPreCalender(){
        $param = \Input::all();
        $rules = [
            'startd_ico'=>'required',
            'endd_ico'=>'required',
           
            'price'=>'required|numeric',
             'token'=>'required|numeric',
        ];
        
        $validator = \Validator::make(\Input::all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $json = \General::validation_error_res();
            $json['data'] = $error;
            $json['msg'] = $error[0];
            return \Response::json($json, 200);
        }
        
        $ico = \App\Models\PreSellCalender::add_new_ico($param);
        return \General::success_res('New pre sell calender Added Successfully');
    }
    public function postUpdatePreSellCalender(){
        $param = \Input::all();
        $rules = [
            'startd_ico'=>'required',
            'endd_ico'=>'required',
            'price'=>'required|numeric',
        ];
        
        $validator = \Validator::make(\Input::all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $json = \General::validation_error_res();
            $json['data'] = $error;
            $json['msg'] = $error[0];
            return \Response::json($json, 200);
        }
        
        $ico = \App\Models\PreSellCalender::update_ico($param);
        return \General::success_res('pre sell calender Updated Successfully');
    }
    
     public function postDeleteIco(){
        $param = \Input::all();
        $rules = [
            'ico_id'=>'required',
            
        ];
        
        $validator = \Validator::make(\Input::all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $json = \General::validation_error_res();
            $json['data'] = $error;
            $json['msg'] = $error[0];
            return \Response::json($json, 200);
        }
        
        $ico =\App\Models\CalenderIco::where('id',$param['ico_id'])->delete();
        return \General::success_res('Ico Range Deleted Successfully');
    }
     public function postDeletePreSellCalender(){
        $param = \Input::all();
        $rules = [
            'ico_id'=>'required',
            
        ];
        
        $validator = \Validator::make(\Input::all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $json = \General::validation_error_res();
            $json['data'] = $error;
            $json['msg'] = $error[0];
            return \Response::json($json, 200);
        }
        
        $ico = \App\Models\PreSellCalender::where('id',$param['ico_id'])->delete();
        return \General::success_res('Ico Range Deleted Successfully');
    }
    
    
    
    public function getStackPlan(){
        $view_data = [
            'header' => [
                'title' => 'Stack Plan',
                'css'=>['assets/css/jquery.ui.css',"assets/css/daterangepicker.css"],
                'js'=>["assets/js/moment.min.js","assets/js/daterangepicker.js"],
            ],
            'body'=> [
                'id'=>'stack_plan',
                'lable'=>'Manage Stack Plan',
            ],
            'footer'=>[
                'js'=>['jquery.ui.js'],
            ],
        ];
        
        return view('admin.stack_plan_list',$view_data);
    }
    
    public function postStackPlanFilter(){
        $param = \Input::all();
        
        $cal = \App\Models\StackPlan::stack_plan_report($param);

//        dd($cal);
        
        $res = \General::success_res();
        $res['blade'] = view("admin.stack_plan_filter", $cal)->render();
        $res['total_record'] = $cal['total_record'];

        return $res;
    }
    
    public function getIcoPlan(){
        $view_data = [
            'header' => [
                'title' => 'Ico Plan',
                'css'=>['assets/css/jquery.ui.css',"assets/css/daterangepicker.css"],
                'js'=>["assets/js/moment.min.js","assets/js/daterangepicker.js"],
            ],
            'body'=> [
                'id'=>'ico_plan',
                'lable'=>'Lending Plan',
            ],
            'footer'=>[
                'js'=>['jquery.ui.js'],
            ],
        ];
        
        return view('admin.ico_plan_list',$view_data);
    }
    
    
    public function postIcoPlanFilter(){
        $param = \Input::all();
        
        $cal = \App\Models\InvestPlan::ico_plan_report($param);

//        dd($cal);
        $res = \General::success_res();
        $res['blade'] = view("admin.ico_plan_filter", $cal)->render();
        $res['total_record'] = $cal['total_record'];

        return $res;
    }
    
    
    public function postNewStackPlan(){
        $param = \Input::all();
        
//        dd($param);
        
        $rules = [
            'noi'           => 'required|numeric',
            'period_type'   => 'required|numeric',
            'percent'       => 'required|numeric',
            'months'        => 'required|numeric',
        ];
        
        $validator = \Validator::make(\Input::all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $json = \General::validation_error_res();
            $json['data'] = $error;
            $json['msg'] = $error[0];
            return \Response::json($json, 200);
        }
        
        $stack = \App\Models\StackPlan::add_new_stack_plan($param);
//        return \General::success_res('New Ico Plan Added Successfully');
        return $stack;
    }
    public function postUpdateStackPlan(){
        $param = \Input::all();
        
//        dd($param);
        
        $rules = [
            'stack_id'      => 'required|numeric',
            'noi'           => 'required|numeric',
            'period_type'   => 'required|numeric',
            'percent'       => 'required|numeric',
            'months'        => 'required|numeric',
        ];
        
        $validator = \Validator::make(\Input::all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $json = \General::validation_error_res();
            $json['data'] = $error;
            $json['msg'] = $error[0];
            return \Response::json($json, 200);
        }
        
        $stack = \App\Models\StackPlan::update_stack_plan($param);
//        return \General::success_res('New Ico Plan Added Successfully');
        return $stack;
    }
    
    public function postNewIcoPlan(){
        $param = \Input::all();
        $rules = [
            'plan_name' =>'required',
            'from_usd'  =>'required|numeric',
            'to_usd'    =>'required|numeric',
           
            'percent'   =>'required|numeric',
            'extra_b'   =>'numeric',
            'days'      =>'required|numeric',
        ];
        
        $validator = \Validator::make(\Input::all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $json = \General::validation_error_res();
            $json['data'] = $error;
            $json['msg'] = $error[0];
            return \Response::json($json, 200);
        }
        
        $ico =  \App\Models\InvestPlan::add_new_ico_plan($param);
        return \General::success_res('New Ico Plan Added Successfully');
    }
    
    public function postUpdateIcoPlan(){
        $param = \Input::all();
        $rules = [
            'plan_name' =>'required',
            'from_usd'  =>'required|numeric',
            'to_usd'    =>'required|numeric',
           
            'percent'   =>'required|numeric',
            'extra_b'   =>'numeric',
            'days'      =>'required|numeric',
        ];
        
        $validator = \Validator::make(\Input::all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $json = \General::validation_error_res();
            $json['data'] = $error;
            $json['msg'] = $error[0];
            return \Response::json($json, 200);
        }
        
        $ico =\App\Models\InvestPlan::update_ico_plan($param);
        return \General::success_res('Ico Plan Updated Successfully');
    }
    
     public function postDeleteStackPlan(){
        $param = \Input::all();
//        dd($param);
        $rules = [
            'stack_id'=>'required',
        ];
        
        $validator = \Validator::make(\Input::all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $json = \General::validation_error_res();
            $json['data'] = $error;
            $json['msg'] = $error[0];
            return \Response::json($json, 200);
        }
        
        $ico =\App\Models\StackPlan::where('id',$param['stack_id'])->delete();
        return \General::success_res('Ico Plan  Deleted Successfully');
    } 
     public function postDeleteIcoPlan(){
        $param = \Input::all();
        $rules = [
            'ico_id'=>'required',
            
        ];
        
        $validator = \Validator::make(\Input::all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $json = \General::validation_error_res();
            $json['data'] = $error;
            $json['msg'] = $error[0];
            return \Response::json($json, 200);
        }
        
        $ico =\App\Models\InvestPlan::where('id',$param['ico_id'])->delete();
        return \General::success_res('Ico Plan  Deleted Successfully');
    } 
    
    public function getStackingReport(){
        $view_data = [
            'header' => [
                'title' => 'Stacking Report',
                'css'=>['assets/css/jquery.ui.css',"assets/css/daterangepicker.css"],
                'js'=>["assets/js/moment.min.js","assets/js/daterangepicker.js"],
            ],
            'body'=> [
                'id'=>'stacking_reports',
                'lable'=>'Stacking Report',
            ],
            'footer'=>[
                'js'=>['jquery.ui.js'],
            ],
        ];
        return view('admin.stacking_report',$view_data);
    }
    public function postStackingFilter(){
        $param = \Input::all();
        
        $users = \App\Models\StackRecord::filter_stack_report($param);
//        dd($users);
        $res = \General::success_res();
        $res['blade'] = view("admin.stacking_report_filter", $users)->render();
        $res['total_record'] = $users['total_record'];
//        dd($res['blade']);
        return $res;
    }
    
    
    public function postSendUserCustomMail()
    {
        $param=\Request::all();
//        dd($param);
       
        $mail_data['email_to']=explode(',',$param['user_ids']);
        $mail_data['content']=$param['email_content'];
        $mail_data['email_subject']=$param['email_subject'];
        
        $custom_msg=[
            'user_ids.required'=>'Please fill receiver Email Address',
            'email_content.required'=>'Please Fill Email Content',
            'email_subject.required'=>'Please Fill Subject ',
        ];
        $validator =\Validator::make(\Input::all(),\Validation::get_rules("admin", "send_bulk_mail"),$custom_msg);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $errors = $messages->all();
            
            $res=\General::validation_error_res('validation Error occur');
            $res['data']=$errors;
            return \Response::json($res,200);
        }
        
        \Mail::send([],[], function ($message) use ($mail_data) {
            $message->from(config('constant.SUPPORT_MAIL'),config('constant.PLATFORM_NAME'))->to($mail_data['email_to'])->subject($mail_data['email_subject'])->setBody($mail_data['content'],'text/html');
        });
        
        $res=\General::success_res('Mail Sent Sucessfully');
        return \Response::json($res,200);
    }
    
}