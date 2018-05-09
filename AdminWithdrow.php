<?php 

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AdminWithdrow extends Model {
    protected $fillable = [
        'status', 'user_id', 'amount','coin_type','note'
    ];
    protected $table    = 'admin_withdrow';
    protected $hidden   = [];
    public $timestamps  = true;
    
    
    public static function add_request($param){
//        dd(app('settings')['btc_auto_withdraw']);
        $d = new self;
        $d->status  = 0;
        $d->amount  = $param['amount'];
        $d->note    = $param['comment'];
        $d->address = $param['address'];
        
        $d->save();
        
        if($param['amount'] > \General::admin_balance()){
            $res = \General::error_res('Insufficient Balance.');
            return \Response::json($res,200);
        }
        
        $data = [
            "address" => $param['address'],
            "amount"  => $param['amount'],
        ];

        $obj = new \App\Lib\PayGateLib();
        $tr  = $obj::generate_transfer($param);
        
        if(isset($tr['flag']) && $tr['flag'] != 0){
            $d->status  = 1;
            $d->transaction_id = $tr['tx_id'];
            $d->save();

//            $data = [
//                'user_id'   => $param['user_id'],
//                'type'      => 2,
//                'reference_id'=> null,
//                'credit'    => 0,
//                'debit'     => $param['amount'],
//                'txn_type'  => 2,
//                'txn_address'=> $tr['tx_id'],
//                'comment'   => $param['amount'].' debited for withdrawal request #'.$d->id,
//            ];
//
//
//            $trans = BtcTransactions::add_transaction($data);
        }else{
            return $tr;
        }
        
        $res = \General::success_res();
        $res['data'] = $d->toArray();
        return $res;
    }
    
    
    
    
    
     public static function filter_withdrawal_report($param){
        
        $report = self::orderBy('id','desc');
        
        if(isset($param['status']) && $param['status'] != ''){
            $report = $report->where('status',$param['status']);
        }
        if(isset($param['start_date']) && $param['start_date'] != '' && isset($param['end_date']) && $param['end_date'] != ''){
            $report = $report->whereBetween('created_at', array($param['start_date'], $param['end_date']));
        }
        
        $count  = $report->count();
        
        $len    = $param['itemPerPage'];
        $start  = ($param['currentPage'] - 1) * $len;
        
        $report = $report->skip($start)->take($len)->get()->toArray();
        $res['data'] = $report;
        $res['total_record'] = $count;
        
        return $res;
    }
    
     
}
