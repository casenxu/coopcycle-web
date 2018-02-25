<?php

namespace AppBundle\Validator\Constraints;

use AppBundle\Service\RoutingInterface;
use Carbon\Carbon;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintValidator;

class DeliveryValidator extends ConstraintValidator
{
    private $routing;

    public function __construct(RoutingInterface $routing)
    {
        $this->routing = $routing;
    }

    private function validateNotBlank($value, $path)
    {
        $notBlank = new Assert\NotBlank();

        $violations = $this->context->getValidator()->validate($value, $notBlank);
        if (count($violations) > 0) {
            $this->context->buildViolation($notBlank->message)
                ->atPath($path)
                ->addViolation();
        }
    }

    public function validate($object, Constraint $constraint)
    {
        $validator = $this->context->getValidator();

        if (null !== $object->getOrder()) {

            $this->validateNotBlank($object->getDate(), 'date');

            $order = $object->getOrder();
            $restaurant = $order->getRestaurant();
            $now = Carbon::now();

            $distance = $object->getDistance();
            $duration = $object->getDuration();

            if (null === $distance || null === $duration) {
                $data = $this->routing->getRawResponse(
                    $restaurant->getAddress()->getGeo(),
                    $object->getDeliveryAddress()->getGeo()
                );
                $distance = $data['routes'][0]['distance'];
                $duration = $data['routes'][0]['duration'];
            }

            $maxDistance = $restaurant->getMaxDistance();

            $violations = $validator->validate($distance, new Assert\LessThan(['value' => $maxDistance]));
            if (count($violations) > 0) {
                $this->context->buildViolation($constraint->addressTooFarMessage)
                    ->atPath('deliveryAddress')
                    ->addViolation();
            }

            $readyAt = $order->getReadyAt();

            if (null === $readyAt) {
                // Given the time it takes to deliver,
                // calculate when the order should be ready
                $readyAt = clone $object->getDate();
                $readyAt->modify(sprintf('-%d seconds', $duration));
            }

            if (!$restaurant->isOpen($readyAt)) {
                $this->context->buildViolation(sprintf('Restaurant is closed at %s', $readyAt->format('Y-m-d H:i:s')))
                    ->atPath('date')
                    ->addViolation();
            }

            $totalDuration = $order->getDuration() + $object->getDuration();
            $timeLeftToPrepare = $readyAt->getTimestamp() - $now->getTimestamp();

            if ($timeLeftToPrepare < $totalDuration) {
                $this->context->buildViolation('Delivery date %date% is invalid')
                    ->setParameter('%date%', $object->getDate()->format('Y-m-d H:i:s'))
                    ->atPath('date')
                    ->addViolation();
            }

        }
    }
}
