<?php 
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TradeHistory extends Model {

	
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'trade_history';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['user_id', 'price','amount','total_price','trade_type' ];
        
	public $timestamps = true;

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = [];
        
        public static function add_new_trade($param)
        {
            $sbn=new self;
            $sbn->user_id=$param['user_id'];
            $sbn->order_id=$param['order_id'];
            $sbn->price=$param['price'];
            $sbn->amount=$param['amount'];
            $sbn->total_price=$param['total_price'];
            $sbn->trade_type=$param['trade_type'];
            $sbn->save();
            
            $user = Users::where('id',$sbn->user_id)->first();
            if($user){
                if($param['trade_type'] == 0){
                    $trf = 'Buy coin';
                    $user->balance = $user->balance + $sbn->total_price;
                }else if($param['trade_type'] == 1){
                    $trf = 'Sell coin';
                    $user->coin = $user->coin + $sbn->amount;
                }
                $user->save();
                
                if($param['trade_type'] == 0){
                    $debit = [
                        'user_id'=>$sbn->user_id,
                        'type' =>11,
                        'reference_id'=>$sbn->id,
                        'credit'=>0,
                        'debit'=>$sbn->total_price,
                        'comment'=> $sbn->total_price .' USD debited for buy request.',
                    ];
                    $credit = [
                        'user_id'=>$sbn->user_id,
                        'type' =>8,
                        'reference_id'=>$sbn->id,
                        'credit'=>$sbn->amount,
                        'debit'=>0,
                        'comment'=> $sbn->amount .' '.config('constant.COIN_SHORT_NAME').' credited for buy request.',
                    ];
                    $debitUSD = BalanceTransactions::add_transaction($debit);
                    $creditCoin = CoinTransactions::add_transaction($credit);
                }else if($param['trade_type'] == 1){
                    $debit = [
                        'user_id'=>$sbn->user_id,
                        'type' =>8,
                        'reference_id'=>$sbn->id,
                        'credit'=>0,
                        'debit'=>$sbn->amount,
                        'comment'=> $sbn->amount .' '.config('constant.COIN_SHORT_NAME').' debited for sell request.',
                    ];
                    $credit = [
                        'user_id'=>$sbn->user_id,
                        'type' =>11,
                        'reference_id'=>$sbn->id,
                        'credit'=>$sbn->total_price,
                        'debit'=>0,
                        'comment'=> $sbn->total_price .' USD credited for sell request.',
                    ];
                    $debitCoin = CoinTransactions::add_transaction($debit);
                    $creditUSD = BalanceTransactions::add_transaction($credit);
                }
                
                
            }
            
            return \General::success_res('trade placed successfully.');
        }
        
        public static function get_all_trades($param){
            $start = 0;
            $len = config('constant.DEFAULT_TRADE_DATA_LIMIT');
            $order = self::orderBy('id','desc');
//            if(isset($param['user_id']) && $param['user_id'] != ''){
            if(isset($param['user_id'])){
                $order = $order->where('user_id',$param['user_id']);
            }
            if(isset($param['trade_type']) && $param['trade_type'] != ''){
                $order = $order->where('trade_type',$param['trade_type']);
            }
            $order = $order->skip($start)->take($len)->get()->toArray();
            $res = \General::success_res();
            $res['data'] = $order;
            return $res;
        }
        
        public static function buy_trade(){
            \Log::info('buy trade called ');
            $start = 0;
            $len = config('constant.DEFAULT_SELL_DATA_LIMIT');
            $seller = SellOrders::orderBy('price','asc')->whereIn('status',[0,2])->skip($start)->take($len)->get()->toArray();
            if(count($seller) == 0){
                \Log::info('no seller found.');
                return \General::error_res('no seller found.');
            }
            $buyer = BuyOrders::orderBy('price','desc')->whereIn('status',[0,2])->first();
            if(!$buyer){
                \Log::info('no buyer found.');
                return \General::error_res('no buyer found.');
            }
            $b_amount = $buyer->amount - $buyer->amount_bought;
            $minimum_sell_price = $seller[0]['price'];
            if($minimum_sell_price > $buyer->price){
                \Log::info('no seller matched with buyer price.');
                return \General::error_res('no seller matched with buyer price.');
            }
            
            $res = \General::error_res();
            $buyUsers = [
                $buyer->user_id =>'your buy coin trade executed',
            ];
            $sellUsers = [];
            foreach($seller as $sell){
//                if($buyer->user_id == $sell['user_id']){
//                    \Log::info('buyer and seller same');
//                    continue;
//                }
                if($sell['amount_sold'] >= $sell['amount']){
                    SellOrders::where('id',$sell['id'])->update(['status'=>1]);
                    continue;
                }
                if($sell['price'] <= $buyer->price){
                    \Log::info('buy trade for seller user id : '.$sell['user_id']);
                    $s_amount = $sell['amount'] - $sell['amount_sold'];
                    if($b_amount > $s_amount){
                    \Log::info('buy trade for buy amount : '.$b_amount);   
                        $data = [
                            'b_user_id'=>$buyer->user_id,
                            'b_price'=>$buyer->price,
                            'b_amount'=>$s_amount,
                            's_user_id'=>$sell['user_id'],
                            's_price'=>$sell['price'],
                            's_amount'=>$s_amount,
                            'par_b_amount'=>$s_amount,
                            'par_s_amount'=>$s_amount,
                            'b_order_id'=>$buyer->id,
                            's_order_id'=>$sell['id'],
                        ];
                        
                        $pt = self::prepre_trade($data);
                        
                        $res = \General::success_res();
                        $sellUsers[$sell['user_id']] = 'your sell coin trade executed of amount : '.$s_amount.' at price : '.$sell['price'];
                        
                        $b_amount = $b_amount - $s_amount;
                    }else if($b_amount > 0 && $b_amount <= $s_amount){
                    \Log::info('buy trade for buyer amount <= : '.$s_amount);    
                        $data = [
                            'b_user_id'=>$buyer->user_id,
                            'b_price'=>$buyer->price,
                            'b_amount'=>$b_amount,
                            's_user_id'=>$sell['user_id'],
                            's_price'=>$sell['price'],
                            's_amount'=> $b_amount,
                            'par_b_amount'=>$b_amount,
                            'par_s_amount'=>$b_amount,
                            'b_order_id'=>$buyer->id,
                            's_order_id'=>$sell['id'],
                        ];
                        
                        $pt = self::prepre_trade($data);
                        
                        $sellUsers[$sell['user_id']] = 'your sell coin trade executed of amount : '.$b_amount.' at price : '.$sell['price'];
                        
                        $b_amount = $b_amount - $s_amount;
                        
                        $res = \General::success_res();
                    }
                }
            }
            $res_data = [
                'buy'=>$buyUsers,
                'sell'=>$sellUsers,
            ];
            $res['data'] = $res_data;
            return $res;
//            dd($buyer->price,$b_amount,$s_amount);
        }
        
        public static function prepre_trade($param){
            $buyTrade = [
                'user_id' => $param['b_user_id'],
                'price' => $param['b_price'],
                'amount' => $param['b_amount'],
                'total_price' => $param['b_amount'] * $param['b_price'] ,
                'trade_type' => 0,
                'order_id' => $param['b_order_id'],
            ];

            $sellTrade = [
                'user_id' => $param['s_user_id'],
                'price' => $param['s_price'],
                'amount' => $param['s_amount'],
                'total_price' => $param['s_amount'] * $param['s_price'] ,
                'trade_type' => 1,
                'order_id' => $param['s_order_id'],
            ];

            $parBuy = [
                'order_id'=>$param['b_order_id'],
                'amount'=>$param['par_b_amount'],
            ];
            $parSell = [
                'order_id'=>$param['s_order_id'],
                'amount'=>$param['par_s_amount'],
            ];
            
            $buy = self::add_new_trade($buyTrade);
            $pb = PartialBuyOrder::add_order($parBuy);
            
            $sell = self::add_new_trade($sellTrade);
            $ps = PartialSellOrder::add_order($parSell);
            
        }
        
        public static function sell_trade(){
            \Log::info('sell trade called ');
            $start = 0;
            $len = config('constant.DEFAULT_BUY_DATA_LIMIT');
            $buyer = BuyOrders::orderBy('price','desc')->whereIn('status',[0,2])->skip($start)->take($len)->get()->toArray();
            if(count($buyer) == 0){
                \Log::info('no buyer found.');
                return \General::error_res('no buyer found.');
            }
            $seller = SellOrders::orderBy('price','asc')->whereIn('status',[0,2])->first();
            if(!$seller){
                \Log::info('no seller found.');
                return \General::error_res('no seller found.');
            }
            $s_amount = $seller->amount - $seller->amount_sold;
            $minimum_buyer_price = $buyer[0]['price'];
            if($minimum_buyer_price < $seller->price){
                \Log::info('no buyer matched with seller price.');
                return \General::error_res('no buyer matched with seller price.');
            }
            
            $res = \General::error_res();
            $sellUsers= [
                $seller->user_id =>'your sell coin trade executed',
            ];
            $buyUsers  = [];
            foreach($buyer as $buy){
//                if($seller->user_id == $buy['user_id']){
//                    \Log::info('buyer and seller are same');
//                    continue;
//                }
                if($buy['amount_bought'] >= $buy['amount']){
                    BuyOrders::where('id',$buy['id'])->update(['status'=>1]);
                    continue;
                }
                if($buy['price'] >= $seller->price){
                    \Log::info('sell trade for buyer user id : '.$buy['user_id']);
                    $b_amount = $buy['amount'] - $buy['amount_bought'];
                    if($s_amount > $b_amount){
                       \Log::info('sell trade for sell amount : '.$s_amount);  
                        $data = [
                            'b_user_id'=>$buy['user_id'],
                            'b_price'=>$buy['price'],
                            'b_amount'=>$b_amount,
                            's_user_id'=>$seller->user_id,
                            's_price'=>$seller->price,
                            's_amount'=>$b_amount,
                            'par_b_amount'=>$b_amount,
                            'par_s_amount'=>$b_amount,
                            'b_order_id'=>$buy['id'],
                            's_order_id'=>$seller->id,
                        ];
                        
                        $pt = self::prepre_trade($data);
                        
                        $res = \General::success_res();
                        $buyUsers[$buy['user_id']] = 'your buy coin trade executed of amount : '.$b_amount.' at price : '.$buy['price'];
                        
                        $s_amount = $s_amount - $b_amount;
                    }else if($s_amount > 0 && $s_amount <= $b_amount){
                        \Log::info('sell trade for seller amount <= : '.$b_amount);
                        $data = [
                            'b_user_id'=>$buy['user_id'],
                            'b_price'=>$buy['price'],
                            'b_amount'=>$s_amount,
                            's_user_id'=>$seller->user_id,
                            's_price'=>$seller->price,
                            's_amount'=> $s_amount,
                            'par_b_amount'=>$s_amount,
                            'par_s_amount'=>$s_amount,
                            'b_order_id'=>$buy['id'],
                            's_order_id'=>$seller->id,
                        ];
                        
                        $pt = self::prepre_trade($data);
                        
                        $buyUsers[$buy['user_id']] = 'your buy coin trade executed of amount : '.$s_amount.' at price : '.$buy['price'];
                        
                        $s_amount = $s_amount - $b_amount;
                        
                        $res = \General::success_res();
                    }
                }
            }
            $res_data = [
                'buy'=>$buyUsers,
                'sell'=>$sellUsers,
            ];
            $res['data'] = $res_data;
            return $res;
//            dd($buyer->price,$b_amount,$s_amount);
        }
        public static function get_volume_history($param){
            $h = self::orderBy('created_at','desc');
            if(isset($param['date']) && $param['date'] != ''){
                $h = $h->where('created_at','>=',$param['date']);
            }
            $h = $h->get()->toArray();
            $res = \General::success_res();
            $res['data'] = $h;
            return $res;
        }
        
    public static function get_trade_report($param){
      //  dd($param);
        $count=self::where('user_id',$param['user_id'])->orderBy('id','desc');
       
  
        if(isset($param['startd']) && $param['startd']!='') {
           
                $count=$count->where('created_at','>=',$param['startd']);
        }
        
        if(isset($param['type']) && $param['type']!='') {
           
                $count=$count->where('trade_type','=',$param['type']);
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
        
        if(isset($param['type']) && $param['type']!='') {
           
                $tokenReport=$tokenReport->where('trade_type','=',$param['type']);
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
