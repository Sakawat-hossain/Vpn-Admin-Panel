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
        $request->validate(['receipt_data' => ['required', 'string']]);
        $receiptBase64Data = $request->input('receipt_data');
        // Shared secret from App Store Connect (APPSTORE_PASSWORD); the client value is
        // only a fallback for installations that haven't configured it yet.
        $sharedSecret = config('liap.appstore_password') ?: $request->input('shared_secret');

        $validator = new iTunesValidator(iTunesValidator::ENDPOINT_PRODUCTION);

        try {
            $validator->setReceiptData($receiptBase64Data);
            if ($sharedSecret) {
                $validator->setSharedSecret($sharedSecret);
            }
            $response = $validator->validate();
            // 21007: sandbox receipt sent to production (TestFlight / App Review).
            if ($response->getResultCode() === 21007) {
                $response = $validator->setEndpoint(iTunesValidator::ENDPOINT_SANDBOX)->validate();
            }
        } catch (Exception $e) {
            report($e);
            return response()->json(['error' => __('Could not verify the receipt with the App Store')], 502);
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
