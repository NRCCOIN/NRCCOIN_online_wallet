<?php 

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CoinAddress extends Model {
    protected $fillable = [
        'user_id','coin_address'
    ];
    protected $table = 'users_coin_address';
    protected $hidden = [];
    public $timestamps=true;
    
    public static function new_coin_address($param){
        $ct = new self;
        $ct->user_id = $param['user_id'];
        $ct->coin_address = $param['coin_address'];
        $ct->save();
        
        return \General::success_res('coin address added successfully.');
    }
    
    public static function check_address($param){
        $address = $param['coin_address'];
        $check = self::where('coin_address',$address)->get()->toArray();
        if(count($check) > 0){
            $res = \General::success_res();
            $res['data'] = $check;
            return $res;
        }
        return \General::error_res('this address is invalid.please enter valid coin address');
    }
    
    
    
    public static function check_ex($param){
        
        $address = self::where("coin_address", $param['address'])->first();
        $res['data']=$address;
        
if (is_null($address)) {
            $res['flag']=0;
            return $res;
        } else {

        
        $res['flag']=1;
        return $res;
}
 
    }
        
    
    
    
}
