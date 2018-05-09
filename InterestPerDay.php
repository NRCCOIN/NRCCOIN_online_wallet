<?php 

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class InterestPerDay extends Model {
    protected $fillable = [
        'day','val'
    ];
    protected $table = 'interest_per_day';
    protected $hidden = [];
    public $timestamps=false;
    
    public static function save_interest($param){
        
        for($i = 1;$i<=31;$i++){
            $int = self::where('day',$i)->first();
            if($int){
                $int->val = $param[$i];
                $int->save();
            }else{
                $new = new self;
                $new->day = $i;
                $new->val = $param[$i];
                $new->save();
            }
        }
        return \General::success_res('per day interest modified successfully.'); 
    }
    
}
