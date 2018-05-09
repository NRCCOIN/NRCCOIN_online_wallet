<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Lib\coin\jsonRPCClient;
use Symfony\Component\HttpFoundation\Request;


class ExplorerController extends Controller {
    public $client;
    
    public function __construct() {
        $this->client = new \App\Lib\coin\jsonRPCClient();
    }
    
    public function getBlockId($id = ''){
//        dd($id);
        $block_hash = $this->client->getblockhash((int)$id);
//        dd($block_hash);
        $data = $this->client->getblock(trim($block_hash));
        dd($data);
    }
    public function getBlock($id = ''){
//        dd($id);
        
        $data = $this->client->getblock(trim($id));
        dd($data);
    }
    public function getTx($id = ''){
//        dd($id);
        $data = $this->client->getrawtransaction(trim($id),1);
        dd($data);
    }
    public function getAddress($id = ''){
//        dd($id);
        $account = $this->client->getaccount(trim($id));
//        $t = $this->client->listtransactions($account,120,0);
        
        $data = $this->client->listtransactions($account,120,0);
        $rec = [];
        $send = [];
        
        dd($data);
        
        foreach($data as $key=>$d){
            
            if($d['category'] == "receive"){
                if($id == $d['address']){
                    $rec[] = $data[$key];
                }
            }
            else if($d['category'] == "send"){
                if($id == $d['address']){
                    $send[] = $data[$key];
                }
            }
        }
        
        $res = [];
        $res['account'] = $account;
        $res['receive'] = $rec;
        $res['send'] = $send;
        dd($res);
    }
    
    public function getTest($id = '',$acc = ''){
//        $this->client = new \App\Lib\coin\jsonRPCClient();
        $r = $t = null;
//        $r = $this->client->getaccountaddress();
//        $r = $this->client->getaccountaddress('test_1');
//        $t = $this->client->getaccountaddress($id);
//        
//        $r = $this->client->getaccountaddress($id);
//        
//        $r = $this->client->getnewaddress('test_1');
//        $t = $this->client->getnewaddress();
//        
//        $t = $this->client->getaccount('Nyz9xkVLyUZ7NNQtZHwcurvadDokqk4vPT');
//        $r = $this->client->getaccount($id);
//        
//        $t = $this->client->gettransaction('6a6807b4fb0970c6a426a66d511d549181d62cbacc4e2a40dc28326e6a68ca6e');
//        
//        $t = $this->client->getaddressesbyaccount('');
//        
//        $t = $this->client->getbalance('hello');//21.998
//        
//        $t = $this->client->getaccount('LbEA2yzyDFB3LWpad7VYPZgoiwyddpSen8');
//        
//        $t = $this->client->getbalance();
//        
//        $t = $this->client->getmininginfo();
//        
//        $t = $this->client->listaccounts();
//        
//        $t = $this->client->move('test_main','testmain',0.01);
//        
//        $t = $this->client->listaddressgroupings();
        $t = $this->client->listreceivedbyaddress();
//        
//        $t = $this->client->listtransactions();
//        
//        $t = $this->client->listtransactions('hello',120,0);
//        
//        $t = $this->client->getwalletinfo();
//        
//        $r = $this->client->sendfrom(trim('b_1'),trim($id),50);
//        $r = $this->client->sendfrom(trim('admin'),trim('P2K51PMyXVLBPvPuz7CLDWHMyUK8umKbsD'),10);
//        
//        $t = $this->client->sendfrom(trim('test_1'),trim($id),0.01);
        dd($r , $t);
        
    }
    
}