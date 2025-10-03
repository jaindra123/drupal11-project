<?php

namespace Drupal\events_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\Entity\Node;

/**
 * Controller class for the Events API.
 * Provides a JSON response of upcoming events.
 */
class EventsApiController extends ControllerBase {

  /**
   * Returns a JSON response of upcoming events.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON array of upcoming events with title, date, category, and URL.
   */
  public function getUpcomingEvents() {
    // Get today's date (not currently used but can be used for future filtering).
    $today = date('Y-m-d');

    // Get the storage handler for 'node' entities.
    $storage = \Drupal::entityTypeManager()->getStorage('node');

    // Build an entity query to fetch 'event' nodes:
    $query = $storage->getQuery()
      ->accessCheck(TRUE)          // Ensure only nodes the current user can access are returned.
      ->condition('type', 'event') // Filter by content type 'event'.
      ->condition('status', 1)     // Only published nodes.
      ->sort('created', 'DESC')    // Sort by creation date, newest first.
      ->range(0, 10);              // Limit to the first 10 nodes.

    // Execute the query and get the node IDs.
    $nids = $query->execute();

    // Load full node entities from the retrieved IDs.
    $nodes = Node::loadMultiple($nids);

    // Initialize an array to store event data for JSON output.
    $events = [];

    // Loop through each node and extract required information.
    foreach ($nodes as $node) {
      $events[] = [
        'title' => $node->label(), // Node title.
        
        // Event date: check if field exists to prevent errors.
        'date' => $node->hasField('field_event_date') ? $node->get('field_event_date')->value : null,
        
        // Event category: check if field exists and has an entity reference.
        'category' => ($node->hasField('field_category') && $node->get('field_category')->entity)
          ? $node->get('field_category')->entity->label()
          : '',
        
        // Absolute URL to the node.
        'url' => $node->toUrl()->setAbsolute()->toString(),
      ];
    }

    // Return the data as a JSON response.
    return new JsonResponse($events);
  }
}
