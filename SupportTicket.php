<?php 

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model {
    
    protected $fillable = [
        'title','category','coin','txid','wallet_address','content'
    ];
    
    protected $table = 'support_ticket';
    protected $hidden = ['txid','wallet_address'];
    public $timestamps=true;
    
    
    public function user(){
        return $this->hasOne('App\Models\Users','id','user_id');
    }
    
    public static function get_my_ticket($param){
//        dd($param);
        $count=self::where('user_id',$param['loggerId'])->where('status',1)->orderBy('id','asc');
         
//        if(isset($param['credit']) && $param['credit']!='') {
//           
//           if($param['creditSL'] == 'eq'){
//                $count=$count->where('credit','=',$param['credit']);
//           }
//           else if($param['creditSL'] == 'lt'){
//                $count=$count->where('credit','<',$param['credit']);
//           }
//           else if($param['creditSL'] == 'gt'){
//                $count=$count->where('credit','>',$param['credit']);
//           }
//        }
//        
//        if(isset($param['debit']) && $param['debit']!='') {
//           
//           if($param['debitSL'] == 'eq'){
//                $count=$count->where('debit','=',$param['debit']);
//           }
//           else if($param['debitSL'] == 'lt'){
//                $count=$count->where('debit','<',$param['debit']);
//           }
//           else if($param['debitSL'] == 'gt'){
//                $count=$count->where('debit','>',$param['debit']);
//           }
//        }
//        
//        if(isset($param['startd']) && $param['startd']!='') {
//           
//                $count=$count->where('created_at','>=',$param['startd']);
//        }
//        
//        if(isset($param['endd']) && $param['endd']!='') {
//           
//                $count=$count->where('created_at','<=',$param['endd']);
//        }
        
        $count = $count->count();
//         dd($count);
        $page=isset($param['crnt'])?$param['crnt']:1;
        $len=isset($param['len'])?$param['len']:20;
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
        
        
        $tokenReport=self::where('user_id',$param['loggerId'])->where('status',1)->skip($start)->take($len)->orderBy('id','desc');
        
        
        
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
//        dd($tokenReport);
        $res['len']=$len;
        $res['crnt_page']=$crnt_page;
        $res['total_page']=$total_page;
        
        $res['result']=$tokenReport;
        $res['flag']=$flag;
//        dd($res);
        return $res;
        
    }
    
    
    public static function get_my_ticket_all($param){
//        dd($param);
        $count=self::where('user_id',$param['loggerId'])->where('status','!=',4)->orderBy('id','asc');
         
//        if(isset($param['credit']) && $param['credit']!='') {
//           
//           if($param['creditSL'] == 'eq'){
//                $count=$count->where('credit','=',$param['credit']);
//           }
//           else if($param['creditSL'] == 'lt'){
//                $count=$count->where('credit','<',$param['credit']);
//           }
//           else if($param['creditSL'] == 'gt'){
//                $count=$count->where('credit','>',$param['credit']);
//           }
//        }
//        
//        if(isset($param['debit']) && $param['debit']!='') {
//           
//           if($param['debitSL'] == 'eq'){
//                $count=$count->where('debit','=',$param['debit']);
//           }
//           else if($param['debitSL'] == 'lt'){
//                $count=$count->where('debit','<',$param['debit']);
//           }
//           else if($param['debitSL'] == 'gt'){
//                $count=$count->where('debit','>',$param['debit']);
//           }
//        }
//        
//        if(isset($param['startd']) && $param['startd']!='') {
//           
//                $count=$count->where('created_at','>=',$param['startd']);
//        }
//        
//        if(isset($param['endd']) && $param['endd']!='') {
//           
//                $count=$count->where('created_at','<=',$param['endd']);
//        }
        
        $count = $count->count();
//         dd($count);
        $page=isset($param['crnt'])?$param['crnt']:1;
        $len=isset($param['len'])?$param['len']:20;
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
        
        
        $tokenReport=self::where('user_id',$param['loggerId'])->where('status','!=',4)->skip($start)->take($len)->orderBy('id','desc');
        
        
        
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
        
        
       
        if(isset($param['startd']) && $param['startd']!='') {
           
           
                $tokenReport=$tokenReport->where('created_at','>=',$param['startd']);
           
        }
        
        if(isset($param['endd']) && $param['endd']!='') {
           
           
                $tokenReport=$tokenReport->where('created_at','<=',$param['endd']);
           
        }

        $tokenReport = $tokenReport->get()->toArray();
//        dd($tokenReport);
        $res['len']=$len;
        $res['crnt_page']=$crnt_page;
        $res['total_page']=$total_page;
        
        $res['result']=$tokenReport;
        $res['flag']=$flag;
//        dd($res);
        return $res;
        
    }
    
    
    
    
    
    
    public static function  add_new_ticket($param){
        $ticket=new self;
        $ticket->title=$param['title'];
        $ticket->category=$param['type'];
        $ticket->coin=$param['coin'];
        if($param['type']==1){
            $ticket->txid=$param['txid'];
        }
        else if($param['type']==2){
            $ticket->wallet_address=$param['wallet_add'];
        }
        
        $ticket->content=$param['content'];
        $ticket->status=1;
        $ticket->user_id=$param['loggerId'];
        $ticket->save();
        return \General::success_res('Ticket Generated Successfully');
    }
    
    
    public static function get_ticket_report($param){
//        dd($param);
        
        $count=self::orderBy('id','desc');
        if(isset($param['title']) && $param['title']!='') {
                $count=$count->where('title','like','%'.$param['title'].'%');
        }
        
        if(isset($param['content']) && $param['content']!='') {
                $count=$count->where('content','like','%'.$param['content'].'%');
        }
        
        if(isset($param['category']) && $param['category']!='') {
                $count=$count->where('category',$param['category']);
        }
        
        if(isset($param['status']) && $param['status']!='') {
                $count=$count->where('status',$param['status']);
        }
        
        if(isset($param['startd']) && $param['startd']!='') {
                $count=$count->where('created_at','>=',$param['startd']);
        }
        
        if(isset($param['endd']) && $param['endd']!='') {
                $count=$count->where('created_at','<=',$param['endd']);
        }
        
        
        $count = $count->count();
//         dd($count);
        $page=isset($param['crnt'])?$param['crnt']:1;
        $len=isset($param['len'])?$param['len']:20;
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
                
                
                $crnt_page=$page-1;
                $start=($crnt_page-1)*$len;
            }
            else{
               
                $crnt_page=$page;
                $start=($crnt_page-1)*$len;
            }
        }
        

        $tokenReport=self::skip($start)->take($len)->orderBy('id','desc');
        
        if(isset($param['title']) && $param['title']!='') {
                 $tokenReport=$tokenReport->where('title','like','%'.$param['title'].'%');
        }
        
        if(isset($param['content']) && $param['content']!='') {
                 $tokenReport=$tokenReport->where('content','like','%'.$param['content'].'%');
        }
        
        if(isset($param['category']) && $param['category']!='') {
           
                 $tokenReport=$tokenReport->where('category',$param['category']);
        }
        
        if(isset($param['status']) && $param['status']!='') {
                 $tokenReport=$tokenReport->where('status',$param['status']);
        }
        
        if(isset($param['startd']) && $param['startd']!='') {
                $tokenReport=$tokenReport->where('created_at','>=',$param['startd']);
        }
        
        if(isset($param['endd']) && $param['endd']!='') {
                $tokenReport=$tokenReport->where('created_at','<=',$param['endd']);
        }

        $tokenReport = $tokenReport->with('user')->get()->toArray();
//        dd($tokenReport);
        $res['len']=$len;
        $res['crnt_page']=$crnt_page;
        $res['total_page']=$total_page;
        
        $res['result']=$tokenReport;
        $res['flag']=$flag;
//        dd($res);
        return $res;
        
    }

}
