<?php 
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PartialSellOrder extends Model {

	
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'partial_sell_order';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['amount'];
        
	public $timestamps = true;

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = [];
        
        public static function add_order($param)
        {
            $sbn=new self;
            $sbn->order_id=$param['order_id'];
            $sbn->amount= $param['amount'];
            $sbn->save();
            
            $buy = SellOrders::where('id',$sbn->order_id)->first();
            
            $amount = $buy->amount;
            $total_amount = $buy->amount_sold + $sbn->amount ;
            
            $buy->amount_sold = $buy->amount_sold + $sbn->amount;
            $buy->status = 2;
            if($total_amount >= $amount){
                $buy->status = 1;
            }
            $buy->save();
            $res = \General::success_res();
            return $res;
        }
        

}
