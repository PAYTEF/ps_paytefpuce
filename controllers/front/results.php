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

require_once(__DIR__."/../../src/puceutils.php");

class Ps_PaytefPuceResultsModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        parent::init();
    }

    public function initContent()
    {
        parent::initContent();
        if ($this->module->active == false) {
            die("Module inactive");
        }
        try
        {
            $mac = $_POST['Op_MAC'];
            if(empty($mac)) {
                die("Empty MAC");
            }
            $calcMac = PuceUtils::calculateResultsMAC($_POST);
            if($mac != $calcMac) {
                die("Invalid MAC");
            }

            $authID = $_POST['Op_Authorization_ID'];
            $parts = explode("-", $authID);
            if(sizeof($parts) != 2) {
                die("Invalid Authorization ID");
            }
            $customer_id = intval($parts[0]);
            $cart_id = intval($parts[1]);

            $order = Order::getByCartId($cart_id);
            if (!$order || !Validate::isLoadedObject($order)) {
                die("Invalid cart, no order");
            }
            $resultCode = $_POST["Op_Result_Code"];
            $approved = ($resultCode == "X" || $resultCode == "Z");
            
            $newState = null;
            if($approved) {
                if (PuceUtils::isOrderPending($order)) {
                    // Change to payed
                    $newState = PuceUtils::orderStatusApproved();
                } else {
                    // Change to error because it was payed but order is in an invalid state
                    // this is an issue and probably something funky happened
                    PuceUtils::logError("Order with reference ".strval($order->reference)." is in an invalid state. Payment was made but couldn't modify order status");
                    $newState = PuceUtils::orderStatusError();
                }
            } else {
                if (!PuceUtils::isOrderCancelledOrError($order)) {
                    $newState = PuceUtils::orderStatusCanceled();
                }
            }
            if ($newState != $order->current_state) {
                $history = new OrderHistory();
                $history->id_order = (int)$order->id;
                $history->changeIdOrderState( (int)$newState, $order); 
                $history->addWithemail(true); // Send email
            }
        } catch(Exception $ex) {
            PuceUtils::logException("PAYTEF.results", $ex);
            die("Error: ".$ex->getMessage());
        }
        die("OK");
    }
}
