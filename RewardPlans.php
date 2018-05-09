<?php 

namespace App\Models;
use Illuminate\Database\Eloquent\Model as Eloquent;

class RewardPlans extends Eloquent {
    
    public $table = 'reward_plans';
    protected $hidden = [];
    protected $fillable = array('issue_date','value','description', 'min_coin', 'max_reward','status');
    public $timestamps = true;

    public function scopeActive($query) {
        return $query->where('status', '=', 1);
    }
    
}
