<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Lib\coin\jsonRPCClient;
use Symfony\Component\HttpFoundation\Request;


class GeneralController extends Controller {

    public function __construct() {
        
    }
    
    public function getTest(){
        $client = new \App\Lib\coin\jsonRPCClient();
//        $r = $client->getbalance();
        $r = $client->getnewaddress('test_2');
//        $r = $client->getaddressesbyaccount('a_0');
        dd('Success',$r);
    }
    
    public function getCalculateLendingInterest(){
        $now = date('Y-m-d');
        
        $to_date = date('d');
        
        $invest_list = \App\Models\InvestRecord::active()->with('plan')->get()->toArray();
        
        $rate = \App\Models\InterestPerDay::where('day',$to_date)->
                                            first()->toArray();
        
        $rate = $rate['val'] == '' ? 0 : $rate['val'];
        
        foreach($invest_list as $key=>$invest){
            
//            $check = \App\Models\BalanceTransactions::where([
//                                                                'type'          => 7,
            $check = \App\Models\EarningHistory::where([
                                                                'type'          => 1,
                                                                'reference_id'  => $invest['id'],
                                                                'user_id'       => $invest['user_id'],
                                                        ])
                                                        ->whereDate('created_at', '=', $now)
                                                        ->first();

            if(is_null($check)){
                $reward         = $invest['amount'] * $rate / 100;
                $benefit_rate   = $invest['plan']['extra_benefit'];
                $extra_benefit  = $invest['amount'] * $benefit_rate  / 100;
                
                $inv_return_data = [
                    'user_id'   => $invest['user_id'],
//                    'type'      => 7,
                    'type'      => 1,
                    'reference_id'=> $invest['id'],
                    'credit'    => $reward,
                    'debit'     => 0,
                    'comment'   => $reward.' USD Reward for Invest of '.$invest['amount'].' USD',
                    'interest_rate'=> $rate
                ];
                
//                $inv_return = \App\Models\BalanceTransactions::add_transaction($inv_return_data);
                $inv_return = \App\Models\EarningHistory::add_transaction($inv_return_data);
                
                $child = \App\Models\Users::where('id',$invest['user_id'])->first()->toArray();
                $setting = app('settings');
                $referel = \App\Models\General::usd_to_parent($child['parent_id'], $inv_return, $reward, $setting, 'lending');
                
                if($extra_benefit > 0){
                    $benefit_return_data = [
                        'user_id'   => $invest['user_id'],
//                        'type'      => 7,
                        'type'      => 1,
                        'reference_id'=> $invest['id'],
                        'credit'    => $extra_benefit,
                        'debit'     => 0,
                        'comment'   => $extra_benefit.' USD extra benefit for Invest of '.$invest['amount'].' USD',
                        'interest_rate'=> $benefit_rate
                    ];
                
//                    $benefit_return = \App\Models\BalanceTransactions::add_transaction($benefit_return_data);
                    $benefit_return = \App\Models\EarningHistory::add_transaction($benefit_return_data);
                }
                
//                $user = \App\Models\Users::where('id',$invest['user_id'])->first();
//                $user->earning_balance = $user->earning_balance + $reward + $extra_benefit;
//                $user->save();
            }
        }
        
        $list = \App\Models\InvestRecord::active()->with('plan')
                                          ->where('end_date','<',$now)
                                          ->get()->toArray();
        
//        dd($list);
        foreach($list as $inv){
            
            $release_data = [
                'user_id'   =>$inv['user_id'],
                'type'      =>8,
                'reference_id'=>$inv['id'],
                'credit'    =>$inv['amount'],
                'debit'     =>0,
                'comment'   =>$inv['amount'].' USD Released From Invest of '.$inv['amount'].' USD',
            ];
            
            $release = \App\Models\BalanceTransactions::add_transaction($release_data);
            
        }
        
        $update_status = \App\Models\InvestRecord::active()
                                                ->where('end_date','<',$now)
                                                ->update(['status'=>0]);
                      
        
                                                
//        dd($now,$invest_list,$to_date,$rate);
        $res = \General::success_res('Return Broadcast Completed.');
        return $res;
    }
    
    public function getCalculateStakingInterest(){
        $now = date('Y-m-d');
        
        $list = \App\Models\StackRecord::active()->with('plan')
                                          ->where('end_date','<',$now)
                                          ->get()->toArray();
        
//        dd($list);
        foreach($list as $inv){
            
            $reward         = $inv['amount'] * $inv['percentage'] / 100;
            
            $inv_return_data = [
                'user_id'   => $inv['user_id'],
                'type'      => 5,
                'reference_id'=> $inv['id'],
                'credit'    => $reward,
                'debit'     => 0,
                'comment'   => $reward.' '.config('constant.COIN_SHORT_NAME').' Reward for Stacking Investment of '.$inv['amount'].' '.config('constant.COIN_SHORT_NAME'),
            ];
                
            $inv_return = \App\Models\CoinTransactions::add_transaction($inv_return_data);
            
            $release_data = [
                'user_id'   => $inv['user_id'],
                'type'      => 6,
                'reference_id'=> $inv['id'],
                'credit'    => $inv['amount'],
                'debit'     => 0,
                'comment'   => $inv['amount'].' '.config('constant.COIN_SHORT_NAME').' Released From Stacking Investment of '.$inv['amount'].' '.config('constant.COIN_SHORT_NAME'),
            ];
            
            $release = \App\Models\CoinTransactions::add_transaction($release_data);
            
        }
        
        $update_status = \App\Models\StackRecord::active()
                                                ->where('end_date','<',$now)
                                                ->update(['status'=>0]);
                      
        
                                                
//        dd($now,$invest_list,$to_date,$rate);
        $res = \General::success_res('Stacking Return Process Completed.');
        return $res;
    }
    
    /*public function getGenerateCoinAddress($offset = 0,$len = 0){
        if($len == 0){
            return 'please specify valid user length to regenerate coin address';
        }
        if($offset > 0){
            $offset = $offset -1 ;
        }
        $users = \App\Models\Users::take($len)->skip($offset)->get()->toArray();
        if(count($users) > 0){
            foreach($users as $u){
                $email = explode('@',$u['email']);
                $name = $email[0].'_'.$u['id'];
//                $address = \App\Models\General::generate_coin_address($name);
                $address = \App\Models\General::generate_coin_address();
                $user = \App\Models\Users::where('id',$u['id'])->first();//update(['coin_address'=>$address]);
                $user->coin_address = trim($address);
                if($user->coin_account_name == ''){
                    $user->coin_account_name = $name;
                }
                $user->save();
                $data = [
                    'user_id'=>$u['id'],
                    'coin_address'=>trim($address),
                ];
                
                \App\Models\CoinAddress::new_coin_address($data);
            }
            return $len.' users coin address generated successfully.';
        }else{
            return 'no users found';
        }
    } */
    
    /*public function getGenerateCoinAccount($offset = 0,$len = 0){
        /* this function was created for changing wallet address of all given length user with associated account name * /
        if($len == 0){
            return 'please specify valid user length to regenerate coin address';
        }
        if($offset > 0){
            $offset = $offset -1 ;
        }
        $c = 0;
        $users = \App\Models\Users::take($len)->skip($offset)->get()->toArray();
        if(count($users) > 0){
            foreach($users as $u){
                $c++;
                $email = explode('@',$u['email']);
                $name = $email[0].'_'.$u['id'];
//                $address = \App\Models\General::generate_coin_address($name);
//                $address = \App\Models\General::generate_coin_address();
                $address = \App\Models\General::generate_coin_account($name);
                $user = \App\Models\Users::where('id',$u['id'])->first();//update(['coin_address'=>$address]);
                $user->coin_address = trim($address);
                if($user->coin_account_name == ''){
                    $user->coin_account_name = $name;
                }
                $user->save();
                $data = [
                    'user_id'=>$u['id'],
                    'coin_address'=>trim($address),
                ];
                
                \App\Models\CoinAddress::new_coin_address($data);
            }
            return $c.' users coin address generated successfully.';
        }else{
            return 'no users found';
        }
    } */
    
    public function getChangeCoinAddress($user_id = ''){
        /* this function was created for changing wallet address of user which is associated with its account name */
        if($user_id == ''){
            return 'please specify user id to change coin address.';
        }
        
        $user = \App\Models\Users::where('id',$user_id)->first();
        if(!$user){
            return 'no user found.';
        }else{
            $email = explode('@',$user['email']);
            $name = $email[0].'_'.$user['id'];
//            $address = \App\Models\General::generate_coin_address($name);
//            $address = \App\Models\General::generate_coin_address();
            $address = \App\Models\General::generate_coin_account($name);
            $user->coin_address = trim($address);
            if($user->coin_account_name == ''){
                $user->coin_account_name = $name;
            }
            $user->save();
            
            $data = [
                    'user_id'=>$user_id,
                    'coin_address'=>trim($address),
                ];
                
                \App\Models\CoinAddress::new_coin_address($data);
                
                return 'coin address changed successfully.';
        }
        
    }
    
    public function getProcessTransaction($trxid = '',$test = ''){
        /* this function was created for receiving wallet notification and capture that transaction to database for further use of approve tranasction */
        if($trxid == ''){
            return 'transaction id is required.';
        }
        if($test != ''){
            \Log::info('wallet notification recieved and parameters are  : '.$test);
        }
        \Log::info('wallet notification recieved for transaction id : '.$trxid);
//        echo $trxid;
        $trxid = trim($trxid);
//        $check = \App\Models\General::check_transaction($trxid);
        $param = [
            'transaction_id' => $trxid,
        ];
        
        $check = \App\Models\WalletNotifications::get_notification($param);
        if($check['flag'] == 1){
            \Log::info('transaction id already exist');
            return \General::error_res('transaction exist.');
        }
        
        $new = \App\Models\WalletNotifications::new_notification($param);
        return $new;
    }
    
    public function getApproveTransaction(){
        /* this funcion was created for approving that transaction which are generated using rpc commands and received from walletnotify.*/
        $all = \App\Models\WalletNotifications::where('status',0)->get()->toArray();
        \Log::info('approve transaction called.');
        $c = 0;
        if(count($all) > 0){
            foreach($all as $a){
                $trxid = $a['transaction_id'];
                $approve = \App\Models\General::check_transaction($trxid);
                if($approve['flag'] == 1 || $approve['flag'] == 3){
                    $c++;
                    $upd = \App\Models\WalletNotifications::where('transaction_id',$trxid)->first();
                    $upd->status = 1;
                    $upd->save();
                }
            }
        }
        \Log::info('total '.$c.' transaction approved.');
        return \General::success_res('total '.$c.' transaction approved.');
    }
    
    /*public function getAllocateBalanceToAccount($offset = 0,$len = 0){
        /* this function was created for assigning balance to all node user accounts from node admin account as per user have mysql db balance.  * /
        if($len == 0){
            return 'please specify valid user length to allocate balance';
        }
        if($offset > 0){
            $offset = $offset -1 ;
        }
        $c = 0;
        $users = \App\Models\Users::take($len)->skip($offset)->get()->toArray();
        if(count($users) > 0){
            foreach($users as $u){
                $to_acc= trim($u['coin_account_name']);
                $address = trim($u['coin_address']);
                $from_acc = 'admin';
                $amount = $u['coin'];
                if($amount > 0){
                    $c++;    
                    $trx_id = \App\Models\General::assign_balance_to_account($from_acc,$to_acc, $amount);
                }
            }
            return $c.' users balance allocated successfully.';
        }else{
            return 'no users found';
        }
    }*/
    
    /*public function getAllocateBalanceToAdmin($bal = 0){
        /* this function was created for assign balance to admin account in node account * /
        $client = new \App\Lib\coin\jsonRPCClient();
        $adminbal = $client->getbalance('');
        if($bal <= 0 ){
            return 'please enter valid coin balance.available coin balance is : '.$adminbal;
        }
        $bal = (double) $bal;
        $address = $client->getaccountaddress('admin');
//        $send = $client->sendtoaddress($address,$bal);
        $send = $client->move('','admin',$bal);
//        dd($send);
        if(!isset($send['flag'])){
            $adm = \App\Models\Admin\User::where('id', config('constant.DEFAULT_ADMIN_ID'))->first();
            if($adm){
                $adm->wallet_address = $address;
                $adm->save();
            }
//            echo 'trxid : '.$send.'<br>';
        }else{
            dd($send,$address,$bal);
        }
        return $bal.' coin balance transfered to admin';
    }*/
    
    public function getTestGenerateAddress(){
        $client = new \App\Lib\coin\jsonRPCClient();
//        $r = $client->getaccountaddress();
//        $t = $client->getaccountaddress('hello');
//        $r = $client->getnewaddress('a_0');
//        $t = $client->getnewaddress();
        //LMxzhQjpvvNeSraRVAw5yfVMJ7Pafods25
        //Le59VcEnojDcRnEX2pv7Uy8ru3DCbimvFo
//        $r = $client->getaccount('LMjuExfTZzGp5GAPbufnrzZBXzkDRRJ7FJ');
//        $t = $client->getaccount('LcuJSCwBUHCpjh5Nf3AL64u81FdP3MC6tG');
//        $t = $client->getaccount('LMjuExfTZzGp5GAPbufnrzZBXzkDRRJ7FJ');
//        $t = $client->getaccount('LggSqJ3FrDHrnK5ByUSti2QCNPcWVTs7v7');
//        $t = $client->getaccount('NwjZTha31EYutmD36eHLbsf7UHnpCgXpt7');
//        $t = \App\Models\General::generate_coin_address('hello');
//        $t = \App\Models\General::generate_transaction_id('LZex452sh8sMqSqguEyfrdn9Vxab8bLd4M',10000.5);
//        $t = \App\Models\General::generate_transaction_id($id,8);
//        $t = \App\Models\General::generate_transaction_id('','LMjuExfTZzGp5GAPbufnrzZBXzkDRRJ7FJ',0.001);
//        $t = \App\Models\General::generate_transaction_id('LZex452sh8sMqSqguEyfrdn9Vxab8bLd4M',1);
//        trxid = 0bd7363cf117963867cd7e0d9f7769d36372ebbe58971825ede70179072ebf04
        
//        $t = $client->gettransaction('97c6d5e281ae57492e71308e334f706a8ab03f49e22ca849ebd0cb7d6281444c');
//        $t = $client->gettransaction('0bfb838970e6061d54c659b21a514fe4937b9adfcf1d9a8ebfd3c3809d4c906a');
//        $t = $client->gettransaction('595f0883861bbc7385585ee73026e49ecd61e36f9e84c70eb323b8c5cf74fa8d');
//        $t = $client->gettransaction('21452372a095369a57cc044b4342f6d92323032a64c916dc8b7af4cb20b40186');
//        $t = $client->gettransaction('388e261126124f5704b4099e593021a1cfd4931cdb4a49f9c5f1debd4491f1f2');
//        $t = $client->gettransaction('309acb11ba9a67491c8f1d529cfdbf38a118e7cc3cc1d7f65f06e08fc2d605af');
//        $t = $client->getaddressesbyaccount('');
//        $t = $client->getaddressesbyaccount('a_0');
//        $t = $client->getbalance('hello');//21.998
//        $t1 = $client->getbalance('test_2');//20.1
//        $t = $client->getaccount('P3dEPJ9AgepGSbjvFxXDB1MySDnM4LBL1P');
//        $t = $client->getbalance();
//        $t = $client->getmininginfo();
        $t = $client->listaccounts();
//        $t = $client->sendtoaddress('P6ZZZiJ1xX4XiTN5fa6tC1ooG3Dskeyn8K',4);
//        $t = $client->move('test_main','testmain',0.01);
//        $t = $client->move('','testmain',1);
//        $t = $this->get_all_acccounts_with_address();
//        $t = $client->sendfrom('testmain','LciRhpPcs4Ldy8Lo4T856Q7jU2XPztCGj1',1);//97c6d5e281ae57492e71308e334f706a8ab03f49e22ca849ebd0cb7d6281444c
//        $t = $client->listaddressgroupings();
//        $t = $client->listtransactions();
//        $t = $client->listtransactions('hello',120,0);
//        $t = $client->getwalletinfo();
//        dd($acc,$id);
//        $t = $client->sendfrom(trim('test_main'),trim($id),30);
//        $t = $client->sendfrom(trim($acc),trim($id),8);
//        dd($t,$t1);
        dd($t);
        //109.93
    }
    
    public function get_all_acccounts_with_address(){
        $client = new \App\Lib\coin\jsonRPCClient();
        $t = $client->listaccounts();
        foreach($t as $k=>$v){
            echo $k;
            $l = $client->getaddressesbyaccount($k);
            echo '<pre>';
            print_r($l);
            echo '</pre>';
        }
    }
    
    public function getMakeTrade(){
        $param = \Request::all();
        $trade_type= -1;
        if(isset($param['trade_type']) && $param['trade_type'] == 0 ){
            $trade_type = 0;
        }
        else if(isset($param['trade_type']) && $param['trade_type'] == 1){
            $trade_type = 1;
        }
        
        if($trade_type == -1){
            return \General::error_res('trade type not set.');
        }
        
        if($trade_type == 0){
            $buy = \App\Models\TradeHistory::buy_trade();
        }
        
    }
    
    public function getClearCache(){
        $exitCode = \Artisan::call('cache:clear');
        dd($exitCode);
    }

    public function getNextIcoStart(){
        $ico = \App\Models\General::get_next_ico_date();
        if($ico['flag']!= 1){
            return \General::error_res('no next date found');
        }
//        dd($ico);
//        $buy_ico = strtotime($ico)-time();
        $buy_ico = strtotime($ico['data']['date']);
        
        $res = \General::success_res();
        $res['data'] = [
            'date'=>$buy_ico,
            'date_formated'=>$ico['data']['date'],
            'price'=>$ico['data']['price'],
        ];
        return $res;
    }
    
    public function getHashedPass(){
        $list = \App\Models\Users::orderBy('id','desc')->get()->toArray();
        
        $res = \General::error_res('Already Hashed');
        
        if(env('HASH_PASS')){
            foreach($list as $u){
                $i = \App\Models\Users::where('id',$u['id'])
                                        ->update(['password'=>\Hash::make($u['password'])]);
            }
            $list = \App\Models\Users::orderBy('id','desc')->get()->toArray();
            $res = \General::error_res('Hashing Complete');
        }
        
        return \Response::json($res,200);
    }
}