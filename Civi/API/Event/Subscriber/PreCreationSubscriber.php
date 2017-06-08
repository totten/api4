<?php

namespace Civi\API\Event\Subscriber;

use Civi\API\Event\PrepareEvent;
use Civi\API\V4\Action\Create;

abstract class PreCreationSubscriber Extends AbstractPrepareSubscriber {
  /**
   * @param PrepareEvent $event
   */
  public function onApiPrepare(PrepareEvent $event) {
    $apiRequest = $event->getApiRequest();
    if (!$apiRequest instanceof Create) {
      return;
    }

    $this->addDefaultCreationValues($apiRequest);
    if ($this->applies($apiRequest)) {
      $this->modify($apiRequest);
    }
  }

  /**
   * Modify the request
   *
   * @param Create $request
   *
   * @return void
   */
  abstract protected function modify(Create $request);

  /**
   * Check if this subscriber should be applied to the request
   *
   * @param Create $request
   *
   * @return bool
   */
  abstract protected function applies(Create $request);

  /**
   * Sets default values common to all creation requests
   *
   * @param Create $request
   */
  protected function addDefaultCreationValues(Create $request) {
    if (NULL === $request->getValue('is_active')) {
      $request->setValue('is_active', 1);
    }
  }
}
