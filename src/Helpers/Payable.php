<?php

namespace Solutionplus\Payable\Helpers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Request;
use Solutionplus\MicroService\Helpers\MsHttp;

class Payable
{
    public string $uri;
    public array $headers = [];

    public function __construct(string $companyReferenceNumber)
    {
        $this->uri = "companies/{$companyReferenceNumber}/payment-requests";
    }

    public function withHeaders(array $headers = []): self
    {
        $this->headers = \array_merge(request()->header(), $headers);
        return $this;
    }

    public function createPaymentRequest(array $payableData)
    {
        return MsHttp::post(
            microserviceName: 'payment',
            uri: $this->uri,
            data: self::validatedPayableData($payableData),
            additionalHeaders: $this->headers
        );
    }

    public function createTransaction(string $paymentRequestReferenceNumber, array $transactionData = [])
    {
        if (! Str::startsWith($paymentRequestReferenceNumber, 'MSPR')) abort(409, 'wrongs reference number');

        return MsHttp::post(
            microserviceName: 'payment',
            uri: "{$this->uri}/{$paymentRequestReferenceNumber}",
            data: self::validatedTransactionData($transactionData),
            additionalHeaders: $this->headers
        );
    }

    public static function validatedPayableData(array $payableData)
    {
        return Request::validate([
            'gateway' => 'required|string|exists:gateways,name',
            'currency' => 'required|string|exists:currencies,iso_code',
            'amount' => 'required|numeric|min:1|max:9999999999',
            'due_date' => 'required|date_format:Y-m-d H:i:s|after:' . now(),
            'payable_reference_number' => 'sometimes|string|min:10|max:25',
        ], $payableData);
    }

    public static function validatedTransactionData(array $transactionData)
    {
        return Request::validate([
            'name' => 'nullable|string|min:3|max:225',
            'email' => 'nullable|email',
            'phone' => 'nullable|min:1|digits_between:9,15',
            'country_code' => 'required_with:phone|min:3|max:5',
            'street' => 'nullable|string|min:3|max:20',
            'city' => 'nullable|string|min:3|max:20',
            'state' => 'nullable|string|min:3|max:20',
            'country' => 'nullable|string|min:3|max:20',
            'zip' => 'nullable|numeric|digits:5',
        ], $transactionData);
    }
}
