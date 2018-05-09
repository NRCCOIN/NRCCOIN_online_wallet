<?php 
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SellOrders extends Model {

	
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'sell_orders';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['user_id', 'price','amount','total_price','amount_sold','status' ];
        
	public $timestamps = true;

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = [];
        
        public static function add_new_order($param)
        {
            $sbn=new self;
            $sbn->user_id=$param['user_id'];
            $sbn->price=$param['Price'];
            $sbn->amount=$param['Amount'];
            $sbn->total_price= $param['Amount'] * $param['Price'];
            $sbn->status=0;
            $sbn->save();
            
            $user = Users::where('id',$sbn->user_id)->first();
            if($user){
                $settings = app('settings');
                $fee = $settings['sell_exchange_fee'];
                $total = $sbn->amount + ($sbn->amount * $fee / 100);
                $user->coin = $user->coin - $total;
                $user->save();
            }
            
            $trade = TradeHistory::sell_trade();
            $res = \General::success_res('Sell order place successfully.');
            if(isset($trade['data'])){
                $res['data'] = $trade['data'];
            }
            
            return $res;
        }
        
        public static function get_sell_orders($param){
            $start = 0;
            $len = config('constant.DEFAULT_SELL_DATA_LIMIT');
            $order = self::orderBy('price','asc')->whereIn('status',[0,2]);
//            if(isset($param['user_id']) && $param['user_id'] != ''){
            if(isset($param['user_id'])){
                $order = $order->where('user_id',$param['user_id']);
            }
            $order = $order->skip($start)->take($len)->get()->toArray();
            $res = \General::success_res();
            $res['data'] = $order;
            return $res;
        }
        
        public static function cancel_order($param){
            $order = self::where('id',$param['id'])->where('user_id',$param['user_id'])->whereNotIn('status',[1,3])->first();
            if(!$order){
                return \General::error_res('order not found.');
            }
            $pending_amount = $order->amount - $order->sold_amount;
            $price = $pending_amount * $order->price;
            
            $order->status = 3;
            $order->save();
            
            $user = Users::where('id',$order->user_id)->first();
            if($user){
                $user->coin = $user->coin + $pending_amount;
                $user->save();
            }
            return \General::success_res('your order cancelled successfully.');
        }
        
        public static function get_sell_order_report($param){
      //  dd($param);
        $count=self::where('user_id',$param['user_id'])->orderBy('id','desc');
       
  
        if(isset($param['startd']) && $param['startd']!='') {
           
                $count=$count->where('created_at','>=',$param['startd']);
        }
        
        if(isset($param['status']) && $param['status']!='') {
           
                $count=$count->where('status','=',$param['status']);
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
        
        
        $tokenReport=self::where('user_id',$param['user_id'])->skip($start)->take($len)->orderBy('id','desc');
        
        if(isset($param['status']) && $param['status']!='') {
           
                $tokenReport=$tokenReport->where('status','=',$param['status']);
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
}
