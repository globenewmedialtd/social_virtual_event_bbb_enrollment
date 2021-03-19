<?php

namespace Drupal\social_virtual_event_bbb_enrollment\Controller;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\virtual_events\Entity\VirtualEventsEventEntity;
use BigBlueButton\Parameters\JoinMeetingParameters;
use Drupal\virtual_event_bbb\VirtualEventBBB;

/**
 * Class VirtualEventZoomModalController.
 */
class VirtualEventBBBEnrolledModalController extends ControllerBase {

  /**
   * Join.
   *
   * @return string
   *   Return Hello string.
   */
  public function join(VirtualEventsEventEntity $event) {
    $BBBKeyPluginManager = \Drupal::service('plugin.manager.bbbkey_plugin');
    $virtualEventsCommon = \Drupal::service('virtual_events.common');
    $user = \Drupal::currentUser();

    $entity = $event->getEntity();
    $enabled_event_source = $event->getEnabledSourceKey();
    $event_config = $event->getVirtualEventsConfig($enabled_event_source);
    $source_config = $event_config->getSourceConfig($enabled_event_source);
    $source_data = $event->getSourceData();
    $settings = $source_data["settings"];

    if(!isset($source_config["data"]["key_type"])){
      drupal_set_message(t("Couldn't create meeting! please contact system administrator."), 'error');
    }

    $keyPlugin = $BBBKeyPluginManager->createInstance($source_config["data"]["key_type"]);
    $keys = $keyPlugin->getKeys($source_config);

    $apiUrl = $keys["url"];
    $secretKey = $keys["secretKey"];
    $bbb = new VirtualEventBBB($secretKey, $apiUrl);

    /* Check if meeting is not active,
    recreate it before showing the join url */
    $event->reCreate();

    /* Check access for current entity, if user can update
    then we can consider the user as moderator,
    otherwise we consider the user as normal attendee.
     */
    if ($entity->access('update')) {
      $joinMeetingParams = new JoinMeetingParameters($event->id(), $user->getDisplayName(), $settings["moderatorPW"]);
    }
    elseif ($entity->access('view')) {
      $joinMeetingParams = new JoinMeetingParameters($event->id(), $user->getDisplayName(), $settings["attendeePW"]);
    }
    else {
      throw new AccessDeniedHttpException();
    }

    try {
      $joinMeetingParams->setRedirect(TRUE);
      $url = $bbb->getJoinMeetingURL($joinMeetingParams);

      return [
        '#theme' => 'virtual_event_bbb_iframe',
        '#url' => $url,
      ];
    } catch (\RuntimeException $exception) {
      watchdog_exception('virtual_event_bbb', $exception, $exception->getMessage());
      drupal_set_message(t("Couldn't get meeting join link! please contact system administrator."), 'error');
    }


  }

}
