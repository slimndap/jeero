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
               $this->slug = 'oerol';
               $this->name = __( 'Oerol program and playlists', 'oerol-jeero' );
               parent::__construct();
               // Hook into Jeero inbox processing for this calendar (action 'import').
               add_filter(
                   'jeero/inbox/process/item/import/calendar=' . $this->slug,
                   [ $this, 'process_data' ],
                   10,
                   5
               );
           }
       }
      
       /**
        * Process each imported item before saving.
        *
        * Triggered via 'jeero/inbox/process/item/import/calendar={slug}'.
        *
        * @param bool|\WP_Error $result        The current result; return WP_Error to cancel.
        * @param array          $data          Structured event data.
        * @param mixed          $raw           Raw event data.
        * @param string         $theater       Theater/source name.
        * @param Subscription   $subscription  Subscription object.
        * @return bool|\WP_Error             Modified result or error to halt.
        */
       public function process_data( $result, $data, $raw, $theater, $subscription ) {
           // Your custom logic, e.g., skip or transform events.
           return $result;
       }

       \Jeero\Calendars\register_calendar( 'My_Calendar' );
   } );
       ```
      
2. **Process Imported Items**

   The `process_data()` method you added in the class will be called for each imported inbox item. It is triggered via the `jeero/inbox/process/item/import/calendar={slug}` filter.

3. **Activate and Configure**

   - Activate your custom destination code (e.g., via a plugin or theme).
   - In the Jeero settings, select "Oerol program and playlists" (or your custom name) as the destination and configure its options.

For more advanced integrations, refer to the existing destination implementations in `wordpress.org/includes/Calendars` and `wordpress.org/includes/Theaters`.