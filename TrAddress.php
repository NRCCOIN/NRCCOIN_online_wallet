<?php 

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TrAddress extends Model {
    protected $fillable = [
        'user_id','tr_address'
    ];
    protected $table = 'users_tr_address';
    protected $hidden = [];
    public $timestamps=true;
    
    public static function new_tr_address($param){
        $ct = new self;
        $ct->user_id = $param['user_id'];
        $ct->tr_address = $param['tr_address'];
        $ct->save();
        
        return \General::success_res('tr address added successfully.');
    }
    
    public static function check_tr_address($param){
        $tr_address = $param['tr_address'];
        $check = self::where('tr_address',$tr_address)->get()->toArray();
        if(count($check) > 0){
            $res = \General::success_res();
            $res['data'] = $check;
            return $res;
        }
        return \General::error_res('this address is invalid.please enter valid tr address');
    }
        
    public static function check_tr($param){
        
        $address = self::where("tr_address", $param['tr_address'])->first();
        $res['data']=$address;
        
if (is_null($address)) {
            $res['flag']=0;
            return $res;
        } else {

        
        $res['flag']=1;
        return $res;
}
 
    }
            
    public static function check_trade_address($param){
        
        $address = self::where("tr_address", $param['address'])->first();
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
