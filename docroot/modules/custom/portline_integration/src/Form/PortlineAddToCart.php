<?php

namespace Drupal\portline_integration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\portline_integration\Services\CartServices;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PortlineAddToCart extends FormBase {

  /**
   * @var \Drupal\portline_integration\Services\CartServices
   */
  private $cartServices;

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer;

  /**
   * PortlineAddToCart constructor.
   *
   * @param \Drupal\portline_integration\Services\CartServices $cartServices
   *   Portline cart service.
   */
  public function __construct(CartServices $cartServices, RendererInterface $renderer) {
    $this->cartServices = $cartServices;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('portline_integration.cart_services'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'portline-cart';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['part_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Part Number'),
      '#maxlength' => 50,
      '#size' => 34,
      '#required' => TRUE,
    ];

    $form['part_quantity'] = [
      '#type' => 'number',
      '#title' => $this->t('Quantity'),
      '#maxlength' => 5,
      '#size' => 4,
      '#required' => TRUE,
      '#default_value' => 1,
    ];

    $form['submit_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add to Cart'),
      '#submit' => ['::submitForm'],
    ];

    $form['back_to_cart_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Back to Cart'),
      '#limit_validation_errors' => [],
      '#submit' => ['::returnToCart'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $add_to_cart_result = $this->cartServices->addToCart($values["part_quantity"], $values["part_number"]);
    if (!empty($add_to_cart_result['errors'])) {
      $form_state->setRebuild(FALSE);
      foreach ($add_to_cart_result['errors'] as $error_message) {
        $this->messenger()->addWarning($this->t('There has been a problem with the form you have just filled out. 
        Please review the message displayed below, and make the changes to the form accordingly.'));
        if ($error_message === 'Invalid part number.') {
          $this->messenger()
            ->addError($error_message . $this->t('Please try again or click @here_link for Assistance...',
                [
                  '@here_link' => Link::fromTextAndUrl($this->t('here'), Url::fromUri('https://payments.bimba.com/assistance.cfm'))
                    ->toString()
                ]));
        }
        else {
          $this->messenger()->addError($error_message);
        }
      }
    }
  }

  /**
   * Back button handler.
   */
  public function returnToCart(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('my_cart');
  }
}