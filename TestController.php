<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class TestController extends Controller {

    public function __construct() {
        
    }

    public function getIndex() {
        return view("user.test1");
        $l_id = 1;
        $layout = \App\Models\BusLayout::find($l_id)->toArray();
        $data = \App\Models\SeatMap::where("layout_id", $layout['id'])->orderBy("row", "ASC")->orderBy("col", "ASC")->get()->toArray();
        $seats = \App\Models\SeatMap::prepare_seatmap_array($data,$layout['rows'],$layout['columns']);
        dd($seats);
        $data = [
            "layouts" => [
                $l_id => [
                    "row" => $layout['rows'],
                    "col" => $layout['columns'],
                    "map" => $data,
                    "seats" => $seats,
                ],
                
            ],
            'header' => [
                    'js' => ['ThrowPropsPlugin.min.js','TweenLite.min.js','CSSPlugin.min.js','Draggable.min.js']
            ]
        ];
        
        
        
//        \General::dd($data);
        return view("site.test", $data);
    }
    
    
    
    public function getTestDemo($no){
//        echo $no;
        $view_data = [
            'header' => [
                'title' => 'Test Demo',
            ],
            'body' => [
                'no' => $no,
            ],
            'footer' => [],
        ];
        
        return view('site.testdemo',$view_data);
    }
    
    public function getBarCode()
    {
        echo \DNS2D::getBarcodeHTML("Paresh Thummar", "QRCODE");
    }
    
    public function getTestInvest(){
        $res = \App\Models\InvestPlan::get()->toArray();
        return $res;
        
    }
    public function getTestKeys(){
        $coin_address = \App\Models\General::generate_coin_address();
        $transation_id = \App\Models\General::generate_transaction_id();
        
        $res = \General::success_res();
        $res['data'] = [
            'coin_address'=>$coin_address,
            'transaction_id'=>$transation_id,
        ];
        return $res;
    }
    
    
    public function getTest() {
        
//        if (!\Auth::guard('user')->check()) {
//            return redirect('login');
//        }
//        
//        $user = \Auth::guard('user')->user()->toArray();
        
//        $view_data = [
//            'header' => [
//                "title" => 'Dashboard',
//                "js"    => [],
//                "css"   => [],
//            ],
//            'body' => [
//                'user'      => $user,
//                'page_title'=> 'Dashboard',
//                'menu_id'   => "dashboard",
//            ],
//            'footer' => [
//                "js"    => [],
//                "css"   => []
//            ],
//        ];

//        return view("user.test", $view_data);
        return view("user.test1");
    }
}
