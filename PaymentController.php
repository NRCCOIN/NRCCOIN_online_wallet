<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Request;
use App\Models\Admin\User;
use App\mpdf\MPDF57\mpdf;
class PaymentController extends Controller {

    public function __construct() {
        
    }
    
   
    public function getBitPayGenKey(){
        $res = \App\Lib\BitPayLib::generate_keys();
        return \Response::json($res,200);
    }
    
    public function getBitPayPair($paircode = ''){
        if($paircode != ''){
            return \App\Lib\BitPayLib::pair($paircode);
        }
        else{
            $res = \General::error_res('Pair Code is blank.');
            return \Response::json($res,200);
        }
    }
    
    public function postTransferUsd(){
        $param = \Input::all();
        
        $user = \App\Models\Users::where('id',$param['u_id'])->first();
        if(is_null($user)){
            $res = \General::error_res('User Not found.');
            return $res;
        }
        $user = $user->toArray();
        
        if($user['earning_balance'] < $param['dollar']){
            $res = \General::error_res('No Sufficient Balance.');
            return $res;
        }
        
        $data = [
                'user_id'=>$user['id'],
                'type'=>2,
                'reference_id'=>null,
                'credit'=>0,
                'debit'=>$param['dollar'],
                'comment'=>$param['dollar'].' debited for withdrawal request #',
            ];
        
        $trans = \App\Models\EarningHistory::add_transaction($data);
        
        
        $usd = new \App\Models\BalanceTransactions();
        $usd->user_id       = $user['id'];
        $usd->type          = 10;
        $usd->reference_id  = $trans['id'];
        $usd->credit        = $param['dollar'];
        $usd->debit         = 0;
        $usd->comment       = $param['dollar'].' USD Moved From Earning Balance.Trx-'.$trans;
        $usd->save();
        
//        $user = \App\Models\Users::where('id',$param['u_id'])->first();
//        $user->earning_balance = $user->earning_balance - $param['dollar'];
//        $user->save();
        
//        dd($param);
        
        
       
        
        $res = \General::success_res('Balance Transfered Successfully');
        return \Response::json($res,200);
    }
    public function postInvestUsd(){
        $param = \Input::all();
        
        $user = \App\Models\Users::where('id',$param['u_id'])->first();
        if(is_null($user)){
            $res = \General::error_res('User Not found.');
            return $res;
        }
        $user = $user->toArray();
        
        if($user['balance'] < $param['dollar']){
            $res = \General::error_res('No Sufficient Balance.');
            return $res;
        }
        
        
        
        $invest_plan = \App\Models\InvestPlan::active()
                                                ->OrderBy('created_at','DESC')
                                                ->where('from','<=',$param['dollar'])
                                                ->where('to','>=',$param['dollar'])
                                                ->first();
       // dd($invest_plan->toArray());
        
        if(is_null($invest_plan)){
            $res = \General::error_res('Currently No Plans Available for Investment. Please, Try After Some time.');
            return $res;

        }
        
        $invest_plan->toArray();
        
        $now        = date('Y-m-d H:i:s');
        $end_date   = date('Y-m-d H:i:s', strtotime(' +'.$invest_plan['days'].' days'));
        
        $investment = new \App\Models\InvestRecord;
        $investment->plan_id    = $invest_plan['id'];
        $investment->user_id    = $user['id'];
        $investment->status     = 1;
        $investment->amount     = $param['dollar'];
        $investment->end_date   = $end_date;
        $investment->save();
        
        
        $usd = new \App\Models\BalanceTransactions();
        $usd->user_id       = $user['id'];
        $usd->type          = 5;
        $usd->reference_id  = $investment['id'];
        $usd->credit        = 0;
        $usd->debit         = $param['dollar'];
        $usd->comment       = $param['dollar'].' USD Investment.';
        $usd->save();
        
        $user = \App\Models\Users::where('id',$param['u_id'])->first();
        $user->invested_balance = $user->invested_balance + $param['dollar'];
        $user->save();
        
//        dd($param,$now,$end_date,$invest_plan->toArray(),$investment->toArray(),$usd->toArray());
        
        $res = \General::success_res('Investment Done Successfully');
        return \Response::json($res,200);

    }
    
    public function postBitPayGenInvoice(){
        $param = \Input::all();
//        dd($param);
        $user = \App\Models\Users::where('id',$param['u_id'])->first();
        if(is_null($user)){
            $res = \General::error_res('User Not found.');
            return $res;
        }
        $user = $user->toArray();
//        dd($user);
        
        $orderId = config('constant.COIN_SHORT_NAME').'_'.\General::rand_str(5);
        
        $data = [];
        $data['email_id'] = $user['email'];
        $data['order_id'] = $orderId;
        $data['coin_code'] = config('constant.COIN_SHORT_NAME');
        $data['price'] = (float)$param['dollar'];
        $data['description'] = 'Trx-'.$orderId.' of price '.$param['dollar'].' dollar.';
        
        $res = \App\Lib\BitPayLib::generate_invoice($data);

//        dd($res);
        
        $setting = app('settings');
        
        $setting['conversion_rate']=\General::global_price(config('constant.COIN_SHORT_NAME'));
        if($res['flag'] == 1){
            $trx = new \App\Models\CoinInvoice();
            $trx->status        = 2;
            $trx->order_id      = $orderId;
            $trx->invoice_id    = $res['id'];
            $trx->user_id       = $user['id'];
            $trx->btc   = $res['price'];
            $trx->usd   = $param['dollar'];
            $trx->coin   = $param['dollar'] / $setting['conversion_rate'];
            $trx->ua    = \Request::server("HTTP_USER_AGENT");
            $trx->ip    = \Request::getClientIp();
            $trx->save();
            
        }
        
        return \Response::json($res,200);
    }
    
    public function postBitPayGetInvoice(){
        $param = \Input::all();
        
        $trx = \App\Models\CoinInvoice::where('invoice_id',$param['t_id'])->first();
        if(!is_null($trx)){
            if($trx->status == 0){
                $res = \General::error_res('Payment Failed. Try After Sometime.');
                return $res;
            }
            else if($trx->status == 1){
                $res = \General::success_res('Payment Already Paid.');
                return $res;
            }
        }
        
        
        
        $param['price'] = $trx->usd;
        
        $res = \App\Lib\BitPayLib::get_invoice($param);
        
        $setting = app('settings');
        $setting['conversion_rate']=\General::global_price(config('constant.COIN_SHORT_NAME'));
        
        if($res['flag'] == 1){
            $trx = \App\Models\CoinInvoice::where('invoice_id',$param['t_id'])->first();

            if(!is_null($trx)){
                $trx = $trx->toArray();
                $trx1 = \App\Models\CoinInvoice::where('id',$trx['id'])->update(['status'=>1]);
                
                $coin = $trx['usd'];
                
                if($setting['refer_reward_type'] == 'COIN'){
                    $coin = $trx['usd'] / $setting['conversion_rate'];
                }
                
                
                $coin = new \App\Models\CoinTransactions();
                $coin->user_id  = $trx['user_id'];
                $coin->type     = 1;
                $coin->reference_id = $trx['id'];
                $coin->credit   = $coin;
                $coin->debit    = 0;
                $coin->comment  = $coin.' '.config('constant.COIN_SHORT_NAME').' purchased.';
                $coin->save();
                
                $child = \App\Models\Users::where('id',$trx['user_id'])->first()->toArray();
                
                
                if($setting['refer_reward_type'] == 'COIN'){            // Referral Reward in Coin
                        // Refere Reward to Level 1 Parent
                        $p_user_1 = \App\Models\Users::where('id',$child['parent_id'])->first();
                        if(!is_null($p_user_1)){

                            $reward_coin = $coin * $setting['refer_level_1'] / 100;

                            $coin1 = new \App\Models\CoinTransactions();
                            $coin1->user_id     = $child['parent_id'];
                            $coin1->type        = 0;
                            $coin1->reference_id= $trx['id'];
                            $coin1->credit      = $reward_coin;
                            $coin1->debit       = 0;
            //                    $coin1->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward.';
                            $coin1->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward for Trx-'.$trx['id'];
                            $coin1->save();

                            // Refere Reward to Level 2 Parent
                            $p_user_2 = \App\Models\Users::where('id',$p_user_1['parent_id'])->first();
                            if(!is_null($p_user_2)){

                                $reward_coin = $coin * $setting['refer_level_2'] / 100;

                                $coin2 = new \App\Models\CoinTransactions();
                                $coin2->user_id     = $p_user_1['parent_id'];
                                $coin2->type        = 0;
                                $coin2->reference_id= $trx['id'];
                                $coin2->credit      = $reward_coin;
                                $coin2->debit       = 0;
            //                        $coin2->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward.';
                                $coin2->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward for Trx-'.$trx['id'];
                                $coin2->save();

                                // Refere Reward to Level 3 Parent
                                $p_user_3 = \App\Models\Users::where('id',$p_user_2['parent_id'])->first();
                                if(!is_null($p_user_3)){

                                    $reward_coin = $coin * $setting['refer_level_3'] / 100;

                                    $coin3 = new \App\Models\CoinTransactions();
                                    $coin3->user_id     = $p_user_2['parent_id'];
                                    $coin3->type        = 0;
                                    $coin3->reference_id= $trx['id'];
                                    $coin3->credit      = $reward_coin;
                                    $coin3->debit       = 0;
            //                            $coin3->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward.';
                                    $coin3->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward for Trx-'.$trx['id'];
                                    $coin3->save();
                                }
                            }
                        }
                    }
                    else{                               // Referral Reward in USD
                        // Refere Reward to Level 1 Parent
                        $p_user_1 = \App\Models\Users::where('id',$child['parent_id'])->first();
                        if(!is_null($p_user_1)){

                            $reward_coin = $coin * $setting['refer_level_1'] / 100;

                            $coin1 = new \App\Models\BalanceTransactions();
                            $coin1->user_id     = $child['parent_id'];
                            $coin1->type        = 0;
                            $coin1->reference_id= $trx['id'];
                            $coin1->credit      = $reward_coin;
                            $coin1->debit       = 0;
            //                    $coin1->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward.';
                            $coin1->comment     = $reward_coin.' USD Reward for Trx-'.$trx['id'];
                            $coin1->save();

                            // Refere Reward to Level 2 Parent
                            $p_user_2 = \App\Models\Users::where('id',$p_user_1['parent_id'])->first();
                            if(!is_null($p_user_2)){

                                $reward_coin = $coin * $setting['refer_level_2'] / 100;

                                $coin2 = new \App\Models\BalanceTransactions();
                                $coin2->user_id     = $p_user_1['parent_id'];
                                $coin2->type        = 0;
                                $coin2->reference_id= $trx['id'];
                                $coin2->credit      = $reward_coin;
                                $coin2->debit       = 0;
            //                        $coin2->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward.';
                                $coin2->comment     = $reward_coin.' USD Reward for Trx-'.$trx['id'];
                                $coin2->save();

                                // Refere Reward to Level 3 Parent
                                $p_user_3 = \App\Models\Users::where('id',$p_user_2['parent_id'])->first();
                                if(!is_null($p_user_3)){

                                    $reward_coin = $coin * $setting['refer_level_3'] / 100;

                                    $coin3 = new \App\Models\BalanceTransactions();
                                    $coin3->user_id     = $p_user_2['parent_id'];
                                    $coin3->type        = 0;
                                    $coin3->reference_id= $trx['id'];
                                    $coin3->credit      = $reward_coin;
                                    $coin3->debit       = 0;
            //                            $coin3->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward.';
                                    $coin3->comment     = $reward_coin.' USD Reward for Trx-'.$trx['id'];
                                    $coin3->save();
                                }
                            }
                        }
                    }

            }
        }
        return $res;
    }
    
    public function BitPayConfirmation(){

        $raw_post_data = file_get_contents('php://input');

        if (false === $raw_post_data) {
            \Log::info('BitPay Notification : Error. Could not read from the php://input stream or invalid Bitpay IPN received.\n');
            throw new \Exception('Could not read from the php://input stream or invalid Bitpay IPN received.');
        }
        
//        dd($raw_post_data);
        $ipn = json_decode($raw_post_data);
        
        $trx = \App\Models\CoinInvoice::where('invoice_id',$ipn->id)->first();
        if(!is_null($trx)){
            if($trx->status == 0){
                $res = \General::error_res('Payment Failed.');
                \Log::info('BitPay Notification : Payment Failed.');
                exit(0);
//                return $res;
            }
            else if($trx->status == 1){
                $res = \General::success_res('Payment Already Paid.');
                \Log::info('BitPay Notification : Payment Failed.');
                exit(0);
//                return $res;
            }
        }
        
        $res = \App\Lib\BitPayLib::callback_notification($ipn);

        $setting = app('settings');
        
        $setting['conversion_rate']=\General::global_price(config('constant.COIN_SHORT_NAME'));
        
        if($res['flag'] == 1){
            $trx = \App\Models\CoinInvoice::where('invoice_id',$ipn->id)->first();

            if(!is_null($trx)){
                $trx = $trx->toArray();
                $trx1 = \App\Models\CoinInvoice::where('id',$trx['id'])->update(['status'=>1]);
                
                $coin = $trx['usd'];
                
                if($setting['refer_reward_type'] == 'COIN'){
                    $coin = $trx['usd'] / $setting['conversion_rate'];
                }
                
                $coin = new \App\Models\CoinTransactions();
                $coin->user_id = $trx['user_id'];
                $coin->type = 1;
                $coin->reference_id = $trx['id'];
                $coin->credit = $coin;
                $coin->debit = 0;
                $coin->comment = $coin.' '.config('constant.COIN_SHORT_NAME').' purchased.';
                $coin->save();
                
//                $user = \App\Models\Users::where('id',$trx['user_id'])->first()->toArray();
//                
//                $p_user = \App\Models\Users::where('id',$user['parent_id'])->first();
//                if(!is_null($p_user)){
//                    
//                    $reward_coin = $coin * $setting['ico_refer'] / 100;
//                    
//                    $coin = new \App\Models\CoinTransactions();
//                    $coin->user_id = $user['parent_id'];
//                    $coin->type = 0;
//                    $coin->reference_id = $trx['id'];
//                    $coin->credit = $reward_coin;
//                    $coin->debit = 0;
//                    $coin->comment = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward.';
//                    $coin->save();
//                }
                
                $child = \App\Models\Users::where('id',$trx['user_id'])->first()->toArray();
                
                
                if($setting['refer_reward_type'] == 'COIN'){            // Referral Reward in Coin
                        // Refere Reward to Level 1 Parent
                        $p_user_1 = \App\Models\Users::where('id',$child['parent_id'])->first();
                        if(!is_null($p_user_1)){

                            $reward_coin = $coin * $setting['refer_level_1'] / 100;

                            $coin1 = new \App\Models\CoinTransactions();
                            $coin1->user_id     = $child['parent_id'];
                            $coin1->type        = 0;
                            $coin1->reference_id= $trx['id'];
                            $coin1->credit      = $reward_coin;
                            $coin1->debit       = 0;
            //                    $coin1->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward.';
                            $coin1->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward for Trx-'.$trx['id'];
                            $coin1->save();

                            // Refere Reward to Level 2 Parent
                            $p_user_2 = \App\Models\Users::where('id',$p_user_1['parent_id'])->first();
                            if(!is_null($p_user_2)){

                                $reward_coin = $coin * $setting['refer_level_2'] / 100;

                                $coin2 = new \App\Models\CoinTransactions();
                                $coin2->user_id     = $p_user_1['parent_id'];
                                $coin2->type        = 0;
                                $coin2->reference_id= $trx['id'];
                                $coin2->credit      = $reward_coin;
                                $coin2->debit       = 0;
            //                        $coin2->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward.';
                                $coin2->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward for Trx-'.$trx['id'];
                                $coin2->save();

                                // Refere Reward to Level 3 Parent
                                $p_user_3 = \App\Models\Users::where('id',$p_user_2['parent_id'])->first();
                                if(!is_null($p_user_3)){

                                    $reward_coin = $coin * $setting['refer_level_3'] / 100;

                                    $coin3 = new \App\Models\CoinTransactions();
                                    $coin3->user_id     = $p_user_2['parent_id'];
                                    $coin3->type        = 0;
                                    $coin3->reference_id= $trx['id'];
                                    $coin3->credit      = $reward_coin;
                                    $coin3->debit       = 0;
            //                            $coin3->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward.';
                                    $coin3->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward for Trx-'.$trx['id'];
                                    $coin3->save();
                                }
                            }
                        }
                    }
                    else{                               // Referral Reward in USD
                        // Refere Reward to Level 1 Parent
                        $p_user_1 = \App\Models\Users::where('id',$child['parent_id'])->first();
                        if(!is_null($p_user_1)){

                            $reward_coin = $coin * $setting['refer_level_1'] / 100;

                            $coin1 = new \App\Models\BalanceTransactions();
                            $coin1->user_id     = $child['parent_id'];
                            $coin1->type        = 0;
                            $coin1->reference_id= $trx['id'];
                            $coin1->credit      = $reward_coin;
                            $coin1->debit       = 0;
            //                    $coin1->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward.';
                            $coin1->comment     = $reward_coin.' USD Reward for Trx-'.$trx['id'];
                            $coin1->save();

                            // Refere Reward to Level 2 Parent
                            $p_user_2 = \App\Models\Users::where('id',$p_user_1['parent_id'])->first();
                            if(!is_null($p_user_2)){

                                $reward_coin = $coin * $setting['refer_level_2'] / 100;

                                $coin2 = new \App\Models\BalanceTransactions();
                                $coin2->user_id     = $p_user_1['parent_id'];
                                $coin2->type        = 0;
                                $coin2->reference_id= $trx['id'];
                                $coin2->credit      = $reward_coin;
                                $coin2->debit       = 0;
            //                        $coin2->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward.';
                                $coin2->comment     = $reward_coin.' USD Reward for Trx-'.$trx['id'];
                                $coin2->save();

                                // Refere Reward to Level 3 Parent
                                $p_user_3 = \App\Models\Users::where('id',$p_user_2['parent_id'])->first();
                                if(!is_null($p_user_3)){

                                    $reward_coin = $coin * $setting['refer_level_3'] / 100;

                                    $coin3 = new \App\Models\BalanceTransactions();
                                    $coin3->user_id     = $p_user_2['parent_id'];
                                    $coin3->type        = 0;
                                    $coin3->reference_id= $trx['id'];
                                    $coin3->credit      = $reward_coin;
                                    $coin3->debit       = 0;
            //                            $coin3->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward.';
                                    $coin3->comment     = $reward_coin.' USD Reward for Trx-'.$trx['id'];
                                    $coin3->save();
                                }
                            }
                        }
                    }
            }
        }
        
        
        
        
        //Respond with HTTP 200, so BitPay knows the IPN has been received correctly
        //If BitPay receives <> HTTP 200, then BitPay will try to send the IPN again with increasing intervals for two more hours.
        header("HTTP/1.1 200 OK");
    }
    
    public function postPayGateGenInvoice($userd=null){
        $param = \Input::all();
      //  dd($param);
       // $user   = \Auth::guard('user')->user();
        if(isset($userd['api_call'])){
            $param['u_id']=$userd['id'];
        }
        
        \Log::info('PayGate Generate Invoice Request : '.json_encode($param));
        
        $user = \App\Models\Users::where('id',$param['u_id'])->first();
        if(is_null($user)){
            $res = \General::error_res('User Not found.');
            return $res;
        }
        $user = $user->toArray();
//        dd($user);
        
        $check = 1;
//        $c = 0;
        while(!is_null($check)){
            $orderId = config('constant.COIN_SHORT_NAME').'_'.\General::rand_str(5);
            $check   = \App\Models\CoinInvoice::where('order_id',$orderId)->first();
//            $c++;
        }
        
//        dd($c);
        
        $data = [];
        $data['user_id']    = $user['id'];
        $data['email_id']   = $user['email'];
        $data['order_id']   = $orderId;
        $data['coin_code']  = config('constant.COIN_SHORT_NAME');
        $data['price']      = (float)$param['dollar'];
        $data['description']= 'Trx-'.$orderId.' of price '.$param['dollar'].' dollar.';
        
//        $res = \App\Lib\BitPayLib::generate_invoice($data);
//        $res['flag'] = 1;
//        $obj = new \App\Lib\GoUrlLib;
        
        $obj = new \App\Lib\PayGateLib;
        //dd($data);
        
        $res = $obj::generate_invoice($data);

        \Log::info('PayGate Genrate Invoice Library Response : '.json_encode($res));
        
        $setting = app('settings');
        
         $setting['conversion_rate']=\General::global_price(config('constant.COIN_SHORT_NAME'));
        
       // dd($res);
        
        if($res['flag'] == 1){
            $trx = new \App\Models\CoinInvoice();
            $trx->status        = 2;
            $trx->order_id      = $orderId;
            $trx->invoice_id    = $res['address'];
            $trx->user_id       = $user['id'];
            $trx->btc   = 0;
            $trx->usd   = $param['dollar'];
            $trx->coin   = $param['dollar'] / $setting['conversion_rate'];
            $trx->ua    = \Request::server("HTTP_USER_AGENT");
            $trx->ip    = \Request::getClientIp();
            $trx->save();
            
        }
        $res['order_id'] = $orderId;
        $cokie = \App\Lib\Mycrypt::encrypt($orderId.'|'.$user['id']);
//        dd($cokie,\App\Lib\Mycrypt::decrypt($cokie));
//        \Cookie::set('gourl_token',$cokie);
        
        \Log::info('PayGate Generate Invoice Response : '.json_encode($res));
//        dd($res);
       // return \Response::json($res,200);
//        return \Response::json($res,200);
        return \Response::json($res,200)->withCookie(cookie('paygate_token', $cokie, 15));
    }
    
    public function postPayGateBtcGenInvoice($userd=null){
        $param = \Input::all();
//        dd($param);
//        $param['btc']=$param['dollar'];
         
        \Log::info('PayGate BTC Generate Invoice Request : '.json_encode($param));
         if(isset($userd['api_call'])){
            $param['u_id']=$userd['id'];
        }
        $user = \App\Models\Users::where('id',$param['u_id'])->first();
        if(is_null($user)){
            $res = \General::error_res('User Not found.');
            return $res;
        }
        $user = $user->toArray();
//        dd($user);
        
        $check = 1;
//        $c = 0;
        while(!is_null($check)){
            $orderId = 'BTC_'.\General::rand_str(5);
            $check   = \App\Models\CoinInvoice::where('order_id',$orderId)->first();
//            $c++;
        }
        
//        dd($c);
        $btcp = \App\Models\Setting::where('name','deposit_btc_limit')->first();
       if($param['btc']<$btcp->val){
            $res = \General::error_res('Minimun Deposit btc limit is: '.$btcp->val);
            return \Response::json($res,200);
       }
        
        $data = [];
        $data['user_id']    = $user['id'];
        $data['email_id']   = $user['email'];
        $data['order_id']   = $orderId;
        $data['coin_code']  = 'BTC';
        $data['btc']        = (float)$param['btc'];
        $data['description']= 'Trx-'.$orderId.' of price '.$param['btc'].' BTC.';
        
//        $res = \App\Lib\BitPayLib::generate_invoice($data);
//        $res['flag'] = 1;
        $obj = new \App\Lib\PayGateLib;
        $res = $obj::generate_invoice($data);
//        $json = $obj->
//        dd($res);
        
        \Log::info('PayGate BTC Genrate Invoice Library Response : '.json_encode($res));
        
        if($res['flag'] == 1){
            $trx = new \App\Models\CoinInvoice();
            $trx->status        = 2;
            $trx->order_id      = $orderId;
            $trx->invoice_id    = $res['address'];
            $trx->user_id       = $user['id'];
            $trx->btc   = $param['btc'];
            $trx->usd   = 0;
            $trx->coin   = 0;
            $trx->ua    = \Request::server("HTTP_USER_AGENT");
            $trx->ip    = \Request::getClientIp();
            $trx->save();
            
        }
        $res['order_id'] = $orderId;
        $cokie = \App\Lib\Mycrypt::encrypt($orderId.'|'.$user['id']);
//        dd($cokie,\App\Lib\Mycrypt::decrypt($cokie));
//        \Cookie::set('gourl_token',$cokie);
        
        \Log::info('PayGate BTC Generate Invoice Response : '.json_encode($res));
       
//        return \Response::json($res,200);
        return \Response::json($res,200)->withCookie(cookie('gourl_token', $cokie, 15));
    }
    
    public function postPayGateGetInvoice(){
        $param = \Input::all();
        
        \Log::info('PayGate Get Invoice Request : '.json_encode($param));
        
        $trx = \App\Models\CoinInvoice::where('order_id',$param['t_id'])->first();
        if(!is_null($trx)){
            if($trx->status == 0){
                $res = \General::error_res('Payment Failed. Try After Sometime.');
                return $res;
            }
            else if($trx->status == 1){
                $res = \General::success_res('Payment Already Paid.');
                return $res;
            }
        }
        
        $param['order_id']  = $trx->order_id;
        
        $obj = new \App\Lib\PayGateLib;
        $res = $obj::get_invoice($param);
//        $res = \App\Lib\GoUrlLib::get_invoice($param);
        
        \Log::info('PayGate Get Invoice Library Response : '.json_encode($res));
        
        $setting = app('settings');
        
           $setting['conversion_rate']=\General::global_price(config('constant.COIN_SHORT_NAME'));
        
        if($res['flag'] == 1){
            $trx = \App\Models\CoinInvoice::where('order_id',$param['t_id'])->first();
            
            $trx->tx_id  = $res['tx_id'];
            $trx->status = 3;
            if($res['status'] == 'payment_confirmed'){
                $trx->status = 1;
            }

            $trx->save();

            $box_status = 'cryptobox_updated';

            if($trx->status == 1){
                $coin = explode('_',$res['order_id'])[0];
        
                if($coin == config('constant.COIN_SHORT_NAME')){
                    $p_data['order'] = $param['data']['order_id'];
                    $p_data['user']  = $trx->user_id;
                    $process = \App\Models\General::process_gourl($p_data);
//                    $process = \App\Models\General::process_gourl($param);
                }else if($coin == 'BTC'){
                    \Log::info("\n\t\tBTC Transaction\t\t\n");
                    $btc_data = [
                        'user_id'   => $trx->user_id,
                        'type'      => 1,
                        'reference_id' => $trx->id,
                        'credit'    => $trx->btc,
                        'debit'     => 0,
                        'comment'   => $trx->btc.' BTC purchased.',
                        'txn_address'=> $res['tx_id']
                    ];
                    $btc_add = \App\Models\BtcTransactions::add_transaction($btc_data);
                }
            }
        }

        \Log::info('PayGate Get Invoice Response : '.json_encode($res));
        return $res;
    }
    
    public function postPayGateCheckInvoice(){
        $param = \Input::all();
        
        \Log::info('PayGate Check Invoice Request : '.json_encode($param));
        
        $trx = \App\Models\CoinInvoice::where('order_id',$param['t_id'])->first();
        if(!is_null($trx)){
            if($trx->status == 0){
                $res = \General::error_res('Payment Failed. Try After Sometime.');
                return $res;
            }
            else if($trx->status == 1){
                 $param['order_id']  = $trx->order_id;
        
                $obj = new \App\Lib\PayGateLib;
                $res = $obj::check_invoice($param);
                //$res = \General::success_res('Payment Already Paid.');
                return $res;
            }
        }
        
        $param['order_id']  = $trx->order_id;
        
        $obj = new \App\Lib\PayGateLib;
        $res = $obj::check_invoice($param);
//        $res = \App\Lib\GoUrlLib::get_invoice($param);
        
//        dd($res);
        
        \Log::info('PayGate Check Invoice Library Response : '.json_encode($res));
        
        
            $setting = app('settings');
        
            $setting['conversion_rate']=\General::global_price(config('constant.COIN_SHORT_NAME'));

            if($res['flag'] == 1 && ($res['status'] == 'payment_processing' || $res['status'] == 'payment_confirmed')){
                $trx = \App\Models\CoinInvoice::where('order_id',$param['t_id'])->first();

                $trx->tx_id  = $res['tx_id'];
                $trx->status = 3;
                if($res['status'] == 'payment_confirmed'){
                    $trx->status = 1;
                }
                
                $trx->save();

                $box_status = 'cryptobox_updated';
                
                if($trx->status == 1){
                    $coin = explode('_',$res['order_id'])[0];
        
                    if($coin == config('constant.COIN_SHORT_NAME')){

                        $p_data['order'] = $param['order_id'];
                        $p_data['user']  = $trx->user_id;
                        $process = \App\Models\General::process_gourl($p_data);
        //                $process = \App\Models\General::process_gourl($param);
                    }else if($coin == 'BTC'){
                        \Log::info("\n\t\tBTC Transaction\t\t\n");
                        $btc_data = [
                            'user_id'   => $trx->user_id,
                            'type'      => 1,
                            'reference_id' => $trx->id,
                            'credit'    => $trx->btc,
                            'debit'     => 0,
                            'comment'   => $trx->btc.' BTC purchased.',
                            'txn_address'=> $res['tx_id']
                        ];
                        $btc_add = \App\Models\BtcTransactions::add_transaction($btc_data);
                    }
                }
            }

        \Log::info('PayGate Check Invoice Response : '.json_encode($res));
       
        return $res;
    }
    
    public function postGoUrlGenInvoice(){
        $param = \Input::all();
//        dd($param);
        
        \Log::info('GoUrl Generate Invoice Request : '.json_encode($param));
        
        $user = \App\Models\Users::where('id',$param['u_id'])->first();
        if(is_null($user)){
            $res = \General::error_res('User Not found.');
            return $res;
        }
        $user = $user->toArray();
//        dd($user);
       
//                dd($icoId->toArray());
       
        $check = 1;
//        $c = 0;
        while(!is_null($check)){
            $orderId = config('constant.COIN_SHORT_NAME').'_'.\General::rand_str(5);
            $check   = \App\Models\CoinInvoice::where('order_id',$orderId)->first();
//            $c++;
        }
        
//        dd($c);
        
        $data = [];
        $data['user_id']    = $user['id'];
        $data['email_id']   = $user['email'];
        $data['order_id']   = $orderId;
        $data['coin_code']  = config('constant.COIN_SHORT_NAME');
        $data['price']      = (float)$param['dollar'];
        $data['description']= 'Trx-'.$orderId.' of price '.$param['dollar'].' dollar.';
        
//        $res = \App\Lib\BitPayLib::generate_invoice($data);
//        $res['flag'] = 1;
        $obj = new \App\Lib\GoUrlLib;
        $res = $obj->generate_invoice($data);
//        $json = $obj->
//        dd($res);
        
        \Log::info('GoUrl Genrate Invoice Library Response : '.json_encode($res));
        
        $setting = app('settings');
        
          $setting['conversion_rate']=\General::global_price(config('constant.COIN_SHORT_NAME'));
        
//        $cico = new \App\Models\CalenderIco();
//        $cico=$cico::where('token','!=','sold_token')->where('status','=',1)->where('end_date','>=',date('Y-m-d H:i:s'))->orderBy('end_date','asc')->first();
//        if(is_null($cico)){
//            $res = \General::error_res('No ICO found!Please check next ICO start date');
//            return \Response($res,200);
//        }
        $cp=$param['dollar'] / $setting['conversion_rate'];
//        if($cp<$cico->buy_limit){
//            $res = \General::error_res('Minimum coin purchase limit is:'.$cico->buy_limit);
//            return \Response($res,200);
//        }
             
       $usdp = \App\Models\Setting::where('name','coin_buy_limit')->first();
       if($param['dollar']<$usdp->val){
            $res = \General::error_res('Minimun Buy coin limit is: '.$usdp->val.' USD');
            return \Response::json($res,200);
       }
       
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
        
//        $nextIco = \App\Models\CalenderIco::where('status','=',1)->where('start_date','<=',date('Y-m-d H:i:s'))
//                                            ->where('end_date','>=',date('Y-m-d H:i:s'))
//                                            ->first(); 
//       
//        $soldCoin = \App\Models\CoinTransactions::where('type',1)->whereBetween('created_at', [$nextIco->start_date, $nextIco->end_date])
//                                            ->sum('credit');
//        dd($nextIco->token);
//        dd($soldCoin);
        
//        dd(is_null($nextIco) || $soldCoin + $cp >= $nextIco->token);
        
        if(is_null($nextIco) || $soldCoin + $cp >= $nextIco->token){

            $nextIco = \App\Models\PreSellCalender::where('status',1)->where('start_date','>=',date('Y-m-d H:i:s'))->OrderBy('start_date','asc')->first();
            if(is_null($nextIco)){
                $nextIco = \App\Models\CalenderIco::where('status',1)->where('start_date','>=',date('Y-m-d H:i:s'))->OrderBy('start_date','asc')->first();
            }
            if(is_null($nextIco)){
//                $nextIco = 'Not Found';
                $res = \General::error_res('All Coins are sold. Please, Try in next sale.');
                return \Response::json($res,200);
            }
        }
        
//        if($soldCoin + $cp > $nextIco->token){
//            $lc = (int)$nextIco->token - $soldCoin;
//           
//            $res = \General::error_res('Only '.$lc.' coin are left.');
//            return \Response::json($res,200);
//        }
        
        if($res['flag'] == 1){
            $trx = new \App\Models\CoinInvoice();
            $trx->status        = 2;
            $trx->order_id      = $orderId;
            $trx->invoice_id    = $res['iframe_id'];
            $trx->user_id       = $user['id'];
            $trx->btc   = 0;
            $trx->usd   = $param['dollar'];
            $trx->coin   = $param['dollar'] / $setting['conversion_rate'];
            $trx->ua    = \Request::server("HTTP_USER_AGENT");
            $trx->ip    = \Request::getClientIp();
            $trx->save();
            
        }
        $res['order_id'] = $orderId;
        $cokie = \App\Lib\Mycrypt::encrypt($orderId.'|'.$user['id']);
//        dd($cokie,\App\Lib\Mycrypt::decrypt($cokie));
//        \Cookie::set('gourl_token',$cokie);
        
        \Log::info('GoUrl Generate Invoice Response : '.json_encode($res));
        
//        return \Response::json($res,200);
        return \Response::json($res,200)->withCookie(cookie('gourl_token', $cokie, 15));
    }
    
    public function postGoUrlBtcGenInvoice(){
        $param = \Input::all();
//        dd($param);
        
        \Log::info('GoUrl BTC Generate Invoice Request : '.json_encode($param));
        
        $user = \App\Models\Users::where('id',$param['u_id'])->first();
        if(is_null($user)){
            $res = \General::error_res('User Not found.');
            return $res;
        }
        $user = $user->toArray();
//        dd($user);
        
        $check = 1;
//        $c = 0;
        while(!is_null($check)){
            $orderId = 'BTC_'.\General::rand_str(5);
            $check   = \App\Models\CoinInvoice::where('order_id',$orderId)->first();
//            $c++;
        }
        
//        dd($c);
        
        $data = [];
        $data['user_id']    = $user['id'];
        $data['email_id']   = $user['email'];
        $data['order_id']   = $orderId;
        $data['coin_code']  = 'BTC';
        $data['btc']        = (float)$param['btc'];
        $data['description']= 'Trx-'.$orderId.' of price '.$param['btc'].' BTC.';
        
//        $res = \App\Lib\BitPayLib::generate_invoice($data);
//        $res['flag'] = 1;
        $obj = new \App\Lib\GoUrlLib;
        $res = $obj->generate_invoice($data);
//        $json = $obj->
//        dd($res);
        
        \Log::info('GoUrl BTC Genrate Invoice Library Response : '.json_encode($res));
        
//        $setting = app('settings');
//        
//        $cnv_rate = \App\Models\CalenderIco::where('start_date','<=',date('Y-m-d H:i:s'))
//                                            ->where('end_date','>=',date('Y-m-d H:i:s'))
//                                            ->first();
//        if(!is_null($cnv_rate)){
//            $setting['conversion_rate'] = $cnv_rate->price;
//            
//            if($cnv_rate->price == 0){
//                $old_rate = \App\Models\CalenderIco::OrderBy('end_date','DESC')
//                                                    ->where('end_date','<',$cnv_rate->start_date)
//                                                    ->first();
//                if(!is_null($old_rate)){
//                    $setting['conversion_rate'] = $old_rate->price;
//                }
//                else{
//                    $setting['conversion_rate'] = \App\Models\Setting::where('name','conversion_rate')->value('val');
//                }
//            }
//        }
        
        if($res['flag'] == 1){
            $trx = new \App\Models\CoinInvoice();
            $trx->status        = 2;
            $trx->order_id      = $orderId;
            $trx->invoice_id    = $res['iframe_id'];
            $trx->user_id       = $user['id'];
            $trx->btc   = $param['btc'];
            $trx->usd   = 0;
            $trx->coin   = 0;
            $trx->ua    = \Request::server("HTTP_USER_AGENT");
            $trx->ip    = \Request::getClientIp();
            $trx->save();
            
        }
        $res['order_id'] = $orderId;
        $cokie = \App\Lib\Mycrypt::encrypt($orderId.'|'.$user['id']);
//        dd($cokie,\App\Lib\Mycrypt::decrypt($cokie));
//        \Cookie::set('gourl_token',$cokie);
        
        \Log::info('GoUrl BTC Generate Invoice Response : '.json_encode($res));
        
//        return \Response::json($res,200);
        return \Response::json($res,200)->withCookie(cookie('gourl_token', $cokie, 15));
    }
    
    public function postGoUrlGetInvoice(){
        $param = \Input::all();
        
        \Log::info('GoUrl Get Invoice Request : '.json_encode($param));
        
        $trx = \App\Models\CoinInvoice::where('order_id',$param['t_id'])->first();
        if(!is_null($trx)){
            if($trx->status == 0){
                $res = \General::error_res('Payment Failed. Try After Sometime.');
                return $res;
            }
            else if($trx->status == 1){
                $res = \General::success_res('Payment Already Paid.');
                return $res;
            }
        }
        
        $param['price']     = $trx->usd;
        $param['order_id']  = $trx->order_id;
        $param['user_id']   = $trx->user_id;
        
        $obj = new \App\Lib\GoUrlLib;
        $res = $obj->get_invoice($param);
//        $res = \App\Lib\GoUrlLib::get_invoice($param);
        
        \Log::info('GoUrl Get Invoice Library Response : '.json_encode($res));
        
        $setting = app('settings');
        
        $setting['conversion_rate']=\General::global_price(config('constant.COIN_SHORT_NAME'));
        
        if($res['flag'] == 1){
            $trx = \App\Models\CoinInvoice::where('invoice_id',$param['t_id'])->first();
            $trx->status = 1;
            $trx->save();

            $box_status = 'cryptobox_updated';

            $p_data['order'] = $param['order'];
            $p_data['user']  = $param['user'];
            $process = \App\Models\General::process_gourl($p_data);
//                $process = \App\Models\General::process_gourl($param);
        }

        \Log::info('GoUrl Get Invoice Response : '.json_encode($res));
        return $res;
    }
    
    public function GoUrlConfirmation(){
        
//        if(!$req->isMethod('post')){
//           echo 'Only POST Data Allowed';
//        }
        
        $param = \Input::all();
        
        \Log::info('GoUrl Callback Request : '.json_encode($param));
//        dd($param);
        $box_status = 'cryptobox_nochanges';
        
        $verify = \App\Lib\GoUrlLib::callback_func($param);
        
//        dd($verify);
        
        if(isset($verify['flag']) && $verify['flag'] != 1){
            $box_status = 'Only POST Data Allowed';
        }else{
//            dd($verify);
            
            $trx = \App\Models\CoinInvoice::where('order_id',$param['order'])
                                            ->where('user_id',$param['user'])
                                            ->first();
//            dd($param,$trx);
            if($trx){
                if($trx->status == 0){
                    $res = \General::error_res('Payment Failed.');
                    \Log::info('GoUrl Notification : Payment Failed.');
//                    $box_status = 'Payment Failed';
                    $box_status = 'cryptobox_nochanges';
                    
                }else if($trx->status == 1){
                    $res = \General::success_res('Payment Already Paid.');
                    \Log::info('GoUrl Notification : Payment Already Paid.');
                    $box_status = 'cryptobox_nochanges';
                }else{
                    $data['price']     = $param['amountusd'];
                    $data['order_id']  = $param['order'];
                    $data['user_id']   = $param['user'];
                    
                    $obj = new \App\Lib\GoUrlLib;
                    \Log::info('GoUrl Callback Get Invoice Request : '.json_encode($data));
                    $res = $obj->get_invoice($data);
                    \Log::info('GoUrl Callback Get Invoice Response : '.json_encode($res));
                    if($res['flag'] == 1){
                        $trx->status = 1;
                        $trx->save();

                    
                        \Log::info('Order : ( '.$param['order'].' ) of User : ( '.$param['user'].' ) Paid Successfully.');
//                        $box_status = 'cryptobox_updated';
                        $box_status = 'cryptobox_newrecord';

                        $coin = explode('_',$param['order'])[0];
                        
                        if($coin == config('constant.COIN_SHORT_NAME')){
                            $p_data['order'] = $param['order'];
                            $p_data['user']  = $param['user'];
                            $process = \App\Models\General::process_gourl($p_data);
    //                        $process = \App\Models\General::process_gourl($param);
                        }
                        else if($coin == 'BTC'){
                            $btc_data = [
                                'user_id'   => $param['user'],
                                'type'      => 1,
                                'reference_id' => $trx->id,
                                'credit'    => $param['amount'],
                                'debit'     => 0,
                                'comment'   => $param['amount'].' BTC purchased.',
                            ];
                            $btc_add = \App\Models\BtcTransactions::add_transaction($btc_data);
                        }
                    }
                }
            }
        }
        
        echo $box_status; // don't delete it
    }
    public function PayGateTransferConfirmation(Request $req){
        
        $param = \Input::all();
        
        \Log::info("\n\n\nPayGate Transfer Callback Request : ".json_encode($param));
        $box_status = 'paygate_nochanges';
        
        if(!$req->isMethod('post')){
            $box_status = 'Only POST Data Allowed';
        }else{
            
            $trx = \App\Models\WithdrowRequest::where('transaction_id',$param['tx_id'])
                                                ->first();
//            dd($param,$trx);
            if($trx){
                if($trx->status == 2){
                    $res = \General::error_res('Request Declined.');
                    \Log::info('PayGate Transfer Notification : Request Declined.');
                    $box_status = 'paygate_nochanges';
                    
                }else if($trx->status == 1){
                    $res = \General::success_res('Request Already Approved.');
                    \Log::info('PayGate Transfer Notification : Request Already Approved.');
                    $box_status = 'paygate_nochanges';
                }else{

                    $data['tx_id']  = $param['tx_id'];
                    
                    $obj = new \App\Lib\PayGateLib;
                    \Log::info('PayGate Transfer Callback Check Invoice Request : '.json_encode($data));
                    $res = $obj::get_transfer($data);
                    \Log::info('PayGate Transfer Callback Check Invoice Response : '.json_encode($res));
                    if($res['flag'] == 1){
                        $trx->status = 1;
                        $trx->save();

                    
                        \Log::info('Transfer to Address ( '.$param['address'].' ) from User : ( '.$trx->user_id.' ) Done Successfully.');
//                        $box_status = 'paygate_updated';
                        $box_status = 'paygate_newrecord';

                        \Log::info("\n\t\tBTC Transaction\t\t\n");
                        
                        $data = [
                            'user_id'   => $trx->user_id,
                            'type'      => 2,
                            'reference_id'=>null,
                            'credit'    => 0,
                            'debit'     => $param['amount'],
                            'txn_type'  => 2,
                            'txn_address'=> $tr['tx_id'],
                            'comment'   => $param['amount'].' debited for withdrawal request #'.$trx->id,
                        ];
                        $btc_add = \App\Models\BtcTransactions::add_transaction($btc_data);
                        
                    }
                }
            }
            else{
                \Log::info('PayGate Transfer Notification : No Request Found.');
            }
        }
        
        echo $box_status; // don't delete it
    }
    public function PayGateConfirmation(Request $req){
        
        $param = \Input::all();
        
        \Log::info("\n\n\nPayGate Callback Request : ".json_encode($param));
        $box_status = 'paygate_nochanges';
        
        if(!$req->isMethod('post')){
            $box_status = 'Only POST Data Allowed';
        }else{
            
            $trx = \App\Models\CoinInvoice::where('order_id',$param['order_id'])
                                            ->first();
//            dd($param,$trx);
            if($trx){
                if($trx->status == 0){
                    $res = \General::error_res('Payment Failed.');
                    \Log::info('PayGate Notification : Payment Failed.');
                    $box_status = 'paygate_nochanges';
                    
                }else if($trx->status == 1){
                    $res = \General::success_res('Payment Already Paid.');
                    \Log::info('PayGate Notification : Payment Already Paid.');
                    $box_status = 'paygate_nochanges';
                }else{

                    $data['order_id']  = $param['order_id'];
                    
                    $obj = new \App\Lib\PayGateLib;
                    \Log::info('PayGate Callback Check Invoice Request : '.json_encode($data));
                    $res = $obj::check_invoice($data);
                    \Log::info('PayGate Callback Check Invoice Response : '.json_encode($res));
                    if($res['flag'] == 1){
                        
                        $trx->tx_id  = $res['tx_id'];
                        $trx->status = 3;
                        if($param['status'] == 'payment_confirmed'){
                            $trx->status = 1;
                        }
                        $trx->save();

                    
                        if($trx->status == 1){
                            
                            \Log::info('Order : ( '.$param['order_id'].' ) of User : ( '.$trx->user_id.' ) Paid Successfully.');
//                            $box_status = 'paygate_updated';
                            $box_status = 'paygate_newrecord';

                            $coin = explode('_',$param['order_id'])[0];


                            if($coin == config('constant.COIN_SHORT_NAME')){
                                $p_data['order'] = $param['order_id'];
                                $p_data['user']  = $trx->user_id;
                                $process = \App\Models\General::process_gourl($p_data);
                            }
                            else if($coin == 'BTC'){
                                \Log::info("\n\t\tBTC Transaction\t\t\n");
                                $btc_data = [
                                    'user_id'   => $trx->user_id,
                                    'type'      => 1,
                                    'reference_id' => $trx->id,
                                    'credit'    => $param['amount'],
                                    'debit'     => 0,
                                    'comment'   => $param['amount'].' BTC purchased.',
                                    'txn_address'=> $res['tx_id']
                                ];
                                $btc_add = \App\Models\BtcTransactions::add_transaction($btc_data);
                            }
                            
                        }
                        else{
                            \Log::info('Order : ( '.$param['order_id'].' ) of User : ( '.$trx->user_id.' ) Under-Processing.');
                        }
                        
                    }
                }
            }
        }
        
        echo $box_status; // don't delete it
    }
    
    public function anyPayGateCallback(Request $req){
        
//        if(!$req->isMethod('post')){
//           echo 'Only POST Data Allowed';
//        }
        
        $param = \Input::all();
        
        \Log::info("\n\n\nPayGate Client Callback Request : ".json_encode($param));
        $box_status = 'paygate_nochanges';
        
        if(!$req->isMethod('post')){
            $box_status = 'Only POST Data Allowed';
        }else{
            
            $trx = \App\Models\CoinInvoice::where('order_id',$param['order_id'])
//                                            ->where('user_id',$param['user'])
                                            ->first();
//            dd($param,$trx);
            if($trx){
                if($trx->status == 0){
                    $res = \General::error_res('Payment Failed.');
                    \Log::info('PayGate Notification : Payment Failed.');
//                    $box_status = 'Payment Failed';
                    $box_status = 'paygate_nochanges';
                    
                }else if($trx->status == 1){
                    $res = \General::success_res('Payment Already Paid.');
                    \Log::info('PayGate Notification : Payment Already Paid.');
                    $box_status = 'paygate_nochanges';
                }else{
//                    $data['price']     = $param['amountusd'];
                    $data['order_id']  = $param['order_id'];
//                    $data['user_id']   = $param['user'];
                    
                    $obj = new \App\Lib\PayGateLib;
                    \Log::info('PayGate Callback Get Invoice Request : '.json_encode($data));
                    $res = $obj::get_invoice($data);
                    \Log::info('PayGate Callback Get Invoice Response : '.json_encode($res));
                    if($res['flag'] == 1){
                        
                        $trx->tx_id  = $param['tx_id'];
                        $trx->status = 3;
                        if($param['status'] == 'payment_confirmed'){
                            $trx->status = 1;
                        }

                        $trx->save();
                        
                        $box_status = 'paygate_newrecord';
                    
                        if($trx->status == 1){
                            \Log::info('Order : ( '.$param['order_id'].' ) of User : ( '.$trx->user_id.' ) Paid Successfully.');
//                        $box_status = 'paygate_updated';
                        

                            $coin = explode('_',$param['order_id'])[0];

                            if($coin == config('constant.COIN_SHORT_NAME')){
                                $p_data['order'] = $param['order_id'];
                                $p_data['user']  = $trx->user_id;
                                $process = \App\Models\General::process_gourl($p_data);
                            }
                            else if($coin == 'BTC'){
                                $btc_data = [
                                    'user_id'   => $trx->user_id,
                                    'type'      => 1,
                                    'reference_id' => $trx->id,
                                    'credit'    => $param['amount'],
                                    'debit'     => 0,
                                    'comment'   => $param['amount'].' BTC purchased.',
                                ];
                                $btc_add = \App\Models\BtcTransactions::add_transaction($btc_data);
                            }
                        }
                        \Log::info('Order : ( '.$param['order_id'].' ) of User : ( '.$trx->user_id.' ) Under-Progress.');
                    }
                }
            }
        }
        
        echo $box_status; // don't delete it
    }
    
    public function anyGourlC( Request $req){
        dd(\Cookie::get('gourl_token'));
    }
    public function anyGourlCallback( Request $req){
        
        if(!$req->isMethod('post')){
           return 'Only POST Data Allowed';
        }
        
        $param = \Input::all();

        
        \Log::info('GoUrl Token_Buy Callback Request : '.json_encode($param));
//        dd($param);
        $box_status = 'cryptobox_nochanges';
        
        if($req->cookie('gourl_token') == null || count($param) == 0 ){
            \Log::info('GoUrl Token_Buy Callback Response : '.$box_status);
            return $box_status;
        }
        
        $cookie_data = explode('|', \App\Lib\Mycrypt::decrypt($req->cookie('gourl_token')));

        if($param['order'] != $cookie_data[0] || $param['user'] != $cookie_data[1]){
            \Log::info('GoUrl Token_Buy Callback Response : '.$box_status);
            return $box_status;
        }
        
        \Cookie::queue(
            \Cookie::forget('gourl_token')
        );

        
        $verify = \App\Lib\GoUrlLib::callback_func($param);

        
        if(isset($verify['flag']) && $verify['flag'] != 1){
            $box_status = 'Only POST Data Allowed';
        }else{
            
            $trx = \App\Models\CoinInvoice::where('order_id',$param['order'])
                                            ->where('user_id',$param['user'])
                                            ->first();
            if($trx){
                if($trx->status == 0){
                    
                    $res = \General::error_res('Payment Failed.');
                    \Log::info('GoUrl Notification : Payment Failed.');
                    $box_status = 'Payment Failed';
                    
                }else if($trx->status == 1){
                    
                    $res = \General::success_res('Payment Already Paid.');
                    \Log::info('GoUrl Notification : Payment Already Paid.');
                    $box_status = 'Payment Already Paid';
                    
                }else{
                    
                    $trx->status = 1;
                    $trx->save();


                    \Log::info('Order : ( '.$param['order'].' ) of User : ( '.$param['user'].' ) Paid Successfully.');
                    $box_status = 'cryptobox_updated';

                    $coin = explode('_',$param['order'])[0];
                        
                    if($coin == config('constant.COIN_SHORT_NAME')){
                        $p_data['order'] = $param['order'];
                        $p_data['user']  = $param['user'];
                        $process = \App\Models\General::process_gourl($p_data);
                    }
                    else if($coin == 'BTC'){
                        $btc_data = [
                            'user_id'   => $param['user'],
                            'type'      => 1,
                            'reference_id' => $trx->id,
                            'credit'    => $param['amount'],
                            'debit'     => 0,
                            'comment'   => $param['amount'].' BTC purchased.',
                        ];
                        $btc_add = \App\Models\BtcTransactions::add_transaction($btc_data);
                    }
                }
            }
        }
        
//        echo $box_status; // don't delete it

        return $box_status; // don't delete it
    }
   
    public function postTestGourlSuccess($userd=null){
        
        
        $param = \Input::all();
        
        
        if(isset($userd['api_call'])){
            $param['user']=$userd['id'];
        }
        $trx = \App\Models\CoinInvoice::where('order_id',$param['order'])
                                            ->where('user_id',$param['user'])
                                            ->first();
        if($trx){
            if($trx->status == 0){
                $res = \General::error_res('Payment Failed.');
                \Log::info('test GoUrl Notification : Payment Failed.');
            }else if($trx->status == 1){
                $res = \General::success_res('Payment Already Paid.');
                \Log::info('test GoUrl Notification : Payment Already Paid.');
            }else{

                $trx->status = 1;
                $trx->save();

                $coin = explode('_',$param['order'])[0];
                        
                if($coin == config('constant.COIN_SHORT_NAME')){
                    $p_data['order'] = $param['order'];
                    $p_data['user']  = $param['user'];
                   
                    $process = \App\Models\General::process_gourl($p_data);
    //                $process = \App\Models\General::process_gourl($param);
                }
                else if($coin == 'BTC'){
                    $btc_data = [
                        'user_id'   => $param['user'],
                        'type'      => 1,
                        'reference_id' => $trx->id,
                        'credit'    => $trx->btc,
                        'debit'     => 0,
                        'comment'   => $trx->btc.' BTC purchased.',
                    ];
//                    dd($btc_data);
                    $btc_add = \App\Models\BtcTransactions::add_transaction($btc_data);
                }
                    
                $res = \General::success_res('Invoice Paid successfully.');
            }
        }
        
        return $res;
    }
    
    public function getTestGetInvoice($order_id = '',$user_id='',$price=''){
//        dd($order_id,$user_id,$price);
        $param['order_id'] = $order_id;
        $param['user_id'] = $user_id;
        $param['price'] = $price;
        
        $obj=new \App\Lib\GoUrlLib;
        $res = $obj->get_invoice_test($param);
        
//        $res = \App\Lib\GoUrlLib::get_invoice_test($param);
        
//        dd(__DIR__);
//        $param = [];
//        $param['t_id'] = $id;
        
        
    }
    
    public function getTestGo(){
        
//          $obj=new \App\Lib\GoUrlLib;
//        $res = $obj->generate_invoice();
//        $res['body'] = \App\Lib\GoUrlLib::generate_invoice();
//        return view('user.test',$res);
        return view('user.test');
    }
    
    public function postTestGo(){
        
         $obj=new \App\Lib\GoUrlLib;
         $res['body'] = $obj->generate_invoice();
//        $res['body'] = \App\Lib\GoUrlLib::generate_invoice();
        return \Response::json($res,200);
    }

}