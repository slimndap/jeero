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
           }
       }

       \Jeero\Calendars\register_calendar( 'My_Calendar' );
   } );
   ```

2. **Activate and Configure**

   - Activate your custom destination code (e.g., via a plugin or theme).
   - In the Jeero settings, select "Oerol program and playlists" (or your custom name) as the destination and configure its options.

For more advanced integrations, refer to the existing destination implementations in `wordpress.org/includes/Calendars` and `wordpress.org/includes/Theaters`.