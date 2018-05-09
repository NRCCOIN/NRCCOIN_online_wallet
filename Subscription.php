<?php 
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Subscription extends Model {

	
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'subscription';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['email', 'status', ];
        
	public $timestamps = true;

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = [];
        
        public static function add_subscription($param)
        {
            $sbn=new self;
            $sbn->email=$param['email'];
            $sbn->status=1;
            $sbn->save();
            if(!is_null($sbn)){
                $res['flag']=1;
            }else{
                $res['flag']=0;
            }
            
            return $res;
        }
        

}
