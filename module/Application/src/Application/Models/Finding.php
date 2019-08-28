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

class Finding
{ 
    protected $finding; 
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
    function findText($iText='')
    {
        //Please create a new function for finding X, Y, Z value
        $oText = '';
        try
        { 
            ############### how to find them 1 #################################################################
        
            /*if ( strstr( $iText, 'X' ) && strstr( $iText, 'Y' ) && strstr( $iText, 'Z' )  ) 
            {
                $oText = 'Found X Y Z';
            } 
            else 
            {
                $oText = 'Text Not found';
            }*/
         
            ############### how to find them 2 #################################################################
            /*
            $iText = str_replace(' ','',$iText); // remove space
            $arrText = explode(',',$iText);//transform to array
        
            if (in_array('X', $arrText) && in_array('Y', $arrText) && in_array('Z', $arrText)) 
            {
                $oText = 'Found X Y and Z';
            }
            else
            {
                $oText = 'Text Not found';
            }
            */
        
            ############### how to find them 3 #################################################################
            $oText = '';
            if ( strstr( $iText, 'X' )) 
            {
                $oText .= 'Found X ';
            } 
            
            if(strstr( $iText, 'Y' )) 
            {
                $oText .= 'Found Y ';
            }
            
            if(strstr( $iText, 'Z' )) 
            {
                $oText .= 'Found Z ';
            }
            
            if($oText == '') 
            {
                $oText = 'Text Not found';
            }
            return ($oText);
        }
        catch( Exception $e )
        {
            print_r($e);
        }
    }
################################################################################    
    function findPlace($iText='')
    {
        try
        { 
            //Please use “Place search|Place API(by Google)” for finding all restaurants in Bangsue area and show result by JSON
            $response = $this->Apiservice($iText);    
            //return $response;
            
            $arrRes = (array)json_decode($response);
            
            foreach($arrRes['results'] as $key=>$value)
            {
                $messages[] =  [
                                'id' => $value->id,
                                'name' => $value->name,
                                'rating' => $value->rating,
                                'formatted_address' => $value->formatted_address
                            ];
            }

            $response = json_encode($messages);
            return $response;  
//            return (array)json_decode($response);  
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
    