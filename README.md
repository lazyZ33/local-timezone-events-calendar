# Events Calendar Timezone Override

![WordPress](https://img.shields.io/badge/WordPress-Plugin_Override-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)
![License](https://img.shields.io/badge/License-GPLv2-green.svg)

> Custom template override for The Events Calendar that displays event times in visitors' local timezones

## Features

- 🌐 Automatic timezone detection using browser API
- 🕒 Real-time conversion of event times to visitor's local time
- 🍪 Persistent timezone storage via cookies
- ⚙️ Fallback mechanisms for error handling
- 📝 Clear user messaging about timezone conversions
- 🔄 Seamless integration with The Events Calendar plugin

## Installation

1. Place the `tribe-events` folder in your theme directory: wp-content/themes/your-theme/tribe-events/
2. Ensure The Events Calendar plugin is installed and active

## Technical Implementation

### Client-Side Detection
```javascript
<script type="text/javascript">
 if (navigator.cookieEnabled) {
     try {
         var userTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
         document.cookie = "tribe_browser_time_zone=" + encodeURIComponent(userTimeZone) + "; path=/; SameSite=Lax";
     } catch (e) {
         console.warn("Could not determine browser timezone:", e);
     }
 }
</script>
```
# Server-Side Conversion
```php
if (isset($_COOKIE['tribe_browser_time_zone']) && !empty($_COOKIE['tribe_browser_time_zone'])) {
    $raw_browser_tz_string = sanitize_text_field(wp_unslash($_COOKIE['tribe_browser_time_zone']));
    
    if (preg_match('/^[A-Za-z0-9_\-\/]+$/', $raw_browser_tz_string)) {
        try {
            $browser_dtz = new DateTimeZone($raw_browser_tz_string);
            $event_start_date_in_utc_timezone = new DateTime($event_start_utc, new DateTimeZone('UTC'));
            $event_start_in_browser_tz_obj = clone $event_start_date_in_utc_timezone;
            $event_start_in_browser_tz_obj->setTimezone($browser_dtz);
            
            $time_format = get_option('time_format', 'g:i a');
            $event_start_date_in_browser_timezone_formatted = $event_start_in_browser_tz_obj->format($time_format);
            $browser_time_zone_abbreviation = $event_start_in_browser_tz_obj->format('T');
        } catch (Exception $e) {
            // Error handling
        }
    }
}
```

# Error Handling System (Scenario and User Message)

1. No cookie set: "Please reload to see local times"
2. Invalid timezone string: "Could not convert time"
3. Missing event UTC data: "Event time not available"
4. DateTime conversion error: "Timezone conversion error"

#Requirements

1. WordPress 5.0+
2. The Events Calendar plugin (v4.6.19+)
3. PHP 7.4+ (with DateTime and DateTimeZone support)
4. JavaScript enabled in visitor's browser

# Customisation Options

1. Change Cookie Settings
Modify in Javascript section:
```javascript
document.cookie = "tribe_browser_time_zone=" + encodeURIComponent(userTimeZone) + 
                 "; path=/; SameSite=Lax" + 
                 "; max-age=2592000"; // 30 days
```
2. Adjust Time Display
Edit the PHP time formatting:
```php
$time_format = 'H:i'; // 24-hour format
// or use WordPress setting:
$time_format = get_option('time_format', 'g:i a');
```

#Troubleshooting
1. Times not converting:
 i. Verify cookies are enabled
 ii. Check browser console for JavaScript errors
 iii. Ensure WP_DEBUG is enabled to log server-side issues

2. Incorrect timezone detection:
 i. Test with different browsers
 ii. Verify the Intl API is available in the browser

3. Debugging:
```php
 if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Timezone Debug: ' . print_r([
        'cookie_value' => $_COOKIE['tribe_browser_time_zone'] ?? 'Not set',
        'event_utc' => $event_start_utc,
        'converted_time' => $event_start_date_in_browser_timezone_formatted ?? 'Not converted'
    ], true));
}
```
