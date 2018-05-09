<?php 

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class StackPlan extends Model {
    protected $fillable = [
        'status','from','to','percentage','days',
    ];
    protected $table    = 'stack_plans';
    protected $hidden   = [];
    public $timestamps  = true;
    
    public function scopeActive($query) {
        return $query->where('status', '=', 1);
    }
    
    
    public static function add_new_stack_plan($param){
//        dd($param);
        
        $check = self::where('no_invest',$param['noi'])->get()->toArray();
        if(count($check) > 0){
            $res = \General::error_res('Plan Index in use, Try another Index.');
            return $res;
        }
        
        if(($param['period_type'] == 1 && $param['months'] > 12) 
        || ($param['period_type'] == 2 && $param['months'] > 9)
        || ($param['period_type'] == 3 && $param['months'] > 6)
        || ($param['period_type'] == 4 && $param['months'] > 3)
        ){
            $res = \General::error_res('Months must be less than Selected Period.');
            return $res;
        }
        
        $trn = new self;
        $trn->no_invest     = $param['noi'];
        $trn->percentage_period    = $param['period_type'];
        $trn->percentage    = $param['percent'];
        $trn->months        = $param['months'];
        
        $trn->save();
        
        
        
        $res = \General::success_res('Plan Added Successfully.');
        $res['data'] = $trn->toArray();
        
        return $res;
        
    }
    
    public static function update_stack_plan($param){
         
        $check = self::where('no_invest',$param['noi'])->get()->toArray();
//        dd($check);
        if(count($check) > 0){
            if($check[0]['id'] != $param['stack_id']){
                $res = \General::error_res('Plan Index in use, Try another Index.');
                return $res;
            }
        }
        
        if(($param['period_type'] == 1 && $param['months'] > 12) 
        || ($param['period_type'] == 2 && $param['months'] > 9)
        || ($param['period_type'] == 3 && $param['months'] > 6)
        || ($param['period_type'] == 4 && $param['months'] > 3)
        ){
            $res = \General::error_res('Months must be less than Selected Period.');
            return $res;
        }
       
        $trn = self::where('id',$param['stack_id'])->first();
        $trn->no_invest     = $param['noi'];
        $trn->percentage_period    = $param['period_type'];
        $trn->percentage    = $param['percent'];
        $trn->months        = $param['months'];
        
        $trn->save();
        
        $res = \General::success_res('Plan Updated Successfully.');
        $res['data'] = $trn->toArray();
        
        return $res;
        
    }
    
    public static function get_plan_list($param){
//        dd($param);
        // DB::enableQueryLog();
        $count=self::orderBy('id','asc');
       
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
        
        
        $tokenReport = self::skip($start)->take($len)->orderBy('id','asc');
        
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
    
    
    public static function stack_plan_report($param){
        
        $report = self::orderBy('no_invest','asc');
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


