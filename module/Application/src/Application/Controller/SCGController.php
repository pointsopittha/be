<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;

use Zend\Mvc\ModuleRouteListener;

use Application\Models\Users;
use Application\Models\Finding;
use Application\Models\LineAPI;

use Zend\Json\Json;
use Zend\View\Model\JsonModel;

use Zend\Cache\StorageFactory;
use Zend\Cache\Storage\Adapter\Memcached;
use Zend\Cache\Storage\StorageInterface;
use Zend\Cache\Storage\AvailableSpaceCapableInterface;
use Zend\Cache\Storage\FlushableInterface;
use Zend\Cache\Storage\TotalSpaceCapableInterface;
/*
$this->params()->fromPost('paramname');   // From POST
$this->params()->fromQuery('paramname');  // From GET
$this->params()->fromRoute('paramname');  // From RouteMatch
$this->params()->fromHeader('paramname'); // From header
$this->params()->fromFiles('paramname');
*/
class SCGController extends AbstractActionController
{
################################################################################ 
    public function __construct()
    {
        $this->cacheTime = 36000;
        $this->now = date("Y-m-d H:i:s");
        $this->config = include __DIR__ . '../../../../config/module.config.php';
        $this->adapter = new Adapter($this->config['Db']);
    }
################################################################################
    public function basic()
    {
        $view = new ViewModel();
        //Route
        $view->lang = $this->params()->fromRoute('lang', 'th');
        $view->action = $this->params()->fromRoute('action', 'index');
        $view->id = $this->params()->fromRoute('id', '');
        $view->page = $this->params()->fromQuery('page', 1);
        //$uri = $this->getRequest()->getUri();
        //$view->baseUrl = $baseUrl;   
        //$view->settext = $this->params()->fromRoute('settext', 'X, 5, 9, 15, 23, Y, Z');
        
        return $view;
    } 
################################################################################
    public function cvAction() 
    {
        try
        {
            $view = $this->basic();
            return $view;
        }
        catch( Exception $e )
        {
            print_r($e);
        }
    }    
################################################################################
    
    public function findAction() 
    {
        try
        { 
            $view = $this->basic();  
            $models = new Finding($this->adapter, $view->id, $view->page);
            
            $iText = 'X, 5, 9, 15, 23, Y, Z';
            $oText='';
            
            $view->settext = $iText;
            $view->foundtext = $models->findText($iText);
            
            $view->foundPlace = $models->findPlace('Place');
            $view->fx =  'find';   
            return $view; 
        }
        catch( Exception $e )
        {
            print_r($e);
        }
    }
################################################################################   
    public function lineAction() 
    {
        try
        {
            $view = $this->basic();
            $act = $this->params()->fromQuery('act', '');
            $models = new LineAPI($this->adapter, $view->id, $view->page);
            if($act == 'detail')
            {
                $view->data = $models->getList();
                $view->detail = $models->getDetail($view->id);
            }
            /*
            else if($act == 'add')
            {
                $name = $this->params()->fromPost('name');
                if($name) $models->add($name);
                $this->redirect()->toRoute('index', ['action'=>'line']);
            }
            else if($act == 'edit')
            {
                $name = $this->params()->fromPost('name');
                if($name) $models->edit($name);
                $this->redirect()->toRoute('index', ['action'=>'line']);
            }
            else if($act == 'del')
            {
                $models->del();
                $this->redirect()->toRoute('index', ['action'=>'line']);
            }*/
            else if($act == 'send')
            {
                $view->lineResult = $models->sendMsg();
                $this->redirect()->toRoute('index', ['action'=>'line']);
            }
            else
            {
                $view->data = $models->getList();
            }
            $view->fx =  'line';   
            return $view;
        }
        catch( Exception $e )
        {
            print_r($e);
        }
    }
################################################################################   
    public function callbackAction() 
    {
        try
        {
            $view = $this->basic();
            $models = new LineAPI($this->adapter, $view->id, $view->page);
            
                $view->lineResult = $models->sendMsg();
                //$this->redirect()->toRoute('index', ['action'=>'line']);
           
            return $view;
        }
        catch( Exception $e )
        {
            print_r($e);
        }
    }    
################################################################################
    public function scgAction() 
    {
        try
        {
            $view = $this->basic();
            $act = $this->params()->fromQuery('act', '');
            $models = new Users($this->adapter, $view->id, $view->page);
            if($act == 'detail')
            {
                $view->data = $models->getList();
                $view->detail = $models->getDetail($view->id);
            }
            else if($act == 'add')
            {
                $name = $this->params()->fromPost('name');
                if($name) $models->add($name);
                $this->redirect()->toRoute('index', ['action'=>'user']);
            }
            else if($act == 'edit')
            {
                $name = $this->params()->fromPost('name');
                if($name) $models->edit($name);
                $this->redirect()->toRoute('index', ['action'=>'user']);
            }
            else if($act == 'del')
            {
                $models->del();
                $this->redirect()->toRoute('index', ['action'=>'user']);
            }
            else
            {
                $view->data = $models->getList();
            }
              $view->fx =  'scg';   
            return $view;
        }
        catch( Exception $e )
        {
            print_r($e);
        }
    }
################################################################################
}