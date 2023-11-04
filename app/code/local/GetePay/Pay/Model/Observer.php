<?php
class GetePay_Pay_Model_Observer
{
    public function checkPendingPayments()
    {
        // Fetch all pending orders' increment_id from the sales_order table.
        $orderCollection = Mage::getModel('sales/order')->getCollection()->addFieldToSelect(['increment_id', 'getepay_payment_id'])->addFieldToFilter('status', 'pending')->addFieldToFilter('getepay_payment_id', ['notnull' => true]);
        $result = $orderCollection->getData();

        /**
         * @var $module GetePay_Pay_Model_Functions
         */
        $module = Mage::getModel( 'pay/functions' );
        if ( $module->getGetepayPendingPaymentsCron() ? true : false ) {

            $req_url        = $module->getRequestURL();
            $payment_chk_url= $module->getPaymentRequeryURL();
            $getepay_mid    = $module->getGetepayMID();
            $terminalId     = $module->getTerminalID();
            $getepay_key    = $module->getGetepayKEY();
            $getepay_iv     = $module->getGetepayIV();
            $key            = base64_decode($getepay_key);
            $iv             = base64_decode($getepay_iv);
            // Loop through each order Ids
            foreach ($result as $row) {
                $incrementId = $row['increment_id'];
                $getepayPaymentId = $row['getepay_payment_id'];
                //GetePay Callback
                $requestt = array(
                    "mid" => $getepay_mid ,
                    "paymentId" => $getepayPaymentId,
                    "referenceNo" => "",
                    "status" => "",
                    "terminalId" => $terminalId,
                );
                $json_requset = json_encode($requestt);	
                $ciphertext_raw = openssl_encrypt($json_requset, "AES-256-CBC", $key, $options = OPENSSL_RAW_DATA, $iv);	
                $ciphertext = bin2hex($ciphertext_raw);	
                $newCipher = strtoupper($ciphertext);	
                $request = array(
                    "mid" => $getepay_mid,
                    "terminalId" => $terminalId,
                    "req" => $newCipher
                );
                $curl = curl_init();	
                curl_setopt($curl, CURLOPT_URL, $payment_chk_url);
                curl_setopt($curl, CURLINFO_HEADER_OUT, true);
                curl_setopt(
                    $curl,
                    CURLOPT_HTTPHEADER,
                    array(
                        'Content-Type:application/json',
                    )
                );
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($request));
                $result = curl_exec($curl);
                curl_close($curl);	
                $jsonDecode = json_decode($result);
                $jsonResult = $jsonDecode->response;	
                $ciphertext_raw = hex2bin($jsonResult);
                $original_plaintext = openssl_decrypt($ciphertext_raw, "AES-256-CBC", $key, $options = OPENSSL_RAW_DATA, $iv);
                $json = json_decode($original_plaintext);
                $orderId = $json->merchantOrderNo;
                $getepayTxnId = $json->getepayTxnId;
                # get order and payment objects
                $order = Mage::getModel( 'sales/order' )->loadByIncrementId( $orderId );
            
                // Update order status
                if($json->txnStatus == "SUCCESS"){
                    /**
                     * @var $invoice Mage_Sales_Model_Order_Invoice
                     */
                    $invoice = $order->prepareInvoice();
                    $invoice->setState( Mage_Sales_Model_Order_Invoice::STATE_PAID );
                    $invoice->register()->capture();
                    $order->addRelatedObject( $invoice );
                    $order->setState( Mage_Sales_Model_Order::STATE_PROCESSING, 'Processing', 'Payment Response: Payment SUCCESS with Getepay', true );

                    if ( $module->getOrderSuccessfulEmail() ? true : false ) {
                        $order->sendOrderUpdateEmail( true, 'Payment Completed with Getepay' );
                    }
                    $this->clearCart();
            
                } 
                elseif( $json->txnStatus == "FAILED" ) {                    
                    $order->cancel();
                    $order->setState( Mage_Sales_Model_Order::STATE_CANCELED, true, 'Payment Response: Payment FAILED with Getepay' );
                    $order->setStatus( 'canceled' );

                    if ( $module->getOrderFailedEmail() ? true : false ) {
                        $order->sendOrderUpdateEmail( true, 'Failed to make Payment with Getepay due to transaction declined' );
                    }
                }
                $order->save();
            }
        }
    }

    public function checkCanclePendingOrders()
    {
        // Execute only if Cancel Pending Order Cron is Enabled
        /**
         * @var $module GetePay_Pay_Model_Functions
         */
        $module = Mage::getModel( 'pay/functions' );
            
        if ( $module->getCanclePendingOrdersCron() ? true : false ) {

        $pendingOrderTimeout = $module->getPendingOrdersTimeout() > 0 ? $module->getPendingOrdersTimeout() : 2880;

            if ($module->getCanclePendingOrdersCron() === true )
            {
                $dateTimeCheck = date('Y-m-d H:i:s', strtotime('-' . $pendingOrderTimeout . ' minutes'));

                $orderCollection = Mage::getModel('sales/order')->getCollection()
                ->addFieldToSelect(['increment_id', 'getepay_payment_id' , 'updated_at'])
                ->addFieldToFilter('state', 'new')
                ->addFieldToFilter('status', 'pending');
                $result = $orderCollection->getData();
            
                foreach ($result as $row) {
                    if ($row['updated_at'] < $dateTimeCheck)  {
                        
                        $incrementId = $row['increment_id'];
                        $getepayPaymentId = $row['getepay_payment_id'];

                        Mage::getModel('sales/order')
                            ->loadByIncrementId($incrementId)
                            ->setState( Mage_Sales_Model_Order::STATE_CANCELED, true, 'Order Cancelled' )
                            ->setStatus( 'canceled' )
                            ->save();
                        // $order = Mage::getModel( 'sales/order' )->loadByIncrementId( $incrementId );
                        // if ( $module->getOrderFailedEmail() ? true : false ) {
                        //     $order->sendOrderUpdateEmail( true, 'Failed to make Payment with Getepay due to transaction declined' );
                        // }
                    }
                }           
            }
        }
    }
    
}