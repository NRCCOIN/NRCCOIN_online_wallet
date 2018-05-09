<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Request;
use App\Models\Users;
use App\Models\Serviceprovider;
use Illuminate\Support\Facades\Mail;
use DB;
use File;

use PragmaRX\Google2FA\Google2FA as Google2FA;
use \ParagonIE\ConstantTime\Base32;
use Illuminate\Support\Facades\Auth;

class TradeController extends Controller {

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

    public function tradedashboard() {

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

        
     //   $my_user_id=$user['id'];
        
      //  $transactions = withdrow_request::where('user_id',$user['id'])->first()->toArray();
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
                'icoList'   => $icoList,
                'PreviousTransactions'   => $PreviousTransactions,
                'pending'   => $pending,
                'iplan'     => $iplan,
                'icoStart'  => $icoStart->start_date,
                'nextIco'   => $nextIco,
                'icoOpen'   => $icoOpen,
                'page_title'=> 'Dashboard',
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
return \View::make('user.tradedashboard', $view_data); 

        
    }
    

}
        
