<?php 

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AdminAnonymousTransfer extends Model {
    protected $fillable = [
        'address','amount','transaction_id'
    ];
    protected $table = 'admin_anonymous_transfer';
    protected $hidden = [];
    public $timestamps=true;
    
    public static function new_coin_transfer($param){
        
        $ct = new self;
        $ct->address   = $param['address'];
        $ct->amount         = $param['amount'];
        $ct->transaction_id = $param['transaction_id'];
        $ct->save();
        
        $param['reference_id']  = $ct->id;
        $param['amount']        = $ct->amount;
        
        $trx_fee = app('settings')['token_transaction_fee'];
        
        
            $to_user = [
                'user_id'   => null,
                'admin_id'   => config('constant.DEFAULT_ADMIN_ID'),
                'type'      => 3,
                'reference_id'=> $ct->id,
                'credit'    => $ct->amount,
                'debit'     => 0,
                'comment'   => $ct->amount.' '.config('constant.COIN_SHORT_NAME').' credited for anonymous transfer request #'.$ct->id .' for '.$param['address'],
            ];
            
            $credit = Admin\AdminCoinTransactions::add_transaction($to_user);
        
        return \General::success_res('Coin Transferred Successfully.');
    }
    
    public static function get_transfer_list($param){
//        dd($param);
        // DB::enableQueryLog();
        $count=self::orderBy('id','desc');
        $address = [];
        if(isset($param['loggerId'])){
            $address = CoinAddress::where('user_id',$param['loggerId'])->pluck('coin_address')->toArray();
            if(count($address) > 0){
                $count = $count->whereIn('from_address',$address);
            }else{
                $count = $count->where('from_address','');
            }
        }
        
        $count = $count->count();
//         dd($count);
//        $query = DB::getQueryLog();
//        $query = end($query);
//        dd($query);

        $page   = $param['crnt'];
        $len    = $param['len'];
        $op     = isset($param['opr'])?$param['opr']:'';
        $total_page = ceil($count/$len);
        $flag   = 1;
        
        $start  = 0;
        
        if($op != ''){
            if($op == 'first'){
                $crnt_page = 1;
                $start = ( $crnt_page - 1 ) * $len;
            }
            
            elseif( $op == 'prev' ){
                $crnt_page = $page - 1;
                if( $crnt_page <= 0){
                    $crnt_page = 1;
                }
                $start = ( $crnt_page - 1 ) * $len;
            }

            elseif($op == 'next'){
                $crnt_page = $page + 1;
                if($crnt_page >= $total_page){
                    $crnt_page = $total_page;
                }
                $start = ( $crnt_page - 1 ) * $len;
            }

            else{
                $crnt_page  = $total_page;
                $start      = ($crnt_page-1)*$len;
            }
        }

        else{
            if($page > $total_page){
//                $flag=0;
                
                $crnt_page  = $page-1;
                $start      = ($crnt_page-1)*$len;
            }
            else{
                
                $crnt_page  = $page;
                $start      = ($crnt_page-1)*$len;
            }
        }
        
        
        $transferReport = self::skip($start)->take($len)->orderBy('id','desc');
        if(isset($param['loggerId'])){
            if(count($address) > 0){
                $transferReport = $transferReport->whereIn('from_address',$address);
            }else{
                $transferReport = $transferReport->where('from_address','');
            }
        }
        $transferReport = $transferReport->get()->toArray();
       
        $res['len']     = $len;
        $res['crnt_page']   = $crnt_page;
        $res['total_page']  = $total_page;
        
        $res['result']  = $transferReport;
        $res['flag']    = $flag;
//        dd($res);
        return $res;
    }
}
