<?php
namespace Application\Models;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;

use Zend\Cache\StorageFactory;
use Zend\Cache\Storage\Adapter\Memcached;
use Zend\Cache\Storage\StorageInterface;

use Zend\Json\Json; 

use Zend\Http\Client; 
use Zend\Http\Request;

class LineAPI
{ 
    protected $lineapi; 
################################################################################ 
	function __construct($adapter, $inID, $inPage) 
    {
        $this->id = $inID; 
        $this->adapter = $adapter;
        $this->perpage = 100;
        $this->page = $inPage;
        $this->pageStart = ($this->perpage*($this->page-1));
        $this->now = date('Y-m-d H:i');
        $this->ip = '';
        if (getenv('HTTP_CLIENT_IP'))
        {
            $this->ip = getenv('HTTP_CLIENT_IP');
        }
        else if(getenv('HTTP_X_FORWARDED_FOR'))
        {
            $this->ip = getenv('HTTP_X_FORWARDED_FOR');
        }
        else if(getenv('HTTP_X_FORWARDED'))
        {
            $this->ip = getenv('HTTP_X_FORWARDED');
        }
        else if(getenv('HTTP_FORWARDED_FOR'))
        {
            $this->ip = getenv('HTTP_FORWARDED_FOR');
        }
        else if(getenv('HTTP_FORWARDED'))
        {
            $this->ip = getenv('HTTP_FORWARDED');
        }
        else if(getenv('REMOTE_ADDR'))
        {
            $this->ip = getenv('REMOTE_ADDR');
        }
        else
        {
            $this->ip = 'UNKNOWN';
        }
    } 
################################################################################ 
    function getList()
    {
        $data = [];
        $sql = "SELECT line_msgid,line_msg,url,imageurl,createby,createdate,updateby,lastupdate FROM vw_line_listmsg WHERE 1 ORDER BY lastupdate DESC LIMIT 1";
        //$sql = "call usp_get_line_listmsg();"
        $query = $this->adapter->query($sql);
        $results = $query->execute();
        $resultSet = new ResultSet;
        $data = $resultSet->initialize($results); 
        $data = $data->toArray();
        return $data;
    }
################################################################################ 
    function getDetail($id=0)
    { 
        $sql = "SELECT * FROM line_msg WHERE line_msgid=".$id." LIMIT 1";
        $statement = $this->adapter->query($sql);
        $results = $statement->execute();
        $row = $results->current();
        return $row;
    }
################################################################################
    function edit($name) 
    { 
        $sql = "UPDATE `users` SET name = '$name', last_update = '$this->now' WHERE id=".$this->id;  
        $sql = $this->adapter->query($sql); 
        return($sql->execute());
    }
################################################################################    
    function sendMsg($iText='')
    {
        try
        { 
            require "vendor/autoload.php";
            //require_once('vendor/linecorp/line-bot-sdk/line-bot-sdk-tiny/LINEBotTiny.php');

            //$access_token = 'cgd3YSfVz6ejOTYzfAKb1lj0Ul4ksYQ6xhjboPhaHFydiDDt9jp2zKYMVcaDs1WDrS/M2woFdcvbUbJigyTULvxzPtUb3hyVcIbHOeus+d4hT0k+wdS/k0brxQG7F1aDmAvyG5xJi5pG9R7DQXlB9gdB04t89/1O/w1cDnyilFU=';
require "vendor/autoload.php";
$access_token = 'cgd3YSfVz6ejOTYzfAKb1lj0Ul4ksYQ6xhjboPhaHFydiDDt9jp2zKYMVcaDs1WDrS/M2woFdcvbUbJigyTULvxzPtUb3hyVcIbHOeus+d4hT0k+wdS/k0brxQG7F1aDmAvyG5xJi5pG9R7DQXlB9gdB04t89/1O/w1cDnyilFU=';
$channelSecret = 'xxxxx';
$idPush = 'xxxxxx'
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($access_token);
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $channelSecret]);
$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder('hello world');
$response = $bot->pushMessage($idPush, $textMessageBuilder);

return $response->getHTTPStatus() . ' ' . $response->getRawBody();
            //return $response->getHTTPStatus() . ' ' . $response->getRawBody();  
            //return ($oText);
        }
        catch( Exception $e )
        {
            print_r($e);
        }
    }
################################################################################    
    function Apiservice($service='', $parameter = [], $type='get')
    {   
        try
        {
            $url= 'https://maps.googleapis.com/maps/api/place/textsearch/json?key=AIzaSyAP4ExPP9WsZav-lGimhJ71omKqiQU4Xb0&query=bangsue&region=th&type=restaurant';
          
            //echo $url;
            $client = new Client($url, array(  
                'adapter' => 'Zend\Http\Client\Adapter\Curl',
                'sslcapath' => '/etc/ssl/certs'
            )); 
            if($type=='post'){  
                $client->setMethod(Request::METHOD_POST);
                $client->setParameterPost($parameter); 
            }
            $response = $client->send();  
            $body = $response->getBody();
            return $body; 
        }
        catch( Exception $e )
        {
            print_r($e);
        }
    }
}
    