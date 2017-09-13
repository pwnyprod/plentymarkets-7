<?php

namespace Payone\Providers\Api\Request;

use Plenty\Modules\Frontend\Events\FrontendUpdateInvoiceAddress;
use Plenty\Modules\Order\Models\Order;

/**
 * Class CaptureDataProvider
 */
class CaptureDataProvider extends DataProviderAbstract implements DataProviderOrder
{
    /** @var FrontendUpdateInvoiceAddress */
    protected $invoice;

      /**
     * {@inheritdoc}
     */
    public function getDataFromOrder(string $paymentCode, Order $order, string $requestReference = null)
    {
        $requestParams = $this->getDefaultRequestData($paymentCode, 'order-' . $order->id); //TODO: get transaction id

        $requestParams['basket'] = $this->getBasketDataFromOrder($order);
        $requestParams['basketItems'] = $this->getOrderItemData($order);

        $billingAddress = $this->addressHelper->getOrderBillingAddress($order);
        $requestParams['billingAddress'] = $this->addressHelper->getAddressData(
            $billingAddress
        );
        $requestParams['customer'] = $this->getCustomerData($billingAddress, $order->ownerId);

        $requestParams['referenceId'] = $requestReference;

        $requestParams['invoice'] = $this->getInvoiceData();
        $requestParams['order'] = $this->getOrderData($order);
        $requestParams['tracking'] = $this->getTrackingData($order->id);

        $this->validator->validate($requestParams);

        return $requestParams;
    }

    /**
     * @param $orderId
     *
     * @return array
     */
    protected function getOrderData(Order $order)
    {
        $amount = $order->amounts[0];

        return [
            'orderId' => $order->id,
            'amount' => (int) round($amount->invoiceTotal * 100),
            'currency' => $amount->currency,
        ];
    }

    /**
     * @param $orderId
     *
     * @return array
     */
    protected function getTrackingData($orderId)
    {
        try {
            $shippingInfo = $this->shippingProviderRepository->getShippingInformationByOrderId($orderId);
        } catch (\Exception $e) {
            return [];
        }

        return [
            'trackingId' => $shippingInfo->transactionId,
            'returnTrackingId' => '',
            'shippingCompany' => $shippingInfo->shippingServiceProvider,
        ];
    }
}
