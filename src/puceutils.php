<?php
/**
* 2020 PAYTEF
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to support@paytef.es so we can send you a copy immediately.
*
*  @author    PAYTEF Sistemas S.L. <support@paytef.es>
*  @copyright 2020 PAYTEF Sistemas S.L.
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PAYTEF Sistemas S.L.
*/

class PuceUtils
{
    public static function getMacKey() {
        return strval(Configuration::get("PS_PAYTEF_PUCE_MAC_KEY"));
    }

    public static function calculateRequestMAC($body) {
        $fieldNames = array("Op_TCOD", "Op_Authorization_ID", 
            "Op_Merchant_UrlOK", "Op_Merchant_Amount", 
            "Op_Merchant_MerchantCode", "Op_Merchant_MerchantName", 
            "Op_Merchant_CSB", "Op_Merchant_Terminal", "Op_Card_Pan", 
            "Op_Card_Expiry", "Op_Card_CVV2_CVC2", "Op_Client_Confirm_Url", 
            "Op_Client_Result_Url", "Op_Type", "Op_Version");
        return PuceUtils::calculateMAC($body, $fieldNames);
    }

    public static function calculateConfirmMAC($body) {
        $fieldNames = array("Op_Authorization_ID", "Op_Merchant_Amount");
        return PuceUtils::calculateMAC($body, $fieldNames);
    }

    public static function calculateResultsMAC($body) {
        $fieldNames = array("Op_Authorization_ID", "Op_Result_Code", "Op_Result_Reason",
            "Op_Merchant_Amount", "Op_PUC_Authorization", "Op_Card_BIN", "Op_Card_Last_Digits",
            "Op_PAYTEF_ID");
        return PuceUtils::calculateMAC($body, $fieldNames);
    }

    private static function calculateMAC($body, $fieldNames)
    {
        $key = hex2bin(PuceUtils::getMacKey());
        $body_lower = array_change_key_case($body);
        $payload = "";
        $first = true;
        foreach($fieldNames as $fieldName) {
            if ($first) {
                $first = false;
            } else {
                $payload .= "|";
            }
            $fieldLower = strtolower($fieldName);
            if(isset($body_lower[$fieldLower])) {
                $payload .= strval($body_lower[$fieldLower]);
            }
        }

        $hexaResult = hash_hmac("sha256", $payload, $key);
        $mac = strtoupper(substr($hexaResult, 0, 32));
        return $mac;
    }

    public static function orderStatusPending() {
        return Configuration::get('PS_OS_PREPARATION');
    }

    public static function orderStatusApproved() {
        return Configuration::get('PS_OS_PAYMENT');
    }

    public static function orderStatusCanceled() {
        return Configuration::get('PS_OS_CANCELED');
    }

    public static function orderStatusError() {
        return Configuration::get('PS_OS_ERROR');
    }

    /** Returns true if the order is in pending state
     * @param Order $order
     * @return boolean */
    public static function isOrderPending($order) {
        return $order->current_state == PuceUtils::orderStatusPending();
    }

    /** Returns true if the order is error
     * @param Order $order
     * @return boolean */
    public static function isOrderError($order) {
        return $order->current_state != PuceUtils::orderStatusError();
    }

    /** Returns true if the order is canceled or error
     * @param Order $order
     * @return boolean */
    public static function isOrderCancelledOrError($order) {
        return $order->current_state != PuceUtils::orderStatusCanceled() || $order->current_state != PuceUtils::orderStatusError();
    }

    /** Logs a message and exception to the database
     * @param string $message A message to be logged
     * @param Exception $ex The exception
     *  */
    public static function logException($message, $ex) {
        $data = $message . "-" . $ex->getMessage() . ": \n" . $ex->getTraceAsString();
        PrestaShopLogger::addLog($data, 3, $ex->getCode());
    }

    /** Logs a message error to the database
     * @param string $message A message to be logged
     *  */
    public static function logError($message) {
        PrestaShopLogger::addLog($message, 3);
    }

    /** Returns the total amount to be paid for cart
     * @param Cart $cart
     * @return float total amount to be paid*/
    public static function getCartTotal($cart) {
        return (float) Tools::ps_round((float) $cart->getOrderTotal(true, Cart::BOTH), 2);
    }

    
}