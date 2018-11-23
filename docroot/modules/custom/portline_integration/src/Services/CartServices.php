<?php

namespace Drupal\portline_integration\Services;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\SessionManagerInterface;
use GuzzleHttp\ClientInterface;
class CartServices {
  /**
   * Http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Wachdog logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerChannelFactory;

  /**
   * Session manager.
   *
   * @var SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * Configuration Factory.
   *
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  private $user;
  private $ssid;

  public function __construct(ClientInterface $httpClient,
                              LoggerChannelFactoryInterface $loggerChannelFactory,
                              ConfigFactoryInterface $configFactory,
                              SessionManagerInterface $sessionManager) {
    $this->httpClient = $httpClient;
    $this->loggerChannelFactory = $loggerChannelFactory;
    $this->configFactory = $configFactory;
    $this->sessionManager = $sessionManager;

    $this->user = \Drupal::currentUser();

  }
  /**
   * @return mixed
   *   Unique Session ID from Portline.
   */
  public function getPortlineSessionId() {
    return $_COOKIE["Drupal_visitor_bimba_portline"];
  }

  /**
   * Saves Portline Session ID into _$COOKIE.
   */
  private function savePorlineSessionId($portlineSessionId) {
    user_cookie_save(['bimba_portline' => $portlineSessionId]);
  }

  /**
   * Generates new Session ID on the Portline side.
   *
   * @return string
   *   Portline cart Session ID.
   */
  private function initiateSessionId() {
    $userEmail = NULL;
    $userId = NULL;
    $conversionId = NULL;
    $current_user = \Drupal::currentUser();
    if (array_key_exists('Drupal_visitor_bimba_portline', $_COOKIE)) {
      $this->ssid = $_COOKIE["Drupal_visitor_bimba_portline"];
      $cart_ssid = $this->getCartQuantity()['ssid'];
      if ($this->ssid != $cart_ssid) {
        $this->savePorlineSessionId($cart_ssid);
      }
      else {
        $this->setPorlineSessionId();
      }
    }
    return;
  }

  private function associateUserWithPortlineSsid() {
    $request = '';
    if ($this->user->isAuthenticated()) {
      $options = [
        'query' => [
          'userId' => $this->user->getEmail(),
          'email' => $this->user->getEmail(),
          'conversationId' => $this->ssid,
        ],
        'auth' => ['client', 'Black]27Rain']
      ];

      $requestUrl = $this->configFactory->get('portline_integration')->get('portline_session_init_url');
      try {
        $request = $this->httpClient->request('POST', $requestUrl, $options);
      }
      catch (\Exception $e) {
        $this->loggerChannelFactory->get('portline_integration')->error($e->getMessage());
        return $request;
      }
    }
    else {
      $this->initiateSessionId();
    }
  }

  private function setPorlineSessionId() {
    user_cookie_delete('bimba_portline');
    $ssid = $this->getCartQuantity()['ssid'];
    $this->savePorlineSessionId($ssid);
  }

  /**
   * Makes response to Portline.
   *
   * @return mixed
   *   Decoded cart JSON.
   */
  public function getCartQuantity() {
    $options = [
      'query' => [
        'ssid' => $this->ssid,
        'action' => 'quantity',
      ],
      'auth' => ['client', 'Black]27Rain']
    ];
    $request = '';
    $requestUrl = $this->configFactory->get('portline_integration')->get('portline_cart_url');
    try {
      $request = $this->httpClient->request('POST', $requestUrl, $options);
    }
    catch (\Exception $e) {
      $this->loggerChannelFactory->get('portline_integration')->error($e->getMessage());
      return $request;
    }
    return Json::decode($request->getBody());
  }

  /**
   * @param $quantity
   *   The quantity of the item. Values are between 1 and 999.
   * @param $partNumber
   *   The part number of the item.
   *
   * @return mixed
   *   Add to cart response.
   */
  public function addToCart($quantity, $partNumber) {
    $options = [
      'query' => [
        'ssid' => $this->getPortlineSessionId(),
        'action' => 'add',
        'quantity' => $quantity,
        'partNumber' => $partNumber,
      ],
    ];
    $request = '';
    $requestUrl = $this->configFactory->get('portline_integration')->get('portline_cart_url');
    try {
      $request = $this->httpClient->request('POST', $requestUrl, $options);
    }
    catch (\Exception $e) {
      $this->loggerChannelFactory->get('portline_integration')->error($e->getMessage());
      return $request;
    }
    return Json::decode($request->getBody());
  }

  public function getCartItemsTable($ssid) {
    $options = [
      'query' => [
        'ssid' => $ssid,
        ],
      'auth' => ['client', 'Black]27Rain']
      ];
    $request = '';
    $requestUrl = $this->configFactory->get('portline_integration')->get('portline_cart_iframe');
    try {
      $request = $this->httpClient->request('POST', $requestUrl, $options);
    }
    catch (\Exception $e) {
      $this->loggerChannelFactory->get('portline_integration')->error($e->getMessage());
      return $request;
    }
    return $request->getBody()->getContents();
  }

  public function getPortlineCartIgrameUrl() {
    return $this->configFactory->get('portline_integration')->get('portline_cart_iframe');
  }

  public function getPortlinePriceAndDeliveryUrl() {
    return $this->configFactory->get('portline_integration')->get('portline_cart_price_and_delivery');
  }

  public function createGeneralRequest() {
    $this->initiateSessionId();
    $this->associateUserWithPortlineSsid();
  }
}