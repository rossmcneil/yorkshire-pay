<?php

namespace Rossmcneil\YorkshirePay;

class YorkshirePay
{
    public function pay(array $request)
    {

        // Signature key entered on MMS. The demo accounts is fixed to this value,
        $key = config('yorkshire-pay.signature_key');

        // Request
        $request = array_merge($request, [
            'merchantID' => config('yorkshire-pay.merchant_id'),
            'action' => 'SALE',
            'type' => 1,
            'countryCode' => config('yorkshire-pay.country_code'),
            'currencyCode' => config('yorkshire-pay.currency_code'),
            'transactionUnique' => (isset($_REQUEST['transactionUnique']) ? $_REQUEST['transactionUnique'] : uniqid())
        ]);

        /**
         * Check if 3D Secure is enabled
         */
        if (config('yorkshire-pay.3DSecure')) {
            $request = array_merge($request, [
                'threeDSMD' => (isset($_REQUEST['MD']) ? $_REQUEST['MD'] : null),
                'threeDSPaRes' => (isset($_REQUEST['PaRes']) ? $_REQUEST['PaRes'] : null),
                'threeDSPaReq' => (isset($_REQUEST['PaReq']) ? $_REQUEST['PaReq'] : null)
            ]);
        }

        // Create the signature using the function called below.
        $request['signature'] = self::createSignature($request, $key);

        // Initiate and set curl options to post to the gateway
        $ch = curl_init(config('yorkshire-pay.gateway_url'));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($request));
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Send the request and parse the response
        parse_str(curl_exec($ch), $response);

        // Close the connection to the gateway
        curl_close($ch);

        // Extract the return signature as this isn't hashed
        $signature = null;
        if (isset($response['signature'])) {
            $signature = $response['signature'];
            unset($response['signature']);
        }

        // Check the return signature
        if (!$signature || $signature !== self::createSignature($response, $key)) {
            // You should exit gracefully
            return [
                'response' => $response,
                'status' => 'failed',
                'message' => 'Signature mismatch'
            ];
        }
        // Check the response code
        if ($response['responseCode'] == 65802) {

            $pageUrl = (@$_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
            if ($_SERVER['SERVER_PORT'] != '80') {
                $pageUrl .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
            } else {
                $pageUrl .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
            }

            return [
                'response' => $response,
                'status' => '3DSecure',
                'form' => [
                    'action' => htmlentities($response['threeDSACSURL']),
                    'inputs' => [
                        'MD' => htmlentities($response['threeDSMD']),
                        'PaReq' => htmlentities($response['threeDSPaReq']),
                        'TermUrl' => htmlentities($pageUrl)
                    ]
                ]
            ];
        } else if ($response['responseCode'] === "0") {
            return [
                'response' => $response,
                'status' => 'success'
            ];
        } else {
            return [
                'response' => $response,
                'status' => 'failed',
                'message' => htmlentities($response['responseMessage'])
            ];
        }
    }

    public function formatAmount($amount)
    {
        return $amount * 100;
    }

    // Function to create a message signature
    private function createSignature(array $data, $key)
    {
        // Sort by field name
        ksort($data);
        // Create the URL encoded signature string
        $ret = http_build_query($data, '', '&');
        // Normalise all line endings (CRNL|NLCR|NL|CR) to just NL (%0A)
        $ret = str_replace(array('%0D%0A', '%0A%0D', '%0D'), '%0A', $ret);
        // Hash the signature string and the key together
        return hash('SHA512', $ret . $key);
    }
}
