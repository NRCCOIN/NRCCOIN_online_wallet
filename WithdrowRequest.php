<?php 

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class WithdrowRequest extends Model {
    protected $fillable = [
        'status', 'user_id', 'amount','coin_type','note'
    ];
    protected $table = 'withdrow_request';
    protected $hidden = [];
    public $timestamps=true;
    
    
     public function user(){
        return $this->hasOne('App\Models\Users','id','user_id');
    }
    public static function get_request_filter($param){
//        dd($param);
        $count=self::where('user_id',$param['loggerId'])->orderBy('id','asc');
       
        
        if(isset($param['credit']) && $param['credit']!='') {
           
           if($param['creditSL'] == 'eq'){
                $count=$count->where('credit','=',$param['credit']);
           }
           else if($param['creditSL'] == 'lt'){
                $count=$count->where('credit','<',$param['credit']);
           }
           else if($param['creditSL'] == 'gt'){
                $count=$count->where('credit','>',$param['credit']);
           }
        }
        
        if(isset($param['debit']) && $param['debit']!='') {
           
           if($param['debitSL'] == 'eq'){
                $count=$count->where('debit','=',$param['debit']);
           }
           else if($param['debitSL'] == 'lt'){
                $count=$count->where('debit','<',$param['debit']);
           }
           else if($param['debitSL'] == 'gt'){
                $count=$count->where('debit','>',$param['debit']);
           }
        }
        
        if(isset($param['startd']) && $param['startd']!='') {
           
                $count=$count->where('created_at','>=',$param['startd']);
        }
        
        if(isset($param['endd']) && $param['endd']!='') {
           
                $count=$count->where('created_at','<=',$param['endd']);
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
        
        
        $tokenReport=self::where('user_id',$param['loggerId'])->skip($start)->take($len)->orderBy('id','desc');
        
        
        
        if(isset($param['credit']) && $param['credit']!='') {
           
           if($param['creditSL'] == 'eq'){
                $tokenReport=$tokenReport->where('credit','=',$param['credit']);
           }
           else if($param['creditSL'] == 'lt'){
                $tokenReport=$tokenReport->where('credit','<',$param['credit']);
           }
           else if($param['creditSL'] == 'gt'){
                $tokenReport=$tokenReport->where('credit','>',$param['credit']);
           }
        }
        
        
        if(isset($param['debit']) && $param['debit']!='') {
           
           if($param['debitSL'] == 'eq'){
                $tokenReport=$tokenReport->where('debit','=',$param['debit']);
           }
           else if($param['debitSL'] == 'lt'){
                $tokenReport=$tokenReport->where('debit','<',$param['debit']);
           }
           else if($param['debitSL'] == 'gt'){
                $tokenReport=$tokenReport->where('debit','>',$param['debit']);
           }
        }
        
        if(isset($param['startd']) && $param['startd']!='') {
           
           
                $tokenReport=$tokenReport->where('created_at','>=',$param['startd']);
           
        }
        
        if(isset($param['endd']) && $param['endd']!='') {
           
           
                $tokenReport=$tokenReport->where('created_at','<=',$param['endd']);
           
        }

        $tokenReport = $tokenReport->get()->toArray();
       
        $res['len']=$len;
        $res['crnt_page']=$crnt_page;
        $res['total_page']=$total_page;
        
        $res['result']=$tokenReport;
        $res['flag']=$flag;

        return $res;
    }
    
    
    public static function add_new_request($param){
//        dd(app('settings')['btc_auto_withdraw']);
        $d = new self;
        $d->status  = 0;
        $d->user_id = $param['user_id'];
        $d->amount  = $param['amount'];
        $d->note    = isset($param['note'])?$param['note']:'';
        $d->address = $param['address'];
        $d->coin_type = $param['coin_type'];
        
        $d->save();
        $res = \General::success_res();
        
        if($param['coin_type'] == 1){
            $res['msg'] = 'we received your request. we will proccess on it soon.';
            $trx_fee    = app('settings')['token_transaction_fee'];
            $d->amount  += $trx_fee;
            if($param['auto_coin'] == 1){
                $res['msg'] = 'your request has been processed successfully.';
                $d->status  = 1;
                $d->transaction_id = $param['trx_id'];
            }else{
                $users = Users::where('id',$param['user_id'])->first();
                $users->coin = $users->coin - $d->amount;
                $users->save();
                

            }
            $d->save();
             // Deduct from sender account        
            $users = Users::where('coin_address',$param['address'])->first();
            $users->coin = $users->coin + $d->amount;
            $users->save();
 
            
//            $users = Users::where('id',$param['user_id'])->first();
//            $users->coin = $users->coin - $d->amount;
//            $users->save();
        }else if($param['coin_type'] == 2){
            $users = Users::where('id',$param['user_id'])->first();
            $users->balance = $users->balance - $d->amount;
            $users->save();
        }else if($param['coin_type'] == 3){
            $res['msg'] = 'we received your request. we will proccess on it soon.';
            $trx_fee    = app('settings')['btc_transaction_fee'];
            $d->amount  += $trx_fee;
            $d->save();
            if(app('settings')['btc_auto_withdraw']){
                $res['msg'] = 'your request has been processed successfully.';
                $data = [
                    "address" => $param['address'],
                    "amount"  => $param['amount'],
                ];
                
                $obj = new \App\Lib\PayGateLib();
                $tr = $obj::generate_transfer($param);
                
//                dd($tr);
                if(isset($tr['flag']) && $tr['flag'] != 0){
                    $d->status  = 1;
                    $d->transaction_id = $tr['tx_id'];
                    $d->save();

                    $data = [
                        'user_id'   => $param['user_id'],
                        'type'      => 2,
                        'reference_id'=>null,
                        'credit'    => 0,
                        'debit'     => $d->amount,
                        'txn_type'  => 2,
                        'txn_address'=> $tr['tx_id'],
                        'comment'   => $d->amount.' debited for withdrawal request #'.$d->id,
                    ];


                    $trans = BtcTransactions::add_transaction($data);
                }else{
                    return $tr;
                }
                
                
                
            }else{
                $users = Users::where('id',$param['user_id'])->first();
                $users->btc_balance = $users->btc_balance - $d->amount;
                $users->save();
            }
            
        }
        
//        $res = \General::success_res();
        $res['data'] = $d->toArray();
        return $res;
    }
    
    public static function add_new_trade($param){
//        dd(app('settings')['btc_auto_withdraw']);
        $d = new self;
        $d->status  = 0;
        $d->user_id = $param['user_id'];
        $d->amount  = $param['tr_amount'];
        $d->note    = isset($param['tr_note'])?$param['tr_note']:'';
        $d->address = $param['tr_address'];
        $d->coin_type = $param['tr_coin_type'];
       // $d->transaction_id = $trx_id['data'];

        $d->save();
        $res = \General::success_res();
        
        if($param['tr_coin_type'] == 4){
            $res['msg'] = 'we received your request. we will proccess on it soon.';
            //$trx_fee    = app('settings')['token_transaction_fee'];
            //$d->amount  += $trx_fee;
          
            $users = Users::where('id',$param['user_id'])->first();
            $users->tr_balance = $users->tr_balance - $d->amount;
            $users->save();
                

           // $d->save();
             // Deduct from sender account        
 
            
        }
        
           // $users = Users::where('tr_address',$param['tr_address'])->first();
           // $users->tr_balance = $users->tr_balance + $d->amount;
           // $users->save();
  
           // $users = Users::where('id',$param['user_id'])->first();
           // $users->tr_balance = $users->tr_balance - $d->amount;
           // $users->save();

        
        
        
//        $res = \General::success_res();
        $res['data'] = $d->toArray();
        return $res;
    }
    
    
    
    
    
     public static function filter_withdrawal_report($param){
        
        $report = self::orderBy('id','desc');
        if(isset($param['user_id']) && $param['user_id'] != ''){
            $report = $report->where('user_id',$param['user_id']);
        }
        if(isset($param['status']) && $param['status'] != ''){
            $report = $report->where('status',$param['status']);
        }
        if(isset($param['start_date']) && $param['start_date'] != '' && isset($param['end_date']) && $param['end_date'] != ''){
            $report = $report->whereBetween('created_at', array($param['start_date'], $param['end_date']));
        }
        
        $count = $report->count();
        
        $len = $param['itemPerPage'];
        $start = ($param['currentPage']-1) * $len;
        
        $report = $report->with('user')->skip($start)->take($len)->get()->toArray();
        $res['data'] = $report;
        $res['total_record'] = $count;
        
        return $res;
    }
    
    public static function process_request($param){
        $id     = $param['id'];
        $type   = $param['type'];
        $trxid  = $param['cnf_trxid'];
        
        $req = self::where('id',$id)->first();
        if(!$req){
            return \General::error_res('No withdrawal request found.');
        }
        
        if($type == 'A'){
            
            $user = Users::where('id',$req->user_id)->first();
            if(!$user){
                return \General::error_res('no user found.');
            }
            
            if($req->coin_type==1){
                if($req->amount > $user->coin){
                    return \General::error_res('No sufficient balance to withdraw.');
                }
            }else if($req->coin_type==2){
                if($req->amount > $user->balance){
                    return \General::error_res('No sufficient balance to withdraw.');
                }
            }else if($req->coin_type == 3){
                if($req->amount > $user->btc_balance){
                    return \General::error_res('No Sufficient Balance to Withdraw.');
            }
            
                if($trxid == ''){
                    return \General::error_res('Enter Valid Transaction ID.');
                }
            }
            
            $req->status = 1;
            $req->transaction_id= $trxid;
            $req->save();
            
            $data = [
                'user_id'   => $req->user_id,
                'type'      => 0,
                'reference_id'=>null,
                'credit'    => 0,
                'debit'     => $req->amount,
                
                'comment'   => $req->amount.' debited for withdrawal request #'.$req->id,
            ];
            
            if($req->coin_type==1){
                $account_name = $user->coin_account_name;
                $trx_id = \App\Models\General::generate_transaction_id($account_name, $req->address, $req->amount);

                if ($trx_id['flag'] != 1) {
                    $req->status = 0;
                    $req->save();
                    return \Response::json($trx_id, 200);
                }else{
                    $req->status = 1;
                    $req->transaction_id= $trx_id['data'];
                    $req->save();
                }
                $users = Users::where('id',$req->user_id)->first();
                $users->coin = $users->coin + $req->amount;
                $users->save();
                $trans = CoinTransactions::add_transaction($data);
            }
            else if($req->coin_type == 2){
            
                $users = Users::where('id',$req->user_id)->first();
                $users->balance = $users->balance + $req->amount;
               $users->save();
                $trans = BalanceTransactions::add_transaction($data);
            }
            else if($req->coin_type == 3){
            
                $users = Users::where('id',$req->user_id)->first();
                $users->btc_balance = $users->btc_balance + $req->amount;
                $users->save();
                $data['type']       = 2;
                $data['txn_type']   = 2;
                $data['txn_address']   = $trxid;
                $trans = BtcTransactions::add_transaction($data);
            }
            
            return \General::success_res('request approved successfully.');

            }   
            elseif($type == 'R'){
                $req->status = 2;
                $req->transaction_id= $trxid;            
                $req->save();
            
                if($req->coin_type==1){
                    $users = Users::where('id',$req->user_id)->first();
                    $users->coin = $users->coin + $req->amount;
                    $users->save();
                    //$trans = CoinTransactions::add_transaction($data);
                }
                else if($req->coin_type==2){

                    $users = Users::where('id',$req->user_id)->first();
                    $users->balance = $users->balance + $req->amount;
                    $users->save();
                    //$trans = BalanceTransactions::add_transaction($data);
                }
            
            return \General::success_res('request declined successfully.');
        }
        
        return \General::error_res('please select proper request type to process.');
    }
     
}
