<?php 

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PreviousTransactions extends Model {
    
    protected $fillable = [
        'id','status','user_id','amount','note','transaction_id','coin_type','created_at','updated_at'
    ];
    
    protected $table = 'withdrow_request';
    protected $hidden = [];
    
    
public static function get_previous_transactions($data){
                           
$transactions=self::orderBy('withdrow_request.created_at','desc')
                                ->join('users', 'users.id', '=', 'withdrow_request.user_id')
                              //  ->where('coin_type',1)
                                ->orWhere('withdrow_request.user_id',$data['user_id'])
                                ->orWhere('withdrow_request.address',$data['coin_address'])
            ->select('users.name','withdrow_request.coin_type','withdrow_request.user_id','withdrow_request.transaction_id','withdrow_request.amount','withdrow_request.created_at AS t_created_at')

                           ->take(5)->get()->toArray();

    return $transactions;
    }
    
public static function get_previous_trade_transactions($data){
                           
$transactions=self::orderBy('withdrow_request.created_at','desc')
                                ->join('users', 'users.id', '=', 'withdrow_request.user_id')
                               // ->where('coin_type',4)
                                ->orwhere('withdrow_request.user_id',$data['user_id'])
                                ->orWhere('withdrow_request.address',$data['tr_address'])
    
        ->select('users.name','withdrow_request.coin_type','withdrow_request.user_id','withdrow_request.transaction_id','withdrow_request.amount','withdrow_request.created_at AS t_created_at')
                           ->take(5)->get([''])->toArray();

    return $transactions;
    }
    
    
}
