<?php
/**
 * Copyright © Swarming Technology, LLC. All rights reserved.
 */
namespace Buzzi\PublishCartPurchase\Model;

use Magento\Framework\DataObject;

class DataBuilder
{
    const EVENT_TYPE = 'buzzi.ecommerce.cart-purchase';

    /**
     * @var \Buzzi\Publish\Helper\DataBuilder\Base
     */
    protected $dataBuilderBase;

    /**
     * @var \Buzzi\Publish\Helper\DataBuilder\Cart
     */
    protected $dataBuilderCart;

    /**
     * @var \Buzzi\Publish\Helper\DataBuilder\Customer
     */
    protected $dataBuilderCustomer;

    /**
     * @var \Buzzi\Publish\Helper\DataBuilder\Address
     */
    protected $dataBuilderAddress;

    /**
     * @var \Magento\Customer\Model\CustomerRegistry
     */
    protected $customerRegistry;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventDispatcher;

    /**
     * @param \Buzzi\Publish\Helper\DataBuilder\Base $dataBuilderBase
     * @param \Buzzi\Publish\Helper\DataBuilder\Cart $dataBuilderCart
     * @param \Buzzi\Publish\Helper\DataBuilder\Customer $dataBuilderCustomer
     * @param \Buzzi\Publish\Helper\DataBuilder\Address $dataBuilderAddress
     * @param \Magento\Customer\Model\CustomerRegistry $customerRegistry
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Magento\Framework\Event\ManagerInterface $eventDispatcher
     */
    public function __construct(
        \Buzzi\Publish\Helper\DataBuilder\Base $dataBuilderBase,
        \Buzzi\Publish\Helper\DataBuilder\Cart $dataBuilderCart,
        \Buzzi\Publish\Helper\DataBuilder\Customer $dataBuilderCustomer,
        \Buzzi\Publish\Helper\DataBuilder\Address $dataBuilderAddress,
        \Magento\Customer\Model\CustomerRegistry $customerRegistry,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Framework\Event\ManagerInterface $eventDispatcher
    ) {
        $this->dataBuilderBase = $dataBuilderBase;
        $this->dataBuilderCart = $dataBuilderCart;
        $this->dataBuilderCustomer = $dataBuilderCustomer;
        $this->dataBuilderAddress = $dataBuilderAddress;
        $this->customerRegistry = $customerRegistry;
        $this->cartRepository = $cartRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return mixed[]
     */
    public function getPayload($order)
    {
        $quote = $this->cartRepository->get($order->getQuoteId());

        $payload = $this->dataBuilderBase->initBaseData(self::EVENT_TYPE);
        $payload['customer'] = $this->getCustomerData($order);
        $payload['cart'] = $this->dataBuilderCart->getCartData($quote, $order);
        $payload['cart']['cart_items'] = $this->dataBuilderCart->getCartItemsData($quote);

        $billingAddress = $this->dataBuilderAddress->getBillingAddressesFromOrder($order);
        if ($billingAddress) {
            $payload['cart']['billing_address'] = $billingAddress;
        }

        $shippingAddress = $this->dataBuilderAddress->getShippingAddressesFromOrder($order);
        if ($shippingAddress) {
            $payload['cart']['shipping_address'] = $shippingAddress;
        }

        $transport = new DataObject(['order' => $order, 'payload' => $payload]);
        $this->eventDispatcher->dispatch('buzzi_publish_cart_purchase_payload', ['transport' => $transport]);

        return (array)$transport->getData('payload');
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    protected function getCustomerData($order)
    {
        if ($order->getCustomerId()) {
            $customer = $this->customerRegistry->retrieve($order->getCustomerId());
            $customerData = $this->dataBuilderCustomer->getCustomerData($customer);
        } else {
            $customerData = $this->dataBuilderCustomer->getCustomerDataFromOrder($order);
        }

        return $customerData;
    }
}
