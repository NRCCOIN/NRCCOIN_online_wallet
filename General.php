<?php

namespace App\Models;
use DB;
use App\Lib\coin\jsonRPCClient as RPC;

class General {
    
    public static function process_gourl($param){
        
            
            $setting = app('settings');
//            
////            dd($param);
//            
            $cnv_rate = self::get_conversion_rate();
//            if($cnv_rate['price']){
//                $setting['conversion_rate'] = $cnv_rate['price'];
//            }
            
            $setting['conversion_rate'] = \General::global_price(config('constant.COIN_SHORT_NAME'));
                       
            $trx = \App\Models\CoinInvoice::where('order_id',$param['order'])
                                            ->where('user_id',$param['user'])
                                            ->first();
//            dd($trx->toArray());
            if(!is_null($trx)){
                $trx    = $trx->toArray();
                
//                dd($trx);
                $calander = CalenderIco::where('id',$cnv_rate['id'])->first();
                $calander->sold_token = $calander->sold_token + $trx['coin']; 
                $calander->save();
                
                $coin    = $trx['usd'];
                $current_user_coin =     $trx['usd'] / $setting['conversion_rate'];
                if($setting['refer_reward_type'] == 'COIN'){
                    $coin = $trx['usd'] / $setting['conversion_rate'];
                }
                 
                $coin = new \App\Models\CoinTransactions();
                $coin->user_id      = $trx['user_id'];
                $coin->type         = 1;
                $coin->reference_id = $trx['id'];
//                    $coin->credit = $coin;
                $coin->credit       = $current_user_coin;
                $coin->debit        = 0;
                $coin->comment      = $current_user_coin.' '.config('constant.COIN_SHORT_NAME').' purchased.';
                $coin->save();

//                $cico = new \App\Models\CalenderIco();
//                $cico=$cico::where('token','!=','sold_token')->where('end_date','>=',date('Y-m-d H:i:s'))->orderBy('end_date','asc')->first();
////                dd($icoId->toArray());
//                $cico->sold_token=$cico->sold_token+$current_user_coin;
//                $cico->save();
//                dd($coin);
                
                $child = \App\Models\Users::where('id',$trx['user_id'])->first()->toArray();
                $acc_name = $child['coin_account_name'];
                $add_coin_to_node = General::assign_balance_to_account('admin', $acc_name, $current_user_coin);

                if($setting['refer_reward_type'] == 'COIN'){            // Referral Reward in Coin
                    
                    $add_coin = self::coin_to_parent($child['parent_id'], $trx['id'], $coin, $setting);
                    
                }
                else{                               // Referral Reward in USD
                    $add_usd = self::usd_to_parent($child['parent_id'], $trx['id'], $coin, $setting,'purchase');
                }
            }
            
    }
    
    public static function get_conversion_rate($start_date = '',$end_date = ''){
        if($start_date != ''){
            $start_date = date('Y-m-d H:i:s',strtotime($start_date));
        }else{
            $start_date = date('Y-m-d H:i:s');
        }
        if($end_date != ''){
            $end_date = date('Y-m-d H:i:s',strtotime($end_date));
        }else{
            $end_date = date('Y-m-d H:i:s');
        }
        $cnv_rate = \App\Models\CalenderIco::where('start_date','<=',$start_date)
                                            ->where('end_date','>=',$end_date)
                                            ->first();
        $price = 0;
        $id = 0;
        if(!is_null($cnv_rate)){
            $id = $cnv_rate->id;
            $price = $cnv_rate->price;
            if($cnv_rate->price == 0){
                $old_rate = \App\Models\CalenderIco::OrderBy('end_date','DESC')
                                                    ->where('end_date','<',$cnv_rate->start_date)
                                                    ->first();
                if(!is_null($old_rate)){
                    $price = $old_rate->price;
                }
            }
        }
        $data = [
            'id'=>$id,
            'price'=>$price,
        ];
        return $data;
    }
    
    public static function coin_to_parent($parent_id,$reference_id,$coin,$setting){
        // Refere Reward to Level 1 Parent
        $p_user_1 = \App\Models\Users::where('id',$parent_id)->first();
        if(!is_null($p_user_1)){

            $reward_coin = $coin * $setting['refer_level_1'] / 100;

            $coin1 = new \App\Models\CoinTransactions();
            $coin1->user_id     = $parent_id;
            $coin1->type        = 0;
            $coin1->reference_id= $reference_id;
            $coin1->credit      = $reward_coin;
            $coin1->debit       = 0;
//                    $coin1->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward.';
            $coin1->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward for Trx-'.$reference_id;
            $coin1->save();
            
            $acc_name = $p_user_1['coin_account_name'];
            $add_coin_to_node = General::assign_balance_to_account('admin', $acc_name, $reward_coin);
            // Refere Reward to Level 2 Parent
            $p_user_2 = \App\Models\Users::where('id',$p_user_1['parent_id'])->first();
            if(!is_null($p_user_2)){

                $reward_coin = $coin * $setting['refer_level_2'] / 100;

                $coin2 = new \App\Models\CoinTransactions();
                $coin2->user_id     = $p_user_1['parent_id'];
                $coin2->type        = 0;
                $coin2->reference_id= $reference_id;
                $coin2->credit      = $reward_coin;
                $coin2->debit       = 0;
//                        $coin2->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward.';
                $coin2->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward for Trx-'.$reference_id;
                $coin2->save();
                
                $acc_name = $p_user_2['coin_account_name'];
                $add_coin_to_node = General::assign_balance_to_account('admin', $acc_name, $reward_coin);
                
                // Refere Reward to Level 3 Parent
                $p_user_3 = \App\Models\Users::where('id',$p_user_2['parent_id'])->first();
                if(!is_null($p_user_3)){

                    $reward_coin = $coin * $setting['refer_level_3'] / 100;

                    $coin3 = new \App\Models\CoinTransactions();
                    $coin3->user_id     = $p_user_2['parent_id'];
                    $coin3->type        = 0;
                    $coin3->reference_id= $reference_id;
                    $coin3->credit      = $reward_coin;
                    $coin3->debit       = 0;
//                            $coin3->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward.';
                    $coin3->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward for Trx-'.$reference_id;
                    $coin3->save();
                    
                    $acc_name = $p_user_3['coin_account_name'];
                    $add_coin_to_node = General::assign_balance_to_account('admin', $acc_name, $reward_coin);
                }
            }
        }
    }
    
    public static function usd_to_parent($parent_id,$reference_id,$usd,$setting,$tr_type = ''){
        // Refere Reward to Level 1 Parent
        if($tr_type == ''){
            \Log::info('transaction type not specified for referel reward');
            return;
        }
        $rtypes = ['purchase','lending'];
        if(!in_array($tr_type, $rtypes)){
            \Log::info('invalid transaction type for referel reward');
            return;
        }
        $rewards = [
            'purchase' => [
                'level_1'=>$setting['refer_level_1'],
                'level_2'=>$setting['refer_level_2'],
                'level_3'=>$setting['refer_level_3'],
                'type'=>0,
            ],
            'lending' => [
                'level_1'=>$setting['lending_referel_level_1'],
                'level_2'=>$setting['lending_referel_level_2'],
                'level_3'=>$setting['lending_referel_level_3'],
                'type'=>12,
            ],
        ];
        $p_user_1 = \App\Models\Users::where('id',$parent_id)->first();
        if(!is_null($p_user_1)){

            $reward_coin = $usd * $rewards[$tr_type]['level_1'] / 100;

            $coin1 = new \App\Models\BalanceTransactions();
            $coin1->user_id     = $parent_id;
            $coin1->type        = $rewards[$tr_type]['type'];
            $coin1->reference_id= $reference_id;
            $coin1->credit      = $reward_coin;
            $coin1->debit       = 0;
//                    $coin1->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward.';
            $coin1->comment     = $reward_coin.' USD Reward for Trx-'.$reference_id;
            $coin1->save();

            // Refere Reward to Level 2 Parent
            $p_user_2 = \App\Models\Users::where('id',$p_user_1['parent_id'])->first();
            if(!is_null($p_user_2)){

                $reward_coin = $usd * $rewards[$tr_type]['level_2'] / 100;

                $coin2 = new \App\Models\BalanceTransactions();
                $coin2->user_id     = $p_user_1['parent_id'];
                $coin2->type        = $rewards[$tr_type]['type'];
                $coin2->reference_id= $reference_id;
                $coin2->credit      = $reward_coin;
                $coin2->debit       = 0;
//                        $coin2->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward.';
                $coin2->comment     = $reward_coin.' USD Reward for Trx-'.$reference_id;
                $coin2->save();

                // Refere Reward to Level 3 Parent
                $p_user_3 = \App\Models\Users::where('id',$p_user_2['parent_id'])->first();
                if(!is_null($p_user_3)){

                    $reward_coin = $usd * $rewards[$tr_type]['level_3'] / 100;

                    $coin3 = new \App\Models\BalanceTransactions();
                    $coin3->user_id     = $p_user_2['parent_id'];
                    $coin3->type        = $rewards[$tr_type]['type'];
                    $coin3->reference_id= $reference_id;
                    $coin3->credit      = $reward_coin;
                    $coin3->debit       = 0;
//                            $coin3->comment     = $reward_coin.' '.config('constant.COIN_SHORT_NAME').' Reward.';
                    $coin3->comment     = $reward_coin.' USD Reward for Trx-'.$reference_id;
                    $coin3->save();
                }
            }
        }
    }
    
    public static function rand_str($len) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $len; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }
        
    public static function generate_tr_address() {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < 35; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }        
    public static function generate_tr_transaction_id() {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < 55; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }
    
    public static function generate_coin_address($name = ''){
        $rpc = new RPC();
        $address = $rpc->getnewaddress();
//        return self::rand_str(34);
        return $address;
    }
    public static function generate_coin_account($name){
        $rpc = new RPC();
        $address = $rpc->getnewaddress($name);
//        return self::rand_str(34);
        return $address;
    }
    
    
    public static function generate_transaction_id($name,$address,$amount,$comment = ''){

        $address = trim($address);
        $amount  = (double)$amount;
        $rpc     = new RPC();
        
//        dd($address,$amount,$comment);
        $trx = $rpc->sendtoaddress($address,$amount,$comment);
//        $trx = $rpc->sendfrom($name,$address,$amount);
        
        if(isset($trx['flag']) && $trx['flag'] != 1){
//            dd($trx);
            return $trx;
        }
        \Log::info('coin send to address : '.$address);
        $res = \General::success_res('Done');
        $res['data'] = $trx;
        return $res;
//        return $trx;
    }
    
    public static function assign_balance_to_account($from_acc,$to_acc,$amount){
        $from_acc = trim($from_acc);
        $to_acc = trim($to_acc);
        $amount  = (double)$amount;
        $rpc     = new RPC();
        
//        dd($address,$amount,$comment);
//        $trx = $rpc->sendtoaddress($address,$amount,$comment);
        $trx = $rpc->move($from_acc,$to_acc,$amount);
        
        if(isset($trx['flag']) && $trx['flag'] != 1){
//            dd($trx);
            return $trx;
        }
        \Log::info('coin moved to account : '.$to_acc);
        $res = \General::success_res('Done');
        $res['data'] = $trx;
        return $res;
    }

    public static function check_transaction($trxid){
        
        $rpc     = new RPC();
        $trx = $rpc->gettransaction($trxid);
        if(isset($trx['flag']) && $trx['flag'] != 1){
            return $trx;
        }
        \Log::info('get transaction from id : '.$trxid);
        \Log::info(json_encode($trx));
        $details = $trx['details'];
        $from_address = '';
        foreach($details as $d){
            if($d['category'] == 'receive'){
                $address = trim($d['address']);
                $amount = $d['amount'];
                $user = CoinAddress::where('coin_address',$address)->first();
                if(!$user){
                    \Log::info('no user found in wallet notification.');
                    $admin = self::credit_to_admin($amount,$address,$trxid);
                    return $admin;
                }
                $user_id = $user->user_id;
                $type = '';
                $referenceCoin = CoinTransfer::where('transaction_id',$trxid)->first();
//                dd($referenceCoin);
                $referenceWithdraw = WithdrowRequest::where('transaction_id',$trxid)->first();
                if($referenceCoin){
                    $type = 2;
                    $reference_id = $referenceCoin->id;
                    $trf = 'transfer';
                }elseif($referenceWithdraw){
                    $type = 7;
                    $reference_id = $referenceWithdraw->id;
                    $trf = 'withdrawal';
                }else{
                    $trf = 'transfer';
                    \Log::info('no transaction found in db for trxid : '.$trxid);
                    $udata = [
                        'user_id'=>$user_id,
                        'type' =>2,
                        'reference_id'=>null,
                        'credit'=>$amount,
                        'debit'=>0,
                        'comment'=> $amount .' '.config('constant.COIN_SHORT_NAME').' credited for '.$trf.' request from '.$from_address.' from outside user.',
                    ];
                    $userTrans = self::credit_to_user($udata);
                    return $userTrans;
                }
                
                
                $transaction = CoinTransactions::where(['type'=>$type,'user_id'=>$user_id,'reference_id'=>$reference_id])->first();
                if($transaction){
                    \Log::info('transaction already done for trxid : '.$trxid);
                    return \General::info_res('transaction already done.');
                }
                $to_user = [
                    'user_id'   => $user_id,
                    'type'      => $type,
                    'reference_id'=> $reference_id,
                    'credit'    => $amount,
                    'debit'     => 0,
                    'comment'   => $amount .' '.config('constant.COIN_SHORT_NAME').' credited for '.$trf.' request #'.$reference_id .' from '.$from_address,
                ];

                $credit = CoinTransactions::add_transaction($to_user);
                \Log::info('in wallet notify coin credited for '.$trf.' request of transaction id : '.$trxid);
//                dd($user_id,$reference_id,$transaction,$address);
                return \General::success_res('coin credited successfully.');
                break;
            }else if($d['category'] == 'send'){
                $from_address = trim($d['address']);
            }
        }
//        echo '<pre>';
//        print_r($trx['details']);
//        echo '</pre>';
//        dd($trx);
        return \General::error_res('something might wrong.');
    }
    public static function credit_to_admin($amount,$address,$transaction_id){
        
        $data = [
            'address' =>$address,
            'amount' =>$amount,
            'transaction_id' =>$transaction_id,
        ];
        
        $check = AdminAnonymousTransfer::where('transaction_id',$transaction_id)->first();
        if($check){
            \Log::info('transaction already paid to admin.');
            return \General::info_res('transaction already paid to admin.');
        }
        
        $credit = AdminAnonymousTransfer::new_coin_transfer($data);
        \Log::info('in wallet notification. coin credited to admin');
        return \General::success_res('transaction credited to admin.');
    }
    
    public static function credit_to_user($param){
        $credit = CoinTransactions::add_transaction($param);
        \Log::info($param['credit'].' Coin credited to user #'.$param['user_id'].' which are transfered from outside user.');
        return \General::success_res();
    }
    
    public static function get_qr_for_address($address){
        $qrImg = $address.'.png';
        $dir_path = config('constant.QR_CODE_PATH');
        if(!file_exists($dir_path.$qrImg)){
            $data = \DNS2D::getBarcodePNG($address,"QRCODE",6,6);
            $data = base64_decode($data);
            file_put_contents($dir_path.$qrImg,$data);
        }
////      $imgHTML=\DNS2D::getBarcodeHTML($coin_address, "QRCODE",6,6);
        
        $res = \General::success_res();
        
//      $res['data']=$imgHTML;
        $res['data']=\URL::to('/assets/img/uploads/qr/'.$qrImg);
        return $res;
    }
    
    public static function get_next_ico_date(){
//        $nextIco = \App\Models\PreSellCalender::where('status','=',1)->where('start_date','<=',date('Y-m-d H:i:s'))
//                                            ->where('end_date','>=',date('Y-m-d H:i:s'))
//                                            ->first(); 
//        
//        
//        if(!is_null($nextIco)){
//            $soldCoin = \App\Models\CoinTransactions::where('type',1)->whereBetween('created_at', [$nextIco->start_date, $nextIco->end_date])
//                                            ->sum('credit');
//        }else{
//                $nextIco = \App\Models\CalenderIco::where('status','=',1)->where('start_date','<=',date('Y-m-d H:i:s'))
//                                            ->where('end_date','>=',date('Y-m-d H:i:s'))
//                                            ->first();
//                if(!is_null($nextIco)){
//                    $soldCoin = \App\Models\CoinTransactions::where('type',1)->whereBetween('created_at', [$nextIco->start_date, $nextIco->end_date])
//                                            ->sum('credit');
//                }
//        }
        
//        dd($soldCoin);
        $res = \General::error_res('no next calander date found');
        $icoOpen='False';
//        if(is_null($nextIco) || $soldCoin>=$nextIco->token){
            $nextIco = \App\Models\PreSellCalender::where('status',1)->where('start_date','>=',date('Y-m-d H:i:s'))->OrderBy('start_date','asc')->first();
            if(is_null($nextIco)){
                $nextIco = \App\Models\CalenderIco::where('status',1)->where('start_date','>=',date('Y-m-d H:i:s'))->OrderBy('start_date','asc')->first();
            }
            
            if(is_null($nextIco)){
//                $nextIco='Not Found';
                return $res;
            }
            else{
                $nextIco=[
                    'date'=>$nextIco->start_date,
                    'price'=>$nextIco->price,
                ];
            }
//        }else{
//            
//           $icoOpen='True';
////           $nextIco='Found';   
//           $nextIco=$nextIco->start_date;   
//           
//        }
            $res= \General::success_res();
            $res['data']=$nextIco;
        return $res;
    }
}
