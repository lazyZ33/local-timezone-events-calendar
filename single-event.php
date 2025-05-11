<?php
/**
 * Single Event Template
 * A single event. This displays the event title, description, meta, and
 * optionally, the Google map for the event.
 *
 * Override this template in your own theme by creating a file at
 * [your-theme]/tribe-events/single-event.php
 *
 * @package TribeEventsCalendar
 * @version 4.6.19 (This is the version of the original template.
 * Ensure your The Events Calendar plugin is reasonably up-to-date
 * for compatibility with the functions used in the customization.)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$events_label_singular = tribe_get_event_label_singular();
$events_label_plural   = tribe_get_event_label_plural();

$event_id = Tribe__Events__Main::postIdHelper( get_the_ID() );

/**
 * Allows filtering of the event ID.
 *
 * @since 6.0.1
 *
 * @param numeric $event_id
 */
$event_id = apply_filters( 'tec_events_single_event_id', $event_id );

/**
 * Setting up the cookie if it doesn't exist yet for browser timezone.
 */
if ( ! isset( $_COOKIE['tribe_browser_time_zone'] ) ) { ?>
    <script type="text/javascript">
        if ( navigator.cookieEnabled ) {
            try {
                var userTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                document.cookie = "tribe_browser_time_zone=" + encodeURIComponent(userTimeZone) + "; path=/; SameSite=Lax";
            } catch (e) {
                console.warn("Could not determine browser timezone:", e);
            }
        }
    </script>
<?php }
 
/**
 * Calculating the event start time and time zone based on the browser time zone of the visitor.
 */
 
// Setting default values
$user_time_output = "<small>Your time zone couldn't be detected.";
$user_time_output .= ( ! isset( $_COOKIE['tribe_browser_time_zone'] ) ) ? " Please <a href='javascript:location.reload();' style='text-decoration:underline;'>reload the page</a> to see times in your local timezone." : " Try <a href='javascript:location.reload();' style='text-decoration:underline;'>reloading</a> the page or ensure cookies are enabled.</small>";
$browser_time_zone_string_for_display = "not detected";

if ( isset( $_COOKIE['tribe_browser_time_zone'] ) && ! empty( $_COOKIE['tribe_browser_time_zone'] ) ) {
    $raw_browser_tz_string = sanitize_text_field( wp_unslash( $_COOKIE['tribe_browser_time_zone'] ) );

    if ( preg_match( '/^[A-Za-z0-9_\-\/]+$/', $raw_browser_tz_string ) ) {
        try {
            $browser_dtz = new DateTimeZone( $raw_browser_tz_string );

            $event_start_utc = tribe_get_event_meta( $event_id, '_EventStartDateUTC', true );

            if ( ! empty( $event_start_utc ) ) {
                if ( is_string( $event_start_utc ) ) {
                    $event_start_date_in_utc_timezone = new DateTime( $event_start_utc, new DateTimeZone( 'UTC' ) );
                    $event_start_in_browser_tz_obj = clone $event_start_date_in_utc_timezone;
                    $event_start_in_browser_tz_obj->setTimezone( $browser_dtz );
                    
                    $time_format = get_option( 'time_format', 'g:i a' );
                    $event_start_date_in_browser_timezone_formatted = $event_start_in_browser_tz_obj->format( $time_format );
                    $browser_time_zone_abbreviation = $event_start_in_browser_tz_obj->format( 'T' );

                    $user_time_output = esc_html( $event_start_date_in_browser_timezone_formatted . " " . $browser_time_zone_abbreviation );
                    $browser_time_zone_string_for_display = esc_html( $raw_browser_tz_string ) . ' (detected)';
                } else {
                    $user_time_output = "<small>Event's base time format is unexpected.</small>";
                    $browser_time_zone_string_for_display = esc_html( $raw_browser_tz_string ) . ' (detected, but event base time invalid)';
                    if ( defined('WP_DEBUG') && WP_DEBUG ) {
                        error_log('TEC Timezone Customization: EventStartUTC is not a string for event ID ' . $event_id . '. Value: ' . print_r($event_start_utc, true));
                    }
                }
            } else {
                $user_time_output = "<small>Event's base time not available for conversion.</small>";
                $browser_time_zone_string_for_display = esc_html( $raw_browser_tz_string ) . ' (detected, but event base time missing)';
            }
        } catch ( Exception $e ) {
            if ( defined('WP_DEBUG') && WP_DEBUG ) {
                error_log( 'TEC Timezone Customization Error: ' . $e->getMessage() . ' for TZ string: ' . $raw_browser_tz_string . ' for event ID ' . $event_id);
            }
            $user_time_output = "<small>Could not convert time. The timezone detected (" . esc_html( $raw_browser_tz_string ) . ") may be invalid.</small>";
            $browser_time_zone_string_for_display = esc_html( $raw_browser_tz_string ) . ' (invalid or error during processing)';
        }
    } else {
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log( 'TEC Timezone Customization: Invalid characters in cookie timezone string: ' . $raw_browser_tz_string );
        }
        $user_time_output = "<small>Invalid timezone string format received from browser.</small>";
        $browser_time_zone_string_for_display = esc_html( $raw_browser_tz_string ) . ' (invalid format)';
    }
}

/**
 * Allows filtering of the single event template title classes.
 * @since 5.8.0
 * @param array   $title_classes List of classes to create the class string from.
 * @param numeric $event_id      The ID of the displayed event.
 */
$title_classes = apply_filters( 'tribe_events_single_event_title_classes', [ 'tribe-events-single-event-title' ], $event_id );
$title_classes = implode( ' ', tribe_get_classes( $title_classes ) );

/**
 * Allows filtering of the single event template title before HTML.
 * @since 5.8.0
 * @param string  $before   HTML string to display before the title text.
 * @param numeric $event_id The ID of the displayed event.
 */
$before = apply_filters( 'tribe_events_single_event_title_html_before', '<h1 class="' . esc_attr( $title_classes ) . '">', $event_id );

/**
 * Allows filtering of the single event template title after HTML.
 * @since 5.8.0
 * @param string  $after    HTML string to display after the title text.
 * @param numeric $event_id The ID of the displayed event.
 */
$after = apply_filters( 'tribe_events_single_event_title_html_after', '</h1>', $event_id );

/**
 * Allows filtering of the single event template title HTML.
 * @since 5.8.0
 * @param string  $html     HTML string to display. Return an empty string to not display the title.
 * @param numeric $event_id The ID of the displayed event.
 */
$raw_title = get_the_title( null, null, false );
$title_html = apply_filters( 'tribe_events_single_event_title_html', $raw_title, $event_id ); 
if ( ! empty( $title_html ) && $title_html === $raw_title ) { // Check if filter changed it; if not, apply before/after
    $title_html = $before . esc_html( $title_html ) . $after;
} elseif ( ! empty( $title_html ) ) {
	// Assume the filter has already prepared the full HTML for the title
	// Or, if it only returned a modified string, you might still want to wrap it:
	// $title_html = $before . esc_html( $title_html ) . $after;
	// For now, we assume if the filter returns something different than raw_title, it's ready.
}


$cost  = tribe_get_formatted_cost( $event_id );

?>

<div id="tribe-events-content" class="tribe-events-single">

	<p class="tribe-events-back">
		<a href="<?php echo esc_url( tribe_get_events_link() ); ?>"> <?php printf( '&laquo; ' . esc_html_x( 'All %s', '%s Events plural label', 'the-events-calendar' ), esc_html( $events_label_plural ) ); ?></a>
	</p>

	<?php tribe_the_notices() ?>

	<?php echo $title_html; ?>

	<div class="tribe-events-schedule tribe-clearfix">
		<?php echo tribe_events_event_schedule_details( $event_id, '<h2>', '</h2>' ); ?>
        <?php
        /**
        * Adding the event start time in the visitor's time zone.
        */
        if ( ! tribe_event_is_all_day( $event_id ) && ! empty( $user_time_output ) ) {
            echo "<div class='tribe-events-schedule--browser-time-zone tribe-events-meta-group tribe-events-meta-group-timezone' style='margin-top:10px; margin-bottom:10px;'>";
            echo "<dt class='tribe-events-meta-label'>" . esc_html__( 'Start time where you are:', 'your-text-domain' ) . "</dt>";
            echo "<dd class='tribe-events-meta-value' title='" . esc_attr( 'This is based on your browser time zone (' . $browser_time_zone_string_for_display . ') and it might not be fully accurate.' ) . "'>" . $user_time_output . "</dd>";
            echo "</div>";
        }
        ?>
		<?php if ( ! empty( $cost ) ) : ?>
			<span class="tribe-events-cost"><?php echo esc_html( $cost ); ?></span>
		<?php endif; ?>
	</div>

	<div id="tribe-events-header" <?php tribe_events_the_header_attributes(); ?>>
		<nav class="tribe-events-nav-pagination" aria-label="<?php printf( esc_html__( '%s Navigation', 'the-events-calendar' ), esc_html( $events_label_singular ) ); ?>">
			<ul class="tribe-events-sub-nav">
				<li class="tribe-events-nav-previous"><?php tribe_the_prev_event_link( '<span>&laquo;</span> %title%' ); ?></li>
				<li class="tribe-events-nav-next"><?php tribe_the_next_event_link( '%title% <span>&raquo;</span>' ); ?></li>
			</ul>
		</nav>
	</div>
	<?php while ( have_posts() ) :  the_post(); ?>
		<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<?php echo tribe_event_featured_image( $event_id, 'full', false ); ?>

			<?php do_action( 'tribe_events_single_event_before_the_content' ); ?>
			<div class="tribe-events-single-event-description tribe-events-content">
				<?php the_content(); ?>
			</div>
			<?php do_action( 'tribe_events_single_event_after_the_content' ); ?>

			<?php do_action( 'tribe_events_single_event_before_the_meta' ); ?>
			<?php tribe_get_template_part( 'modules/meta' ); ?>
			<?php do_action( 'tribe_events_single_event_after_the_meta' ); ?>
		</div> <?php if ( get_post_type() == Tribe__Events__Main::POSTTYPE && tribe_get_option( 'showComments', false ) ) comments_template(); ?>
	<?php endwhile; ?>

	<div id="tribe-events-footer">
		<nav class="tribe-events-nav-pagination" aria-label="<?php printf( esc_html__( '%s Navigation', 'the-events-calendar' ), esc_html( $events_label_singular ) ); ?>">
			<ul class="tribe-events-sub-nav">
				<li class="tribe-events-nav-previous"><?php tribe_the_prev_event_link( '<span>&laquo;</span> %title%' ); ?></li>
				<li class="tribe-events-nav-next"><?php tribe_the_next_event_link( '%title% <span>&raquo;</span>' ); ?></li>
			</ul>
		</nav>
	</div>
	</div>