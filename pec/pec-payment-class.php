<?php

class Qcp_PEC
{
    private $wsdl_url = "https://pec.shaparak.ir/NewIPGServices/Sale/SaleService.asmx?WSDL";
    private $wsdl_confirm_url = "https://pec.shaparak.ir/NewIPGServices/Confirm/ConfirmService.asmx?WSDL";
    private $wsdl_reverse_url = "https://pec.shaparak.ir/NewIPGServices/Reverse/ReversalService.asmx";
    private $site_call_back_url =  null;
    private $pin = null;
    public function __construct($pin, $site_call_back_url)
    {
        ini_set("soap.wsdl_cache_enabled", "0");
        $this->pin = $pin;
        $this->site_call_back_url = $site_call_back_url;
        
    }
    public function getBankToken($amount, $order_id, $additionalData)
    {
        $params = array(
            "LoginAccount" => $this->pin,
            "Amount" => $amount,
            "OrderId" => $order_id,
            "CallBackUrl" => $this->site_call_back_url,
            "AdditionalData" => $additionalData
        );
        $client = new SoapClient($this->wsdl_url);
        try {
            $result = $client->SalePaymentRequest(array(
                "requestData" => $params
            ));
            if ($result->SalePaymentRequestResult->Token && $result->SalePaymentRequestResult->Status === 0) {
                $url = "https://pec.shaparak.ir/NewIPG/?Token=" . $result->SalePaymentRequestResult->Token;
                return ['ok' => true, 'url' => $url]; // If everything is ok, redirect the user to the payment URL :)
            } elseif ($result->SalePaymentRequestResult->Status  != '0') {
                $err_msg = "(Error code : " . $result->SalePaymentRequestResult->Status . ") " . $result->SalePaymentRequestResult->Message;
                return ['ok' => false, 'msg' => $err_msg]; // Something bad just happend. Tell the user!
            }
        } catch (Exception $ex) {
            $err_msg =  $ex->getMessage();
            return ['ok' => false, 'msg' => $err_msg]; // And again something gone wrong. Call Admin!
        }
    }
    public function confirmTransaction($token)
    {
        $params = array(
            "LoginAccount" => $this->pin,
            "Token" => $token
        );
        $client = new SoapClient($this->wsdl_confirm_url);

        try {
            $result = $client->ConfirmPayment(array(
                "requestData" => $params
            ));
            if ($result->ConfirmPaymentResult->Status != '0') {
                $err_msg = "(<strong> کد خطا : " . $result->ConfirmPaymentResult->Status . "</strong>) " .
                    $result->ConfirmPaymentResult->Message;
                    return ['ok' => false, 'msg' => $err_msg];
            }
            return ['ok' => true, 'result' => $result];
        } catch (Exception $ex) {
            $err_msg =  $ex->getMessage();
            return ['ok' => false, 'msg' => $err_msg];
        }
    }
    public function reverseTransaction($token)
    {
        $params = array(
            "LoginAccount" => $this->pin,
            "Token" => $token
        );
        $client = new SoapClient($this->wsdl_reverse_url);

        try {
            $result = $client->ReversalRequest(array(
                "requestData" => $params
            ));
            if ($result->ReversalRequestResult->Status != '0') {
                $err_msg = "(<strong> کد خطا : " . $result->ReversalRequestResult->Status . "</strong>) " .
                    $result->ReversalRequestResult->Message;
                    return ['ok' => false, 'msg' => $err_msg];
            }
            return ['ok' => true, 'result' => $result];
        } catch (Exception $ex) {
            $err_msg =  $ex->getMessage();
            return ['ok' => false, 'msg' => $err_msg];
        }
    }
}
