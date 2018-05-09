<?php 

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use DB;

class CoinInvoice extends Model {
    protected $fillable = [
        'user_id','status','order_id','invoice_id','btc','usd','coin','us','ip'
    ];
    protected $table    = 'coin_invoice';
    protected $hidden   = [];
    public $timestamps  = true;
    
    public static function filter_invoice_report($param){
        
        $invoice = self::orderBy('id','desc');
        if(isset($param['user_id']) && $param['user_id'] != ''){
            $invoice = $invoice->where('user_id',$param['user_id']);
        }
        if(isset($param['start_date']) && $param['start_date'] != '' && isset($param['end_date']) && $param['end_date'] != ''){
            $invoice = $invoice->whereBetween('created_at', array($param['start_date'], $param['end_date']));
        }
        
        $count  = $invoice->count();
        $len    = $param['itemPerPage'];
        $start  = ($param['currentPage']-1) * $len;
        
        $invoice     = $invoice->with('user')->skip($start)->take($len)->get()->toArray();
        $res['data'] = $invoice;
        $res['total_record'] = $count;
        
        return $res;
    }
    
    
    public static function get_invoice_report($param){
//        dd($param);
         DB::enableQueryLog();
        $count = self::where('user_id',$param['loggerId'])->orderBy('id','desc');
       
        if(isset($param['type']) && $param['type'] != '') {
//            $count = $count->where('coin','!=',0);
            
            if($param['type'] == 'BTC'){
                $count = $count->where('btc','!=',0);
            } else if($param['type'] == config('constant.COIN_SHORT_NAME')){
                $count = $count->where('coin','!=',0);
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
                $start = ($crnt_page-1)*$len;
            }
            
            elseif($op == 'prev'){
                $crnt_page = $page-1;
                if($crnt_page <= 0){
                    $crnt_page = 1;
                }
                $start = ($crnt_page - 1) * $len;
            }

            elseif($op == 'next'){
                $crnt_page = $page + 1;
                if($crnt_page >= $total_page){
                    $crnt_page = $total_page;
                }
                $start = ($crnt_page - 1) * $len;
            }

            else{
                $crnt_page = $total_page;
                $start = ($crnt_page - 1) * $len;
            }
        }

        else{
            if($page > $total_page){
//                $flag=0;
                
                $crnt_page = $page - 1;
                $start = ($crnt_page - 1) * $len;
            }
            else{
                
                $crnt_page = $page;
                $start = ($crnt_page - 1) * $len;
            }
        }
        
        
        $tokenReport = self::where('user_id',$param['loggerId'])->skip($start)->take($len)->orderBy('id','desc');
        
        if(isset($param['type']) && $param['type'] != '') {
//                $tokenReport = $tokenReport->where('type','=',$param['type']);
            if($param['type'] == 'BTC'){
                $tokenReport = $tokenReport->where('btc','!=',0);
            } else if($param['type'] == config('constant.COIN_SHORT_NAME')){
                $tokenReport = $tokenReport->where('coin','!=',0);
            }
        }

        $tokenReport = $tokenReport->get()->toArray();
        
        $res['len']         = $len;
        $res['crnt_page']   = $crnt_page;
        $res['total_page']  = $total_page;
        $res['result']      = $tokenReport;
        $res['flag']        = $flag;
        
//        dd($res);
        
        return $res;
    }
    
}
