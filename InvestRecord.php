<?php 

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class InvestRecord extends Model {
    protected $fillable = [
        'plan_id','user_id','status','amount','end_date',
    ];
    protected $table    = 'invest_record';
    protected $hidden   = [];
    public $timestamps  = true;
    
    public function scopeActive($query) {
        return $query->where('status', '=', 1);
    }
    
    public function plan(){
        return $this->hasOne('App\Models\InvestPlan','id','plan_id');
    }
    
}
