<?php
/**
 * Module: TIGO Money
 * Author: Marcelo Guevara
 * Email: marcelo.guevara@connaxis.com
 * Skype: connaxis.mguevara
 * Version: 1.1.0
*/
require_once(Mage::getBaseDir('lib') . '/nusoap/nusoap.php');

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
    
    var $proxyusername;
    var $proxypassword;
    var $proxyhost;
    var $proxyport;
    
    /*Verify in he Site*/
    public function setParamKeyTigo(){
        try {
            $this->urlTransaction = $this->getConfigData('submit_url');//url to have a gateway to connect to LOGIC CORE
            $this->pubKey = $this->getConfigData('key_id');//user to validate the connection
            $this->priKey = $this->getConfigData('key_encrypt');//password to connect the connection

            $this->proxyusername = $this->getConfigData('proxyusername');
            $this->proxypassword = $this->getConfigData('proxypassword');
            $this->proxyhost = $this->getConfigData('proxyhost');
            $this->proxyport = $this->getConfigData('proxyport');
        } catch (Exception $ex) {
            Mage::Log('Error setParamKeyTigo: '.$ex->getMessage(), null, 'tigobusiness-tigomoney.log');
        }
    }
    
    /*Verify by cron job*/
    public function setParamKeyTigoCron($storeID){
        try {
            $this->urlTransaction = Mage::getStoreConfig('tigo_tmoney/tmoney/submit_url', $storeID);//url to have a gateway to connect to LOGIC CORE
            $this->pubKey = Mage::getStoreConfig('tigo_tmoney/tmoney/key_id', $storeID);//user to validate the connection
            $this->priKey = Mage::getStoreConfig('tigo_tmoney/tmoney/key_encrypt', $storeID);//password to connect the connection

            $this->proxyusername = Mage::getStoreConfig('tigo_tmoney/tmoney/proxyusername', $storeID);
            $this->proxypassword = Mage::getStoreConfig('tigo_tmoney/tmoney/proxypassword', $storeID);
            $this->proxyhost = Mage::getStoreConfig('tigo_tmoney/tmoney/proxyhost', $storeID);
            $this->proxyport = Mage::getStoreConfig('tigo_tmoney/tmoney/proxyport', $storeID);
        } catch (Exception $ex) {
            Mage::Log('Error setParamKeyTigoCron: '.$ex->getMessage(), null, 'tigobusiness-tigomoney.log');
        }
    }
    
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
        
        $this->proxyusername = $this->getConfigData('proxyusername');
        $this->proxypassword = $this->getConfigData('proxypassword');
        $this->proxyhost = $this->getConfigData('proxyhost');
        $this->proxyport = $this->getConfigData('proxyport');
        
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
            Mage::getSingleton('customer/session')->setRedirectUrl(Mage::getUrl('tigomoney/index/status'));
            //$billingaddress = $order->getBillingAddress();
            $transaction = $this->tigomoney($amount);
            
            $query_ = '';
            $i = 0;
            foreach ($transaction as $label => $val){
                if($i != 0){
                    $query_ .= '&';
                }
                $query_ .=  $label.'='.$val;
                $i++;
            }

            $this->_redirectUrl = Mage::getUrl('tigomoney/index/status', array('_query'=>$query_));
            Mage::getSingleton('customer/session')->setRedirectUrl(Mage::getUrl('tigomoney/index/status', array('_query'=>$query_)));
            
        } catch (Exception $e) {  
            Mage::Log('Item data: '.$e->getMessage(), null, 'tigobusiness-tigomoney.log');
            
        }  
        //if($response){
            //Mage::getSingleton('customer/session')->setRedirectUrl(Mage::getUrl('tigomoney/index/status', array()));
        //}
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
        return Mage::getSingleton('customer/session')->getRedirectUrl($this->_redirectUrl);
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
            $url_transaction = $this->urlTransaction;
            $pubk = $this->pubKey;// Identify key
            $prik = $this->priKey;//Encrypt Key

            $cart = Mage::getModel('checkout/cart')->getQuote();
            foreach ($cart->getAllItems() as $item) {
                $productName = $item->getProduct()->getName();
                $productPrice = $item->getProduct()->getPrice();
                $productQty = ($item->getProduct()->getQty()) ? $item->getProduct()->getQty() : 1 ;
                $p_total = $productQty * $productPrice;
                
                $item_ = '*i'.($i+1).'|'.$productQty.'|'.$productName.'|'.$productPrice.'|'.$amount;
                
                array_push($items, $item_);
                
                $i++;
                //Mage::Log('Item data: '.$item_, null, 'tigobusiness-tigomoney.log');
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
            //$reurl = $url_transaction.'?key='.$pubk.'&parametros='.$crypt;//redirect the URL of transaction
            //Mage::Log('URL of Tigomoney item crypt: '.$prmts, null, 'tigobusiness-tigomoney.log');
            //Mage::Log('URL of Tigomoney: '.$reurl, null, 'tigobusiness-tigomoney.log');
            $param = array(
                'key' => $pubk,
                'parametros' => $crypt
            );
            Mage::Log('URL of Tigomoney: '.print_r($param, true), null, 'tigobusiness-tigomoney.log');
            
            $connect = array();
            
            $connect['proxyusername'] = $this->proxyusername;
            $connect['proxypassword'] = $this->proxypassword;
            $connect['proxyhost'] = $this->proxyhost;
            $connect['proxyport'] = $this->proxyport;
            $connect['urlTransaction'] = $this->urlTransaction;
            
            $reurl = $this->wsAction('solicitarPago', $param, $connect);
            $data_ = array(
                array(
                    'tmoney_order_id' => $this->orderId,
                    'tomoney_ws_id' => ((is_array($reurl))? $reurl['orderId'] : $reurl),
                ),
            );
            //Mage::getModel('tmoney/tigomoney')->setData($data_)->save();
            
        } catch (Exception $ex){
            Mage::Log('Error to create the URL of Tigomoney: '.$ex->getMessage(), null, 'tigobusiness-tigomoney.log');
        }
        
        return $reurl;
    }
    
    /*
     * WSDL action
     */
    public function wsAction($action, $param, $connect = array()){
        $result = '';
        try{
            $proxyusername = $connect['proxyusername'];
            $proxypassword = $connect['proxypassword'];
            $proxyhost = $connect['proxyhost'];
            $proxyport = $connect['proxyport'];
            
            $webservice = $connect['urlTransaction'];
            
            //Mage::Log('Webservice Proxy user: '.$proxyusername.'- pass: '.$proxypassword.' - host:'.$proxyhost.' - port:'.$proxyport.' - url: '.$webservice, null, 'tigobusiness-tigomoney.log');
            
            $client = new nusoap_client($webservice,'wsdl', $proxyhost, $proxyport, $proxyusername, $proxypassword);
            $err = $client->getError();
            if ($err) {
                Mage::Log('Error of webservice: '.$err, null, 'tigobusiness-tigomoney.log');
            }
            $result_ = $client->call(trim($action), $param);
            //Mage::Log('Webservice '.$action.' Info: '.print_r($result_, true).' --- Param: '.print_r($param, true), null, 'tigobusiness-tigomoney.log');
            if($result_){
                if(isset($result_['return'])){
                    if($action == 'solicitarPago'){
                        $result = $this->decrypMessage($result_['return']);
                    }else{
                        $result = $this->decrypMessageVerify($result_['return']);
                    }
                }
            }
            Mage::Log('Webservice '.$action.' Info: '.print_r($result, true), null, 'tigobusiness-tigomoney.log');
            
        } catch (Exception $ex) {
            Mage::Log('Webservice error: '.$ex->getMessage(). ' - action: '.$action, null, 'tigobusiness-tigomoney.log');
        }
        return $result;
    }
    /*
     * Decript Message
     */
    public function decrypMessage($message){
        $result = array();
        try{
            $prik = $this->priKey;
            $response = '';
            $cleanr = '';
            if (isset($message)) {
                $response = base64_decode(str_replace(' ','+',$message));
                $cleanr = mcrypt_ecb( MCRYPT_3DES , $prik , $response, MCRYPT_DECRYPT);
                if($cleanr){
                    $result_ = explode('&', $cleanr);
                    for($i = 0; $i < count($result_); $i++){
                        $data_ = explode('=',$result_[$i]);
                        if(count($data_) > 1){
                            $result[$data_[0]] = $data_[1];
                        }else{
                            $result['data'] = (isset($result_[$i]))? $result_[$i] : $result_;
                        }
                    }
                }
                //Mage::Log('Error: '.print_r($result, true), null, 'tigobusiness-tigomoney.log');
                //Mage::Log('decrypMessage data: '.$cleanr, null, 'tigobusiness-tigomoney.log');
            } 
        } catch (Exception $ex) {
            Mage::Log('Error decrypMessage: '.$ex->getMessage(), null, 'tigobusiness-tigomoney.log');
        }
        return $result;
    }
    /*
     * Decrip Veriy Message
     */
    public function decrypMessageVerify($message){
        $result = array();
        try{
            $prik = $this->priKey;
            $response = '';
            $cleanr = '';
            if (isset($message)) {
                $response = base64_decode(str_replace(' ','+',$message));
                $cleanr = mcrypt_ecb( MCRYPT_3DES , $prik , $response, MCRYPT_DECRYPT);
                if($cleanr){
                    $result = explode(';', $cleanr);
                }
            } 
        } catch (Exception $ex) {
            Mage::Log('Error decrypMessageVerify: '.$ex->getMessage(), null, 'tigobusiness-tigomoney.log');
        }
        return $result;
    }
    /*
     * Verify transaction
     */
    public function verify($id){
        $result = '';
        try{
            
            $this->setParamKeyTigo();
            
            $pubk = $this->pubKey;// Identify key
            $prik = $this->priKey;//Encrypt Key
            Mage::Log('the status -- of Cronjob: '.$this->urlTransaction, null, 'tigobusiness-tigomoney.log');
            $connect = array();
            
            $connect['proxyusername'] = $this->proxyusername;
            $connect['proxypassword'] = $this->proxypassword;
            $connect['proxyhost'] = $this->proxyhost;
            $connect['proxyport'] = $this->proxyport;
            $connect['urlTransaction'] = $this->urlTransaction;
            
            //Mage::Log('Error data Verify: '.$pubk.' ---- '.$prik, null, 'tigobusiness-tigomoney.log');
            
            $response = '';
            $cleanr = '';
            if (isset($id)) {
                $cleanr = base64_encode(mcrypt_ecb( MCRYPT_3DES , $prik , $id, MCRYPT_ENCRYPT)); 
            } else {
                $response='';
            }
            $param = array(
                'key' => trim($pubk),
                'parametros' => trim($cleanr)
            );
            //Mage::Log('Error Param Verify: '.print_r($param, true), null, 'tigobusiness-tigomoney.log');
            $result = $this->wsAction('consultarEstado', $param, $connect);
            //Mage::Log('Error result Verify: '.print_r($result, true), null, 'tigobusiness-tigomoney.log');
        } catch (Exception $ex){
            Mage::Log('Error Verify: '.$ex->getMessage(), null, 'tigobusiness-tigomoney.log');
        }
        return $result;
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
    /*
     * Get Verify Action in cronjob
     */
    public function verifyCron($id, $storeID){
        $result = '';
        try{
            Mage::Log('the status of Cronjob: '.$id.'  -  '. $storeID, null, 'tigobusiness-tigomoney.log');
            $this->setParamKeyTigoCron($storeID);
            Mage::Log('the status -- of Cronjob: '.Mage::getStoreConfig('tigo_tmoney/tmoney/submit_url', $storeID), null, 'tigobusiness-tigomoney.log');
            $pubk = $this->pubKey;// Identify key
            $prik = $this->priKey;//Encrypt Key
            
            $connect = array();
            
            $connect['proxyusername'] = $this->proxyusername;
            $connect['proxypassword'] = $this->proxypassword;
            $connect['proxyhost'] = $this->proxyhost;
            $connect['proxyport'] = $this->proxyport;
            $connect['urlTransaction'] = $this->urlTransaction;
            
            //Mage::Log('Error data Verify: '.$pubk.' ---- '.$prik, null, 'tigobusiness-tigomoney.log');
            
            $response = '';
            $cleanr = '';
            if (isset($id)) {
                $cleanr = base64_encode(mcrypt_ecb( MCRYPT_3DES , $prik , $id, MCRYPT_ENCRYPT)); 
            } else {
                $response='';
            }
            $param = array(
                'key' => trim($pubk),
                'parametros' => trim($cleanr)
            );
            //Mage::Log('Error Param Verify: '.print_r($param, true), null, 'tigobusiness-tigomoney.log');
            $result = $this->wsAction('consultarEstado', $param, $connect);
            //Mage::Log('Error result Verify: '.print_r($result, true), null, 'tigobusiness-tigomoney.log');
        } catch (Exception $ex){
            Mage::Log('Error Verify: '.$ex->getMessage(), null, 'tigobusiness-tigomoney.log');
        }
        return $result;
    }
    /*
     * Error Message
     */
    public function getError($error){
        $result = array();
        try{
            switch ($error){
                case '4': 
                    $result['id'] = $error;
                    $result['msg'] = 'AGENT_NOT_REGISTERED';
                    $result['msg_show'] = 'Comercio no registrado.';
                    break;
                case '7':
                    $result['id'] = $error;
                    $result['msg'] = 'ACCESS_DENIED';
                    $result['msg_show'] = 'Acceso Denegado. Por favor intente nuevamente y verifique los datos ingresados.';
                    break;
                case '8':
                    $result['id'] = $error;
                    $result['msg'] = 'BAD_PASSWORD';
                    $result['msg_show'] = 'PIN no válido, intente nuevamente.';
                    break;
                case '11':
                    $result['id'] = $error;
                    $result['msg'] = 'PASSWORD_RETRY_EXCEED';
                    $result['msg_show'] = 'Tiempo de respuesta excedido. Por favor inicie la transacción nuevamente.';
                    break;
                case '14':
                    $result['id'] = $error;
                    $result['msg'] = 'TARGET_NOT_REGISTERED';
                    $result['msg_show'] = 'Billetera Móvil de destino no registrada. Por favor verifique sus datos.';
                case '17':
                    $result['id'] = $error;
                    $result['msg'] = 'INVALID_AMOUNT';
                    $result['msg_show'] = 'Monto no válido, verifique los datos proporcionados.';
                    break;
                case '19':
                    $result['id'] = $error;
                    $result['msg'] = 'AGENT_BLACKLISTED';
                    $result['msg_show'] = 'Comercio no habilitado para el pago. Por favor comunicarse con el comercio.';
                    break;
                case '23':
                    $result['id'] = $error;
                    $result['msg'] = 'AMOUNT_TOO_SMALL';
                    $result['msg_show'] = 'El monto introducido es menor al requerido, favor verifique los datos.';
                    break;
                case '24':
                    $result['id'] = $error;
                    $result['msg'] = 'AMOUNT_TOO_BIG';
                    $result['msg_show'] = 'El monto introducido es mayor al requerido, favor verifique los datos.';
                    break;
                case '1001':
                    $result['id'] = $error;
                    $result['msg'] = 'INSUFFICIENTFUNDS';
                    $result['msg_show'] = 'Los fondos en su Billetera Móvil son insuficientes, para realizar una carga diríjase al punto Tigo Money más cercano o marque *555#';
                    break;
                case '1002':
                    $result['id'] = $error;
                    $result['msg'] = 'TRANSACTIONRECOVERED';
                    $result['msg_show'] = 'PIN incorrecto, su transacción no pudo ser completada. Inicie la transacción nuevamente y verifique en transacciones por completar.';
                    break;
                case '1004': 
                    $result['id'] = $error;
                    $result['msg'] = 'WALLETCAPEXCEEDED';
                    $result['msg_show'] = 'Estimado cliente ha llegado al límite transacciones, si tiene alguna consulta comuníquese con el *555';
                    break;
                case '1012':
                    $result['id'] = $error;
                    $result['msg'] = 'PASSWORDERRORRETRYEXCEEDED';
                    $result['msg_show'] = 'Estimado cliente ha excedido el límite de intentos de introducción de PIN. Por favor comuníquese con el *555 para solicitar su nuevo PIN.';
                    break;
                case '560':
                    $result['id'] = $error;
                    $result['msg'] = 'MISMO MONTO, ORIGEN Y DESTINO DENTRO DE 1 MIN';
                    $result['msg_show'] = 'Estimado cliente su transacción no fue completada favor intentar nuevamente en 1 minuto.';
                    break;
            }
        } catch (Exception $ex) {
            Mage::Log('Error get ErrorMessage: '.$ex->getMessage(), null, 'tigobusiness-tigomoney.log');
        }
        return $result;
    }
}
