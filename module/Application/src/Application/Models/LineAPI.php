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
        $this->access_token = 'cgd3YSfVz6ejOTYzfAKb1lj0Ul4ksYQ6xhjboPhaHFydiDDt9jp2zKYMVcaDs1WDrS/M2woFdcvbUbJigyTULvxzPtUb3hyVcIbHOeus+d4hT0k+wdS/k0brxQG7F1aDmAvyG5xJi5pG9R7DQXlB9gdB04t89/1O/w1cDnyilFU=';
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
        $sql = "SELECT line_msgid,line_msg,url,imageurl,createby,createdate,updateby,lastupdate FROM vw_line_listmsg";
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
    function getDetailFromText($imsg='')
    { 
        $sql = "SELECT line_msgid,line_msg,url,imageurl FROM vw_line_listmsg WHERE line_msg_content = '".$imsg."'";
        $query = $this->adapter->query($sql);
        $results = $query->execute();
        $resultSet = new ResultSet;
        $data = $resultSet->initialize($results); 
        $data = $data->toArray();
        //$messages;
        foreach($data as $key=>$value)
        {
            $messages[] =  [
                            'type' => 'text',
                            'text' => $value['line_msg'].' '.$value['url']
                        ];
        }
       /* $data = [
                        'replyToken' => '$replyToken',
                        'messages' => $messages,
                        ];
        $post = json_encode($data);*/
        return $messages;
    }
################################################################################
    function edit($name) 
    { 
        $sql = "UPDATE `users` SET name = '$name', last_update = '$this->now' WHERE id=".$this->id;  
        $sql = $this->adapter->query($sql); 
        return($sql->execute());
    }
################################################################################    
    function callbackUser()
    {
        try
        { 
            require "vendor/autoload.php";
            require_once('vendor/linecorp/line-bot-sdk/line-bot-sdk-tiny/LINEBotTiny.php');
            
            // Get POST body content
            $content = file_get_contents('php://input');
            // Parse JSON
            $events = json_decode($content, true);
            // Validate parsed JSON data
            if (!is_null($events['events'])) 
            {
                // Loop through each event
                foreach ($events['events'] as $event) 
                {
                    // Reply only when message sent is in 'text' format
                    if ($event['type'] == 'message' && $event['message']['type'] == 'text') 
                    {
                        // Get userId
                        //$text = $event['source']['userId'];
                        // Get replyToken
                        $replyToken = $event['replyToken'];
                        // prepare message to reply back
                            $messages = [
                            'type' => 'text',
                            'text' => $event['message']['text']
                            ];
                        //$messages[] = getDetailFromText($event['message']['text']);
                        // Make a POST Request to Messaging API to reply to sender
                        $url = 'https://api.line.me/v2/bot/message/reply';
                        $data = [
                        'replyToken' => $replyToken,
                        'messages' => [$messages],
                        ];
                        $post = json_encode($data);
                        $headers = array('Content-Type: application/json', 'Authorization: Bearer ' . $this->access_token);
                        $ch = curl_init($url);
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                        $result = curl_exec($ch);
                        curl_close($ch);
                        return $result . "\r\n";
                        //return $result;
                    }
                }
            }
            return "OK3";
            //return $response->getHTTPStatus() . ' ' . $response->getRawBody();  
            //return ($oText);
        }
        catch( Exception $e )
        {
            print_r($e);
        }
    }
################################################################################    
    function replyMsg($idPush='',$replymsg='')
    {
        try
        { 
/*
            require "vendor/autoload.php";
            //$access_token = 'cgd3YSfVz6ejOTYzfAKb1lj0Ul4ksYQ6xhjboPhaHFydiDDt9jp2zKYMVcaDs1WDrS/M2woFdcvbUbJigyTULvxzPtUb3hyVcIbHOeus+d4hT0k+wdS/k0brxQG7F1aDmAvyG5xJi5pG9R7DQXlB9gdB04t89/1O/w1cDnyilFU=';
            $channelSecret = 'bcc7ab382cd4718d958bf9ea9eb3580f';
            $idPush = 'U4dcee7cf9fb7bb2f9eb2f32603d5bc64'
            $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($this->access_token);
            $bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $channelSecret]);
            $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($replymsg);
            $response = $bot->pushMessage($idPush, $textMessageBuilder);
            
            return $response->getHTTPStatus() . ' ' . $response->getRawBody();
            */
            //return "OK";
            //return $response->getHTTPStatus() . ' ' . $response->getRawBody();  
            //return ($oText);
        }
        catch( Exception $e )
        {
            print_r($e);
        }
    }
################################################################################    
    function Apiservice($service='', $parameter = [], $type='post')
    {   
        try
        {
            $url= 'https://api.line.me/v2/bot/message/push';
           
            //$pre_para='{"to": "U4dcee7cf9fb7bb2f9eb2f32603d5bc64","messages":[{"type":"text","text":"Hello, world1"},{"type":"text","text":"Hello, world2"}]}';
            
          
            //echo $url;
            $client = new Client($url, array(  
                'adapter' => 'Zend\Http\Client\Adapter\Curl',
                'sslcapath' => '/etc/ssl/certs'
            )); 
            if($type=='post'){  
                //$headers = array('Content-Type: application/json', 'Authorization: Bearer ' . $this->access_token);
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
    