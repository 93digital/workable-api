<?php
/**
 * Nine3 Workable API class.
 * Fetches a array of current vacancies from a Workable account.
 * Maps the vacancy description (via an individual request) to each object within the array.
 *
 * The API requests are done on an hourly basis via WP cron.
 * The parsed response data is then stored in a transient which is referenced when attempting
 * to fetch the current vacancies.
 *
 * @package WordPress
 * @author 93digital <info@93digital.co.uk>
 * @version 1.0
 * @see https://workable.readme.io/docs
 */

/**
 * Nine3 Workable Api Class.
 */
class Nine3_Workable_Api {
	/**
	 * Key to retrieve saved transient data
	 *
	 * @var string $_transient_key use this to retrieve the data.
	 */
	private $_transient_key = 'workable_vacancies';

	/**
	 * Remote URL for fetching all published vacancies.
	 * Will include the $_api_subdomain
	 *
	 * @var string
	 */
	private $_api_url;

	/**
	 * The API access token required to authenticate API requests.
	 *
	 * @var string
	 */
	private $_api_access_token;

	/**
	 * Constructor
	 * Declares required API variables (URL, access token).
	 * Registers hooks for the cron job.
	 *
	 * @param string $api_subdomain Subdomain for the Workable account, required for the API URL.
	 * @param string $api_access_token A generated API Access Token from the Workable account.
	 */
	public function __construct( $api_subdomain, $api_access_token ) {
    if ( empty( $api_subdomain ) || empty( $_api_access_token ) ) {
			// Do nothing if the subdomain or access token are empty.
			return;
		}

		$this->_api_url          = 'https://' . $api_subdomain . '.workable.com/spi/v3';
		$this->_api_access_token = $api_access_token;

		// Setup cron event.
		add_action( 'wp', array( $this, 'initiate_cron_job' ) );

		// Scheduled cron job action hook.
		add_action( 'workable_fetch_api_vacancies', array( $this, 'fetch_vacancies' ) );
	}

	/**
	 * 'wp' action hook callback.
	 * Schedules the initial cron event to fetch vacancies from an API.
	 */
	public function initiate_cron_job() {
		if ( ! wp_next_scheduled( 'workable_fetch_api_vacancies' ) ) {
			wp_schedule_event( time(), 'hourly', 'workable_fetch_api_vacancies' );
		}
	}

	/**
	 * Make an API request for all published vacancies.
	 * Each returned vacancy is mapped to a custom array to be used elsewhere in the theme.
	 *
	 * @param  bool $return Whether to return the results or not.
	 * @return array $vacancies List of custom mapped vacancies.
	 */
	public function fetch_vacancies( $return = false ) {
		// API request for a list of published vacancies.
		$response = $this->api_request( '/jobs?state=published' );
		if ( $response === false ) {
			// Erroneous API call or no data returned to bail out!
			return false;
		}

		// Loop through the response and map the available vacancies to an array.
		$vacancies = array();
		if ( array_key_exists( 'jobs', $response ) ) {
			foreach ( $response['jobs'] as $vacancy ) {
				// Another API request must be made to the single vacancy to retrieve it's description.
				$single_response = $this->api_request( '/jobs/' . $vacancy['shortcode'] );

				// Grab the description from the single vacancy request, or default to an empty string.
				if ( ! empty( $single_response ) && isset( $single_response['full_description'] ) ) {
					$vacancy['full_description'] = $single_response['full_description'];
				} else {
					$vacancy['full_description'] = '';
				}

				$vacancies[] = $vacancy;
			}
		}

		// Set transient.
		set_transient( $this->_transient_key, $vacancies );

		if ( $return ) {
			return $vacancies;
		}
	}

	/**
	 * Makes an API request to the Workable API with authentication.
	 * The JSON response is parsed into an array.
	 *
	 * @param string $api_endpoint The Workable API endpoint to query.
	 *
	 * @return array|bool $json_response Array on success, false on error.
	 */
	private function api_request( $api_endpoint ) {
		// Required Auth header. To be sent with the remote request.
		$auth_params = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->_api_access_token,
			),
		);
		$api_response = wp_remote_get( $this->_api_url . $api_endpoint, $auth_params );
		
		// Parse the JSON response.
		$response_body = wp_remote_retrieve_body( $api_response );
		$json_response = json_decode( $response_body, true );

		// Check rate limit header and throttle.
		$rate_remaining = wp_remote_retrieve_header( $api_response, 'X-Rate-Limit-Remaining' );
		$rate_reset     = wp_remote_retrieve_header( $api_response, 'X-Rate-Limit-Reset' );
		if ( isset( $rate_remaining ) && isset( $rate_reset ) && $rate_remaining === 0 ) {
			// Rate limit reached, sleep until the rate has reset and make a new request.
			time_sleep_until( intval( $rate_reset ) + 3 );
			$json_response = $this->api_request( $api_endpoint );
		}

		return $json_response;
	}

	/**
	 * Getter function for all vacancies
	 */
	public function get_vacancies() {
		// Retrieve our saved data.
		$vacancies = get_transient( $this->_transient_key );

		// If it's not there, rerun the api call.
		if ( empty( $vacancies ) ) {
			$vacancies = $this->fetch_vacancies( true );
		}

		return $vacancies;
	}
}
