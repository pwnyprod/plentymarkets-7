<?php

namespace Payone\Providers\Api\Request;

use Plenty\Modules\Basket\Models\Basket;
use Plenty\Modules\Order\Models\Order;

/**
 * Class AuthDataProvider
 */
class AuthDataProvider extends DataProviderAbstract implements DataProviderOrder, DataProviderBasket
{
    /**
     * @param string $paymentCode
     * @param Basket $basket
     * @param string|null $requestReference
     *
     * @return array
     */
    public function getDataFromBasket(string $paymentCode, Basket $basket, string $requestReference = null)
    {
        $requestParams = $this->getDefaultRequestData(
            $paymentCode, 'basket-' . $basket->id . '-' . $basket->updatedAt
        ); //TODO: get transaction id

        $requestParams['basket'] = $this->getBasketData($basket);

        $requestParams['basketItems'] = $this->getCartItemData($basket);
        $requestParams['shippingAddress'] = $this->addressHelper->getAddressData(
            $this->addressHelper->getBasketShippingAddress($basket)
        );
        $billingAddress = $this->addressHelper->getBasketBillingAddress($basket);
        $requestParams['billingAddress'] = $this->addressHelper->getAddressData(
            $billingAddress
        );
        $requestParams['customer'] = $this->getCustomerData($billingAddress, $basket->customerId);

        if ($this->paymentHasAccount($paymentCode)) {
            $requestParams['account'] = $this->getAccountData();
        }
        $this->validator->validate($requestParams);

        return $requestParams;
    }

    /**
     * @param string $paymentCode
     * @param $transactionId
     *
     * @return array
     */
    public function getApiContextParams($paymentCode, $transactionId)
    {
        $apiContextParams = parent::getApiContextParams($paymentCode, $transactionId);
        $apiContextParams['channel'] = $this->config->get('channelPre');

        return $apiContextParams;
    }

    /**
     * @param string $paymentCode
     * @param Order $order
     * @param string|null $requestReference
     *
     * @return array
     */
    public function getDataFromOrder(string $paymentCode, Order $order, string $requestReference = null)
    {
        $requestParams = $this->getDefaultRequestData(
            $paymentCode, 'order-' . $order->id
        ); //TODO: get transaction id

        $requestParams['basket'] = $this->getBasketDataFromOrder($order);

        $requestParams['basketItems'] = $this->getOrderItemData($order);
        $requestParams['shippingAddress'] = $this->addressHelper->getAddressData(
            $this->addressHelper->getOrderShippingAddress($order)
        );
        $billingAddress = $this->addressHelper->getOrderBillingAddress($order);
        $requestParams['billingAddress'] = $this->addressHelper->getAddressData(
            $billingAddress
        );
        $requestParams['customer'] = $this->getCustomerData($billingAddress, $order->ownerId);

        if ($this->paymentHasAccount($paymentCode)) {
            $requestParams['account'] = $this->getAccountData();
        }

        $this->validator->validate($requestParams);

        return $requestParams;
    }
}