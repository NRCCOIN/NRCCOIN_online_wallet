<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Request;
use App\Models\Users;
use App\Models\WithdrowRequest;
use App\Models\Serviceprovider;
use Illuminate\Support\Facades\Mail;
use DB;
use File;

use PragmaRX\Google2FA\Google2FA as Google2FA;
use \ParagonIE\ConstantTime\Base32;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller {

    private static $bypass_url = ['getIndex','getEmailVerify', 'getLogin', 'postLogin', 'getResetpass', 'getForgotPass', 'postForgotPass', 'postResetpass', 'getSignup', 'postSignup', 'getAuthGoogle', 'redirectGoogle', 'getAuthFacebook', 'redirectFacebook','getResendConfirm'];

    public function __construct() {
        $this->middleware('UserAuth', ['except' => self::$bypass_url]);
//        dd(\Input::all());
    }

    public function getIndex() {
        if (!\Auth::guard('user')->check()) {
//            return \Response::view('errors.401', array(), 401);
            return \Redirect::to('/');
        }

        return \Redirect::to('/');
    }

    public function getDashboard() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }

        $user = \Auth::guard('user')->user()->toArray();
        
        if($user['coin_address'] == ''){
            $check = 1;
            $c = 0;
            while(!is_null($check)){
                $coin_address = \App\Models\General::generate_coin_address();
                $check = Users::where('coin_address',$coin_address)->first();
                $c++;
                if($c == 10){
                    return \Response::view('errors.404', array('msg'=>'This Link is Temporily Unavailable. Please, Try After Some time.'), 404);
                }
            }
            
            $address_update = Users::where('id',$user['id'])->update(['coin_address'=>$coin_address]);
            
            $user = Users::where('id',$user['id'])->first()->toArray();
            
            $data = [
                        'user_id'      => $user['id'],
                        'coin_address' => $user['coin_address'],
                    ];
            $add = \App\Models\CoinAddress::new_coin_address($data);
            
        }
        
        
if($user['tr_address'] == ''){
            $check = 1;
            $c = 0;
            while(!is_null($check)){
                $tr_address = \App\Models\General::generate_tr_address();
                $check = Users::where('tr_address',$tr_address)->first();
                $c++;
                if($c == 10){
                    return \Response::view('errors.404', array('msg'=>'This Link is Temporily Unavailable. Please, Try After Some time.'), 404);
                }
            }
            
            $tr_address_update = Users::where('id',$user['id'])->update(['tr_address'=>$tr_address]);
            
            $user = Users::where('id',$user['id'])->first()->toArray();
            
            $trade = [
                        'user_id'      => $user['id'],
                        'tr_address' => $user['tr_address'],
                    ];
            $add = \App\Models\TrAddress::new_tr_address($trade);
            
        } 
   

       
$data = [
                        'user_id'      => $user['id'],
                        'coin_address' => $user['coin_address'],
                    ];
       
 
/*        
$address =$user['coin_address'];
        
$url="http://blockchain.nrccoin.com/ext/getbalance/$address";

$json=file_get_contents("$url");


$block_bal =  json_decode($json);

       
$res = \App\Models\checkAddress::checkAddress($data);
        
        
if($res['flag'] == 1){
    
$update_balance = Users::where('id',$user['id'])->update(['coin'=>$block_bal]);
  
$block_balance = $block_bal;    
    
}else{
    
    
    $block_balance = '0.000000';
    
    
    
}
        
*/        
        
        
       
$address =$user['coin_address'];
        
$url="http://blockchain.nrccoin.com/ext/getbalance/$address";

$json=file_get_contents("$url");
        

$block_bal =  json_decode($json);

$datatype = gettype($block_bal);
       
    
    
if ($datatype == 'object') {
    
    
    
        $block_balance = '0.000000';

}else{

     
$update_balance = Users::where('id',$user['id'])->update(['coin'=>$block_bal]);
    
$block_balance=json_decode($json);    
    
   
}
        
       
/*       
}
        
        
if($res['flag'] == 0){

    
    
}*/
        
    
    
    /*
            
                    $data = [
                        'user_id'      => $user['id'],
                        'coin_address' => $user['coin_address'],
                    ];

   */      
        
        
       $my_user_id=$user['id'];
        
      //  $transactions = withdrow_request::where('user_id',$user['id'])->first()->toArray();
        
      $number_of_transactions = WithdrowRequest::where('user_id',$my_user_id)->count();
 
        
        //$number_or_transactions = withdrow_request::where('user_id',"$my_user_id")->count();

        $PreviousTransactions = \App\Models\PreviousTransactions::get_previous_transactions($data);
  
        $icoStart = \App\Models\CalenderIco::OrderBy('start_date','asc')->first();
       
        $child = Users::where('parent_id',$user['id'])->count();
        $iplan = \App\Models\InvestPlan::where('status','=',1)->get()->toArray();
        $dt = date('Y-m-d H:i:s');
        $icoList = \App\Models\CalenderIco::get_calender_ico_list();
        $pending = \App\Models\WithdrowRequest::where('user_id',$user['id'])
                                              ->where('status',0)
                                              ->where('coin_type',1)
                                              ->sum('amount');
//                                              ->get()
//                                              ->toArray();
        if($pending == null){
            $pending = 0.00000000;
        }
        
        $size = 200;
        $string = $user['coin_address'];
        $img = '';
        if($string){
            $renderer = new \BaconQrCode\Renderer\Image\Png();
            $renderer->setWidth($size);
            $renderer->setHeight($size);

            $bacon = new \BaconQrCode\Writer($renderer);
            $data = $bacon->writeString($string, 'utf-8');

            $img = 'data:image/png;base64,'.base64_encode($data);
        }
        
        $coinrate = \General::global_price(config('constant.COIN_SHORT_NAME'));
        $icoActive = \App\Models\CalenderIco::where('start_date','<=',date('Y-m-d H:i:s'))
                                            ->where('end_date','>=',date('Y-m-d H:i:s'))
                                            ->orderBy('start_date','asc')
                                            ->first();
        $activeIco = 0;
        if($icoActive){
            $activeIco = $icoActive->id;
        }
        
        $dt = date('Y-m-d H:i:s');
        
        $cico = new \App\Models\CalenderIco();
        
         
        $nextIco = \App\Models\PreSellCalender::where('status','=',1)->where('start_date','<=',date('Y-m-d H:i:s'))
                                            ->where('end_date','>=',date('Y-m-d H:i:s'))
                                            ->first(); 
        
        
        if(!is_null($nextIco)){
            $soldCoin = \App\Models\CoinTransactions::where('type',1)->whereBetween('created_at', [$nextIco->start_date, $nextIco->end_date])
                                            ->sum('credit');
        }else{
                $nextIco = \App\Models\CalenderIco::where('status','=',1)->where('start_date','<=',date('Y-m-d H:i:s'))
                                            ->where('end_date','>=',date('Y-m-d H:i:s'))
                                            ->first();
                if(!is_null($nextIco)){
                    $soldCoin = \App\Models\CoinTransactions::where('type',1)->whereBetween('created_at', [$nextIco->start_date, $nextIco->end_date])
                                            ->sum('credit');
                }
        }
        
//        dd($soldCoin);
        
        $icoOpen='False';
        if(is_null($nextIco) || $soldCoin >= $nextIco->token){
//            $nextIco = \App\Models\CalenderIco::where('status',1)->where('start_date','>=',date('Y-m-d H:i:s'))->OrderBy('start_date','asc')->first();
            $nextIco = \App\Models\PreSellCalender::where('status',1)->where('start_date','>=',date('Y-m-d H:i:s'))->OrderBy('start_date','asc')->first();
            if(is_null($nextIco)){
                $nextIco = \App\Models\CalenderIco::where('status',1)->where('start_date','>=',date('Y-m-d H:i:s'))->OrderBy('start_date','asc')->first();
            }
            if(is_null($nextIco)){
                $nextIco = 'Not Found';
            }else{
                $nextIco = $nextIco->start_date;
            }
        }else{
           $icoOpen = 'True';
           $nextIco = 'Found';   
        }
        
       // dd($iplan);
        $view_data = [
            'header' => [
                "title" => 'Dashboard',
                "js"    => ['assets/js/flipclock.min.js','assets/js/paygate.min.js'],
//                "css"   => ['assets/css/dashboard.min.css','assets/css/ico_index.min.css'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'child'     => $child,
                'my_bal'     => $block_balance,
                'number_of_transactions' => $number_of_transactions,
                'icoList'   => $icoList,
                'PreviousTransactions'   => $PreviousTransactions,
                'pending'   => $pending,
                'iplan'     => $iplan,
                'icoStart'  => $icoStart->start_date,
                'nextIco'   => $nextIco,
                'icoOpen'   => $icoOpen,
                'page_title'=> 'EXCHANGE WALLET DASHBOARD',
                'menu_id'   => "dashboard",
                'wallet_address' => $img,
                'coinRate'  => $coinrate,
                'activeIco' => $activeIco,
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.dashboard", $view_data);
    }
    
    
    
    
public function getTrade()      
{ 
  
    


        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }

        $user = \Auth::guard('user')->user()->toArray();
        
       /* if($user['coin_address'] == ''){
            $check = 1;
            $c = 0;
            while(!is_null($check)){
                $tr_address = \App\Models\General::generate_tr_address();
                $check = Users::where('tr_address',$tr_address)->first();
                $c++;
                if($c == 10){
                    return \Response::view('errors.404', array('msg'=>'This Link is Temporily Unavailable. Please, Try After Some time.'), 404);
                }
            }
            
            $address_update = Users::where('id',$user['id'])->update(['tr_address'=>$tr_address]);
            
            $user = Users::where('id',$user['id'])->first()->toArray();
            
            $data = [
                        'user_id'      => $user['id'],
                        'tr_address' => $user['tr_address'],
                    ];
            $add = \App\Models\CoinAddress::new_coin_address($data);
            
        }*/
        
                if($user['tr_address'] == ''){
            $check = 1;
            $c = 0;
            while(!is_null($check)){
                $tr_address = \App\Models\General::generate_tr_address();
                $check = Users::where('tr_address',$tr_address)->first();
                $c++;
                if($c == 10){
                    return \Response::view('errors.404', array('msg'=>'This Link is Temporily Unavailable. Please, Try After Some time.'), 404);
                }
            }
            
            $tr_address_update = Users::where('id',$user['id'])->update(['tr_address'=>$tr_address]);
            
            $user = Users::where('id',$user['id'])->first()->toArray();
            
            $trade = [
                        'user_id'      => $user['id'],
                        'tr_address' => $user['tr_address'],
                    ];
            $add = \App\Models\TrAddress::new_tr_address($trade);
            
        }
        
        
        
        
        
        
                    $data = [
                        'user_id'      => $user['id'],
                        'tr_address' => $user['tr_address'],
                    ];

        
     //   $my_user_id=$user['id'];
        
      //  $transactions = withdrow_request::where('user_id',$user['id'])->first()->toArray();
        $PreviousTradeTransactions = \App\Models\PreviousTransactions::get_previous_trade_transactions($data);
  
        $icoStart = \App\Models\CalenderIco::OrderBy('start_date','asc')->first();
       
        $child = Users::where('parent_id',$user['id'])->count();
        $iplan = \App\Models\InvestPlan::where('status','=',1)->get()->toArray();
        $dt = date('Y-m-d H:i:s');
        $icoList = \App\Models\CalenderIco::get_calender_ico_list();
        $pending = \App\Models\WithdrowRequest::where('user_id',$user['id'])
                                              ->where('status',0)
                                              ->where('coin_type',1)
                                              ->sum('amount');
//                                              ->get()
//                                              ->toArray();
        if($pending == null){
            $pending = 0.00000000;
        }
        
        $size = 200;
        $string = $user['coin_address'];
        $img = '';
        if($string){
            $renderer = new \BaconQrCode\Renderer\Image\Png();
            $renderer->setWidth($size);
            $renderer->setHeight($size);

            $bacon = new \BaconQrCode\Writer($renderer);
            $data = $bacon->writeString($string, 'utf-8');

            $img = 'data:image/png;base64,'.base64_encode($data);
        }
        
        $coinrate = \General::global_price(config('constant.COIN_SHORT_NAME'));
        $icoActive = \App\Models\CalenderIco::where('start_date','<=',date('Y-m-d H:i:s'))
                                            ->where('end_date','>=',date('Y-m-d H:i:s'))
                                            ->orderBy('start_date','asc')
                                            ->first();
        $activeIco = 0;
        if($icoActive){
            $activeIco = $icoActive->id;
        }
        
        $dt = date('Y-m-d H:i:s');
        
        $cico = new \App\Models\CalenderIco();
        
         
        $nextIco = \App\Models\PreSellCalender::where('status','=',1)->where('start_date','<=',date('Y-m-d H:i:s'))
                                            ->where('end_date','>=',date('Y-m-d H:i:s'))
                                            ->first(); 
        
        
        if(!is_null($nextIco)){
            $soldCoin = \App\Models\CoinTransactions::where('type',1)->whereBetween('created_at', [$nextIco->start_date, $nextIco->end_date])
                                            ->sum('credit');
        }else{
                $nextIco = \App\Models\CalenderIco::where('status','=',1)->where('start_date','<=',date('Y-m-d H:i:s'))
                                            ->where('end_date','>=',date('Y-m-d H:i:s'))
                                            ->first();
                if(!is_null($nextIco)){
                    $soldCoin = \App\Models\CoinTransactions::where('type',1)->whereBetween('created_at', [$nextIco->start_date, $nextIco->end_date])
                                            ->sum('credit');
                }
        }
        
//        dd($soldCoin);
        
        $icoOpen='False';
        if(is_null($nextIco) || $soldCoin >= $nextIco->token){
//            $nextIco = \App\Models\CalenderIco::where('status',1)->where('start_date','>=',date('Y-m-d H:i:s'))->OrderBy('start_date','asc')->first();
            $nextIco = \App\Models\PreSellCalender::where('status',1)->where('start_date','>=',date('Y-m-d H:i:s'))->OrderBy('start_date','asc')->first();
            if(is_null($nextIco)){
                $nextIco = \App\Models\CalenderIco::where('status',1)->where('start_date','>=',date('Y-m-d H:i:s'))->OrderBy('start_date','asc')->first();
            }
            if(is_null($nextIco)){
                $nextIco = 'Not Found';
            }else{
                $nextIco = $nextIco->start_date;
            }
        }else{
           $icoOpen = 'True';
           $nextIco = 'Found';   
        }
        
       // dd($iplan);
        $view_data = [
            'header' => [
                "title" => 'Dashboard',
                "js"    => ['assets/js/flipclock.min.js','assets/js/paygate.min.js'],
//                "css"   => ['assets/css/dashboard.min.css','assets/css/ico_index.min.css'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'child'     => $child,
                'icoList'   => $icoList,
                'PreviousTradeTransactions'   => $PreviousTradeTransactions,
                'pending'   => $pending,
                'iplan'     => $iplan,
                'icoStart'  => $icoStart->start_date,
                'nextIco'   => $nextIco,
                'icoOpen'   => $icoOpen,
                'page_title'=> 'TRADE WALLET DASHBOARD',
                'menu_id'   => "dashboard",
                'wallet_address' => $img,
                'coinRate'  => $coinrate,
                'activeIco' => $activeIco,
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.tradedashboard", $view_data);
    }
    
    
    
    
    
    public function getIco() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }
        $sold_slc   = 0;
        $total_slc  = 0;
        $dt         = date('Y-m-d H:i:s');
        $icoList    = \App\Models\CalenderIco::get_calender_ico_list();
//        dd($icoList);
        $icoStart   = \App\Models\CalenderIco::OrderBy('start_date','asc')->first();
        $preSaleStart = \App\Models\PreSellCalender::OrderBy('start_date', 'asc')->first();
         
         
        $nextIco = \App\Models\CalenderIco::OrderBy('start_date','asc')->where('start_date','>=',date('Y-m-d H:i:s'))->where('status',1)->first();
        if(is_null($nextIco)){
            $nextIco = 'Not Found';
        }else{
            $nextIco = $nextIco->start_date;
            
        }
        
        foreach($icoList as $il){
           if($il['status'] == 1){
               $sold_slc    = $sold_slc + $il['token'];
               $total_slc   = $total_slc + $il['token'];
               
           }else if($il['status'] == 0){
               $total_slc = $total_slc + $il['token'];
           }
        }

        
        
        $preSaleIco = \App\Models\PreSellCalender::get_calender_pre_sale_list();
        
        $nextIco = \App\Models\CalenderIco::OrderBy('start_date','asc')->where('start_date','>=',date('Y-m-d H:i:s'))->where('status',1)->first();
        if(is_null($nextIco)){
            $nextIco = 'Not Found';
        }else{
            $nextIco = $nextIco->start_date;
            
        }
        
//        dd($icoEnd);
                
        $icoActive = \App\Models\CalenderIco::where('start_date','<=',date('Y-m-d H:i:s'))
                                            ->where('end_date','>=',date('Y-m-d H:i:s'))
                                            ->orderBy('start_date','asc')
                                            ->first();
        $activeIco = 0;
        if($icoActive){
            $activeIco = $icoActive->id;
        }
        
        $preSaleActive = \App\Models\PreSellCalender::where('start_date','<=',date('Y-m-d H:i:s'))
                                            ->where('end_date','>=',date('Y-m-d H:i:s'))
                                            ->orderBy('start_date','asc')
                                            ->first();
        
        if(is_null($preSaleActive)){
            $preSaleActive =0;
            
        }else{
            $preSaleActive = $preSaleActive->id;
        }
     
        
         
        $nextPreSaleIco = \App\Models\PreSellCalender::OrderBy('start_date','asc')->where('start_date','>=',date('Y-m-d H:i:s'))->where('status',1)->first();
        if(is_null($nextPreSaleIco)){
            $nextPreSaleIco='Not Found';
        }else{
            $nextPreSaleIco=$nextPreSaleIco->start_date;
            
        }
        
        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'ICO Information',
                "js"    => ['https://bitpay.com/bitpay.js','assets/js/flipclock.min.js'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'icoList'   => $icoList,
                'sold_slc'  => $sold_slc,
                'total_slc' => $total_slc,
                'page_title'=> 'ICO Information',
                'menu_id'   => "ico_index",
                'nextIco'   => $nextIco,
                'icoStart'  => $icoStart->start_date,
                'activeIco' => $activeIco,
                'preSaleStart'  => $preSaleStart->start_date,
                'preSaleActive' => $preSaleActive,
                'preSaleIcoList'=>$preSaleIco,
                'nextPreSaleIco'=>$nextPreSaleIco,
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];
//        dd($view_data);
        return view("user.ico", $view_data);
    }
    public function getSecurity() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }

        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'Security',
                "js"    => ['https://bitpay.com/bitpay.js','assets/js/flipclock.min.js'],
                "css"   => ['assets/css/user-profile.min.css'],
            ],
            'body' => [
                'user'      => $user,
                'page_title'=> 'User Security',
                'menu_id'   => "user_security",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.security", $view_data);
    }
    
    public function getTicket() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }

        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'Ticket',
                "js"    => ['https://bitpay.com/bitpay.js','assets/js/flipclock.min.js'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'page_title'=> 'Support Ticket',
                'menu_id'   => "support_ticket",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.ticket", $view_data);
    }
    
     public function getTicketNew() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }
        
        
        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'Generate Ticket',
                "js"    => ['https://bitpay.com/bitpay.js','assets/js/moment.min.js',],
                "css"   => ['assets/css/wallet.min.css','assets/css/exchanges.min.css','assets/css/main.css'],
            ],
            'body' => [
                'user'      => $user,
                'page_title'=> 'Generate Ticket',
                'menu_id'   => "support_ticket",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.ticket_new", $view_data);
    }
    
    public function getShare() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }

        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'Share',
                "js"    => ['https://bitpay.com/bitpay.js','assets/js/flipclock.min.js'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'page_title'=> 'Network Share',
                'menu_id'   => "network_share",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.share", $view_data);
    }
    public function getTree() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }

        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'Tree',
                "js"    => ['https://bitpay.com/bitpay.js','assets/js/flipclock.min.js'],
                "css"   => ['assets/css/user-profile.min.css'],
            ],
            'body' => [
                'user'      => $user,
                'page_title'=> 'Network Tree',
                'menu_id'   => "network_tree",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.tree", $view_data);
    }
    public function getUserProfile() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }

        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'User Profile',
                "js"    => ['https://bitpay.com/bitpay.js','assets/js/flipclock.min.js'],
                "css"   => ['assets/css/user-profile.min.css'],
            ],
            'body' => [
                'user'      => $user,
                'page_title'=> 'User Profile',
                'menu_id'   => "user_index",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.user_profile", $view_data);
    }
    public function getTransaction() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }

        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'Transaction',
                "js"    => ['https://bitpay.com/bitpay.js','assets/js/flipclock.min.js'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'page_title'=> 'USD Transaction',
                'menu_id'   => "transaction",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.transaction", $view_data);
    }
    
     public function postTransactionFilter() {
       
       $param=\Input::all();
       $loggerId=\Auth::guard('user')->id(); 
       $param['loggerId']=$loggerId;
       $data=  \App\Models\BalanceTransactions::get_balance_transaction_report($param);
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
            return view("user.transaction_filter",$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);   
    }
    
    public function getBtcTransaction() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }

        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'BTC Transaction',
                "js"    => ['assets/js/flipclock.min.js'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'page_title'=> 'BTC Transaction',
                'menu_id'   => "btc_transaction",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.btc_transaction", $view_data);
    }
    public function getCoinTransaction() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }

        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'Transaction',
                "js"    => ['https://bitpay.com/bitpay.js','assets/js/flipclock.min.js'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'page_title'=> config('constant.COIN_SHORT_NAME').' Transaction',
                'menu_id'   => "coin_transaction",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.coin_transaction", $view_data);
    }
    
    public function getEarningUsd() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }

        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'Earning Balance',
                "js"    => ['https://bitpay.com/bitpay.js','assets/js/flipclock.min.js'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'page_title'=> 'Earning Report',
                'menu_id'   => "earning_usd",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.earning_usd", $view_data);
    }

    public function postEarningUsdFilter() {
       
       $param=\Input::all();
       $loggerId=\Auth::guard('user')->id(); 
       $param['loggerId']=$loggerId;
       $data= \App\Models\EarningHistory::get_earning_history_report($param);
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
            return view("user.earning_usd_filter",$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);   
    }
    
    public function getIcoBuy() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }
        
        $dt = date('Y-m-d H:i:s');
        
        $cico = new \App\Models\CalenderIco();
        
         
        $nextIco = \App\Models\CalenderIco::where('status','=',1)->where('start_date','<=',date('Y-m-d H:i:s'))
                                            ->where('end_date','>=',date('Y-m-d H:i:s'))
                                            ->first();
        
      
        if(!is_null($nextIco)){
            $soldCoin = \App\Models\CoinTransactions::where('type',1)->whereBetween('created_at', [$nextIco->start_date, $nextIco->end_date])
                                            ->sum('credit');
        }
        
//        dd($soldCoin);
        
        $icoOpen='False';
        if(is_null($nextIco) || $soldCoin>=$nextIco->token){
            $nextIco = \App\Models\CalenderIco::where('status',1)->where('start_date','>=',date('Y-m-d H:i:s'))->OrderBy('start_date','asc')->first();
            if(is_null($nextIco)){
                $nextIco='Not Found';
            }else{
                $nextIco=$nextIco->start_date;

            }
        }else{
            
           $icoOpen='True';
           $nextIco='Found';   
           
        }
        
      
        $icoList = \App\Models\CalenderIco::get_calender_ico_list_dash();
        
        $coinrate = \General::global_price(config('constant.COIN_SHORT_NAME'));
        
       
        
       
        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'Buy ICO',
                "js"    => ['https://bitpay.com/bitpay.js','assets/js/flipclock.min.js','assets/js/cryptobox.min.js','assets/js/paygate.min.js'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'icoList'   => $icoList,
                'coinRate'  => $coinrate,
                'page_title'=> 'Buy',
                'menu_id'   => "ico_buy",
                'nextIco'   => $nextIco,
                'icoOpen'   => $icoOpen
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

//        dd($view_data);
        if(!is_null($cnv_rate) && $cnv_rate->price == 0){
            return view("user.token_no_buy", $view_data);
        }
        
        return view("user.ico_buy", $view_data);
        
    }
    
    public function postIcoBuyFilter() {
       $param=\Input::all();
       
       $loggerId=\Auth::guard('user')->id(); 
       
       $param['loggerId']=$loggerId;
       $data=\App\Models\CoinTransactions::get_token_report($param);
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
            return view("user.ico_buy_filter",$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
        
    }
    
    public function getBtcBuy() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }
        
        $dt = date('Y-m-d H:i:s');
        $icoList = \App\Models\CalenderIco::get_calender_ico_list_dash();
        
//        $coinrate = \App\Models\Setting::where('name','conversion_rate')->value('val');
//        
//        $cnv_rate = \App\Models\CalenderIco::where('start_date','<=',date('Y-m-d H:i:s'))
//                                            ->where('end_date','>=',date('Y-m-d H:i:s'))
//                                            ->first();
//        if(!is_null($cnv_rate)){
//            $coinrate = $cnv_rate->price;
//            if($cnv_rate->price == 0){
//                $old_rate = \App\Models\CalenderIco::OrderBy('end_date','DSC')
//                                                    ->where('end_date','<',$cnv_rate->start_date)
//                                                    ->first();
//                if(!is_null($old_rate)){
//                    $coinrate = $old_rate->price;
//                }
//                else{
//                    $coinrate = \App\Models\Setting::where('name','conversion_rate')->value('val');
//                }
//            }
//        }
        
       
        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'Buy BTC Token',
                "js"    => ['https://bitpay.com/bitpay.js','assets/js/flipclock.min.js','assets/js/cryptobox.min.js','assets/js/paygate.min.js'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'icoList'   => $icoList,
//                'coinRate'  => $coinrate,
                'page_title'=> 'BTC Buy',
                'menu_id'   => "btc_buy",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

//        dd($view_data);
//        if(!is_null($cnv_rate) && $cnv_rate->price == 0){
//            return view("user.token_no_buy", $view_data);
//        }
//        
        return view("user.btc_buy", $view_data);
        
    }
    
    public function postBtcInvoiceFilter() {
       $param=\Input::all();
       
       $loggerId=\Auth::guard('user')->id(); 
       
       $param['loggerId'] = $loggerId;
       $param['type']     = 'BTC';

       $data= \App\Models\CoinInvoice::get_invoice_report($param);
       
//       dd($data);
       
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
//            return view("user.btc_buy_filter",$res);
            return view("user.invoice_filter",$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
        
    }
    public function postBtcReportFilter() {
       $param=\Input::all();
       
       $loggerId=\Auth::guard('user')->id(); 
       
       $param['loggerId'] = $loggerId;
       $param['type']     = 'BTC';

       $data= \App\Models\CoinInvoice::get_invoice_report($param);
       
//       dd($data);
       
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
            $res['type'] = 'BTC';
//            return view("user.btc_buy_filter",$res);
            return view("user.invoice_report_filter",$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
        
    }
    public function postCoinReportFilter() {
       $param=\Input::all();
       
       $loggerId=\Auth::guard('user')->id(); 
       
       $param['loggerId'] = $loggerId;
       $param['type']     = config('constant.COIN_SHORT_NAME');

       $data= \App\Models\CoinInvoice::get_invoice_report($param);
       
//       dd($data);
       
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
            $res['type'] = config('constant.COIN_SHORT_NAME');
//            return view("user.btc_buy_filter",$res);
            return view("user.invoice_report_filter",$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
        
    }
    
    public function postBtcBuyFilter() {
       $param=\Input::all();
       
       $loggerId=\Auth::guard('user')->id(); 
       
       $param['loggerId']=$loggerId;
//       $data=\App\Models\CoinTransactions::get_token_report($param);
       $data= \App\Models\BtcTransactions::get_token_report($param);
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
            return view("user.btc_buy_filter",$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
        
    }
    
    
    public function getWallet() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }
        
//        $dt = date('Y-m-d H:i:s');
//        $last_reward = \App\Models\RewardPlans::active()->OrderBy('issue_date','DSC')->where('issue_date','<',$dt)->first()->toArray();
//        
//        $upcome_reward = \App\Models\RewardPlans::active()->OrderBy('issue_date','ASC')->where('issue_date','>',$dt)->first()->toArray();
        $btcCommison=\App\Models\Setting::where('name','=','btc_transaction_fee')->value('val');
        $ethCommison=\App\Models\Setting::where('name','=','eth_transaction_fee')->value('val');
        $user = \Auth::guard('user')->user()->toArray();
      
        
      
        $view_data = [
            'header' => [
                "title" => 'Wallet',
                "js"    => [],
                "css"   => ['assets/css/wallet.min.css'],
            ],
            'body' => [
                'user'      => $user,
//               'last'  => $last_reward,
//               'next'  => $upcome_reward,
                'btcCommison'=> $btcCommison,
                'ethCommison'=> $ethCommison,
                'page_title' => 'Wallet',
                'menu_id'    => "wallet_index",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.wallet", $view_data);
    }
    
    
    
     public function postWalletWithdrawRequest(){
        $param = \Input::all();
//      dd($param);
        $flag=0;
        $validator = \Validator::make(\Input::all(), \Validation::get_rules("user", "withdraw"));
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $json = \General::validation_error_res();
            $json['data'] = $error;
            $json['msg'] = 'Fill necessary field';
            return \Response::json($json,200);
        }
        
        $user = \Auth::guard('user')->user();
        $user_id = $user->id;
        $param['user_id'] = $user_id;
        
        if($param['coinType']=='btc'){
            $coinBal = $user->btc_balance;
           $param['coinType'] = 2;
            $flag=1;
           
        }else if($param['coinType']=='eth') {   
            $coinBal = $user->eth_balance;
                  $param['coinType'] = 3;
           $flag=2;
        }
        
        
        $withBal = $param['amount'];
        
        if($withBal>$coinBal){
            return \General::error_res('You have no sufficient balance to withdraw');
        }
        $balance=$coinBal-$withBal;
        
        if($param['coinType']=='btc'){
            $re = \App\Models\Users::where('id',$user_id)->update(['btc_balance'=>$balance]);
        }else if($param['coinType']=='eth') {
            $re = \App\Models\Users::where('id',$user_id)->update(['eth_balance'=>$balance]);
        }
        

        if($flag==1){
            $withDrow = \App\Models\BtcTransactions::add_order($param);
            
        }else if($flag==2){
            $withDrow = \App\Models\EthTransactions::add_order($param);
            
        }
        
        $withDrow = \App\Models\WalletTransactions::add_order($param);
        return \General::success_res('Amount Successfully Withdraw');
    }
    
    
    public function getWalletHistory() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }
        
        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'Wallet History',
                "js"    => ['https://bitpay.com/bitpay.js','assets/js/flipclock.min.js'],
                "css"   => ['assets/css/wallet.min.css'],
            ],
            'body' => [
                'user'      => $user,
                'page_title'=> 'Wallet History',
                'menu_id'   => "wallet_history",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.wallet_history", $view_data);
    }
    
    public function postWalletHistoryFilter(){
     $param=\Request::all();
//   dd($param);
     $loggerId=\Auth::guard('user')->id(); 
     $param['loggerId']=$loggerId;
     if(isset($param['select-coin']) && $param['select-coin']!=''){
         if($param['select-coin']==1){
//         $data=\App\Models\WalletTransactions::get_wallet_transaction_report($param);
        }
        else if($param['select-coin']==2){
          $data=\App\Models\BtcTransactions::get_wallet_transaction_report($param);  
        }
        else if($param['select-coin']==3){
          $data=\App\Models\EthTransactions::get_wallet_transaction_report($param);  
       }
     }else{   
          $data=\App\Models\WalletTransactions::get_wallet_transaction_report($param);
     }
     
        if ( $data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
//            dd($res);
            return view("user.wallet_history_filter",$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
    }
    
    public function getExchange() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }
        
        $dt = date('Y-m-d H:i:s');
        $last_reward = \App\Models\RewardPlans::active()->OrderBy('issue_date','DSC')->where('issue_date','<',$dt)->first()->toArray();
        
        $upcome_reward = \App\Models\RewardPlans::active()->OrderBy('issue_date','ASC')->where('issue_date','>',$dt)->first()->toArray();
        
        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'Exchange',
                "js"    => ['https://bitpay.com/bitpay.js','assets/js/moment.min.js','assets/js/flipclock.min.js','assets/js/client.js','assets/js/chart.min.js','assets/js/client.js'],
                "css"   => ['assets/css/wallet.min.css','assets/css/exchanges.min.css','assets/css/main.css'],
            ],
            'body' => [
                'user'      => $user,
                'last'      => $last_reward,
                'next'      => $upcome_reward,
                'page_title'=> 'Exchange',
                'menu_id'   => "exchange",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.exchange", $view_data);
    }
    public function getTokenBuy() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }
        
        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'Token Buy',
//                "js"    => ['https://bitpay.com/bitpay.js'],
                "js"    => [],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'page_title'=> 'Token Buy',
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.token_buy", $view_data);
//        return view("user.token_no_buy", $view_data);
    }
    
    public function getTokenReport() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }

        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'Token Report',
                "js"    => [],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'page_title'=> 'Report',
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.token_report", $view_data);
    }
    
    public function postTokenReportFilter() {
       
       $loggerId=\Auth::guard('user')->id(); 
       
       $data=\App\Models\CoinTransactions::get_token_report($loggerId);
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
    
    public function getWithdrawalRequests() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }

        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'Withdrawal Request',
                "js"    => [],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'page_title'=> 'Withdrawal Request',
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.withdrawal_request", $view_data);
    }
    
  
    
    
    
    
    
    public function getTradeWallet() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }

        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'Withdrawal Request',
                "js"    => [],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'page_title'=> 'Withdrawal Request',
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.withdrawal_request", $view_data);
    }
    
    
        
    
    
    
    
    
    public function getWithdrawalLists() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }

        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'Withdrawal List',
                "js"    => [],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'page_title'=> 'Withdrawal List',
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.withdrawal_list", $view_data);
    }
      
   public function postWithdrawalListFilter() {
       $param = \Input::all();
       $user = \Auth::guard('user')->user();
       $user_id = $user->id;
       
       $param['user_id'] = $user_id;
       
       $req = \App\Models\WithdrowRequest::get_request_filter($param);
       
       return view("user.withdrawal_list_filter",$req);
    }
    
    public function getBalanceReport() {
        
        if (!\Auth::guard('user')->check()) {
            return \Response::view('errors.404', array(), 404);
        }

        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'Balance Report',
                "js"    => [],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'page_title'=> 'Balance Report',
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.balance_report", $view_data);
    }
    
    public function postBalanceReportFilter() {

       $loggerId=\Auth::guard('user')->id(); 

       $data=  \App\Models\BalanceTransactions::get_balance_transaction_report($loggerId);
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
            return view("user.transaction_filter",$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
    }
        
    
    public function postLogin(Request $req) {
       
        $param = $req->input();
        //dd($param);
        /* add {!! Recaptcha::render() !!} in front page for enable recaptcha*/
//        dd($param);
        
       // dd($setting);
        
        $custome_msg = [
            'g-recaptcha-response.required'   => 'Please Verify you are Human.',
        ];
        
        $validator = \Validator::make(\Input::all(), \Validation::get_rules("user", "login"),$custome_msg);
       
        $setting=app('settings');
        if($setting['login_disable_status']==1){
                \Session::put('auth_msg',$setting['login_disable_status_msg']);
                return redirect()->back();
        }
        
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
//            $res = \General::validation_error_res($error[0]);
//            $res['data'] = $error;
//            return $res;
            
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
            
//            dd($param,$error[0]);
//            return view('site.login', $view_data)->withErrors($validator);
            return view('user.login', $view_data)->withErrors($validator);
        }
        
        
        
        $res = Users::doLogin($param);
            if($res['flag'] == 0){
//                 return \General::error_res('Wrong Login Credential');
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
//                return view('site.login', $view_data)->withErrors('Wrong User Id or Password !!');
                return view('user.login', $view_data)->withErrors('Wrong User Id or Password !!');
            }else if($res['flag'] == 4){
//               return \General::email_verify_error_res();
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
                return view('user.login', $view_data)->withErrors('Email Id is Not Verified');
        
            }else if($res['flag'] == 1){
                
                $user = $res['data'];
                if($user['google2fa_secret']){
                   $check = \General::google_authenticate($user,$param);
                   if($check['flag'] != 1){
//                       return $check;
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
                        return view('user.login', $view_data)->withErrors($check['msg']);
                   }
                }
//                dd($user);
//                exit;
//                 return \General::success_res('Login successful');
                
                             //  $email=$param['user_email'];
                                //$email=$param['user_pass'];

                
                
              // return \Redirect::to("https://epay.nrccoin.com/auth.php?email=$email&token=$token_data");

                
                
               return \Redirect::to("user/dashboard");
      
            }           
            
    }

    public function getLogout() {
        \App\Models\Token::delete_token();
        \Auth::guard('user')->logout();
        return redirect("login");
    }
    
    public function postSignup() {
        $param = \Input::all();
    //    dd($param);
        $custome_msg = [
            'name.required'         => 'Email Address Require',
            'email.required'        => 'Email Address Require',
            'email.email'           => 'Invalid Email Address',
            'password.required'     => 'Password Require',
            'mobile.unique'         => 'Contact No. Already Registered.',
            'mobile.digits_between' => 'Mobile No Must 10 Digit.',
            'con_password.unique'   => 'Email Address Already Registered.',
        ];
        
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
                
        
        if($param['password'] != $param['con_password']){
           
//            return \General::error_res("Password and Confirm password do not match.");
            return view('user.signup', $view_data)->withErrors('Password and Confirm password do not match.');
            
//             dd($param);
        }
        
        $validator = \Validator::make(\Input::all(), \Validation::get_rules("user", "signup"), $custome_msg);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
//            $res = \General::validation_error_res($error[0]);
//            $res['data'] = $error;
//            return $res;
            
            return view('user.signup', $view_data)->withErrors($error[0]);
        }
//        dd('fsdaf');
        $res = App\Models\Users::signup($param);
        
        return view('user.signup', $view_data)->withErrors($res['msg']);
//      return $res;
      
    }
    
    public function getResendConfirm() {

        if (\Auth::guard("user")->check()) {
//            dd(\Auth::guard('user')->user());
            return \Redirect::to("user/dashboard");
        }
        $view_data = [
            'header' => [
                "title" => 'Resend Confirmation',
                "js"    => [],
                "css"   => [],
            ],
            'body' => [],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];
        return view('user.resend_confirmation', $view_data);
    }
    public function getForgotPass() {

        if (\Auth::guard("user")->check()) {
//            dd(\Auth::guard('user')->user());
            return \Redirect::to("user/dashboard");
        }
        $view_data = [
            'header' => [
                "title" => 'Forgot Password',
                "js"    => [],
                "css"   => [],
            ],
            'body' => [],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];
        return view('user.forgotpass', $view_data);
    }
    
    public function postForgotPass() {

        $view_data_back = [
            'header' => [
                "title" => 'Forgot Password',
                "js"    => [],
                "css"   => [],
            ],
            'body' => [],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];
        $validator = \Validator::make(\Input::all(), \Validation::get_rules("user", "forget_pass"));
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $res = \General::validation_error_res($error[0]);
//            $res['data'] = $error;
//            return $res;
//            dd($error);
            return view("user.forgotpass", $view_data_back)->withErrors($error[0]);
        }
        $param = \Input::all();
        $res = \App\Models\Users::forget_password($param);
//            return \General::success_res('Forgot Passsword Link is sent to the Registered Email Address');
        
        if($res['flag'] == 0){
            return view("user.forgotpass", $view_data_back)->withErrors($res['msg']);
        }

        return view("user.forgotpass", $view_data_back)->withErrors([1,'Forgot Passsword Link is sent to the Registered Email Address']);
    }
    public function postResendConfirmation() {
//dd(0);
        $view_data_back = [
            'header' => [
                "title" => 'Resend Confirmation Mail',
                "js"    => [],
                "css"   => [],
            ],
            'body' => [],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];
        $validator = \Validator::make(\Input::all(), \Validation::get_rules("user", "forget_pass"));
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            return view('user.resend_confirmation', $view_data_back)->withErrors($validator);
        }
        $param = \Input::all();
        $res = \App\Models\Users::resend_confirmation($param);
//        dd($param,$res);
        
        return view('user.resend_confirmation', $view_data_back)->withErrors(['msg'=>$res['msg']]);
    }
    
    public function getResetpass($token = '') {
//        dd($token);
        $pass_token = \App\Models\Token::active()->where('token', '=', $token)->get()->toArray();
        
        if (count($pass_token) <= 0) {

            $view_data = [
                'header' => [
                    "title" => "Reset Password",
                    "js"    => [],
                    "css"   => []
                ],
                'body' => [
    //                'forgorttoken' => null,
                ],
                'footer' => [
                    "js"    => [],
                    "css"   => [],
                    'flag'  => 1,
                ],
            ];
            
            $res['msg'] = 'This Link is Expired.';
            return \Response::view('errors.404', array('msg'=>'This Link is Expired.'), 404);
        }

//        dd($pass_token);

        if (\Auth::guard("user")->check()) {
            return \Redirect::to("user/dashboard");
        }
        $view_data = [
            'header' => [
                "title" => "Forgot Password",
                "js"    => [],
                "css"   => []
            ],
            'body' => [
                'forgorttoken' => $token,
            ],
            'footer' => [
                "js"    => [],
                "css"   => [],
                'flag'  => 1,
            ],
        ];
        return view('user.resetpass',$view_data);
    }

    public function postResetpass() {
        $param = \Input::all();
        
        $view_data_back = [
            'header' => [
                "title" => "Reset Password",
                "js"    => [],
                "css"   => []
            ],
            'body' => [
                'forgorttoken' => $param['forgottoken'],
            ],
            'footer' => [
                "js"    => [],
                "css"   => [],
                'flag'  => 1,
            ],
        ];
        $validator = \Validator::make(\Input::all(), \Validation::get_rules("user", "reset_pass"));
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            return view('user.resetpass', $view_data_back)->withErrors($validator);
        }
        
        if($param['new_pass'] != $param['cnew_pass']){
            return view('user.resetpass', $view_data_back)->withErrors(['msg' => 'New Password and Confirm Password not Matched.' ]);
        }
        
        $user = \App\Models\Token::where('type',\Config::get("constant.FORGETPASS_TOKEN_STATUS"))->where('token',$param['forgottoken'])->first();
        if(!is_Null($user)){
//            dd($param,$user->toArray());
            $userInfo = \App\Models\Users::where('id',$user->user_id)->first();
            if(is_Null($userInfo)){
                return view('user.resetpass', $view_data_back)->withErrors(['msg' => 'User Not Found.' ]);
            }
            // $userInfo->password = $param['new_pass'];
//            dd($userInfo->toArray());
            $userInfo->password = \Hash::make($param['new_pass']);
            $userInfo->save();
            $user->delete();
        }
//        dd($param,$userInfo->toArray(),$user->toArray());
        return \Redirect('login');
    }
    
    public function postChangePass() {
        $param = \Input::all();
//        dd($param);
        $validator = \Validator::make(\Input::all(), \Validation::get_rules("user", "change_password"));
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $res = \General::error_res($error[0]);
//            $res['data'] = $error;
            return $res;
        }
        $res = Users::change_password($param);
//        dd($res);
        return $res;
        
    }
    
    public function postUpdateProfile(){
        $param = \Input::all();
//        dd($param);
        $validator = \Validator::make(\Input::all(), \Validation::get_rules("user", "update_2"));
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $res = \General::error_res($error[0]);
//            $res['data'] = $error;
            return $res;
        }
        
        $res = Users::update_profile($param);
        return $res;
//        dd($param);
    }
    
    public function getBitPayGenerate(){
        echo \App\Lib\BitPayLib::generate_invoice();
    }
    
    public function getBitPayGenKey(){
        return \App\Lib\BitPayLib::generate_keys();
    }
    
    public function getBitPayPair(){
        $pairingCode = \Input::get("pairing_code");
        return \App\Lib\BitPayLib::pair($pairingCode);
    }
    
    public function getTest(){
//        dd(app('settings'));
        return view('user.test');
    }
    
    public function postWithdrawRequest(){
        $param = \Input::all();
//        dd($param);
        $user = \Auth::guard('user')->user();
        $user_id = $user->id;
        $param['user_id'] = $user_id;
        
        $coinBal = $user->coin;
        $withBal = $param['amount'];
        
        
        
        if($withBal > $coinBal){
            return \General::error_res('you have no sufficient balance to withdraw');
        }
        
        $withDrow = \App\Models\WithdrowRequest::add_newrequest($param);
        
        return $withDrow;
    }
    
    public function getEmailVerify($token = ''){
//        dd($token);
        $pass_token = \App\Models\Token::active()->where('token', '=', $token)->get()->toArray();
//        dd($pass_token);
        if (count($pass_token) <= 0) {
            return \Response::view('errors.404', array('msg'=>'This Link is Expired.!'), 404);
        }

//        dd($pass_token);
        $user = \App\Models\Token::where('type',\Config::get("constant.ACCOUNT_ACTIVATION_TOKEN_STATUS"))->where('token',$token)->first();
        
//        dd($user);
        if(!is_Null($user)){
//            dd($param,$user->toArray());
            $userInfo = \App\Models\Users::where('id',$user->user_id)->first();
            if(is_Null($userInfo)){
                return \Response::view('errors.404', array('msg'=>'This Link is Invalid.!'), 404);
            }
            
            $check = 1;
            $c = 0;
            while(!is_null($check)){
                $email = explode('@',$userInfo['email']);
                $name = $email[0].'_'.$userInfo['id'];
//                $coin_address = \App\Models\General::generate_coin_address($name);
//                $coin_address = \App\Models\General::generate_coin_address();
                $coin_address = \App\Models\General::generate_coin_address($name);
                $check = \App\Models\Users::where('coin_address',$coin_address)->first();
                $c++;
                if($c == 10){
                    return \Response::view('errors.404', array('msg'=>'This Link is Temporily Unavailable. Please, Try After Some time.'), 404);
                }
            }

//            dd($c);
//            $coin_address = \App\Models\General::generate_coin_address();
            
            $userInfo->status = 1;
            $userInfo->coin_account_name = $name;
            $userInfo->coin_address = trim($coin_address);
            $userInfo->save();
            $user->delete();
            
            $data = [
                'user_id'=>$user->user_id,
                'coin_address'=>trim($coin_address),
            ];
            $addAddress = \App\Models\CoinAddress::new_coin_address($data);
            return view('user.email_verify');
        }
        
        if (\Auth::guard("user")->check()) {
            return \Redirect::to("user/dashboard");
        }
        
        
//        $view_data = [
//            'header' => [
//                "title" => "Email",
//                "js"    => [],
//                "css"   => []
//            ],
//            'body' => [
//                'forgorttoken' => $token,
//            ],
//            'footer' => [
//                "js"    => [],
//                "css"   => [],
//                'flag'  => 1,
//            ],
//        ];
//        return view('user.email_verify', $view_data);
//        return view('user.email_verify');
        return \Response::view('errors.404', array(), 404);
    }
    
    
    public function postQrCode() {
        $param=\Request::all();
        
        $user = \Auth::guard('user')->user()->toArray();
        
        if($param['address']=='btc')
        {  
            if($user['btc_address']==''){
            
                return \General::error_res('BTC Deposit Address is Not Available');
            }  
            $coin_address = $user['btc_address'];
            
        }else if($param['address']=='eth'){
            if($user['eth_address']==''){
                return \General::error_res('ETH Deposit Address is Not Available');
            }
            $coin_address = $user['eth_address'];
        }
        
      
        $qrImg = $coin_address.'.png';
        $dir_path = config('constant.QR_CODE_PATH');
////      $imgHTML=\DNS2D::getBarcodeHTML($coin_address, "QRCODE",6,6);
        $data = \DNS2D::getBarcodePNG($coin_address,"QRCODE",6,6);
        $data = base64_decode($data);
        file_put_contents($dir_path.$qrImg,$data);
        $res = \General::success_res();
        
//      $res['data']=$imgHTML;
        $res['data']=\URL::to('/assets/img/uploads/qr/'.$qrImg);
        return $res;
//      return \Response::download($dir_path . $qrImg);
        
  
      }
    
    public static function postTransactionFee(){
        $param=\Input::all();
        $flag=0;
        $finalRate=0;
        $price=$param['amount'];
        if($param['coinType']=='btc'){
            $flag=1;
            $mgn=\App\Models\Setting::where('name','=','btc_transaction_fee')->value('val');
        }
        else if($param['coinType']=='eth'){
            $flag=2;
            $mgn=\App\Models\Setting::where('name','=','eth_transaction_fee')->value('val');
        }
       
//        if(\General::is_json($result) )
//        {
//            $jdata =json_decode($result,true);
//            if($flag==1){
//                $price=$jdata['bpi']['USD']['rate_float'];
//            }else if($flag==2){
//                 $price=$jdata[0]['price_usd'];
//            }
//            
//        }else {
//            return  "data not found";
//        }
        $comm=0;
        $mgna=str_split($mgn);
        
        
        $mgna=str_split($mgn);
      
        if(end($mgna)=='%'){
            $val=(float)substr($mgn,0,strpos($mgn,'%'));
            $comm=(($price*$val)/100);
            $finalRate=$price+(($price*$val)/100);
            
        }else{
            $val=(float)$mgn;
            $comm=(float)$mgn;
            $finalRate=$price+$val;
        }
        
        
        
    
        return  ['commison'=>number_format((float)$comm, 8, '.', ''),'db_amount'=>number_format((float)$finalRate, 8, '.', '')];
    }
    
    public function getMembers() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }

        $user = \Auth::guard('user')->user()->toArray();
        $loggerId=\Auth::guard('user')->id();
       
        $total_member= \App\Models\Users::where('parent_id',$loggerId)->count();
        
        if($loggerId!=0){
            $parent_info = \App\Models\Users::where('id',$user['parent_id'])->first()->toArray();
        }else{
            $parent_info=0;
        }
        
        

        $user['total_member']=$total_member;
        $view_data = [
            'header' => [
                "title" => 'Members',
                "js"    => ['https://bitpay.com/bitpay.js','assets/js/flipclock.min.js'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'page_title'=> 'Members',
                'menu_id'   => "network_member",
                'parentInfo'=> $parent_info,
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.member", $view_data);
    }
    
    public function postMembersFilter() {
        $param=\Request::all();
        $loggerId=\Auth::guard('user')->id();
        $param['loggerId']=$loggerId;


       $data= \App\Models\Users::filter_referral_users($param);
//       dd($data);
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
            return view("user.members_filter",$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
    }
    
    public function postGetCoinAddress() {
        
        $param=\Input::all();
        $user = \Auth::guard('user')->user()->toArray();
        $flag=0;
        
        if($param['address']=='btc')
        {
            if($user['btc_address']==''){
                return \General::error_res('BTC Deposit Address is Not Available');
            }
            $coin_address = $user['btc_address'];
            
        }else if($param['address']=='eth'){
            if($user['eth_address']==''){
                return \General::error_res('ETH Deposit Address is Not Available');
            }
            $coin_address = $user['eth_address'];
        }
        
        if($flag==0){
            $res=\General::success_res();
            $res['data']=$coin_address;
        }
        
        return $res;
        
    }
    
    public function postSupportTicketFilter() {
      
        $param=\Request::all();
        $loggerId=\Auth::guard('user')->id();
        $param['loggerId']=$loggerId;
        

       $data= \App\Models\SupportTicket::get_my_ticket($param);
//     dd($data);
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
//            dd($res);
            return view("user.ticket_filter",$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
    }
    
    public function postAddNewTicket(){
        $param = \Input::all();
        $param=\Request::all();
        $loggerId=\Auth::guard('user')->id();
        $param['loggerId']=$loggerId;
        
        $rules = [
            'title'=>'required',
            'type'=>'required',
            
            'content'=>'required',
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
        
        $res = \App\Models\SupportTicket::add_new_ticket($param);
        if($res['flag'] == 1){
            return $res;
        }else{
            return \General::error_res('Error Occur');
        }
    }
    
    public function postDeleteTicket(){
        $param = \Input::all();
        $param=\Request::all();
        $loggerId=\Auth::guard('user')->id();
        $param['loggerId']=$loggerId;
        
        $rules = [
            'did'=>'required',
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
        $res = \App\Models\SupportTicket::where('id',$param['did'])->update(['status'=>'4']);
       
        return \General::success_res('Ticket Deleted Successfully');
        
    }
    
    
    
    
         public function postAddressbook(){

                  if (!\Auth::guard('user')->check()) {
            return \General::error_res('your session expired !');
        }
        $param  = \Input::all();
        $user   = \Auth::guard('user')->user();
        $user_id    = $user->id;
        $param['user_id'] = $user_id;
        
      //  dd($param);
             
                     $data = [
                        'user_id'      => $user_id,
                ];


       
        
        $rules = [
            'address'=>'required',
            'name'=>'required',
        ];

        $rulesMsg =[
            'address.required' => 'Please enter address',
        ];    
             
               $validator = \Validator::make(\Input::all(), $rules,$rulesMsg);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $json = \General::validation_error_res();
            $json['data'] = $error;
            $json['msg'] = 'Fill necessary field';
            return \Response::json($json,200);
        } 
             
$loggerId = $user_id;
             
if ($param['address_type']==1) {   
    
     $res = \App\Models\CoinAddress::check_ex($param);
    
    
if($res['flag'] == 1){

   $res = \App\Models\AddAddress::add_address($param);
           
$addressbook = \App\Models\Addressbook::myaddresses($data);
             
             
             
         $view_data = [
            'header' => [
                "title" => 'Addressbook',
                "js"    => ['assets/js/flipclock.min.js'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'addressbook'=> $addressbook,
                'page_title'=> 'Addressbook',
                'menu_id'   => "address_book",
                'address_added'   => "Address added to addressbook",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.addressbook", $view_data);              
            
            
            
            
            
            
            
            
        }  
    
    if($res['flag'] == 0){

            
              
             
$addressbook = \App\Models\Addressbook::myaddresses($data);
             
             
             
         $view_data = [
            'header' => [
                "title" => 'Addressbook',
                "js"    => ['assets/js/flipclock.min.js'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'addressbook'=> $addressbook,
                'address_not_exist'=> 'Exchange Address does not exist',
                'page_title'=> 'Addressbook',
                'menu_id'   => "address_book",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.addressbook", $view_data);              
            
            
            
                  
            
            
            
}  
    
    
    
    
}
             
             
 
             
             
             
             
if ($param['address_type']==2) {
    
    
    
  $res = \App\Models\TrAddress::check_trade_address($param);

    
    //  $res = \App\Models\TrAddress::check_tr_in_addressbook($param);

    
         
if($res['flag'] == 0){
    
    
    
    
 
            
$addressbook = \App\Models\Addressbook::myaddresses($data);
             
             
             
         $view_data = [
            'header' => [
                "title" => 'Addressbook',
                "js"    => ['assets/js/flipclock.min.js'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'addressbook'=> $addressbook,
                'page_title'=> 'Addressbook',
                'menu_id'   => "address_book",
                'address_not_exist'   => "Trade wallet address does not exist",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.addressbook", $view_data);              
            
    
}
   
    
    
    
    
if($res['flag'] == 1){
        
    
  $res = \App\Models\AddAddress::add_address($param);
    

            
$addressbook = \App\Models\Addressbook::myaddresses($data);
             
             
             
         $view_data = [
            'header' => [
                "title" => 'Addressbook',
                "js"    => ['assets/js/flipclock.min.js'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'addressbook'=> $addressbook,
                'page_title'=> 'Addressbook',
                'menu_id'   => "address_book",
                'address_added'   => "Trade wallet address added to addressbook",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.addressbook", $view_data);              
            
            
            
            
   
    
}
    
}
             
             
               
             
             
             
             
             
          
 
}
    
    
    
    
    
     public function postWithdrawalRequest(){
         if (!\Auth::guard('user')->check()) {
            return \General::error_res('your session expired !');
        }
        $param  = \Input::all();
        $user   = \Auth::guard('user')->user();
        $user_id    = $user->id;
        $btc_addr   = $user->btc_address;
        $coin_addr  = $user->coin_address;
        $param['user_id'] = $user_id;
        
      //  dd($param);
       
        
        $rules = [
            'coin_type'=>'required',
            'amount'=>'required|numeric',
        ];

        $rulesMsg =[
            'coin_type.required' => 'Please Choose Wallet',
        ];

        $validator = \Validator::make(\Input::all(), $rules,$rulesMsg);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $json = \General::validation_error_res();
            $json['data'] = $error;
            $json['msg'] = 'Fill necessary field';
            return \Response::json($json,200);
        }
        
        $trx_fee = app('settings')['token_transaction_fee'];
//        dd($setting);
        
        if($param['amount'] == 0){
            return \General::error_res('Withdrawal Amount must greater than 0');
        }
        
        if($param['coin_type'] == 1 ){
            
            if($param['coin_address'] == ''){
                return \General::error_res('Please Fill Coin Address');
            }
//            if($param['coin_address'] != $coin_addr){
//                return \General::error_res('Please Verify your Coin Address');
//            }
            if(strlen($param['coin_address']) != 34){
                return \General::error_res('Please Enter Valid Coin Address');
            }
            $param['address'] = $param['coin_address'];
        }else if($param['coin_type']== 2 || $param['coin_type'] == 3){
            
            if($param['btc_address']==''){
                return \General::error_res('Please Fill BTC Address');
             }
            if(strlen($param['btc_address']) != 34){
                return \General::error_res('Please Enter Valid Coin Address');
            }
             
//            if($param['btc_address']!= $btc_addr){
//                return \General::error_res('Please Verify your BTC Address');
//            }
            $param['address']=$param['btc_address'];
        }
       
        if($user->google2fa_secret){
            $ud = [
                'id'=>$user->id,
                'google2fa_secret'=>$user->google2fa_secret,
            ];
            $check = \General::google_authenticate($ud,$param);
            if($check['flag'] != 1){
//                       return $check;
                return \Response::json($check, 200);
            }
        }
        
        $coinBal    = $user->coin;
        $usdBal     = $user->balance;
        $btcBal     = $user->btc_balance;
        $withBal    = $param['amount'];
        $account_name = $user->coin_account_name;
//        $trx_fee    = app('settings')['token_transaction_fee'];
        $auto = 0;
        $param['auto_coin'] = 0;
        if($param['coin_type'] == 1){
            if(($withBal + $trx_fee) > $coinBal){
                return \General::error_res('You have no sufficient coin balance to withdraw');
            }
            if(app('settings')['coin_auto_withdraw']){
                $auto = 1;
                $param['auto_coin'] = 1;
                $trx_id = \App\Models\General::generate_transaction_id($account_name,$param['address'],$param['amount']);
                if($trx_id['flag'] != 1 ){
                    return $trx_id;
                }
                $param['trx_id'] = $trx_id['data'];
            }
            
        }else if($param['coin_type'] == 2){
            if($withBal > $usdBal){
                return \General::error_res('You have no sufficient usd balance to withdraw.');
            }
        }else if($param['coin_type'] == 3){
            if($withBal > $btcBal){
                return \General::error_res('You have No Sufficient BTC Balance to Withdraw.');
            }
        }
        
        $res = \App\Models\WithdrowRequest::add_new_request($param);
        
        
       //    dd($res);
        $chekAddress = \App\Models\CoinAddress::check_address(['coin_address'=>$param['address']]);
        
        
        
        if($res['flag'] == 1){
            $credit = $debit = [];
            if(isset($user->id)){
                $from_user = [
                    'user_id'   => $user->id,
                    'type'      => 7,
                    'reference_id'=> $res['data']['id'],
                    'credit'    => 0,
                    'debit'     => $param['amount'] + $trx_fee,
                    'comment'   => $param['amount'] + $trx_fee.' '.config('constant.COIN_SHORT_NAME').' debited for transfer request #'.$res['data']['id'] .' to '.$param['address'],
                ];

                
                 if($param['coin_type'] == 1 && $auto == 1){
                        $debit = \App\Models\CoinTransactions::add_transaction($from_user);
                 }
                
            }
            
//            if($chekAddress['flag'] == 1){
//                $to_user = [
//                    'user_id'   => $chekAddress['data'][0]['user_id'],
//                    'type'      => 7,
//                    'reference_id'=> $res['data']['id'],
//                    'credit'    => $param['amount'],
//                    'debit'     => 0,
//                    'comment'   => $param['amount'].' '.config('constant.COIN_SHORT_NAME').' credited for transfer request #'.$res['data']['id'] .' from '.$user->coin_address,
//                ];
//
//                $credit = \App\Models\CoinTransactions::add_transaction($to_user);
//            }
//            dd($param,$res,$chekAddress,$credit,$debit);
            return $res;
        }else{
            return \General::error_res('Something might be Wrong. Try after Sometime.');
        }
        
      
        
        }
     
    
    
    
    
    
    
    
    
    
    
    
     
    
     public function postTradeRequest(){
         if (!\Auth::guard('user')->check()) {
            return \General::error_res('your session expired !');
        }
        $param  = \Input::all();
        $user   = \Auth::guard('user')->user();
        $user_id    = $user->id;
      //  $btc_addr   = $user->btc_address;
        $tr_addr  = $user->tr_address;
        $tr_balance  = $user->tr_balance;
        $param['user_id'] = $user_id;
        
      //  dd($param);
       
         $tr_address = $param['tr_address'];
        
        $rulesMsg =[
            'tr_coin_type.required' => 'Please Choose Wallet',
        ];
         
         
         
    $rules = array(
            'tr_coin_type'=>'required',
            'tr_amount'=>'required|numeric',
    );

         

        $validator = \Validator::make(\Input::all(), $rules,$rulesMsg);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $json = \General::validation_error_res();
            $json['data'] = $error;
            $json['msg'] = 'Fill necessary field';
            return \Response::json($json,200);
        }
        
       // $trx_fee = app('settings')['token_transaction_fee'];
//        dd($setting);
        
        if($param['tr_amount'] == 0){

        
        
    
        $user     = \Auth::guard('user')->user()->toArray();
        $loggerId = \Auth::guard('user')->id();
        $total_member = \App\Models\Users::where('parent_id',$loggerId)->count();
        $user['total_member'] = $total_member;
        
        $view_data = [
            'header' => [
//                "title" => 'Withdraw Request',
                "title" => 'Transfer Coin',
                "js"    => ['assets/js/flipclock.min.js'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
//                'page_title'=> 'Withdrawal Request',
                'page_title'=> 'Transfer Coin',
                'menu_id'   => "withdrawal_request",
                'greater_than_zero'   => "Trade amount must greater than 0",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];      
            
return view("user.withdrawal", $view_data);   
     
       
        
        
        
        }
         
         
         
        if($param['tr_amount'] > $tr_balance){
            
            
            
            
            
            
        $user     = \Auth::guard('user')->user()->toArray();
        $loggerId = \Auth::guard('user')->id();
        $total_member = \App\Models\Users::where('parent_id',$loggerId)->count();
        $user['total_member'] = $total_member;
        
        $view_data = [
            'header' => [
//                "title" => 'Withdraw Request',
                "title" => 'Transfer Coin',
                "js"    => ['assets/js/flipclock.min.js'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
//                'page_title'=> 'Withdrawal Request',
                'page_title'=> 'Transfer Coin',
                'menu_id'   => "withdrawal_request",
                'insufficient_credit'   => "Sorry you do not not have sufficient credit to perform transaction",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];      
            
return view("user.withdrawal", $view_data);   
     
            

        }

         
        
        
             
            if($param['tr_address'] == ''){
                return \General::error_res('Please Fill Coin Address');
            }
//            if($param['coin_address'] != $coin_addr){
//                return \General::error_res('Please Verify your Coin Address');
//            }
            if(strlen($param['tr_address']) < 10){
                return \General::error_res('Please Enter Valid Coin Address');
            }
         
         
         
         
 $res = \App\Models\TrAddress::check_tr($param);

         
if($res['flag'] == 1){
        

            
$add_new_trade = \App\Models\WithdrowRequest::add_new_trade($param);
         
         
 $trBal = $user->tr_balance;
 $withBal    = $param['tr_amount'];
 $new_balance = ($trBal - $withBal);   
        

         
 $my_balance = Users::where('id',$user['id'])->update(['tr_balance'=>$new_balance]);
    
 //$address_balance =  Users::decrement('tr_balance', $withBal);
 
 /*
 $query = DB::Users($wys_total_attend_table)
  ->where('studid',$student->id)                                     
  ->where('tr_address','=',$date_exploded[1])
  ->where('syear','=',$date_exploded[2]);

$query->increment('stotal_a');
$query->decrement('stotal_p');
*/
 
 
 
 $deposit_trader =  Users::where('tr_address', $tr_address)->increment('tr_balance', $withBal);
  
 //$address_balance = Users::where('tr_address',$tr_address)->update(['tr_balance'=>$new_balance]);
    
 //$deposit_trader = \App\Models\depositTrader::depositTrader($param);
   
// $address_balance = Users::where('tr_address',$tr_addr)->update(['tr_balance'=>$new_balance]);
 
         
         
         
         
         
        $user= \Auth::guard('user')->user()->toArray();
        $loggerId = \Auth::guard('user')->id();
        $total_member = \App\Models\Users::where('parent_id',$loggerId)->count();
        $user['total_member'] = $total_member;
        
        $view_data = [
            'header' => [
//                "title" => 'Withdraw Request',
                "title" => 'Transfer Coin',
                "js"    => ['assets/js/flipclock.min.js'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
//                'page_title'=> 'Withdrawal Request',
                'page_title'=> 'Transfer Coin',
                'menu_id'   => "withdrawal_request",
                'trade_sucessful'   => "Thank you for choosing NRCCOIN, Trade sucessful!!",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.withdrawal", $view_data);   
         
} else {
    
    
    
    
    
         
         
        $user= \Auth::guard('user')->user()->toArray();
        $loggerId = \Auth::guard('user')->id();
        $total_member = \App\Models\Users::where('parent_id',$loggerId)->count();
        $user['total_member'] = $total_member;
        
        $view_data = [
            'header' => [
//                "title" => 'Withdraw Request',
                "title" => 'Transfer Coin',
                "js"    => ['assets/js/flipclock.min.js'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
//                'page_title'=> 'Withdrawal Request',
                'page_title'=> 'Transfer Coin',
                'menu_id'   => "withdrawal_request",
                'insufficient_credit'   => "Address does not exist",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.withdrawal", $view_data);      
    
    
    
    
    
}
         
         
         
         
        

        
        }
      
    
    
    
    
    
    
    
    
    
    
    public function getWithdrawalRequest() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }

        $user     = \Auth::guard('user')->user()->toArray();
        $loggerId = \Auth::guard('user')->id();
        $total_member = \App\Models\Users::where('parent_id',$loggerId)->count();
        $user['total_member'] = $total_member;
        
        $view_data = [
            'header' => [
//                "title" => 'Withdraw Request',
                "title" => 'Transfer Coin',
                "js"    => ['assets/js/flipclock.min.js'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
//                'page_title'=> 'Withdrawal Request',
                'page_title'=> 'Transfer Coin',
                'menu_id'   => "withdrawal_request",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.withdrawal", $view_data);
    }
        
    
    
    
    
    
    public function getAddressbook() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }

        $user     = \Auth::guard('user')->user()->toArray();
        $loggerId = \Auth::guard('user')->id();
        $total_member = \App\Models\Users::where('parent_id',$loggerId)->count();

        
        $data = [
                        'user_id'      => $user['id'],
                ];


        
        
        $addressbook = \App\Models\Addressbook::myaddresses($data);

        
        $view_data = [
            'header' => [
//                "title" => 'Withdraw Request',
                "title" => 'Addressbook',
                "js"    => ['assets/js/flipclock.min.js'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
//                'page_title'=> 'Withdrawal Request',
                'addressbook'=> $addressbook,
                'page_title'=> 'Addressbook',
                'menu_id'   => "address_book",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.addressbook", $view_data);
    }
    
    public function getWithdrawalList() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }

        $user = \Auth::guard('user')->user()->toArray();
        $loggerId=\Auth::guard('user')->id();
        $total_member= \App\Models\Users::where('parent_id',$loggerId)->count();
        $user['total_member']=$total_member;
        
        $view_data = [
            'header' => [
                "title" => 'Withdraw History',
                "js"    => ['assets/js/flipclock.min.js'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'page_title'=> 'Withdrawal History',
                'menu_id'   => "withdrawal_list",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.withdrawal_request_list", $view_data);
    }
    
    public function postWithdrawalRequestFilter() {
      
        $param=\Request::all();
        $loggerId=\Auth::guard('user')->id();
        $param['loggerId']=$loggerId;
        

       $data = \App\Models\WithdrowRequest::get_request_filter($param);
//     dd($data);
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
//            dd($res);
            return view("user.withdrawal_request_filter",$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
    }
    
    public function getTestInvest(){
        $res = \App\Models\InvestPlan::active()->get()->toArray();
        return $res;
        
    }

        
    public function getInvest() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }
        $sold_slc=0;
        $total_slc=0;
        $dt = date('Y-m-d H:i:s');
        $icoList = \App\Models\CalenderIco::get_calender_ico_list();
//        dd($icoList);
        foreach($icoList as $il){
           if($il['status']==1){
               $sold_slc=$sold_slc+$il['token'];
               $total_slc=$total_slc+$il['token'];
               
           }else if($il['status']==0){
               $total_slc=$total_slc+$il['token'];
           }
        }
        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'Invest',
                "js"    => ['https://bitpay.com/bitpay.js','assets/js/flipclock.min.js'],
                "css"   => ['assets/css/ico_index.min.css'],
            ],
            'body' => [
                'user'      => $user,
                'icoList'   => $icoList,
                'sold_slc'  => $sold_slc,
                'total_slc' => $total_slc,
                'page_title'=> 'Invest',
                'menu_id'   => "invest",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];
//        dd($view_data);
        return view("user.invest", $view_data);
    }
    
    public function getInvestUsd() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }
        
        $dt = date('Y-m-d H:i:s');
       // $icoList = \App\Models\CalenderIco::get_calender_ico_list_dash();
        $coinrate = \General::global_price(config('constant.COIN_SHORT_NAME'));

        $param['crnt']=1;
        $param['len']=10;
        $iplan =\App\Models\InvestPlan::get_plan_list($param);
        
        
       
        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'Lending',
                "js"    => ['https://bitpay.com/bitpay.js','assets/js/flipclock.min.js','assets/js/cryptobox.js'],
                "css"   => [],
            ],
            'body' => [
                'user'  => $user,
               
                'coinRate'  => $coinrate,
                'page_title'=> 'Lending',
                'menu_id'   => "invest-usd",
                'iplan'     => $iplan,
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.invest_usd", $view_data);
    }
    
    public function getBtcToUsd() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }
        
        $dt = date('Y-m-d H:i:s');
        
        $coinrate = \General::global_price('btc');
       
        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'BTC to USD',
                "js"    => ['https://bitpay.com/bitpay.js','assets/js/flipclock.min.js','assets/js/cryptobox.js','assets/js/jquery.form.min.js'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'coinRate'  => $coinrate,
                'page_title'=> 'BTC to USD',
                'menu_id'   => "btc-to-usd",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.btc_to_usd", $view_data);
    }
    
    public function postBtcToUsd(){
        $param      = \Input::all();
        $user       = \Auth::guard('user')->user();
        $user_id    = $user->id;
        $param['user_id'] = $user_id;
        $coinBal    = $user->btc_balance;
     
//        if($param['coin_amt']==0 || $param['usd_amt']==0)
        if($param['coin_amt'] == 0){
            return \General::error_res('Please Enter Valid Amount');
        }
        $rules = [
            'coin_amt'  =>'required|numeric',
//            'usd_amt'   =>'required|numeric',
        ];
        
        $validator = \Validator::make(\Input::all(), $rules);
        if ($validator->fails()) {
            $messages       = $validator->messages();
            $error          = $messages->all();
            $json           = \General::validation_error_res();
            $json['data']   = $error;
            $json['msg']    = 'Fill necessary field';
            return \Response::json($json,200);
        }
        
        
        $mcoinBal = $param['coin_amt'];
        $coinrate = \General::global_price('btc');
        
        
        
        $usdAmt = $mcoinBal * $coinrate;
        
//        $trx_fee    = app('settings')['token_transaction_fee'];
//        $mcoinBal +=  $trx_fee;
        
        if($mcoinBal > $coinBal){
            return \General::error_res('You have no sufficient Coin  For conversion');
        }
        
        
        $dataCoin = [
                'user_id'   => $user_id,
                'type'      => 4,
                'reference_id'=> null,
                'credit'    => 0,
                'debit'     => $mcoinBal,
                'comment'   => $mcoinBal.' Debited for Conversion request #',
        ];
        
        $trans  = \App\Models\BtcTransactions::add_transaction($dataCoin);
        $dataUsd = [
                'user_id'   => $user_id,
                'type'      => 4,
                'reference_id'=> null,
                'credit'    => $usdAmt,
                'debit'     => 0,
                'comment'   => $usdAmt.' Credited for Conversion request Trx-'.$trans,
        ];
        
       
        $trans  = \App\Models\BalanceTransactions::add_transaction($dataUsd);
        $user   = \App\Models\Users::where('id',$user_id)->first();
        $cbal   = $user->btc_balance;
        $ubal   = $user->balance;
        $res    = \General::success_res('Conversion successfully');
        $res['data'] = ['usdc'=>$ubal,'coinc'=>$cbal];
        return \Response::json($res,200);
        
    }
    
    public function getConvertCoin() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }
        
        $dt = date('Y-m-d H:i:s');
        
        $coinrate = \General::global_price(config('constant.COIN_SHORT_NAME'));
       
        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'Coin to USD',
                "js"    => ['https://bitpay.com/bitpay.js','assets/js/flipclock.min.js','assets/js/cryptobox.js','assets/js/jquery.form.min.js'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'coinRate'  => $coinrate,
                'page_title'=> 'Convert Coin',
                'menu_id'   => "convert-coin",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.convert_coin", $view_data);
    }
    
    public function postConvertCoin(){
        $param  = \Input::all();
        $user   = \Auth::guard('user')->user();
        $user_id= $user->id;
        $param['user_id'] = $user_id;
        $coinBal= $user->coin;
     
//        if($param['coin_amt']==0 || $param['usd_amt']==0)
        if($param['coin_amt'] == 0){
            return \General::error_res('Please Enter Valid Amount');
        }
        $rules = [
            'coin_amt'  =>'required|numeric',
//            'usd_amt'   =>'required|numeric',
        ];

        $rulesMsg =[
            'coin_type.required' => 'Please Choose Wallet',
            
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
        
        
        $mcoinBal = $param['coin_amt'];
        $coinrate = \General::global_price(config('constant.COIN_SHORT_NAME'));
        
        $trx_fee    = app('settings')['token_transaction_fee'];
        
        $usdAmt = $mcoinBal * $coinrate;
        $mcoinBal +=  $trx_fee;
        if($mcoinBal > $coinBal){
            return \General::error_res('You have no sufficient Coin  For conversion');
        }
        
        
        $dataCoin = [
                'user_id'   => $user_id,
                'type'      => 4,
                'reference_id'=> null,
                'credit'    => 0,
                'debit'     => $mcoinBal,
                'comment'   => $mcoinBal.' Debited for Conversion request #',
        ];
        
        $trans  = \App\Models\CoinTransactions::add_transaction($dataCoin);
        $dataUsd = [
                'user_id'   => $user_id,
                'type'      => 4,
                'reference_id'=> null,
                'credit'    => $usdAmt,
                'debit'     => 0,
                'comment'   => $usdAmt.' Credited for Conversion request Trx-'.$trans,
        ];
        
       
        $trans  = \App\Models\BalanceTransactions::add_transaction($dataUsd);
        $user   = \App\Models\Users::where('id',$user_id)->first();
        $cbal   = $user->coin;
        $ubal   = $user->balance;
        $res    = \General::success_res('Conversion successfully');
        $res['data'] = ['usdc'=>$ubal,'coinc'=>$cbal];
        return \Response::json($res,200);
        
    }
    
    public function postBtcConversionFilter() {
       
       $param=\Input::all();
       
       $loggerId=\Auth::guard('user')->id(); 
       
       $param['loggerId']   = $loggerId;
       $param['type']       = 4;
       $data=\App\Models\BtcTransactions::get_token_report($param);
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
            return view("user.coin_conversion_filter",$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
        
    }
    public function postCoinConversionFilter() {
       
       $param=\Input::all();
       
       $loggerId=\Auth::guard('user')->id(); 
       
       $param['loggerId']=$loggerId;
       $param['type']=4;
       $data=\App\Models\CoinTransactions::get_token_report($param);
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
            return view("user.coin_conversion_filter",$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
        
    }
    
    public function postInvestUsdFilter() {
       
       $param=\Input::all();
       
       $loggerId=\Auth::guard('user')->id(); 
       
       $param['loggerId']=$loggerId;
       $param['type']=5;
       $data=  \App\Models\BalanceTransactions::get_balance_transaction_report($param);
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
            return view("user.invest_usd_filter",$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
        
    }
    
    
     public function postInvestPlanFilter() {
       
       $param=\Request::all();
       
       $data=  \App\Models\InvestPlan::get_plan_list($param);
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
            return view("user.plan_list_filter",$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
        
    }
    
    
    
       
    public function getStaking() {

        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }
        $user   = \Auth::guard('user')->user();
        
        $dt = date('Y-m-d H:i:s');
       // $icoList = \App\Models\CalenderIco::get_calender_ico_list_dash();
        $coinrate = \General::global_price(config('constant.COIN_SHORT_NAME'));

        $param['crnt']=1;
        $param['len']=10;
        $iplan =\App\Models\InvestPlan::get_plan_list($param);
        
        
       
        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'Staking',
                "js"    => ['https://bitpay.com/bitpay.js','assets/js/flipclock.min.js','assets/js/cryptobox.js'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'coinRate'  => $coinrate,
                'page_title'=> 'Staking',
                'menu_id'   => "staking-usd",
                'iplan'     => $iplan,
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.stacking_usd", $view_data);
    }
    
    
    
    public function postStackingFilter() {
       
       $param=\Input::all();
       
       $loggerId=\Auth::guard('user')->id(); 
       
       $param['loggerId']=$loggerId;
      
       $data= \App\Models\StackRecord::get_stack_transaction_report($param);
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
            return view("user.stacking_usd_filter",$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
        
    }

    public function postStackingPlanFilter() {
       
       $param=\Request::all();
       
       $data= \App\Models\StackPlan::get_plan_list($param);
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
            return view("user.stacking_plan_list_filter",$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
        
    }
    
    
    public function postInvestStack(){
        $param  = \Input::all();
        $user   = \Auth::guard('user')->user();
        $user_id= $user->id;
        $param['user_id'] = $user_id;
//        $coinBal= $user->balance;
        $coinBal= $user->coin;
     
//        dd($param);

        if($param['coin'] == 0){
            return \General::error_res('Please Enter Valid Amount');
        }
        $rules = [
            'coin'  =>'required|numeric',
//            'usd_amt'   =>'required|numeric',
        ];

        if($param['coin']>$coinBal){
            return \General::error_res('You have no sufficient Coin For Invest');
        }
        
        $validator = \Validator::make(\Input::all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $json = \General::validation_error_res();
            $json['data'] = $error;
            $json['msg'] = 'Fill necessary field';
            return \Response::json($json,200);
        }
        
        
        $mcoinBal = $param['coin'];
        $plan_num = \App\Models\StackRecord::where('user_id',$user_id)->first();
     
       // dd($plan_num->toArray());
        if(is_null($plan_num))
        {
              
            $data = \App\Models\StackPlan::orderBy('no_invest',1)->first();
            $date = new \DateTime(date('Y-m-d'));
            
            $interval='';
            switch($data['percentage_period']){
                case 1:
                    
                     $interval = new \DateInterval('P12M');
                    break;
                case 2:
                    
                     $interval = new \DateInterval('P9M');
                    break;
                case 3:
                    
                    $interval = new \DateInterval('P6M');
                    break;
                case 4:
                    
                    $interval = new \DateInterval('P3M');
                    break;
                default :   
                     $interval = new \DateInterval('P0M');
                    break;
            }
           
           
            $date->add($interval);
            
            $data['end_date']=$date->format('Y-m-d H:i:s');
           // dd($data->toArray());
        }else{
            $plan_num = \App\Models\StackRecord::orderBy('plan_id','desc')->where('user_id',$user_id)->first();
            
            $pn=$plan_num->plan_id+1;
             
            $data=\App\Models\StackPlan::where('no_invest',$pn)->first(); 
             if(is_null($data)){
                  return \General::error_res('Next Stacking Plan Not Found');
             }
           
            $date = new \DateTime(date('Y-m-d'));
            
            $interval='';
            switch($data['percentage_period']){
                case 1:
                    
                     $interval = new \DateInterval('P12M');
                    break;
                case 2:
                    
                     $interval = new \DateInterval('P9M');
                    break;
                case 3:
                    
                    $interval = new \DateInterval('P6M');
                    break;
                case 4:
                    
                    $interval = new \DateInterval('P3M');
                    break;
                default :   
                     $interval = new \DateInterval('P0M');
                    break;
            }
          
            $date->add($interval);
            
            $data['end_date']=$date->format('Y-m-d H:i:s');
//            dd($data['end_date']);
           // dd($data->toArray());
        }
       
        
//        $cnv_rate = \App\Models\CalenderIco::where('start_date','<=',date('Y-m-d H:i:s'))
//                                            ->where('end_date','>=',date('Y-m-d H:i:s'))
//                                            ->first();
        
        $dataCoin = [
                
                'plan_id'   => $data['no_invest'],
                'user_id'   => $user_id ,
                'status'    => 1,
                'percentage' => $data['percentage'],
                'percentage_period' => $data['percentage_period'],
                'amount'   => $mcoinBal,
                'end_date' => $data['end_date'],
        ];
        $trans  = \App\Models\StackRecord::add_transaction($dataCoin);
         $dataUsd = [
                'user_id'   => $user_id,
                'type'      => 9,
                'reference_id'=> null,
                'credit'    => 0,
                'debit'     => $mcoinBal,
                'comment'   => $mcoinBal.' debited for Stack Invest Trx-'.$trans,
        ];
        
        
//        $trans  = \App\Models\BalanceTransactions::add_transaction($dataUsd);
        $trans  = \App\Models\CoinTransactions::add_transaction($dataUsd);
 
        $res    = \General::success_res('Transaction successful');
      
        return \Response::json($res,200);
        
    }

    public function postEnable2sf(){
        if (!\Auth::guard('user')->check()) {
            return redirect('');
        }
        $user   = \Auth::guard('user')->user();
        
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
        if (!\Auth::guard('user')->check()) {
            return redirect('');
        }
        $user   = \Auth::guard('user')->user();
        $user->google2fa_secret = null;
        $user->save();
        
        return \General::success_res('authenticator disabled successfully.');
    }
    
    public function getTransfer(){
        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }
        
        $dt = date('Y-m-d H:i:s');
       // $icoList = \App\Models\CalenderIco::get_calender_ico_list_dash();
        

        $param['crnt']=1;
        $param['len']=10;
        $iplan = \App\Models\CoinTransfer::get_transfer_list($param);
        
        
       
        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'Transfer Coin',
                "js"    => ['https://bitpay.com/bitpay.js','assets/js/flipclock.min.js','assets/js/cryptobox.js'],
                "css"   => [],
            ],
            'body' => [
                'user'      => $user,
                'page_title'=> 'Coin Transfer',
                'menu_id'   => "transfer",
                'iplan'     => $iplan,
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.transfer_coin", $view_data);
    }
    public function postTransferCoinFilter() {
       
       $param=\Input::all();
       
       $loggerId=\Auth::guard('user')->id(); 
       
       $param['loggerId']=$loggerId;
       $param['type']=5;
       $data=  \App\Models\CoinTransfer::get_transfer_list($param);
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
            return view("user.transfer_coin_filter",$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
        
    }
    
    public function postTransferCoin(){
        $param = \Input::all();
//        dd($param);
        $rules =  [
                'coin_address'=>'required',
                'coin_amount'=>'required',
            ];
        $customMsg = [
            'coin_address.required'=>'coin address is required.',
            'amount.required'=>'transfer amount is required.',
        ];
        
        $validator = \Validator::make($param, $rules,$customMsg);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $json = \General::validation_error_res();
            $json['data'] = $error;
            $json['msg'] = $error[0];
            return \Response::json($json,200);
        }
        
        $logger     = \Auth::guard('user')->user();
        $loggerId   = $logger->id; 
        
        $trx_fee    = app('settings')['token_transaction_fee'];
        
        $chekBalData = [
            'user_id'     => $loggerId,
            'wallet_type' => 'c',
            'transaction_type' => 'd',
            'amount'      => $param['coin_amount'],
        ];
        
        $userCoinBal = \App\Models\Users::manage_coin_balance($chekBalData);
        
        
        if($userCoinBal['flag'] != 1 || $logger->coin < ($param['coin_amount'] + $trx_fee) ){
            $msg = 'you have not sufficient coin balance to transfer';
            $rd = \General::validation_error_res($msg);
            $rd['data'] = [$msg];
            return $rd;
        }
        
        $chekAddress = \App\Models\CoinAddress::check_address($param);
//        if($chekAddress['flag'] != 1){
//            $rd = \General::validation_error_res($chekAddress['msg']);
//            $rd['data'] = [$chekAddress['msg']];
//            return $rd;
//        }
        
        if(strlen($param['coin_address']) != 34){
            $rd = \General::validation_error_res('This Address is Invalid. Please enter Valid Coin Address');
            $rd['data'] = [$chekAddress['msg']];
            return $rd;
        }
        if($chekAddress['flag'] == 1){
            $user = $chekAddress['data'][0]['user_id'];
            if($user == $logger->id){
                $msg = 'you can not transfer coin to your address';
                $rd = \General::validation_error_res($msg);
                $rd['data'] = [$msg];
                return $rd;
            }
        }
        
        $account_name = $logger->coin_account_name;
//        dd($logger->toArray());
        
        $trx_id = \App\Models\General::generate_transaction_id($account_name,$param['coin_address'],$param['coin_amount']);
        if($trx_id['flag'] != 1 ){
            return $trx_id;
        }
        
        $param['to_address']    = $param['coin_address'];
        $param['to_user_id']    = $chekAddress['flag'] == 1 ? $chekAddress['data'][0]['user_id'] : null;
        $param['amount']        = $param['coin_amount'] + $trx_fee;
        $param['from_address']  = $logger->coin_address;
        $param['from_user_id']  = $logger->id;
        $param['transaction_id']= $trx_id['data'];
        $transferCoin = \App\Models\CoinTransfer::new_coin_transfer($param);
        
//        $res = \General::success_res();
//        $res['data']=$param;
        return $transferCoin;
    }
    
    public function getLiveExchange(){
        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }
        
        $dt = date('Y-m-d H:i:s');
       // $icoList = \App\Models\CalenderIco::get_calender_ico_list_dash();
        
        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'Exchange',
                "js"    => ['https://bitpay.com/bitpay.js','assets/js/flipclock.min.js','assets/js/cryptobox.js','assets/js/highstock.js','assets/js/custom_exchange.min.js'],
                "css"   => ['assets/css/exchanges.css'],
            ],
            'body' => [
                'user'      => $user,
                'page_title'=> 'Live Exchange',
                'menu_id'   => "sub_exchange",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.live_exchange", $view_data);
    }
    public function getOrders(){
        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }
        
        $dt = date('Y-m-d H:i:s');
       // $icoList = \App\Models\CalenderIco::get_calender_ico_list_dash();
        

        $param['crnt']=1;
        $param['len']=10;
        $iplan = \App\Models\CoinTransfer::get_transfer_list($param);
        
        
       
        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'My orders',
                "js"    => ['https://bitpay.com/bitpay.js','assets/js/flipclock.min.js','assets/js/cryptobox.js'],
                "css"   => ['assets/css/exchanges.css'],
            ],
            'body' => [
                'user'      => $user,
                'page_title'=> 'My Orders',
                'menu_id'   => "orders",
                'iplan'     => $iplan,
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.user_orders", $view_data);
    }
    public function getTrades(){
        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }
        
        $user = \Auth::guard('user')->user()->toArray();
        $view_data = [
            'header' => [
                "title" => 'My Trade History',
                "js"    => [],
                "css"   => ['assets/css/exchanges.css'],
            ],
            'body' => [
                'user'      => $user,
                'page_title'=> 'My Trade History',
                'menu_id'   => "trade_history",
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.user_trades", $view_data);
    }
    
    public function postBuyOrder(){
        $param = \Input::all();
        $res = \General::success_res();
        
        $data = \App\Models\BuyOrders::get_buy_orders($param);
        
        if($data['flag'] != 1){
            return \General::error_res('no data found');
        }
        
        $allData = [];
        
        foreach($data['data'] as $v){
            $allData[] = [
                'Price'=>$v['price'],
                'Amount' => $v['amount'] - $v['amount_bought'],
                'user_id'=>$v['user_id'],
            ];
        }
        
        $res['data'] = $allData;
        return $res;
    }
    
    public function postSellOrder(){
        $param = \Input::all();
        $res = \General::success_res();
        $data = \App\Models\SellOrders::get_sell_orders($param);
        
        if($data['flag'] != 1){
            return \General::error_res('no data found');
        }
        
        $allData = [];
        
        foreach($data['data'] as $v){
            $allData[] = [
                'Price'=>$v['price'],
                'Amount' => $v['amount'] - $v['amount_sold'],
                'user_id'=>$v['user_id'],
            ];
        }
        
        $res['data'] = $allData;
        return $res;
    }
    public function postTradeHistory(){
        $param = \Input::all();
        $res = \General::success_res();
        $data = \App\Models\TradeHistory::get_all_trades($param);
        
        if($data['flag'] != 1){
            return \General::error_res('no data found');
        }
        
        $allData = [];
        
        foreach($data['data'] as $v){
            $allData[] = [
                'Price'=>$v['price'],
                'Amount' => $v['amount'],
                'CreatedDate' => $v['created_at'],
                'trade_type' => $v['trade_type'],
            ];
        }
        
        $res['data'] = $allData;
        return $res;
    }
    
    public function postOrderItem(){
        if (!\Auth::guard('user')->check()) {
            return \General::error_res('your session is expired.');
        }
        $param = \Input::all();
        $rules = [
            'Price'=>'required|numeric',
            'Amount'=>'required|numeric',
            'TotalPaid'=>'required|numeric',
            'OrderType'=>'required|numeric',
        ];
        
        $validator = \Validator::make($param, $rules);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $json = \General::validation_error_res();
            $json['data'] = $error;
            $json['msg'] = $error[0];
            return \Response::json($json,200);
        }
        
        if($param['Price'] <= 0 || $param['Amount'] <= 0 || $param['TotalPaid'] <=0){
            return \General::error_res('you have entered wrong detail.');
        }
        $settings = app('settings');
        $user = \Auth::guard('user')->user()->toArray();
        $param['user_id'] = $user['id'];
        $total = $param['Price'] * $param['Amount'];
        if( round($total,8) < round($param['TotalPaid'],8)){
            return \General::error_res('you have entered wrong total price.');
        }
        
        if($param['OrderType'] == 0){
            $balance = $user['balance'];
            $fee = $settings['buy_exchange_fee'];
            $total = $total + ($total * $fee / 100);
            if($total > $balance){
                return \General::error_res('you have not sufficient USD balance.');
            }
            $buy = \App\Models\BuyOrders::add_new_order($param);
            return $buy;
        }elseif($param['OrderType'] == 1){
            $coin = $user['coin'];
            $fee = $settings['sell_exchange_fee'];
            $total = $param['Amount'] + ($param['Amount'] * $fee / 100);
            if($total > $coin){
                return \General::error_res('you have not sufficient '.config('constant.COIN_SHORT_NAME').' balance.');
            }
            
            $sell = \App\Models\SellOrders::add_new_order($param);
            return $sell;
        }
        
        return \General::error_res('somthing migh wrong.please try again.');
//        dd($param);
    }
    
    public function postWalletInfo(){
        if (!\Auth::guard('user')->check()) {
            return \General::error_res('your session is expired.');
        }
        $user = \Auth::guard('user')->user()->toArray();
        $res = \General::success_res();
        $data = [
            'USD'=>$user['balance'],
            'MZB'=>$user['coin']
        ];
        $res['data'] = $data;
        return $res;
    }
    
    public function postUserExchangeHistory(){
        if (!\Auth::guard('user')->check()) {
            return \General::error_res('your session is expired.');
        }
        $user = \Auth::guard('user')->user()->toArray();
        $param = \Input::all();
        $param['user_id'] = $user['id'];
        $buy_data = \App\Models\BuyOrders::get_buy_orders($param)['data'];
        
        foreach($buy_data as $k=>$b){
            $buy_data[$k]['amount'] = $buy_data[$k]['amount'] - $buy_data[$k]['amount_bought'];
            $buy_data[$k]['type'] = 0;
            $buy_data[$k]['amount_sold'] = 0;
        }
        
        $sell_data = \App\Models\SellOrders::get_sell_orders($param)['data'];
        
        foreach($sell_data as $k=>$s){
            $sell_data[$k]['amount'] = $sell_data[$k]['amount'] - $sell_data[$k]['amount_sold'];
            $sell_data[$k]['type'] = 1;
            $sell_data[$k]['amount_bought'] = 0;
            array_push($buy_data, $sell_data[$k]);
        }
        
        uasort($buy_data, function($a, $b) {
            if ($a['created_at']==$b['created_at']) return 0;
            return ($a['created_at'] > $b['created_at'])?-1:1;
//            return $a['created_at'] < $b['created_at'];
        });
        
//        dd($buy_data);
        
        $trade_data = \App\Models\TradeHistory::get_all_trades($param)['data'];
        $retdata = [
            'trades'=>$trade_data,
            'orders'=>$buy_data
        ];
        $res = \General::success_res();
        $res['data'] = $retdata;
        return $res;
    }
    public function postCancelOrder(){
        if (!\Auth::guard('user')->check()) {
            return \General::error_res('your session is expired.');
        }
        $user = \Auth::guard('user')->user()->toArray();
        $param = \Input::all();
        $param['user_id'] = $user['id'];
        $rules = [
            'type'=>'required|numeric',
            'id'=>'required|numeric',
        ];
        
        $validator = \Validator::make($param, $rules);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $error = $messages->all();
            $json = \General::validation_error_res();
            $json['data'] = $error;
            $json['msg'] = $error[0];
            return \Response::json($json,200);
        }
        if($param['type'] != 0 && $param['type'] != 1){
            return \General::error_res('order type is invalid');
        }
        $res = \General::error_res('something wrong.please try again later.');
        if($param['type'] == 0){
            $res = \App\Models\BuyOrders::cancel_order($param);
        }elseif($param['type'] == 1){
            $res = \App\Models\SellOrders::cancel_order($param);
        }
        
        return $res;
    }
    
    public function getAuthGoogle() {
        return \Socialite::driver('google')->redirect();
    }

    public function redirectGoogle() {
        $user = \Socialite::driver('google')->user();
        if (is_null($user) || $user->email == "" || $user->email == null) {
            $res = \General::error_res("you didn't give proper permission. Please try again");
            \Session::set("msg",  json_encode($res));
            return \Redirect::to("/");
        }
        $access_token = $user->token;
        $code = \Input::get("code");
        $user_email = $user->email;
        $user_name = $user->name;
        $param = [
            "type" => 'google',
            'access_token' => $access_token,
            "device_token" => $code,
            "email" => $user_email,
            "name" => $user_name
        ];
        
        Users::do_login($param);
        return \Redirect::to("/user/dashboard");
    }

    
    public function getAuthFacebook() {
        
        return \Socialite::driver('facebook')->scopes(['email'])->redirect();
       
    }

    public function redirectFacebook() {
        $user = \Socialite::driver('facebook')->user();
        if(isset($user->id)){
            $user->email=$user->id.'@facebook.com';
        }else{
            return \Redirect::to("/");
        }
        if(is_null($user) || $user->email == "" || $user->email == null) {
            $res =\General::error_res("you didn't give proper permission. Please try again");
//            \Session::set("msg",  json_encode($res));
//            \Socialite::driver('facebook')->deauthorize($user->token);
            return \Redirect::to("/");
        }
        $access_token = $user->token;
        $code = \Input::get("code");
        $user_email = $user->email;
        $user_name = $user->name;
        $param = [
            "type" => 'facebook',
            'access_token' => $access_token,
            "device_token" => $code,
            "email" => $user_email,
            "name" => $user_name
        ];
       
        Users::do_login($param);
        return \Redirect::to("/user/dashboard");
    }
    public function postVolumeHistory(){
         if (!\Auth::guard('user')->check()) {
            return \General::error_res('your session is expired.');
        }
        $user = \Auth::guard('user')->user()->toArray();
        $date = date('Y-m-d H:i',strtotime('-24 hours'));
        $param['date'] = $date;
        $history = \App\Models\TradeHistory::get_volume_history($param);
        $data = $history['data'];
        $price = [];
        $amount = [];
        $paid = [];
        $lprice = $high = $low = $totalPaid = $volume = $l24hprice = 0 ;
        if(count($data)){
            $lprice = $data[0]['price'];
            foreach($data as $d){
                $price[] = $d['price'];
                $amount[] = $d['amount'];
                $paid[] = $d['price'] * $d['amount'];
            }
            $high = max($price);
            $low = min($price);
            $volume = array_sum($amount);
            $totalPaid = array_sum($paid);
            $l24hprice = end($price);
        }
        $rdata = [
            'last_price'=>$lprice,
            'high'=>$high,
            'low'=>$low,
            'volume'=>$volume,
            'total_paid'=>$totalPaid,
            'last_24h_price'=>$l24hprice,
        ];
        $res = \General::success_res();
        $res['data'] =$rdata;
        return $res;
    }
    public function postUserOrdersFilter() {
       $param=\Input::all();
       
       $loggerId=\Auth::guard('user')->id(); 
       
       $param['user_id']=$loggerId;
       $blade = 'user_orders_buy_filter';
       if($param['type'] == 0){
           $data = \App\Models\BuyOrders::get_buy_order_report($param);
           $blade = 'user_orders_buy_filter';
       }else{
           $data = \App\Models\SellOrders::get_sell_order_report($param);
           $blade = 'user_orders_sell_filter';
       }
       
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
            return view("user.".$blade,$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
        
    }
    public function postUserTradesFilter() {
       $param=\Input::all();
       
       $loggerId=\Auth::guard('user')->id(); 
       
       $param['user_id']=$loggerId;
       
       $data = \App\Models\TradeHistory::get_trade_report($param);
       
       if ($data['flag'] == 1) {
            $res = \General::success_res();
            unset($data['flag']);
            $res['data'] = $data;
            return view("user.user_trades_filter",$res);
        }

        $res = \General::error_res('No Data Found.');
        unset($data['flag']);
        return \Response::json($res, 200);
        
    }
    public function getDepositeWithdrawal(){
        if (!\Auth::guard('user')->check()) {
            return redirect('login');
        }
        
        $user = \Auth::guard('user')->user()->toArray();
        $coinrate = \General::global_price(config('constant.COIN_SHORT_NAME'));
        $size = 200;
        $string = $user['coin_address'];
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
                "title" => 'Deposits & Withdrawals',
                "js"    => ['assets/js/paygate.min.js'],
                "css"   => ['assets/css/exchanges.css'],
            ],
            'body' => [
                'user'      => $user,
                'coinRate' => $coinrate,
                'page_title'=> 'Deposits & Withdrawals',
                'menu_id'   => "dep_withd",
                'qr_coin'   => $img,
            ],
            'footer' => [
                "js"    => [],
                "css"   => []
            ],
        ];

        return view("user.deposites_withdrawals", $view_data);
    }
}
        
