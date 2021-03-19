<?php

namespace Drupal\social_virtual_event_bbb_enrollment\Plugin\VirtualEvent\FormatterPlugin;

use Drupal\virtual_event_bbb\Plugin\VirtualEvent\FormatterPlugin\VirtualEventBBBFormatter;
use Drupal\virtual_events\Plugin\VirtualEventFormatterPluginBase;
use Drupal\virtual_events\Entity\VirtualEventsEventEntity;
use Drupal\virtual_events\Entity\VirtualEventsFormatterEntity;
use Drupal\virtual_event_bbb\VirtualEventBBB;
use Drupal\virtual_event_bbb\Form\VirtualEventBBBLinkForm;
use Drupal\Core\Entity\EntityInterface;
use BigBlueButton\Parameters\JoinMeetingParameters;
use BigBlueButton\Parameters\GetRecordingsParameters;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Url;

class VirtualEventBBBEnrolledFormatter extends VirtualEventBBBFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElement(EntityInterface $entity, VirtualEventsEventEntity $event, VirtualEventsFormatterEntity $formatters_config, array $source_config, array $source_data, array $formatter_options) {
    $user = \Drupal::currentUser();
    $entity_type = $entity->getEntityTypeId();
    $entity_bundle = $entity->bundle();
    $entity_id = $entity->id();
    $BBBKeyPluginManager = \Drupal::service('plugin.manager.bbbkey_plugin');

    $element = [];
    $settings = [];
    if (isset($source_data["settings"])) {
      $settings = $source_data["settings"];
    }

    if(!isset($source_config["data"]["key_type"])) return;

    $keyPlugin = $BBBKeyPluginManager->createInstance($source_config["data"]["key_type"]);
    $keys = $keyPlugin->getKeys($source_config);
    try {
      if ($event) {
        if ($formatter_options) {
          $display_options = $formatter_options;
          if (!$display_options["show_iframe"]) {
            if (isset($display_options["modal"], $display_options["modal"]["open_in_modal"]) && $display_options["modal"]["open_in_modal"]) {
              if ($entity->access('view')) {
                if (empty($display_options)) {
                  $display_options = $this->defaultSettings();
                }
                if (!$entity->access('update')) {
                  
                  $element["virtual_event_bbb_modal"] = [
                    '#theme' => 'virtual_event_bbb_modal',
                    '#join_url' => Url::fromRoute('virtual_event_bbb.virtual_event_bbb_modal_controller_join', ['event' => $event->id()]),
                    '#display_options' => $display_options,
                  ];
                }
                else {
                  $element["join_link"] = \Drupal::formBuilder()->getForm(VirtualEventBBBLinkForm::class, $event, $display_options);
                }
              }
            }
            else {
              $element["join_link"] = \Drupal::formBuilder()->getForm(VirtualEventBBBLinkForm::class, $event, $display_options);
            }
          }
          else {
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

            $joinMeetingParams->setRedirect(TRUE);
            try {
              $url = $bbb->getJoinMeetingURL($joinMeetingParams);

              $element["meeting_iframe"] = [
                '#theme' => 'virtual_event_bbb_iframe',
                '#url' => $url,
              ];
            } catch (\RuntimeException $exception) {
              watchdog_exception('virtual_event_bbb', $exception, $exception->getMessage());
              drupal_set_message(t("Couldn't get meeting join link! please contact system administrator."), 'error');
            }

          }

          if ($display_options["recordings"]["show_recordings"]) {
            $apiUrl = $keys["url"];
            $secretKey = $keys["secretKey"];
            $bbb = new VirtualEventBBB($secretKey, $apiUrl);

            $recordingParams = new GetRecordingsParameters();
            $recordingParams->setMeetingID($event->id());

            try {
              $response = $bbb->getRecordings($recordingParams);
              if (!empty($response->getRawXml()->recordings->recording)) {
              switch ($display_options["recordings"]["recordings_display"]) {
                case 'links':
                  $element["meeting_recordings"] = [
                    '#theme' => 'virtual_event_bbb_recordings_links',
                    '#url' => Url::fromRoute('virtual_event_bbb.virtual_event_b_b_b_recording_controller_view_recording', ['event' => $event->id()]),
                    '#display_options' => $display_options,
                    '#recordings' => $response->getRawXml()->recordings->recording,
                  ];
                  break;

                case 'linked_thumbnails':
                  $element["meeting_recordings"] = [
                    '#theme' => 'virtual_event_bbb_recordings_linked_thumbnails',
                    '#url' => Url::fromRoute('virtual_event_bbb.virtual_event_b_b_b_recording_controller_view_recording', ['event' => $event->id()]),
                    '#display_options' => $display_options,
                    '#recordings' => $response->getRawXml()->recordings->recording,
                  ];
                  break;

                case 'video':
                  $element["meeting_recordings"] = [
                    '#theme' => 'virtual_event_bbb_recordings_video',
                    '#recordings' => $response->getRawXml()->recordings->recording,
                  ];
                  break;

                default:
                  $element["meeting_recordings"] = [
                    '#theme' => 'virtual_event_bbb_recordings_links',
                    '#url' => Url::fromRoute('virtual_event_bbb.virtual_event_b_b_b_recording_controller_view_recording', ['event' => $event->id()]),
                    '#display_options' => $display_options,
                    '#recordings' => $response->getRawXml()->recordings->recording,
                  ];
                  break;
              }
            }
            } catch (\RuntimeException $exception) {
              watchdog_exception('virtual_event_bbb', $exception, $exception->getMessage());
              drupal_set_message(t("Couldn't get recordings! please contact system administrator."), 'error');
            }
          }
        }
        else {
          $element["join_link"] = \Drupal::formBuilder()->getForm(VirtualEventBBBLinkForm::class, $event);
        }
      }
    }
    catch (\RuntimeException $error) {
      $element = [];
    }
    return $element;
  }

}
