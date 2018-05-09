<?php 

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Carbon;

class PaymentRecord extends Model {
    protected $fillable = [
        
    ];
    protected $table = 'payment_record';
    protected $hidden = [];
    public $timestamps=true;
    
    
    public function bookingDetails(){
        return $this->hasOne('App\Models\Bookings','booking_id','booking_id');
    }
    
    
}
