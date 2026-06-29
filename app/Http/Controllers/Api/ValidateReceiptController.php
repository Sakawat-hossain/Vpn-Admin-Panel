<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ReceiptValidator\iTunes\Validator as iTunesValidator;
use Exception;

class ValidateReceiptController extends Controller
{
    public function validateReceipt(Request $request)
    {
        $receiptBase64Data = $request->input('receipt_data');
        $yourSharedSecret = $request->input('shared_secret');

        $validator = new iTunesValidator(iTunesValidator::ENDPOINT_PRODUCTION); // Or iTunesValidator::ENDPOINT_SANDBOX if sandbox testing

        try {
            $response = $validator->setReceiptData($receiptBase64Data)->validate();
            $sharedSecret = $yourSharedSecret; // Generated in iTunes Connect's In-App Purchase menu
            $response = $validator->setSharedSecret($sharedSecret)->setReceiptData($receiptBase64Data)->validate(); // use setSharedSecret() if for recurring subscriptions
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        if ($response->isValid()) {
            $receiptData = $response->getReceipt();


            return response()->json([
                'valid' => true,
                'receipt' => $receiptData,
            ]);
        } else {
            return response()->json([
                'valid' => false,
                'result_code' => $response->getResultCode()
            ]);
        }
    }
}
