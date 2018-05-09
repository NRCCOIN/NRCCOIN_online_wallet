<?php 

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class InvestPlan extends Model {
    protected $fillable = [
        'status','from','to','percentage','days',
    ];
    protected $table = 'invest_plan';
    protected $hidden = [];
    public $timestamps=true;
    
    public function scopeActive($query) {
        return $query->where('status', '=', 1);
    }
    
    
    public static function add_new_ico_plan($param){
        
//        dd($param);
        
        $trn = new self;
        $trn->name  = $param['plan_name'];
        $trn->from  = $param['from_usd'];
        $trn->to    = $param['to_usd'];
        $trn->percentage = $param['percent'];
        $trn->days  = $param['days'];
        $trn->extra_benefit = isset($param['extra_b']) && $param['extra_b'] != '' ? $param['extra_b'] : null;
        
        $trn->status = isset($param['status'])?1:0;
        $trn->save();
        
    }
    
     public static function update_ico_plan($param){
       
        $trn = self::where('id',$param['uico'])->first();
        $trn->name  = $param['plan_name'];
        $trn->from  = $param['from_usd'];
        $trn->to    = $param['to_usd'];
        $trn->percentage = $param['percent'];
        $trn->days  = $param['days'];
        $trn->extra_benefit = isset($param['extra_b']) && $param['extra_b'] != '' ? $param['extra_b'] : null;
        
        $trn->status = isset($param['status'])?1:0;
        $trn->save();
        
    }
    
    public static function get_plan_list($param){
//        dd($param);
        // DB::enableQueryLog();
        $count=self::active()->orderBy('id','desc');
       
        if(isset($param['type']) && $param['type']!='') {
            $count = $count->where('type','=',$param['type']);
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
        
        
        $tokenReport = self::active()->skip($start)->take($len)->orderBy('id','desc');
        
        if( isset($param['type']) && $param['type'] != '' ) {
                $tokenReport = $tokenReport->where('type','=',$param['type']);
        }

        $tokenReport = $tokenReport->get()->toArray();
       
        $res['len']     = $len;
        $res['crnt_page']   = $crnt_page;
        $res['total_page']  = $total_page;
        
        $res['result']  = $tokenReport;
        $res['flag']    = $flag;
//        dd($res);
        return $res;
    }
    
    
    public static function ico_plan_report($param){
        
        $report = self::orderBy('id','desc');
        if(isset($param['user_id']) && $param['user_id'] != ''){
            $report = $report->where('user_id',$param['user_id']);
        }
        if(isset($param['start_date']) && $param['start_date'] != '' && isset($param['end_date']) && $param['end_date'] != ''){
            $report = $report->whereBetween('created_at', array($param['start_date'], $param['end_date']));
        }
        
        $count = $report->count();
        
        $len   = $param['itemPerPage'];
        $start = ( $param['currentPage'] - 1 ) * $len;
        
        $report = $report->get()->toArray();
        $res['data'] = $report;
        $res['total_record'] = $count;
        
        return $res;
    }
    
    
}


