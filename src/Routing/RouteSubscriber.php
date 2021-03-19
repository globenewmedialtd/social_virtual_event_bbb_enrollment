<?php
/**
 * @file
 * Contains \Drupal\social_virtual_event_bbb_enrollment\Routing\RouteSubscriber.
 */

namespace Drupal\social_virtual_event_bbb_enrollment\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Replace "some.route.name" below with the actual route you want to override.
    if ($route = $collection->get('virtual_event_bbb.virtual_event_bbb_modal_controller_join')) {
      $route->setDefaults(array(
        '_controller' => '\Drupal\social_virtual_event_bbb_enrollment\Controller\VirtualEventBBBEnrolledModalController::join',
      ));
    }
  }
}
