<?php 

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use DB;

class EthTransactions extends Model {
    protected $fillable = [
        'user_id','type','reference_id','txn_address','coin_type','credit','debit','comment'
    ];
    protected $table = 'eth_transactions';
    protected $hidden = [];
    public $timestamps=true;
    
    
    public function user(){
        return $this->hasOne('App\Models\Users','id','user_id');
    }
   
    public static function add_transaction($param){
        $trn = new self;
        $trn->user_id = $param['user_id'];
        $trn->type = $param['type'];
        $trn->reference_id = $param['reference_id'];
        $trn->credit = $param['credit'];
        $trn->debit = $param['debit'];
        $trn->comment = $param['comment'];
        $trn->save();
        
         
    }
    
     public static function add_new_request($param){
        $d = new self;
        $d->status = 0;
        $d->user_id = $param['user_id'];
        $d->amount = $param['amount'];
        $d->coin_type = 3;
        $d->note = $param['note'];
       
        
        $d->save();
        
        $res = \General::success_res();
        return $res;
    }
    
    public static function add_order($param){
        
        $ord = new self();
        $ord->user_id = isset($param['user_id']) ? $param['user_id'] : '';
        $ord->type =2;
        $ord->txn_address = $param['address'];
        $ord->txn_type = $param['txnType'];
        $ord->coin_type = 3;
        $ord->debit = $param['amount'];
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
       
       
        $count = $count->count();
        
        $page=$param['crnt'];
        $len=$param['len'];
        $op=isset($param['opr'])?$param['opr']:'';
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
    
     
    public static function filter_balance_report($param){
        
        $report = self::orderBy('id','desc');
        if(isset($param['user_id']) && $param['user_id'] != ''){
            $report = $report->where('user_id',$param['user_id']);
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
}
