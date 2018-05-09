<?php 

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CalenderIco extends Model {
    
    protected $fillable = [
        'start_date','end_date','token','price','status'
    ];
    
    protected $table = 'calender_ico';
    protected $hidden = [];
    public $timestamps = true;
    
    
    public static function add_new_ico($param){
        
        $trn = new self;
        $trn->start_date = $param['startd_ico'];
        $trn->end_date = $param['endd_ico'];
        $trn->price = $param['price'];
         $trn->token = $param['token'];
        
        $trn->status =1; //isset($param['status'])?1:0;
        $trn->save();
        
    }
    
     public static function update_ico($param){
        
        $trn = self::where('id',$param['uico'])->first();
        $trn->start_date = $param['startd_ico'];
        $trn->end_date = $param['endd_ico'];
        $trn->price = $param['price'];
         $trn->token = $param['token'];
        $trn->status = 1;//isset($param['status'])?1:0;
        $trn->save();
        
    }
 
    
    
    public static function get_calender_ico_list(){
                           
          
        $tokenReport=self::orderBy('start_date','asc')
                           ->where('price','!=',0)
                            ->where('status',1)
                           ->get()->toArray();
        
//        $wdate=self::where('date','<',date('Y-m-d h:i:s'))->orderBy('date','desc')->first();
//        dd($wdate);
//        $tokenReport=self::where('date','>=',$wdate->date)->orderBy('date','asc')->take(3)->get()->toArray();

//      dd($tokenReport);
//      $tokenReport=self::where('active',1)->orderBy('date')->take(3);       
//      $tokenReport = $tokenReport->get()->toArray();
        
        return $tokenReport;
    }
    
     public static function get_calender_ico_list_dash(){
                           
        $wdate = self::orderBy('created_at','desc')
                        ->where('start_date','<=',date('Y-m-d h:i:s'))
                        ->where('end_date','>=',date('Y-m-d h:i:s'))
                        ->first();
        
        if(is_null($wdate)){
            $res = \General::error_res('No Date Found.');
            return $res;    
        }
        
        $tokenReport = self::orderBy('start_date','asc')
                        ->where('price','!=',0)
                        ->where('end_date','>=',$wdate->start_date)
                        ->take(3)->get()->toArray(); 
        
        if(is_null($tokenReport) || count($tokenReport) <= 0){
            $res = \General::error_res('No Date Found.');
            return $res;    
        }
        
//        dd($wdate->toArray(),$tokenReport);
        return $tokenReport;
    }
    
    
    public static function ico_calendar_report($param){
        
        $report = self::orderBy('id','asc');
        if(isset($param['user_id']) && $param['user_id'] != ''){
            $report = $report->where('user_id',$param['user_id']);
        }
        if(isset($param['start_date']) && $param['start_date'] != '' && isset($param['end_date']) && $param['end_date'] != ''){
            $report = $report->whereBetween('created_at', array($param['start_date'], $param['end_date']));
        }
        
        $count = $report->count();
        
        $len = $param['itemPerPage'];
        $start = ($param['currentPage']-1) * $len;
        
        $report =$report->get()->toArray();
        $res['data'] = $report;
        $res['total_record'] = $count;
        
        return $res;
    }
}
