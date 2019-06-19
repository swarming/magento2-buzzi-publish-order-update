<?php
/**
 * Copyright © Swarming Technology, LLC. All rights reserved.
 */
namespace Buzzi\PublishOrderUpdate\Observer;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Buzzi\PublishOrderUpdate\Model\DataBuilder;

class OrderSaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Buzzi\Publish\Model\Config\Events
     */
    private $configEvents;

    /**
     * @var \Buzzi\Publish\Api\QueueInterface
     */
    private $queue;

    /**
     * @var \Buzzi\PublishOrderUpdate\Model\DataBuilder
     */
    private $dataBuilder;

    /**
     * @var \Buzzi\Publish\Helper\AcceptsMarketing
     */
    private $acceptsMarketingHelper;

    /**
     * @param \Buzzi\Publish\Model\Config\Events $configEvents
     * @param \Buzzi\Publish\Api\QueueInterface $queue
     * @param \Buzzi\PublishOrderUpdate\Model\DataBuilder $dataBuilder
     * @param \Buzzi\Publish\Helper\AcceptsMarketing|null $acceptsMarketingHelper
     */
    public function __construct(
        \Buzzi\Publish\Model\Config\Events $configEvents,
        \Buzzi\Publish\Api\QueueInterface $queue,
        \Buzzi\PublishOrderUpdate\Model\DataBuilder $dataBuilder,
        \Buzzi\Publish\Helper\AcceptsMarketing $acceptsMarketingHelper = null
    ) {
        $this->configEvents = $configEvents;
        $this->queue = $queue;
        $this->dataBuilder = $dataBuilder;
        $this->acceptsMarketingHelper = $acceptsMarketingHelper ?: ObjectManager::getInstance()->get(\Buzzi\Publish\Helper\AcceptsMarketing::class);
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getData('order');
        $storeId = $order->getStoreId();

        if (!$this->configEvents->isEventEnabled(DataBuilder::EVENT_TYPE, $storeId)
            || !$this->acceptsMarketingHelper->isAccepts(DataBuilder::EVENT_TYPE, $storeId)
        ) {
            return;
        }

        $payload = $this->dataBuilder->getPayload($order);

        if ($this->configEvents->isCron(DataBuilder::EVENT_TYPE, $storeId)) {
            $this->queue->add(DataBuilder::EVENT_TYPE, $payload, $storeId);
        } else {
            $this->queue->send(DataBuilder::EVENT_TYPE, $payload, $storeId);
        }
    }
}
