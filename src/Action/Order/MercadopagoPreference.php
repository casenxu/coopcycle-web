<?php

namespace AppBundle\Action\Order;

use MercadoPago;
use AppBundle\Service\SettingsManager;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Payment\MercadopagoPreferenceResponse;

class MercadopagoPreference
{
    private $settingsManager;

    public function __construct(
        SettingsManager $settingsManager)
    {
        $this->settingsManager = $settingsManager;
    }

    /**
     * @return MercadoPago\Preference
     */
    public function __invoke($data, Request $request)
    {
        $account = $data->getRestaurant()->getMercadopagoAccount(true);
        if ($account) {
            MercadoPago\SDK::setAccessToken($account->getAccessToken());
        }

        // Create a preference object
        $preference = new MercadoPago\Preference();

        // Create items for the preference
        // We add one item for the whole order
        $items = [];
        $mpItem = new MercadoPago\Item();
        $mpItem->title = sprintf('Pedido #%s', $data->getNumber()); // este texto es el que el vendedor puede ver en el detalle de la Operación/Actividad en mercadopago.com.ar/activities/detail/
        $mpItem->description = $data->getRestaurant()->getName(); // este texto es el que el usuario/comprador puede ver al momento de pagar en el flujo de MP
        $mpItem->quantity = 1;
        $mpItem->unit_price = ($data->getTotal() / 100);
        $items[] = $mpItem;
        $preference->items = $items;

        // Add payer info to improve payment approvals
        // https://www.mercadopago.com.ar/developers/es/guides/online-payments/checkout-pro/advanced-integration
        $payer = new MercadoPago\Payer();
        $payer->email = $data->getCustomer()->getEmail();
        $preference->payer = $payer;

        $preference->marketplace_fee = ($data->getFeeTotal() / 100);

        // Instant Payment Notifications URL
        $preference->notification_url = "https://www.your-site.com/ipn";

        $preference->save();

        $response = new MercadopagoPreferenceResponse($preference);

        return $response->id();
    }
}
