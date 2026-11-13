<?php
use Jeero\Db\Subscriptions as Db;

class Native_Calendar_Read_Test extends Jeero_Test {
	/** @dataProvider metadata_calendars */
	function test_post_calendars_read_native_fields_without_source_metadata( $slug, $meta, $taxonomy ) {
		switch ( $slug ) {
			case 'GDLR_Events':
				$calendar = new class extends Jeero\Calendars\GDLR_Events { function is_active() { return true; } };
				break;
			case 'Very_Simple_Event_List':
				$calendar = new class extends Jeero\Calendars\Very_Simple_Event_List { function is_active() { return true; } };
				break;
			case 'Events_Schedule_Wp_Plugin':
				$calendar = new class extends Jeero\Calendars\Events_Schedule_Wp_Plugin { function is_active() { return true; } };
				register_taxonomy( 'wcs-room', 'class' );
				break;
		}
		register_post_type( $calendar->get_post_type() );
		register_taxonomy( $taxonomy, $calendar->get_post_type() );
		update_option( Db\JEERO_OPTION_SUBSCRIPTION, array( 'native' => array( 'settings' => array( 'theater' => 'veezi' ) ) ) );
		$post_id = self::factory()->post->create( array( 'post_type' => $calendar->get_post_type(), 'post_title' => 'Native title', 'post_content' => 'Native description' ) );
		foreach ( $meta + array( 'jeero/import/post/subscription' => 'native', 'jeero/import/post/theater' => 'veezi', $calendar->get_ref_key( 'veezi' ) => 'native-ref' ) as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}
		wp_set_object_terms( $post_id, array( 'Native category' ), $taxonomy );
		$events = $calendar->get_events( array( 'date' => '2031-02-03', 'query' => 'Native', 'category' => 'Native category' ) );
		$this->assertIsArray( $events );
		$this->assertCount( 1, $events );
		$this->assertSame( 'Native title', $events[0]['production']['title'] );
		$this->assertSame( 'Native description', $events[0]['production']['description'] );
		$this->assertSame( '20:15', substr( $events[0]['start'], 11, 5 ) );
		$this->assertSame( 'native-ref', $calendar->get_event( 'native-ref' )['ref'] );
		$this->assertCount( 0, $calendar->get_events( array( 'date' => '2031-02-04' ) ) );
		wp_trash_post( $post_id );
		$this->assertCount( 0, $calendar->get_events() );
	}

	static function metadata_calendars() {
		$start = strtotime( '2031-02-03 20:15:00 UTC' );
		return array(
			array( 'GDLR_Events', array( 'post-option' => '{"date":"2031-02-03","time":"20:15","status":"sold-out"}' ), 'event_category' ),
			array( 'Very_Simple_Event_List', array( 'event-start-date' => $start, 'event-date' => $start + HOUR_IN_SECONDS ), 'event_cat' ),
			array( 'Events_Schedule_Wp_Plugin', array( '_wcs_timestamp' => $start, '_wcs_duration' => 60 ), 'wcs-type' ),
		);
	}

	function test_custom_post_types_use_each_subscriptions_post_type_and_taxonomy() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			$this->markTestSkipped( 'The Abilities API requires WordPress 6.9 or newer.' );
		}
		update_option( 'jeero/enable_custom_post_types', true );
		$calendar = new Jeero\Calendars\Custom_Post_Type();
		foreach ( array( 'native_one', 'native_two' ) as $type ) {
			register_post_type( $type );
			register_taxonomy( $type . '_cat', $type );
			Db\save_subscription( $type, array( 'theater' => 'veezi', 'calendar' => array( 'Custom_Post_Type' ), 'Custom_Post_Type/import/post_type' => $type, 'Custom_Post_Type/import/categories_taxonomy' => $type . '_cat' ) );
			$post_id = self::factory()->post->create( array( 'post_type' => $type, 'post_title' => 'Native ' . $type, 'post_content' => 'Native content' ) );
			$subscription = new Jeero\Subscriptions\Subscription( $type );
			$calendar->update_ticket_context_meta( $post_id, array( 'ref' => $type, 'start' => '2031-02-03 20:15:00', 'production' => array( 'title' => 'Source title' ) ), $subscription );
			wp_set_object_terms( $post_id, array( $type . ' category' ), $type . '_cat' );
		}
		$events = $calendar->get_events();
		$this->assertCount( 2, $events );
		foreach ( $events as $event ) {
			$this->assertSame( 'Native ' . $event['subscription_id'], $event['production']['title'] );
			$this->assertSame( array( $event['subscription_id'] . ' category' ), $event['production']['categories'] );
		}
		delete_post_meta( $post_id, '_jeero_event_data' );
		$this->assertCount( 1, $calendar->get_events() );
	}
}
