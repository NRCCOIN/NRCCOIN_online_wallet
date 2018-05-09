<?php 

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class StackRecord extends Model {
    protected $fillable = [
        'plan_id','user_id','status','amount','end_date',
    ];
    protected $table    = 'stack_record';
    protected $hidden   = [];
    public $timestamps  = true;
    
    public function scopeActive($query) {
        return $query->where('status', '=', 1);
    }
    
    public function user(){
        return $this->hasOne('App\Models\Users','id','user_id');
    }
    
    public function plan(){
        return $this->hasOne('App\Models\InvestPlan','id','plan_id');
    }
    
    public static function add_transaction($param){
        
//        dd($param);
        $trn = new self;
        $trn->plan_id   = $param['plan_id'];
        $trn->user_id   = $param['user_id'];
        $trn->status    = $param['status'];
        $trn->amount    = $param['amount'];
        $trn->percentage= $param['percentage'];
        $trn->percentage_period = $param['percentage_period'];
        $trn->end_date  = $param['end_date'];
        $trn->save();
        
        return $trn->id;
    }
    
    
    
    
    
    
    
        
  public static function get_stack_transaction_report($param){
      //  dd($param);
        $count=self::where('user_id',$param['loggerId'])->orderBy('id','desc');
       
  
        if(isset($param['startd']) && $param['startd']!='') {
           
                $count=$count->where('created_at','>=',$param['startd']);
        }
        
        if(isset($param['type']) && $param['type']!='') {
           
                $count=$count->where('type','=',$param['type']);
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
        
        if(isset($param['type']) && $param['type']!='') {
           
                $tokenReport=$tokenReport->where('type','=',$param['type']);
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
       // dd($res);
        return $res;
        
    }
   
    
    
     public static function filter_stack_report($param){
        
        $report = self::orderBy('id','desc');
        if(isset($param['user_id']) && $param['user_id'] != ''){
            $report = $report->where('user_id',$param['user_id']);
        }
        if(isset($param['status']) && $param['status'] != ''){
            $report = $report->where('status',$param['status']);
        }
        if(isset($param['start_date']) && $param['start_date'] != '' && isset($param['end_date']) && $param['end_date'] != ''){
            $report = $report->whereBetween('end_date', array($param['start_date'], $param['end_date']));
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
