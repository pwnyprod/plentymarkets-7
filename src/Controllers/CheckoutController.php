<?php

namespace Payone\Controllers;

use Payone\Adapter\Logger;
use Payone\Helpers\SessionHelper;
use Payone\Models\CreditCardCheckResponse;
use Payone\Models\CreditCardCheckResponseRepository;
use Payone\Services\PaymentService;
use Payone\Validator\CardExpireDate;
use Payone\Views\CheckoutErrorRenderer;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;

/**
 * Class CheckoutController
 */
class CheckoutController extends Controller
{
    /** @var SessionHelper */
    private $sessionHelper;

    /** @var CheckoutErrorRenderer */
    private $renderer;
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * CheckoutController constructor.
     *
     * @param SessionHelper $sessionHelper
     * @param CheckoutErrorRenderer $renderer
     * @param Request $request
     * @param Logger $logger
     */
    public function __construct(
        SessionHelper $sessionHelper,
        CheckoutErrorRenderer $renderer,
        Request $request,
        Logger $logger
    ) {
        $this->sessionHelper = $sessionHelper;
        $this->renderer = $renderer;
        $this->request = $request;
        $this->logger = $logger;
    }

    /**
     * @param PaymentService $paymentService
     * @param BasketRepositoryContract $basket
     *
     * @return string
     */
    public function doAuth(
        PaymentService $paymentService,
        BasketRepositoryContract $basket
    ) {
        $this->logger->setIdentifier(__METHOD__)
            ->debug('CheckoutController', $this->request->all());
        if (!$this->sessionHelper->isLoggedIn()) {
            return $this->getJsonErrors([
                'message' => 'Your session expired. Please login and start a new purchase.',
            ]);
        }
        try {
            $auth = $paymentService->openTransaction($basket->load());
        } catch (\Exception $e) {
            return $this->getJsonErrors(['message' => $e->getCode() . PHP_EOL . $e->getMessage() . PHP_EOL . $e->getTraceAsString()]);
        }

        return $this->getJsonSuccess($auth);
    }

    /**
     * @param CreditCardCheckResponseRepository $repository
     * @param CreditCardCheckResponse $response
     *
     * @return string
     */
    public function storeCCCheckResponse(
        CreditCardCheckResponseRepository $repository,
        CreditCardCheckResponse $response,
        CardExpireDate $validator
    ) {
        $this->logger->setIdentifier(__METHOD__)
            ->debug('CheckoutController', $this->request->all());
        if (!$this->sessionHelper->isLoggedIn()) {
            return $this->getJsonErrors(['message' => 'Your session expired. Please login and start a new purchase.']);
        }
        $status = $this->request->get('status');
        if ($status !== 'VALID') {
            return $this->getJsonErrors(['message' => 'Credit card check failed.']);
        }
        try {
            $response->init(
                $this->request->get('status'),
                $this->request->get('pseudocardpan'),
                $this->request->get('truncatedcardpan'),
                $this->request->get('cardtype'),
                $this->request->get('cardexpiredate')
            );
            $validator->validate(\DateTime::createFromFormat('Y-m-d', $response->getCardexpiredate()));
            $repository->storeLastResponse($response);
        } catch (\Exception $e) {
            return $this->getJsonErrors(['message' => $e->getCode() . PHP_EOL . $e->getMessage() . PHP_EOL . $e->getTraceAsString()]);
        }

        return $this->getJsonSuccess($response);
    }


    /**
     * @param FrontendSessionStorageFactoryContract $sessionStorage
     * @param Response $response
     * @return \Plenty\Plugin\Http\Response;
     */
    public function redirectWithNotice(FrontendSessionStorageFactoryContract $sessionStorage, Response $response)
    {
        $this->logger->setIdentifier(__METHOD__);
        $this->logger->debug('redirecting');

        $name = 'notifications';

        $notifications = json_decode($sessionStorage->getPlugin()->getValue($name));

        array_push($notifications, array(
            'message' => 'Something went wrong',
            'type' => 'notice',
            'code' => 0
        ));

        $value = json_encode($notifications);

        $sessionStorage->getPlugin()->setValue($name, $value);

       return $response->redirectTo('checkout');
    }

    /**
     * @param null $data
     *
     * @return string
     */
    private function getJsonSuccess($data = null): string
    {
        return json_encode(['success' => true, 'message' => null, 'data' => $data]);
    }

    /**
     * @param $errors
     *
     * @return string
     */
    private function getJsonErrors($errors): string
    {
        $data = [];
        $data['success'] = false;
        $data['errors'] = $errors;

        return json_encode($data);
    }
}
