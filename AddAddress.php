<?php 

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AddAddress extends Model {
    protected $fillable = [
        'user_id','address','name','address_type'
    ];
    protected $table = 'addressbook';
    protected $hidden = [];
    public $timestamps=true;
    
    public static function add_address($param){
        $ct = new self;
        $ct->user_id = $param['user_id'];
        $ct->address = $param['address'];
        $ct->name = $param['name'];
        $ct->address_type = $param['address_type'];
        $ct->save();
        
        return \General::success_res('Address added successfully.');
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
    
}
