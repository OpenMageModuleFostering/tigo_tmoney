<?php
/**
 * Module: TIGO Money
 * Author: Marcelo Guevara
 * Email: marcelo.guevara@connaxis.com
 * Skype: connaxis.mguevara
 * Version: 1
*/

class Tigo_Tmoney_Model_Paymethod extends Mage_Payment_Model_Method_Abstract{

    protected $_code = 'tmoney';
    protected $_formBlockType = 'tmoney/form';
    protected $_infoBlockType = 'tmoney/info';
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canUseCheckout = true;

    protected $_order;
    protected $_config;
    protected $_payment;
    protected $_redirectUrl;
    
    var $urlTransaction;
    var $pubKey;
    var $priKey;
    
    var $PostalCode = '';
    var $firstname = '';
    var $lastname = '';
    var $costumerMail = '';
    var $address = '';
    var $province_state = '';
    var $orderId = '';  
    var $root_path = '';
    var $telephone = '';
    
    var $company = '';
    var $vat = '';
    
    var $returnURL = '';
    var $backButtonURL = '';
    var $countryName = '';
    var $mage_currency = '';
    var $lang_mage = '';
    
    
    /*
     * Peryment function who create the connection of LOGIC Core and create a message of response.
     * Here set the parameter of user and TIGO Money Code
     */
    public function authorize(Varien_Object $payment, $amount){
        $response = '';
        $message_response = array();
        $urlTransaction = '';
        
        $this->PostalCode = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getPostcode();
        $this->firstname = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getFirstname();
        $this->lastname = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getLastname();
        $this->costumerMail = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getEmail();
        $this->address = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getStreet();
        $this->province_state = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getRegion();
        
        $this->telephone = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getTelephone();
        $this->contactPhone = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getFax();
        
        $this->orderId = $payment->getOrder()->getIncrementId();  
        $this->root_path = Mage::getBaseDir();
        $this->urlTransaction = $this->getConfigData('submit_url');//url to have a gateway to connect to LOGIC CORE
        $this->pubKey = $this->getConfigData('key_id');//user to validate the connection
        $this->priKey = $this->getConfigData('key_encrypt');//password to connect the connection
        $this->returnURL = Mage::getUrl('tigomoney/index/success', array('_secure' => true, '_query'=> array('orderID' => $this->orderId)));
        $this->backButtonURL = Mage::getUrl('tigomoney/index/back', array('_secure' => true, '_query'=> array('orderID' => $this->orderId)));
        $this->countryName = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getCountryId();
        $this->mage_currency = Mage::app()->getStore()->getCurrentCurrencyCode();
        $this->lang_mage = Mage::app()->getLocale()->getLocaleCode();

        //Mage::Log('Product Name: '.$amount, null, 'tigobusiness-tigomoney.log');
        try { 
           
            $checkout = Mage::getSingleton('checkout/session')->getQuote();
            $billAddress = $checkout->getBillingAddress();
            $variable_blling = $billAddress->getData();
            
            $this->company = isset($variable_blling['company'])? $variable_blling['company'] : '';
            $this->vat = isset($variable_blling['vat_id'])? $variable_blling['vat_id'] : '';
            
            //$billingaddress = $order->getBillingAddress();
            $urlTransaction = $this->tigomoney($amount);
            if($urlTransaction != ''){
                $response = 1;
            }
            
        } catch (Exception $e) {  
            $payment->setStatus(self::STATUS_ERROR);  
            $payment->setAmount($amount);  
            $payment->setLastTransId($this->orderId);  
            $this->setStore($payment->getOrder()->getStoreId());  
            Mage::throwException($e->getMessage());  
            $response = 0;
        }  
        if($response){
            Mage::getSingleton('customer/session')->setRedirectUrl($urlTransaction);
        }else{
            Mage::getSingleton('customer/session')->setRedirectUrl(Mage::getUrl('tigomoney/index/error', array('_secure' => true, '_query'=> array('msg'=>'error', 'mesage'=>'error to create the url and redirect it'))));
        }
        return $this;  
    } 
    /*
     * Default function of magento
     * create a session of user for the ckeckout
     */
    protected function _getCheckout(){
        return Mage::getSingleton('checkout/session');
    }
    /*
     * Default function of magento
     * Get the current order of session
     */
    protected function _getOrder(){   
        return $this->_order;
    }
    
    public function getOrderPlaceRedirectUrl(){
        Mage::Log('returning redirect url:: ' . $this->_redirectUrl , null, 'tigobusiness-tigomoney.log'); 
        return Mage::getSingleton('customer/session')->getRedirectUrl();
    }
    public function createFormBlock($name){
        $block = $this->getLayout()->createBlock('tigo/tmoney/form', $name)
            ->setMethod('tmoney_paymethod')
            ->setPayment($this->getPayment())
            ->setTemplate('tigo/tmoney/form.phtml');
        
        return $block;
    }
    
    public function initialize($paymentAction, $stateObject){
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
    }
    
    public function isAvailable($quote = null){
        if (parent::isAvailable($quote)) {
            return true;
        }
        return false;
    }
    /*
     * TigoMoney Method for transaction
     */
    function tigomoney($amount){
        $reurl = '';
        try{
            $nit_user_name = $this->company;
            $nit_user_number = $this->vat;

            $msisdn = Mage::app()->getRequest()->getParam($this->_code.'_tigo_money_id');//$_REQUEST['tmoney_tigo_money_id'];
            $total = 0;
            $items = array();
            $i = 0;
            $successURL = $this->returnURL;
            $errorURL = $this->backButtonURL;
            $p_total = 0;
            $c_nit = $nit_user_number;
            $order_id = $this->orderId;
            $c_razon = $nit_user_name;
            $url_transaction = (isset($this->urlTransaction))? $this->urlTransaction : 'http://190.129.208.178:96/vipagos/faces/payment.xhtml';
            $pubk = (isset($this->pubKey))? $this->pubKey : '3124b901ec27b88b0694263bc32bcf9e6d266f4b3f0fa891bb28b0970eb035ab7abbb456ef0d582142177ddd4c44e15b0948a37347588423ca97ea7f431194cd';// Identify key
            $prik = (isset($this->priKey))? $this->priKey :'HT4BW8DEP6GUPO2U9A6YHMM0';//Encrypt Key

            $cart = Mage::getModel('checkout/cart')->getQuote();
            foreach ($cart->getAllItems() as $item) {
                $productName = $item->getProduct()->getName();
                $productPrice = $item->getProduct()->getPrice();
                $productQty = ($item->getProduct()->getQty()) ? $item->getProduct()->getQty() : 1 ;
                $p_total = $productQty * $productPrice;
                
                $item_ = '*i'.($i+1).'|'.$productQty.'|'.$productName.'|'.$productPrice.'|'.$amount;
                
                array_push($items, $item_);
                
                $i++;
                Mage::Log('Item data: '.$item_, null, 'tigobusiness-tigomoney.log');
            }
            $ndoc = $c_nit;
            $billetera = $msisdn;
            $monto = $amount;

            $orden = $order_id;
            $nomb = $this->firstname.' '.$this->lastname;
            $confi = '';
            $notif = 'Pago recibido por '.$monto.' Bs Pedido '.$orden;
            $rs = $c_razon;
            $nit = $c_nit;
            $hitems = implode('',$items);
            $prmts = "".
                "pv_nroDocumento=$ndoc;".
                "pv_linea=$billetera;".
                "pv_monto=$monto;".
                "pv_orderId=$orden;".
                "pv_nombre=$nomb;".
                "pv_confirmacion=$confi;".
                "pv_notificacion=$notif;".
                "pv_razonSocial=$rs;".
                "pv_nit=$nit;".
                "pv_items=$hitems;".
                "pv_urlCorrecto=".$successURL.";".
                "pv_urlError=".$errorURL.";".
                "";

            Mage::Log('Encrypt Data of Tigomoney: '.$prmts, null, 'tigobusiness-tigomoney.log');
            $crypt = base64_encode(mcrypt_ecb( MCRYPT_3DES , $prik , $prmts, MCRYPT_ENCRYPT)); 
            $reurl = $url_transaction.'?key='.$pubk.'&parametros='.$crypt;//redirect the URL of transaction
            Mage::Log('URL of Tigomoney item crypt: '.$prmts, null, 'tigobusiness-tigomoney.log');
            Mage::Log('URL of Tigomoney: '.$reurl, null, 'tigobusiness-tigomoney.log');
        } catch (Exception $ex){
            Mage::Log('Error to create the URL of Tigomoney: '.$ex->getMessage(), null, 'tigobusiness-tigomoney.log');
        }
        
        return $reurl;
    }
    /*
     * Verify the transacction
     */
    public function tigoverify($resp, $excep){
        $response_ = array();
        try{
            $tmorder = '';
            $prik = (isset($this->priKey))? $this->priKey :'HT4BW8DEP6GUPO2U9A6YHMM0';
            $response = '';
            $cleanr = '';
            $status = '';
            $ids = array();
            if(isset($excep)){
                $tmorder=strip_tags($excep);
            } 
            if (isset($resp)) {
                $response = base64_decode(str_replace(' ','+',$resp));
                $cleanr = mcrypt_ecb( MCRYPT_3DES , $prik , $response, MCRYPT_DECRYPT);// esto puede cambiar en php 5.5
            } else {
                    $response='';
            }

            $arr_resp = explode('&', $cleanr);
            $cod = explode('=', $arr_resp[0]);
            $cod  = $cod[1];
            $response_['code'] = $cod;

            $msg = explode('=', $arr_resp[1]);
            $msg  = $msg[1];
            $response_['msg'] = $msg;

            $oid = explode('=', $arr_resp[2]);
            $oid  = $oid[1];
            $response_['orderId'] = $oid;

            $msg = explode(':', $msg);//el primer string es legible para el publico
            $msg = $msg[0];
            $response_['msg_public'] = $msg;
            
        } catch (Exception $ex) {
            Mage::Log('Error to verify the status of Tigomoney Transaction: '.$ex->getMessage(), null, 'tigobusiness-tigomoney.log');
        }
        return $response_;
    }
}
