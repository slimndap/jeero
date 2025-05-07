 # How to add a custom destination

 Jeero supports sending imported events to custom destinations (e.g., third-party calendar plugins or custom post types). To add a custom destination:

 1. **Create a Destination Class**
    - Implement the required interface or extend the base destination class.
    - Define methods to register settings, map fields, and save events.

 2. **Register Your Destination**
    Use the `jeero_destinations` filter to register your class:
    ```php
    add_filter( 'jeero_destinations', function( $destinations ) {
        $destinations['my_custom'] = [
            'label' => 'My Custom Destination',
            'class' => My_Custom_Destination::class,
        ];
        return $destinations;
    } );
    ```

 3. **Activate and Configure**
    - Activate your custom destination code (e.g., via a plugin or theme).
    - In the Jeero settings, select "My Custom Destination" and configure its options.

 For more advanced integrations, refer to the existing destination implementations in `wordpress.org/includes/Calendars` and `wordpress.org/includes/Theaters`.