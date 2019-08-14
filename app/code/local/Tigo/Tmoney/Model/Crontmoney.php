<?php
/**
 * Module: TIGO Money
 * Author: Marcelo Guevara
 * Email: marcelo.guevara@connaxis.com
 * Skype: connaxis.mguevara
 * Version: 1.1.0
*/
class Tigo_Tmoney_Model_Crontmoney {
    
    public function changeStatusTigoMoneyCronJob(){
        try{
            $website_name = Mage::app()->getWebsite()->getName();
            $store_name = Mage::app()->getStore()->getName();
            
            $tigomoneyb_ = Mage::getModel('tmoney/paymethod');
            
            $timeout = $tigomoneyb_->getConfigData('timeout');
            if($timeout and trim($timeout) != '' and trim($timeout) != '0'){
                $timeout_our = 0;
                $timeout_min = 0;

                if(strpos($timeout, '.') !== false){
                    $new_timeout = explode('.', $timeout);
                    $timeout_our = $new_timeout[0];
                    $timeout_min = (floatval($timeout) - intval($timeout_our)) * 60;
                }else{
                    $timeout_our = $timeout;
                }

                $module_code = $tigomoneyb_->getCode();

                $fromDate = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')-365, date('Y')));
                $toDate = date('Y-m-d H:i:s',mktime(23, 0, 0, date('m'), date('d'), date('Y')));
                $current_date = explode('-', date('d-m-Y', Mage::getModel('core/date')->timestamp(time())));

                $orders = Mage::getResourceModel('sales/order_collection')
                        ->addAttributeToFilter('created_at', array('from'=>$fromDate, 'to'=>$toDate))
                        ->addAttributeToFilter('status', array('eq' => 'pending'))
                        ->addAttributeToSort('increment_id', 'desc')->load();

                Mage::Log(' - time: '. intval($timeout_our).' - min: '.$timeout_min , null, 'tigobusiness-tigomoney-cron.log');

                foreach($orders as $order){
                    $infoPayment = $order->getPayment();
                    $stateOrder = $order->getStatus();
                    $date_time = $order->getCreatedAtStoreDate();
                    $paymentMethod = $infoPayment->getMethodInstance()->getCode();
                    
                    
                    $date_time_ = explode(' ', $date_time);
                    $sepa_ = '-';
                    if (stripos($date_time_[0], '/') !== false) {
                        $sepa_ = '/';
                    }
                    $date_ = explode( $sepa_, $date_time_[0]);
                    $time_ = explode(':',$date_time_[1]);
                    $time_current = Mage::getModel('core/date')->timestamp(time());
                    $time_order = mktime(intval($time_[0]) + intval($timeout_our), intval($time_[1]) + intval($timeout_min), $time_[2], $date_[1], $date_[0], $date_[2]);
                    
                    if(trim($paymentMethod) == $module_code){
                        Mage::Log('Data error: '.$order->getIncrementId().'   -   '.$order->getStoreId() , null, 'tigobusiness-tigomoney-cron.log');
                        
                        $data_money = $tigomoneyb_->verify($order->getIncrementId(), $order->getStoreId());
                        Mage::Log('Data ---- result ---- error: '.print_r($data_money, true) , null, 'tigobusiness-tigomoney-cron.log');
                        if(trim($data_money[0]) != 0 and trim($data_money[0]) != ''){
                            if((intval($date_[0]) == intval($current_date[0])) and (intval($date_[1]) == intval($current_date[1])) and (intval($date_[2]) == intval($current_date[2]))){
                                if($time_current >= $time_order){
                                    if(!$order->canCancel()) {
                                        Mage::Log('Cancel order error: '.$order->getId() , null, 'tigobusiness-tigomoney-cron.log');
                                    }
                                    $errorMsg = 'Canceled';
                                    $order->registerCancellation($errorMsg);
                                    $order->cancel();
                                    $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true)->save();
                                }
                            }else{
                                if(!$order->canCancel()) {
                                    Mage::Log('Cancel order error: '.$order->getId() , null, 'tigobusiness-tigomoney-cron.log');
                                }
                                $errorMsg = 'Canceled';
                                $order->registerCancellation($errorMsg);
                                $order->cancel();
                                $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true)->save();
                            }
                        }else{
                            if(isset(trim($data_money[0])) and trim($data_money[0]) == 0){
                                if(!$order->canCancel()) {
                                    Mage::Log('Cancel order error: '.$order->getId() , null, 'tigobusiness-tigomoney-cron.log');
                                }
                                $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)->save();
                            }
                        }
                    }
                }
            }
                           
        } catch (Exception $ex) {
            Mage::Log('Error in cronjob of TigoMoneyb: ' . $ex->getMessage() , null, 'tigobusiness-tigomoney-cron.log');
        }
    }
    
}
