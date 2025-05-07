 # How to add a custom destination

Jeero supports sending imported events to custom destinations (e.g., third-party calendar plugins or custom post types). To add a custom destination:

1. **Create and Register a Destination Class**

   You can create a new class that extends the `\Jeero\Calendars\Post_Based_Calendar` base destination:

   ```php
   // Register new calendar destination.
   add_action( 'plugins_loaded', function() {

       if ( ! defined( 'Jeero\\VERSION' ) ) {
           return;
       }

       class My_Calendar extends \Jeero\Calendars\Post_Based_Calendar {
           public function __construct() {
               // Unique identifier for this calendar.
               $this->slug = 'my_events';
               // Human-readable name shown in Jeero settings.
               $this->name = __( 'My Events', 'oerol-jeero' );
               // Set the post type for imported events.
               $this->post_type = 'my_events_post_type';
               parent::__construct();
           }
       }
      
       /**
        * Process each imported item.
        *
        * @param bool|\WP_Error $result        The current result; return WP_Error to cancel.
        * @param array          $data          Structured event data.
        * @param mixed          $raw           Raw event data.
        * @param string         $theater       Theater/source name.
        * @param Subscription   $subscription  Subscription object.
        * @return bool|\WP_Error             Modified result or error to halt.
        */
       public function process_data( $result, $data, $raw, $theater, $subscription ) {
           // First, let the base class import or update the event post.
           $result = parent::process_data( $result, $data, $raw, $theater, $subscription );
           
           // Add your custom logic here, e.g., save additional custom fields:
           // if ( ! is_wp_error( $result ) ) {
           //     update_post_meta( $result, '_my_custom_field', $data['production']['custom'] );
           // }
           
           return $result;
       }

       \Jeero\Calendars\register_calendar( 'My_Calendar' );
   } );
   ```
      
2. **Process Imported Items**

   The `process_data()` method you added in the class will be called for each imported inbox item. It is triggered via the `jeero/inbox/process/item/import/calendar={slug}` filter.

3. **Activate and Configure**

   - Activate your custom destination code (e.g., via a plugin or theme).
   - In the Jeero settings, select "My Events" (or your custom name) as the destination and configure its options.
