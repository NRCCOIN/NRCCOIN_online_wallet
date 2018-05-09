<?php 

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class checkAddress extends Model {
    protected $fillable = [
        'tr_address'
    ];
    protected $table = 'withdrow_request';
    protected $hidden = [];
    public $timestamps=true;
    
   
        
public static function checkAddress($data){
        
$address = self::where("address", $data['coin_address'])->first();
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
