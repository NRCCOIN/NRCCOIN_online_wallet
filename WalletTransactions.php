<?php 

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use DB;

class WalletTransactions extends Model {
    protected $fillable = [
        'user_id','type','reference_id','txn_address','coinType','credit','debit','comment'
    ];
    protected $table = 'wallet_transactions';
    protected $hidden = [];
    public $timestamps=true;
    
   
     public static function add_new_request($param){
        $d = new self;
        $d->status = 0;
        $d->user_id = $param['user_id'];
        $d->amount = $param['amount'];
        $d->note = $param['note'];
        $d->coin_type = $param['coin_type'];
        
        $d->save();
        
        $res = \General::success_res();
        return $res;
    }
    
    public static function add_order($param){
        
        $ord = new self();
        $ord->user_id = isset($param['user_id']) ? $param['user_id'] : '';
        $ord->type =2;
        $ord->txn_address = $param['address'];
        $ord->debit = $param['amount'];
        $ord->txn_type = $param['txnType'];
        $ord->coin_type = $param['coinType'];
        $ord->save();
        
        return \General::success_res();
    }
    
    public static function get_wallet_transaction_report($param){
//        dd($param);
        DB::enableQueryLog();
        $count=self::where('user_id',$param['loggerId'])->orderBy('id','desc');

        if(isset($param['select-type']) && $param['select-type']!=''){
            $count=$count->where('txn_type',$param['select-type']);
        }
        if(isset($param['select-coin']) && $param['select-coin']!=''){
                $count=$count->where('coin_type',$param['select-coin']);
        }
       
        $count = $count->count();
        
        $page=$param['crnt'];
        $len=$param['len'];
        $op=$param['opr'];
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

        

        if(isset($param['select-type']) && $param['select-type']!=''){
            $tokenReport=$tokenReport->where('txn_type',$param['select-type']);
        }
        
        if(isset($param['select-coin']) && $param['select-coin']!=''){
                $tokenReport=$tokenReport->where('coin_type',$param['select-coin']);
        }
        $tokenReport = $tokenReport->get()->toArray();
//        $query = DB::getQueryLog();
//        $query = end($query);
//        dd($query);
//        dd($tokenReport);
//        dd($district);
        $res['len']=$len;
        $res['crnt_page']=$crnt_page;
        $res['total_page']=$total_page;
        
        $res['result']=$tokenReport;
        $res['flag']=$flag;
        return $res;
    }
    
    
}
