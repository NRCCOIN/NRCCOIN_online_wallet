<?php 

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class WalletNotifications extends Model {
    protected $fillable = [
        'status','transaction_id'
    ];
    protected $table = 'wallet_notification';
    protected $hidden = [];
    public $timestamps=true;
    
    public static function new_notification($param){
        
        $ct = new self;
        $ct->status   = 0;
        $ct->transaction_id = trim($param['transaction_id']);
        $ct->save();
        
        return \General::success_res('Notificaion saved successfully.');
    }
    
    public static function get_notification($param){
        $notif = self::where('transaction_id',$param['transaction_id']);
        if(isset($param['status']) && $param['status'] != ''){
            $notif = $notif->where('status',$param['status']);
        }
        $notif = $notif->first();
        
        if($notif){
            $notif = $notif->toArray();
            $res = \General::success_res();
            $res['data'] = $notif;
            return $res;
        }
        
        return \General::error_res('no data found');
    }
}
