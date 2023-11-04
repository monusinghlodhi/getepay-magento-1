<?php
/*
 * Copyright (c) 2023 GetePay
 *
 * Author: GetePay
 *
 * Released under the GNU General Public License
 */

class GetePay_Pay_Model_Functions extends Mage_Payment_Model_Method_Abstract
{

    protected $_code       = 'pay_functions';
    protected $_canCapture = true;

    // public function getPostUrl()
    // {
    //     return 'https://secure.getepay.in/initiate.trans';
    // }

    public function getRequestURL()
    {
        if ( $this->getConfigData( 'mode' ) === 1 ) {
            return $this->getConfigData( 'req_url' );
        } else {
            return $this->getConfigData( 'test_req_url' );
        }
    }

    public function getGetepayMID()
    {
        if ( $this->getConfigData( 'mode' ) === 1 ) {
            return $this->getConfigData( 'getepay_mid' );
        } else {
            return $this->getConfigData( 'test_getepay_mid' );
        }
    }

    public function getTerminalID()
    {
        if ( $this->getConfigData( 'mode' ) === 1 ) {
            return $this->getConfigData( 'terminalId' );
        } else {
            return $this->getConfigData( 'test_terminalId' );
        }
    }
    
    public function getGetepayKEY()
    {
        if ( $this->getConfigData( 'mode' ) === 1 ) {
            return $this->getConfigData( 'getepay_key' );
        } else {
            return $this->getConfigData( 'test_getepay_key' );
        }
    }

    public function getGetepayIV()
    {
        if ( $this->getConfigData( 'mode' ) === 1 ) {
            return $this->getConfigData( 'getepay_iv' );
        } else {
            return $this->getConfigData( 'test_getepay_iv' );
        }
    }

    public function getOrderStatus()
    {
        return $this->getConfigData( 'order_status' );
    }

    public function getOrderPlacedEmail()
    {
        return (int)$this->getConfigData( 'order_placed_email' ) === 1;
    }

    public function getOrderSuccessfulEmail()
    {
        return (int)$this->getConfigData( 'order_successful_email' ) === 1;
    }

    public function getOrderFailedEmail()
    {
        return (int)$this->getConfigData( 'order_failed_email' ) === 1;
    }

    public function getSendInvoiceEmail()
    {
        return (int)$this->getConfigData( 'send_invoice_email' ) === 1;
    }

    public function getCanclePendingOrdersCron()
    {
        return (int)$this->getConfigData( 'enable_canclependingorders_cron' ) === 1;
    }

    public function getPendingOrdersTimeout()
    {
        return $this->getConfigData( 'pending_orders_timeout' );
    }

    public function getGetepayPendingPaymentsCron()
    {
        return (int)$this->getConfigData( 'enable_getepay_pendingpayments_cron' ) === 1;
    }

    public function getPaymentRequeryURL()
    {
        if ( $this->getConfigData( 'mode' )  === 1 ) {
            return $this->getConfigData( 'getepay_payment_requery_url' );
        } else {
            return $this->getConfigData( 'test_getepay_payment_requery_url' );
        }
    }

    public function getOrder()
    {
        $order   = Mage::getModel( 'sales/order' );
        $session = Mage::getSingleton( 'checkout/session' );
        $order->loadByIncrementId( $session->getLastRealOrderId() );

        return $order;
    }

    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl( 'pay/processing/redirect' );
    }

    public function getReturnUrl()
    {
        $url = Mage::getUrl( 'pay/processing/return' );
        $url = trim( $url );
        $pos = strpos( $url, '?' );
        if ( $pos ) {
            $url = substr( $url, 0, $pos );
        }

        return $url;
    }

    public function getNotifyUrl()
    {
        $url = Mage::getUrl( 'pay/processing/notify' );
        $url = trim( $url );
        $pos = strpos( $url, '?' );
        if ( $pos ) {
            $url = substr( $url, 0, $pos );
        }

        return $url;
    }

    public function getPendingOrderCencleUrl()
    {
        $url = Mage::getUrl( 'pay/processing/pendingordercancle' );
        $url = trim( $url );
        $pos = strpos( $url, '?' );
        if ( $pos ) {
            $url = substr( $url, 0, $pos );
        }

        return $url;
    }

}
