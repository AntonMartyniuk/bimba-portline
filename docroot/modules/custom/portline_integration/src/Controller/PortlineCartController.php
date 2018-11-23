<?php

namespace Drupal\portline_integration\Controller;

use Drupal\Core\Controller\ControllerBase;

class PortlineCartController extends ControllerBase {

  public function myCart() {
    /** @var \Drupal\portline_integration\Services\CartServices $service */
    $service = \Drupal::service('portline_integration.cart_services');

    //$quantity = $service->getCartQuantity()['ssid'];
    $service->createGeneralRequest();
    $ssid = $service->getPortlineSessionId();

    $iframe_cart_url = $service->getPortlineCartIgrameUrl();

    return [
      '#cache' => [
        'max-age' => 0,
      ],
      '#theme' => 'iframe_cart_table',
      '#ssid' => $ssid,
      '#iframe_cart_url' => $iframe_cart_url,
    ];

  }

  public function priceAndDelivery() {
    /** @var \Drupal\portline_integration\Services\CartServices $service */
    $service = \Drupal::service('portline_integration.cart_services');

    $ssid = $service->getPortlineSessionId();

    $iframe_cart_price_and_delivery = $service->getPortlinePriceAndDeliveryUrl();
    return [
      '#cache' => [
        'max-age' => 0,
      ],
      '#theme' => 'iframe_cart_price_and_delivery',
      '#ssid' => $ssid,
      '#iframe_cart_url' => $iframe_cart_price_and_delivery,
    ];
  }


}
