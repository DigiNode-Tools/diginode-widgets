<?php
/**
 * Plugin Name:       DigiNode Widgets
 * Plugin URI:        https://github.com/DigiNode-Tools/diginode-widgets
 * Description:       Show the latest DigiNode Tools release on your site with the [diginode_latest_release] shortcode.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            Olly Stedall (saltedlolly)
 * Author URI:        https://diginode.tools
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// A small summary of the latest DigiNode Tools release, published at each release.
const DIGINODE_WIDGETS_RELEASE_JSON = 'https://raw.githubusercontent.com/DigiNode-Tools/diginode-tools/main/docs/latest-release.json';
const DIGINODE_WIDGETS_RELEASE_NOTES = 'https://diginode.tools/release-notes/';

/**
 * The latest release, from DigiNode Tools' latest-release.json. Checked at most once an hour. If it can't be
 * fetched, the last good copy is used (and checked again in 10 minutes). Returns null if there has never been one.
 */
function diginode_widgets_latest_release() {
	$cached = get_transient( 'diginode_widgets_latest_release' );
	if ( false !== $cached ) {
		return $cached ? $cached : null;
	}

	$data     = null;
	$response = wp_remote_get( DIGINODE_WIDGETS_RELEASE_JSON, array( 'timeout' => 5 ) );
	if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( is_array( $decoded ) && isset( $decoded['version'] ) && preg_match( '/^\d+\.\d+\.\d+$/', $decoded['version'] ) ) {
			$data = $decoded;
			update_option( 'diginode_widgets_latest_release_last_good', $data, false );
		}
	}

	if ( ! $data ) {
		$data = get_option( 'diginode_widgets_latest_release_last_good', null );
	}

	set_transient( 'diginode_widgets_latest_release', $data ? $data : array(), $data ? HOUR_IN_SECONDS : 10 * MINUTE_IN_SECONDS );
	return $data;
}

/**
 * Whether there are release notes waiting to be released (notes under "Unreleased" on develop). Checked at most
 * once an hour. Used so the banner only shows the next release date when there's something to release.
 */
function diginode_widgets_has_unreleased_notes( $d ) {
	$cached = get_transient( 'diginode_widgets_unreleased' );
	if ( false !== $cached ) {
		return 'yes' === $cached;
	}
	$pending = false;
	if ( ! empty( $d['unreleased_notes_url'] ) && 0 === strpos( $d['unreleased_notes_url'], 'https://' ) ) {
		$response = wp_remote_get( $d['unreleased_notes_url'], array( 'timeout' => 5 ) );
		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$on = false;
			foreach ( preg_split( '/\R/', wp_remote_retrieve_body( $response ) ) as $line ) {
				if ( 0 === strpos( $line, '### DigiNode Tools - Unreleased' ) ) {
					$on = true;
					continue;
				}
				if ( $on && 0 === strpos( $line, '### DigiNode Tools v' ) ) {
					break;
				}
				if ( $on && 0 === strpos( $line, '- ' ) ) {
					$pending = true;
					break;
				}
			}
		}
	}
	set_transient( 'diginode_widgets_unreleased', $pending ? 'yes' : 'no', HOUR_IN_SECONDS );
	return $pending;
}

/**
 * The next release Tuesday from today (the schedule's first_release_day, then every every_days days), as YYYY-MM-DD.
 */
function diginode_widgets_next_release_day( $d ) {
	$s = isset( $d['schedule'] ) && is_array( $d['schedule'] ) ? $d['schedule'] : array();
	if ( empty( $s['first_release_day'] ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $s['first_release_day'] ) || empty( $s['every_days'] ) ) {
		return '';
	}
	$first = strtotime( $s['first_release_day'] . ' 00:00:00 UTC' );
	$today = strtotime( gmdate( 'Y-m-d' ) . ' 00:00:00 UTC' );
	$every = (int) $s['every_days'] * DAY_IN_SECONDS;
	$next  = $first;
	if ( $today > $first ) {
		$next = $first + (int) ceil( ( $today - $first ) / $every ) * $every;
	}
	return gmdate( 'Y-m-d', $next );
}

/**
 * Format a YYYY-MM-DD date with the site's date format.
 */
function diginode_widgets_date( $ymd ) {
	if ( ! is_string( $ymd ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ymd ) ) {
		return '';
	}
	return date_i18n( get_option( 'date_format' ), strtotime( $ymd . ' 12:00:00' ) );
}

/**
 * [diginode_latest_release]
 *
 * Options:
 *   lines="2"            2 = version and date, then the summary (default). 1 = everything on one line.
 *   next="auto"          auto = show the next release date only when there are changes waiting to be released
 *                        (so it never announces a release when there's nothing to release). no = never show it.
 *   logo="auto"          auto = show the DigiNode logo on the left, except on diginode.tools. yes / no.
 *   label="Latest release"
 *   link="..."           Where "See what's new" goes (default: the DigiNode Tools release notes).
 */
function diginode_widgets_latest_release_shortcode( $atts ) {
	$a = shortcode_atts(
		array(
			'lines' => '2',
			'next'  => 'auto',
			'logo'  => 'auto',
			'label' => 'Latest release',
			'link'  => '',
		),
		$atts,
		'diginode_latest_release'
	);

	$d = diginode_widgets_latest_release();
	if ( empty( $d['version'] ) ) {
		return '';
	}

	$link    = $a['link'] ? $a['link'] : DIGINODE_WIDGETS_RELEASE_NOTES;
	$date    = diginode_widgets_date( isset( $d['date'] ) ? $d['date'] : '' );
	$summary = isset( $d['summary'] ) ? $d['summary'] : '';
	$next    = '';
	if ( 'no' !== strtolower( $a['next'] ) && diginode_widgets_has_unreleased_notes( $d ) ) {
		$next = diginode_widgets_date( diginode_widgets_next_release_day( $d ) );
	}
	$one  = ( '1' === (string) $a['lines'] );
	$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	$logo = 'yes' === strtolower( $a['logo'] )
		|| ( 'no' !== strtolower( $a['logo'] ) && ! preg_match( '/(^|\.)diginode\.tools$/', $host ) );

	$html  = '<div class="diginode-latest-release dlr-banner' . ( $one ? ' dlr-one-line' : '' ) . ( $logo ? ' dlr-with-logo' : '' ) . '">';
	if ( $logo ) {
		$html .= '<img class="dlr-logo" src="' . esc_url( plugins_url( 'assets/diginode-icon.png', __FILE__ ) ) . '" alt="DigiNode Tools" width="96" height="96">';
	}
	$html .= '<div class="dlr-text">';
	$html .= '<p class="dlr-top">';
	if ( '' !== $a['label'] ) {
		$html .= '<span class="dlr-label">' . esc_html( $a['label'] ) . '</span> ';
	}
	$html .= '<strong class="dlr-version">DigiNode Tools v' . esc_html( $d['version'] ) . '</strong>';
	if ( $date ) {
		$html .= ' <span class="dlr-date">&middot; ' . esc_html( $date ) . '</span>';
	}
	$html .= '</p>';
	$html .= '<p class="dlr-summary">';
	if ( $summary ) {
		$html .= esc_html( $summary ) . ' ';
	}
	$html .= '<a class="dlr-link" href="' . esc_url( $link ) . '">See what&rsquo;s new&nbsp;&rarr;</a>';
	if ( $next ) {
		$html .= ' <span class="dlr-next">Next release: ' . esc_html( $next ) . '</span>';
	}
	$html .= '</p></div></div>';

	return $html;
}
add_shortcode( 'diginode_latest_release', 'diginode_widgets_latest_release_shortcode' );

/**
 * Styles. They use the site's own font and text colour, so the banner fits light and dark themes. Sites can
 * change the accent colour with: .dlr-banner { --dlr-accent: #0066cc; }
 */
function diginode_widgets_styles() {
	$css = '
.dlr-banner{--dlr-accent:#0066cc;max-width:760px;margin:1em auto;padding:.7em 1em;border:1px solid rgba(0,102,204,.4);background:rgba(0,102,204,.08);border-color:color-mix(in srgb,var(--dlr-accent) 40%,transparent);border-radius:10px;background:color-mix(in srgb,var(--dlr-accent) 8%,transparent);text-align:center;line-height:1.5}
.dlr-banner p{margin:0}
.dlr-banner.dlr-with-logo{display:flex;align-items:center;gap:.8em;text-align:left}
.dlr-banner .dlr-logo{width:2.6em;height:2.6em;flex:none;margin:0}
.dlr-banner .dlr-label{display:inline-block;margin-right:.35em;padding:.1em .55em;border-radius:999px;background:var(--dlr-accent);color:#fff;font-size:.75em;font-weight:600;letter-spacing:.04em;text-transform:uppercase;vertical-align:.1em}
.dlr-banner .dlr-date,.dlr-banner .dlr-next{opacity:.75}
.dlr-banner .dlr-next{white-space:nowrap}
.dlr-banner .dlr-link{color:inherit;text-decoration:underline;text-underline-offset:2px;font-weight:600;white-space:nowrap}
.dlr-banner.dlr-one-line p{display:inline}
.dlr-banner.dlr-one-line .dlr-summary::before{content:" \2014  "}
';
	wp_register_style( 'diginode-widgets', false, array(), '1.0.0' );
	wp_enqueue_style( 'diginode-widgets' );
	wp_add_inline_style( 'diginode-widgets', $css );
}
add_action( 'wp_enqueue_scripts', 'diginode_widgets_styles' );
