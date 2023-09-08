<?php

namespace AppBundle\Action\Delivery;

use AppBundle\Entity\Delivery;
use AppBundle\Exception\Pricing\NoRuleMatchedException;
use AppBundle\Service\DeliveryManager;
use AppBundle\Service\OrderManager;
use AppBundle\Sylius\Order\OrderFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class Create
{
    public function __construct(
        private DeliveryManager $deliveryManager,
        private EntityManagerInterface $entityManager,
        private OrderManager $orderManager,
        private OrderFactory $orderFactory,
        private LoggerInterface $logger
    )
    { }

    /**
     * @throws NoRuleMatchedException
     */
    public function __invoke(Delivery $data, Request $request)
    {
        $store = $data->getStore();

        if (null !== $store && $store->getCreateOrders()) {

            $price = $this->deliveryManager->getPrice($data, $store->getPricingRuleSet());

            if (null === $price) {
                $this->logger->error('Price could not be calculated');

                return $data;
            }

            $price = (int) $price;

            $order = $this->orderFactory->createForDelivery($data, $price);

            $this->entityManager->persist($order);
            $this->entityManager->flush();

            $this->orderManager->onDemand($order);
        }

        return $data;
    }
}