<?php 

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Addressbook extends Model {
    
    protected $fillable = [
        'id','user_id','name','address','address_type','created_at','updated_at'
    ];
    
    protected $table = 'addressbook';
    protected $hidden = [];
    
    
public static function myaddresses($data){
                           
$addresses=self::orderBy('addressbook.created_at','desc')
                                ->join('users', 'users.id', '=', 'addressbook.user_id')
                                ->where('addressbook.user_id',$data['user_id'])
            ->select('addressbook.name','addressbook.address_type','addressbook.user_id','addressbook.address','addressbook.created_at AS t_created_at')

                           ->take(5)->get()->toArray();

    return $addresses;
    }
    

    
    
}
