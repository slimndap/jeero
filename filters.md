 # Filters and Actions

 Jeero provides several WordPress filters and actions to customize its behavior. Below is an overview of the available hooks.

 ## Filters

 | Filter Name                                   | Description                                                           | Parameters                                      |
 |-----------------------------------------------|-----------------------------------------------------------------------|-------------------------------------------------|
 | `jeero/inbox/no_of_items_per_pickup`          | Modify the number of items imported during each inbox pickup.         | `$no_of_items_per_pickup` (int|null)            |
 | `jeero/inbox/pickup_interval`                 | Modify the interval between inbox pickups (seconds).                  | `$interval` (int)                               |

 ## Actions

 | Action Name                                           | Description                                  | Parameters                                                                     |
 |-------------------------------------------------------|----------------------------------------------|--------------------------------------------------------------------------------|
 | `Jeero\Calendars\Post_Based_Calendar\event_updated` | Fires after an event is updated.             | `$post_id` (int), `$data` (array), `$raw` (array), `$theater` (string), `$subscription` (object) |
 | `Jeero\Calendars\Post_Based_Calendar\event_created` | Fires after a new event is created.          | `$post_id` (int), `$data` (array), `$raw` (array), `$theater` (string), `$subscription` (object) |

## Usage Examples

```php
// Change inbox items per pickup to 50.
add_filter( 'jeero/inbox/no_of_items_per_pickup', function( $no_of_items_per_pickup ) {
    return 50;
} );

// Change pickup interval to 5 minutes.
add_filter( 'jeero/inbox/pickup_interval', function( $interval ) {
    return 5 * MINUTE_IN_SECONDS;
} );

```

```php
// Listen for event updates.
add_action( 'Jeero\\Calendars\\Post_Based_Calendar\\event_updated', function( $post_id, $data, $raw, $theater, $subscription ) {
    // Your custom logic here, e.g. syncing to external service.
}, 10, 5 );

// Listen for new events.
add_action( 'Jeero\\Calendars\\Post_Based_Calendar\\event_created', function( $post_id, $data, $raw, $theater, $subscription ) {
    // Your custom logic here, e.g. sending a notification.
}, 10, 5 );
```