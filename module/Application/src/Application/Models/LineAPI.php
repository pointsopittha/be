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
        $this->host  = "http" . (($_SERVER['SERVER_PORT'] == 443) ? "s://" : "://") . $_SERVER['HTTP_HOST'];
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
        
        $this->MAC = exec('getmac'); 
        $this->MAC = strtok($this->MAC, ' '); 
    } 
################################################################################ 
    function getList()
    {
        try
        {
                $data = [];
                $sql = "SELECT line_msgid,line_msg,url,imageurl FROM line_msg";
                //$sql = "call usp_get_line_listmsg();"
                $query = $this->adapter->query($sql);
                $results = $query->execute();
                $resultSet = new ResultSet;
                $data = $resultSet->initialize($results); 
                $data = $data->toArray();
                return $data;
        }
        catch( Exception $e )
        {
            error_log('Exception='.$e);
        }
    }
################################################################################ 
    function getDetail()
    { 
        $sql = "SELECT * FROM line_msg WHERE line_msgid=".$this->id." LIMIT 1";
        $statement = $this->adapter->query($sql);
        $results = $statement->execute();
        $row = $results->current();
        return $row;
    }
################################################################################  
    function add($line_msg,$url,$createby,$contentid)    
    {
        $sql = "INSERT INTO line_msg (line_msg, url,line_msg_contentid,createby) VALUES ('".$line_msg."', '".$url."',1, ".$createby.");";
        $sql = $this->adapter->query($sql);
        return($sql->execute());
    }
################################################################################  
    function log($logdesc,$logaction)    
    {
        $sql = $this->adapter->query("INSERT INTO `log_action` (logdesc, logaction,logip,loguser) VALUES ('$logdesc', '$logaction','$this->ip', $createby)");
        return($sql->execute());
    }
################################################################################  
    function del()    
    {
        $sql = $this->adapter->query("DELETE FROM `line_msg` WHERE line_msgid=".$this->id);
        return($sql->execute());
    }
################################################################################
    function edit($line_msg,$url) 
    { 
        $sql = "UPDATE `line_msg` SET line_msg = '$line_msg',url = '$url', lastupdate = '$this->now' WHERE line_msgid=".$this->id;  
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
            $post='';
            error_log('if');
            // Validate parsed JSON data
            if (!is_null($events['events'])) 
            {
                error_log('!is_null');
                        
                // Loop through each event
                foreach ($events['events'] as $event) 
                {
                    // Reply only when message sent is in 'text' format
                    if ($event['type'] == 'message' && $event['message']['type'] == 'text') 
                    {
                        error_log($event['type']);
                        $url = 'https://api.line.me/v2/bot/message/reply';
                        // Get userId
                        //$text = $event['source']['userId'];
                        // Get replyToken
                        $replyToken = $event['replyToken'];
                        // prepare message to reply back
                        error_log('replytoken='.$replyToken);
                        error_log('text='.$event['message']['text']);
                        $text = $event['message']['text'];
                        //error_log('post'.$post);
                        //$post = getDetailFromText($event['message']['text'],$replyToken);
                        $sql = "SELECT line_msgid,line_msg,url,imageurl FROM line_msg WHERE line_msg_contentid = '$text';";
                        $query = $this->adapter->query($sql);
                        $results = $query->execute();
                        $resultSet = new ResultSet;
                        $dataa = $resultSet->initialize($results); 
                        $dataa = $dataa->toArray();
                        //$messages;
                        error_log('dataa');
                        foreach($dataa as $key=>$value)
                        {
                            $preURL = $this->host."/public/scg/th/click/".$value['line_msgid']."/";
                            error_log('preURL'.$preURL);
                            $messages[] =  [
                                            'type' => 'text',
                                            'text' => $value['line_msg'].' '.$preURL
                                        ];
                        }
                        error_log('foreach');
                        $data = [
                                        'replyToken' => $replyToken,
                                        'messages' => $messages,
                                        ];
                        error_log('data');
                        
                        $post = json_encode($data);
                        //error_log('data');
                        error_log('post');
                        
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
                    }
                }
            }
            else
            {
                 error_log('event null');
            }
            return "OK";
        }
        catch( Exception $e )
        {
            error_log($e);
        }
    }
################################################################################  
    function clickURL()    
    {
        $sql = $this->adapter->query("INSERT INTO MZtG5O9hWn.line_click_log (line_msgid,ip,mac_address) VALUES ($this->id,'$this->ip','$this->MAC');");
        $sql->execute();
        
        $sql = "SELECT * FROM line_msg WHERE line_msgid=".$this->id." LIMIT 1";
        $statement = $this->adapter->query($sql);
        $results = $statement->execute();
        $row = $results->current();
        $redirectURL = $row['url'];
        
        return $redirectURL;
    }
################################################################################    
    function Apiservice($service='', $parameter = [], $type='post')
    {   
        try
        {
            $url= 'https://api.line.me/v2/bot/message/push';
           
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