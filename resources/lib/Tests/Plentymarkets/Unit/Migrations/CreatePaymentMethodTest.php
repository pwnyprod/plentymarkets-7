<?php

namespace Payone\Tests\Unit\Migrations;

use Payone\Helpers\PaymentHelper;
use Payone\Methods\PayoneInvoicePaymentMethod;
use Payone\Methods\PayonePaydirektPaymentMethod;
use Payone\Methods\PayonePayolutionInstallmentPaymentMethod;
use Payone\Methods\PayonePayPalPaymentMethod;
use Payone\Methods\PayoneRatePayInstallmentPaymentMethod;
use Payone\Methods\PayoneSofortPaymentMethod;
use Payone\Migrations\CreatePaymentMethods;
use Plenty\Modules\Payment\Contracts\PaymentOrderRelationRepositoryContract;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;

/**
 * Class PaymentHelperTest
 */
class CreatePaymentMethodTest extends \PHPUnit_Framework_TestCase
{
    /** @var PaymentMethodRepositoryContract|PHPUnit_Framework_MockObject_MockObject */
    private $paymentRepo;
    /** @var PaymentHelper */
    private $helper;

    /**
     * @var CreatePaymentMethods
     */
    private $migration;

    public function setUp()
    {
        $this->paymentRepo = $this->createMock(PaymentMethodRepositoryContract::class);


        $this->helper = new PaymentHelper(
            $this->paymentRepo,
            self::createMock(PaymentOrderRelationRepositoryContract::class)
        );
        $this->migration = new CreatePaymentMethods($this->paymentRepo, $this->helper);
    }

    public function testNotRegisteredPaymentsArerRegistered()
    {
        $this->paymentRepo->method('allForPlugin')
            ->willReturn(
                [
                    (object)
                    [
                        'paymentKey' => PayoneInvoicePaymentMethod::PAYMENT_CODE,
                        'id' => 'invoice_mop',
                    ],
                    (object)
                    [
                        'paymentKey' => PayonePaydirektPaymentMethod::PAYMENT_CODE,
                        'id' => 'direct_mop',
                    ],
                    (object)
                    [
                        'paymentKey' => PayonePayolutionInstallmentPaymentMethod::PAYMENT_CODE,
                        'id' => 'inst_mop',
                    ],
                    (object)
                    [
                        'paymentKey' => PayonePayPalPaymentMethod::PAYMENT_CODE,
                        'id' => 'invoice_mop',
                    ],
                    (object)
                    [
                        'paymentKey' => PayoneRatePayInstallmentPaymentMethod::PAYMENT_CODE,
                        'id' => 'ratepay_inst_mop',
                    ],
                    (object)
                    [
                        'paymentKey' => PayoneSofortPaymentMethod::PAYMENT_CODE,
                        'id' => 'sofort_mop',
                    ],
                ]
            );

        $countOfUnregisteredPayments = 2;
        $this->paymentRepo->expects($this->exactly($countOfUnregisteredPayments))
            ->method('createPaymentMethod');

        $this->migration->run();
    }

    public function testAllPaymentsAreRegistered()
    {
        parent::setUp();
        $this->paymentRepo->method('allForPlugin')
            ->willReturn([]);
        $countOfPaymentMethods = count($this->getAllPaymentMethodClasses());
        $this->paymentRepo->expects($this->exactly($countOfPaymentMethods))
            ->method('createPaymentMethod');

        $this->migration->run();
    }


    protected function getAllPaymentMethodClasses()
    {
        $children = [];
        foreach (get_declared_classes() as $class) {
            if (is_subclass_of($class, 'Payone\Methods\PaymentAbstract')) {
                $children[] = $class;
            }
        }
        return $children;
    }
}
