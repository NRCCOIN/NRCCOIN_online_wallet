<?php 
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Setting extends Model {

	
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'settings';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['name', 'val', 'autoload'];
        
	public $timestamps = false;

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = [];
        
        public static function get_config($name = "")
        {
            $data = [];
            if(is_string($name) && $name != "")
            {
                $settings = self::where("name","=",$name)->skip(0)->take(1)->get();
                
            }
            else if(is_array($name) && !empty ($name))
            {
                $settings = self::whereIn("name",$name)->get();
            }
            else
            {
                $settings = self::where("autoload","=","1")->get();
            }
            if(isset($settings[0]) && isset($settings[0]->name))
            {
                $settings = $settings->toArray();
                
                foreach ($settings as $setting) {
                    $data[$setting['name']] = $setting['val'];
                }
            }
            return $data;
        }
        
        public static function set_config($configs = []) {
                
            foreach ($configs as $key => $val) {
                $setting = self::where("name","=",$key)->first();
                if(isset($setting->name))
                {
                    $setting->val = $val;
                    $setting->save();
                }
            }
            return \General::success_res();
        }
        
        public static function sanitizeInput()
        {
            $res = \General::error_res();
            $trimed_input = \App\Models\Setting::get_config('sanitize_input');
            if(!empty($trimed_input) && $trimed_input['sanitize_input'] == 1)
            {
                $res = \General::success_res();
                $res['data'] = $trimed_input;
            }
            return $res;
        }
        
//        public static function get_settings(){
//            $setting = self::get()->toArray();
//            $list = [];
//            foreach($setting as $set){
//                $list[$set['name']] = $set['val'];
//            }
//            dd($setting,$list);
//        }
}
