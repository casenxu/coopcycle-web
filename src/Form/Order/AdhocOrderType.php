<?php

namespace AppBundle\Form\Order;

use AppBundle\Form\AddressType;
use AppBundle\Form\StripePaymentType;
use AppBundle\Sylius\Order\OrderInterface;
use AppBundle\Form\Type\AsapChoiceLoader;
use AppBundle\Service\TimeRegistry;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\Component\Payment\Model\PaymentInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdhocOrderType extends AbstractType
{
    private $timeRegistry;
    private $orderProcessor;

    public function __construct(
        TimeRegistry $timeRegistry,
        OrderProcessorInterface $orderProcessor)
    {
        $this->timeRegistry = $timeRegistry;
        $this->orderProcessor = $orderProcessor;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('shippingAddress', AddressType::class, [
                'with_widget' => true,
                'with_description' => false,
            ])
            ;

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {

            $form = $event->getForm();
            $cart = $event->getData();

            $vendor = $cart->getVendor();
            $fulfillmentMethod = $cart->getFulfillmentMethodObject();

            $choiceLoader = new AsapChoiceLoader(
                $fulfillmentMethod->getOpeningHours(),
                $this->timeRegistry,
                $vendor->getClosingRules(),
                $fulfillmentMethod->getOrderingDelayMinutes(),
                $fulfillmentMethod->getOption('range_duration', 10),
                $fulfillmentMethod->isPreOrderingAllowed()
            );

            $payment = $cart->getLastPayment();

            $form->add('shippingTimeRange', ChoiceType::class, [
                'choice_loader' => $choiceLoader,
                'choice_label' => function ($choice, $key, $value) {
                    return (string) $choice;
                },
                'choice_value' => function ($choice) {
                    return $choice;
                },
                'data' => null,
                'mapped' => false,
            ]);

            $form->add('shippingAddress', AddressType::class, [
                'with_widget' => true,
                'with_description' => false,
                'label' => 'Dirección',
                'disabled' => $payment->getState() === PaymentInterface::STATE_COMPLETED,
            ]);

            if ($payment->getState() !== PaymentInterface::STATE_COMPLETED) {
                $form->add('payment', StripePaymentType::class, [
                    'data' => $payment,
                    'mapped' => false,
                ]);
            }

        });

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {

                $form = $event->getForm();
                $order = $form->getData();

                $order->setShippingTimeRange($form->get('shippingTimeRange')->getData()->toTsRange());

                $this->orderProcessor->process($order);
            }
        );

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => OrderInterface::class,
            ]);
    }
}
