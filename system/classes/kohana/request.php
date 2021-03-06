<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Request and response wrapper. Uses the [Route] class to determine what
 * [Controller] to send the request to.
 *
 * @package    Kohana
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2008-2011 Kohana Team
 * @license    http://kohanaframework.org/license
 */
class Kohana_Request implements Http_Request {

	/**
	 * @var  string  client user agent
	 */
	public static $user_agent = '';

	/**
	 * @var  string  client IP address
	 */
	public static $client_ip = '0.0.0.0';

	/**
	 * @var  Request  main request instance
	 */
	public static $initial;

	/**
	 * @var  Request  currently executing request instance
	 */
	public static $current;

	public static function factory($uri = TRUE, Cache $cache = NULL)
	{
		// If this is the initial request
		if ( ! Request::$initial)
		{
			if (Kohana::$is_cli)
			{
				// Default protocol for command line is cli://
				$protocol = 'cli';

				// Get the command line options
				$options = CLI::options('uri', 'method', 'get', 'post', 'referrer');

				if (isset($options['uri']))
				{
					// Use the specified URI
					$uri = $options['uri'];
				}

				if (isset($options['method']))
				{
					// Use the specified method
					$method = strtoupper($options['method']);
				}
				else
					$method = 'GET';

				if (isset($options['get']))
				{
					// Overload the global GET data
					parse_str($options['get'], $_GET);
				}

				if (isset($options['post']))
				{
					// Overload the global POST data
					parse_str($options['post'], $_POST);
				}

				if (isset($options['referrer']))
				{
					$referrer = $options['referrer'];
				}
				else
					$referrer = NULL;
			}
			else
			{
				if (isset($_SERVER['REQUEST_METHOD']))
				{
					// Use the server request method
					$method = $_SERVER['REQUEST_METHOD'];
				}
				else
				{
					// Default to GET
					$method = Http_Request::GET;
				}

				if ( ! empty($_SERVER['HTTPS']) AND filter_var($_SERVER['HTTPS'], FILTER_VALIDATE_BOOLEAN))
				{
					// This request is secure
					$protocol = 'https';
				}
				else
				{
					$protocol = 'http';
				}

				if (isset($_SERVER['HTTP_REFERER']))
				{
					// There is a referrer for this request
					$referrer = $_SERVER['HTTP_REFERER'];
				}
				else
				{
					$referrer = NULL;
				}

				if (isset($_SERVER['HTTP_USER_AGENT']))
				{
					// Set the client user agent
					Request::$user_agent = $_SERVER['HTTP_USER_AGENT'];
				}

				if (isset($_SERVER['HTTP_X_REQUESTED_WITH']))
				{
					$requested_with = $_SERVER['HTTP_X_REQUESTED_WITH'];
				}

				if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
				{
					// Use the forwarded IP address, typically set when the
					// client is using a proxy server.
					Request::$client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
				}
				elseif (isset($_SERVER['HTTP_CLIENT_IP']))
				{
					// Use the forwarded IP address, typically set when the
					// client is using a proxy server.
					Request::$client_ip = $_SERVER['HTTP_CLIENT_IP'];
				}
				elseif (isset($_SERVER['REMOTE_ADDR']))
				{
					// The remote IP address
					Request::$client_ip = $_SERVER['REMOTE_ADDR'];
				}

				if ($method !== 'GET')
				{
					// Ensure the raw body is saved for future use
					$body = file_get_contents('php://input');
				}

				if ($uri === TRUE)
				{
					$uri = Request::detect_uri();
				}
			}

			// Create the instance singleton
			$request = new Request($uri, $cache);
			$request->protocol($protocol)
				->method($method)
				->referrer($referrer);

			// Apply the requested with variable
			isset($requested_with) AND $request->requested_with($requested_with);

			// If there is a body, set it to the model
			isset($body) AND $request->body($body);
		}
		else
			$request = new Request($uri, $cache);

		// Create the initial request if it does not exist
		if ( ! Request::$initial)
		{
			Request::$initial = $request;

			$request->query($_GET)
				->post($_POST);
		}

		return $request;
	}

	/**
	 * Automatically detects the URI of the main request using PATH_INFO,
	 * REQUEST_URI, PHP_SELF or REDIRECT_URL.
	 *
	 *     $uri = Request::detect_uri();
	 *
	 * @return  string  URI of the main request
	 * @throws  Kohana_Exception
	 * @since   3.0.8
	 */
	public static function detect_uri()
	{
		if ( ! empty($_SERVER['PATH_INFO']))
		{
			// PATH_INFO does not contain the docroot or index
			$uri = $_SERVER['PATH_INFO'];
		}
		else
		{
			// REQUEST_URI and PHP_SELF include the docroot and index

			if (isset($_SERVER['REQUEST_URI']))
			{
				// REQUEST_URI includes the query string, remove it
				$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

				// Decode the request URI
				$uri = rawurldecode($uri);
			}
			elseif (isset($_SERVER['PHP_SELF']))
			{
				$uri = $_SERVER['PHP_SELF'];
			}
			elseif (isset($_SERVER['REDIRECT_URL']))
			{
				$uri = $_SERVER['REDIRECT_URL'];
			}
			else
			{
				// If you ever see this error, please report an issue at http://dev.kohanaphp.com/projects/kohana3/issues
				// along with any relevant information about your web server setup. Thanks!
				throw new Kohana_Exception('Unable to detect the URI using PATH_INFO, REQUEST_URI, PHP_SELF or REDIRECT_URL');
			}

			// Get the path from the base URL, including the index file
			$base_url = parse_url(Kohana::$base_url, PHP_URL_PATH);

			if (strpos($uri, $base_url) === 0)
			{
				// Remove the base URL from the URI
				$uri = (string) substr($uri, strlen($base_url));
			}

			if (Kohana::$index_file AND strpos($uri, Kohana::$index_file) === 0)
			{
				// Remove the index file from the URI
				$uri = (string) substr($uri, strlen(Kohana::$index_file));
			}
		}

		return $uri;
	}

	/**
	 * Return the currently executing request. This is changed to the current
	 * request when [Request::execute] is called and restored when the request
	 * is completed.
	 *
	 *     $request = Request::current();
	 *
	 * @return  Request
	 * @since   3.0.5
	 */
	public static function current()
	{
		return Request::$current;
	}

	/**
	 * Returns the first request encountered by this framework. This will should
	 * only be set once during the first [Request::factory] invocation.
	 *
	 *     // Get the first request
	 *     $request = Request::initial();
	 *
	 *     // Test whether the current request is the first request
	 *     if (Request::initial() === Request::current())
	 *          // Do something useful
	 *
	 * @return  Request
	 * @since   3.1.0
	 */
	public static function initial()
	{
		return Request::$initial;
	}

	/**
	 * Returns information about the client user agent.
	 *
	 *     // Returns "Chrome" when using Google Chrome
	 *     $browser = Request::user_agent('browser');
	 *
	 * Multiple values can be returned at once by using an array:
	 *
	 *     // Get the browser and platform with a single call
	 *     $info = Request::user_agent(array('browser', 'platform'));
	 *
	 * When using an array for the value, an associative array will be returned.
	 *
	 * @param   mixed   $value String to return: browser, version, robot, mobile, platform; or array of values
	 * @return  mixed   requested information, FALSE if nothing is found
	 * @uses    Kohana::config
	 * @uses    Request::$user_agent
	 */
	public static function user_agent($value)
	{
		if (is_array($value))
		{
			$agent = array();
			foreach ($value as $v)
			{
				// Add each key to the set
				$agent[$v] = Request::user_agent($v);
			}

			return $agent;
		}

		static $info;

		if (isset($info[$value]))
		{
			// This value has already been found
			return $info[$value];
		}

		if ($value === 'browser' OR $value == 'version')
		{
			// Load browsers
			$browsers = Kohana::config('user_agents')->browser;

			foreach ($browsers as $search => $name)
			{
				if (stripos(Request::$user_agent, $search) !== FALSE)
				{
					// Set the browser name
					$info['browser'] = $name;

					if (preg_match('#'.preg_quote($search).'[^0-9.]*+([0-9.][0-9.a-z]*)#i', Request::$user_agent, $matches))
					{
						// Set the version number
						$info['version'] = $matches[1];
					}
					else
					{
						// No version number found
						$info['version'] = FALSE;
					}

					return $info[$value];
				}
			}
		}
		else
		{
			// Load the search group for this type
			$group = Kohana::config('user_agents')->$value;

			foreach ($group as $search => $name)
			{
				if (stripos(Request::$user_agent, $search) !== FALSE)
				{
					// Set the value name
					return $info[$value] = $name;
				}
			}
		}

		// The value requested could not be found
		return $info[$value] = FALSE;
	}

	/**
	 * Returns the accepted content types. If a specific type is defined,
	 * the quality of that type will be returned.
	 *
	 *     $types = Request::accept_type();
	 *
	 * @param   string  $type Content MIME type
	 * @return  mixed   An array of all types or a specific type as a string
	 * @uses    Request::_parse_accept
	 */
	public static function accept_type($type = NULL)
	{
		static $accepts;

		if ($accepts === NULL)
		{
			// Parse the HTTP_ACCEPT header
			$accepts = Request::_parse_accept($_SERVER['HTTP_ACCEPT'], array('*/*' => 1.0));
		}

		if (isset($type))
		{
			// Return the quality setting for this type
			return isset($accepts[$type]) ? $accepts[$type] : $accepts['*/*'];
		}

		return $accepts;
	}

	/**
	 * Returns the accepted languages. If a specific language is defined,
	 * the quality of that language will be returned. If the language is not
	 * accepted, FALSE will be returned.
	 *
	 *     $langs = Request::accept_lang();
	 *
	 * @param   string  $lang  Language code
	 * @return  mixed   An array of all types or a specific type as a string
	 * @uses    Request::_parse_accept
	 */
	public static function accept_lang($lang = NULL)
	{
		static $accepts;

		if ($accepts === NULL)
		{
			// Parse the HTTP_ACCEPT_LANGUAGE header
			$accepts = Request::_parse_accept($_SERVER['HTTP_ACCEPT_LANGUAGE']);
		}

		if (isset($lang))
		{
			// Return the quality setting for this lang
			return isset($accepts[$lang]) ? $accepts[$lang] : FALSE;
		}

		return $accepts;
	}

	/**
	 * Returns the accepted encodings. If a specific encoding is defined,
	 * the quality of that encoding will be returned. If the encoding is not
	 * accepted, FALSE will be returned.
	 *
	 *     $encodings = Request::accept_encoding();
	 *
	 * @param   string  $type Encoding type
	 * @return  mixed   An array of all types or a specific type as a string
	 * @uses    Request::_parse_accept
	 */
	public static function accept_encoding($type = NULL)
	{
		static $accepts;

		if ($accepts === NULL)
		{
			// Parse the HTTP_ACCEPT_LANGUAGE header
			$accepts = Request::_parse_accept($_SERVER['HTTP_ACCEPT_ENCODING']);
		}

		if (isset($type))
		{
			// Return the quality setting for this type
			return isset($accepts[$type]) ? $accepts[$type] : FALSE;
		}

		return $accepts;
	}

	/**
	 * Determines if a file larger than the post_max_size has been uploaded. PHP
	 * does not handle this situation gracefully on its own, so this method
	 * helps to solve that problem.
	 *
	 * @return  boolean
	 * @uses    Num::bytes
	 * @uses    Arr::get
	 */
	public static function post_max_size_exceeded()
	{
		// Make sure the request method is POST
		if (Request::$method !== 'POST')
			return FALSE;

			// Get the post_max_size in bytes		
		$max_bytes = Num::bytes(ini_get('post_max_size'));

		// Error occurred if method is POST, and content length is too long
		return (Arr::get($_SERVER, 'CONTENT_LENGTH') > $max_bytes);
	}

	/**
	 * Process URI
	 *
	 * @param   string  $uri     URI
	 * @param   array   $routes  Route
	 * @return  array
	 */
	public static function process_uri($uri, $routes = NULL)
	{
		// Load routes
		$routes = ($routes === NULL) ? Route::all() : $routes;
		$params = NULL;

		foreach ($routes as $name => $route)
		{
			// We found something suitable
			if ($params = $route->matches($uri))
			{
				if ( ! isset($params['uri']))
				{
					$params['uri'] = $uri;
				}

				if ( ! isset($params['route']))
				{
					$params['route'] = $route;
				}

				break;
			}
		}

		return $params;
	}

	/**
	 * Parses an accept header and returns an array (type => quality) of the
	 * accepted types, ordered by quality.
	 *
	 *     $accept = Request::_parse_accept($header, $defaults);
	 *
	 * @param   string   $header   Header to parse
	 * @param   array    $accepts  Default values
	 * @return  array
	 */
	protected static function _parse_accept( & $header, array $accepts = NULL)
	{
		if ( ! empty($header))
		{
			// Get all of the types
			$types = explode(',', $header);

			foreach ($types as $type)
			{
				// Split the type into parts
				$parts = explode(';', $type);

				// Make the type only the MIME
				$type = trim(array_shift($parts));

				// Default quality is 1.0
				$quality = 1.0;

				foreach ($parts as $part)
				{
					// Prevent undefined $value notice below
					if (strpos($part, '=') === FALSE)
						continue;

					// Separate the key and value
					list ($key, $value) = explode('=', trim($part));

					if ($key === 'q')
					{
						// There is a quality for this type
						$quality = (float) trim($value);
					}
				}

				// Add the accept type and quality
				$accepts[$type] = $quality;
			}
		}

		// Make sure that accepts is an array
		$accepts = (array) $accepts;

		// Order by quality
		arsort($accepts);

		return $accepts;
	}

	/**
	 * @var  string  the x-requested-with header which most likely
	 *               will be xmlhttprequest
	 */
	protected $_requested_with;

	/**
	 * @var  string  method: GET, POST, PUT, DELETE, HEAD, etc
	 */
	protected $_method = 'GET';

	/**
	 * @var  string  protocol: HTTP/1.1, FTP, CLI, etc
	 */
	protected $_protocol;

	/**
	 * @var  string  referring URL
	 */
	protected $_referrer;

	/**
	 * @var  Route       route matched for this request
	 */
	protected $_route;

	/**
	 * @var  Kohana_Response  response
	 */
	protected $_response;

	/**
	 * @var  Kohana_Http_Header  headers to sent as part of the request
	 */
	protected $_header;

	/**
	 * @var  string the body
	 */
	protected $_body;

	/**
	 * @var  string  controller directory
	 */
	protected $_directory = '';

	/**
	 * @var  string  controller to be executed
	 */
	protected $_controller;

	/**
	 * @var  string  action to be executed in the controller
	 */
	protected $_action;

	/**
	 * @var  string  the URI of the request
	 */
	protected $_uri;

	/**
	 * @var  boolean  external request
	 */
	protected $_external = FALSE;

	/**
	 * @var  array   parameters from the route
	 */
	protected $_params;

	/**
	 * @var array    query parameters
	 */
	protected $_get;

	/**
	 * @var array    post parameters
	 */
	protected $_post;

	/**
	 * @var array    cookies to send with the request
	 */
	protected $_cookies = array();

	/**
	 * @var Kohana_Request_Client
	 */
	protected $_client;

	/**
	 * Creates a new request object for the given URI. New requests should be
	 * created using the [Request::instance] or [Request::factory] methods.
	 *
	 *     $request = new Request($uri);
	 *
	 * If $cache parameter is set, the response for the request will attempt to
	 * be retrieved from the cache.
	 *
	 * @param   string  $uri URI of the request
	 * @param   Cache   $cache
	 * @return  void
	 * @throws  Kohana_Request_Exception
	 * @uses    Route::all
	 * @uses    Route::matches
	 */
	public function __construct($uri, Cache $cache = NULL)
	{
		// Initialise the header
		$this->_header = new Http_Header(array());

		// Remove trailing slashes from the URI
		$uri = trim($uri, '/');

		// Detect protocol (if present)
		/**
		 * @todo   make this smarter, search for localhost etc
		 */
		if (strpos($uri, '://') === FALSE)
		{
			$params = Request::process_uri($uri);
			if ($params)
			{
				// Store the URI
				$this->_uri = $params['uri'];

				// Store the matching route
				$this->_route = $params['route'];

				// Is this route external
				$this->_external = $this->_route->is_external();

				if (isset($params['directory']))
				{
					// Controllers are in a sub-directory
					$this->_directory = $params['directory'];
				}

				// Store the controller
				$this->_controller = $params['controller'];

				if (isset($params['action']))
				{
					// Store the action
					$this->_action = $params['action'];
				}
				else
				{
					// Use the default action
					$this->_action = Route::$default_action;
				}

				// These are accessible as public vars and can be overloaded
				unset($params['controller'], $params['action'], $params['directory'], $params['uri'], $params['route']);

				// Params cannot be changed once matched
				$this->_params = $params;

				// Apply the client
				$this->_client = new Request_Client_Internal(array('cache' => $cache));

				return;
			}

			throw new Http_Exception_404('Unable to find a route to match the URI: :uri',
				array(':uri' => $uri));
		}
		else
		{
			// Store the URI
			$this->_uri = $uri;

			// Set external state
			$this->_external = TRUE;

			// Setup the client
			$this->_client = new Request_Client_External(array('cache' => $cache));
		}
	}

	/**
	 * Returns the response as the string representation of a request.
	 *
	 *     echo $request;
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return $this->render();
	}

	/**
	 * Generates a relative URI for the current route.
	 *
	 *     $request->uri($params);
	 *
	 * @param   array   $params  Additional route parameters
	 * @return  string
	 * @uses    Route::uri
	 */
	public function uri(array $params = NULL)
	{
		if ( ! isset($params['directory']))
		{
			// Add the current directory
			$params['directory'] = $this->_directory;
		}

		if ( ! isset($params['controller']))
		{
			// Add the current controller
			$params['controller'] = $this->_controller;
		}

		if ( ! isset($params['action']))
		{
			// Add the current action
			$params['action'] = $this->_action;
		}

		// Add the current parameters
		$params += $this->_params;

		return $this->_route->uri($params);
	}

	/**
	 * Create a URL from the current request. This is a shortcut for:
	 *
	 *     echo URL::site($this->request->uri($params), $protocol);
	 *
	 * @param   array    $params    URI parameters
	 * @param   mixed    $protocol  protocol string or Request object
	 * @return  string
	 * @since   3.0.7
	 * @uses    URL::site
	 */
	public function url(array $params = NULL, $protocol = NULL)
	{
		// Create a URI with the current route and convert it to a URL
		return URL::site($this->uri($params), $protocol);
	}

	/**
	 * Retrieves a value from the route parameters.
	 *
	 *     $id = $request->param('id');
	 *
	 * @param   string   $key      Key of the value
	 * @param   mixed    $default  Default value if the key is not set
	 * @return  mixed
	 */
	public function param($key = NULL, $default = NULL)
	{
		if ($key === NULL)
		{
			// Return the full array
			return $this->_params;
		}

		return Arr::get($this->_params, $key, $default);
	}

	/**
	 * Redirects as the request response. If the URL does not include a
	 * protocol, it will be converted into a complete URL.
	 *
	 *     $request->redirect($url);
	 *
	 * [!!] No further processing can be done after this method is called!
	 *
	 * @param   string   $url   Redirect location
	 * @param   integer  $code  Status code: 301, 302, etc
	 * @return  void
	 * @uses    URL::site
	 * @uses    Request::send_headers
	 */
	public function redirect($url = '', $code = 302)
	{
		if (strpos($url, '://') === FALSE)
		{
			// Make the URI into a URL
			$url = URL::site($url, TRUE);
		}

		// Redirect
		$response = $this->create_response();

		// Set the response status
		$response->status($code);

		// Set the location header
		$response->headers('Location', $url);

		// Send headers
		$response->send_headers();

		// Stop execution
		exit;
	}

	/**
	 * Sets and gets the referrer from the request.
	 *
	 * @param   string $referrer
	 * @return  mixed
	 */
	public function referrer($referrer = NULL)
	{
		if ( ! $referrer)
			return $this->_referrer;

		$this->_referrer = (string) $referrer;
		return $this;
	}

	/**
	 * Sets and gets the route from the request.
	 *
	 * @param   string $route
	 * @return  mixed
	 */
	public function route(Route $route = NULL)
	{
		if ( ! $route)
			return $this->_route;

		$this->_route = $route;
		return $this;
	}

	/**
	 * Sets and gets the directory for the controller.
	 *
	 * @param   string   $directory  Directory to execute the controller from
	 * @return  mixed
	 */
	public function directory($directory = NULL)
	{
		if ( ! $directory)
			return $this->_directory;

		$this->_directory = (string) $directory;
		return $this;
	}

	/**
	 * Sets and gets the controller for the matched route.
	 *
	 * @param   string   $controller  Controller to execute the action
	 * @return  mixed
	 */
	public function controller($controller = NULL)
	{
		if ( ! $controller)
			return $this->_controller;

		$this->_controller = (string) $controller;
		return $this;
	}

	/**
	 * Sets and gets the action for the controller.
	 *
	 * @param   string   $action  Action to execute the controller from
	 * @return  mixed
	 */
	public function action($action = NULL)
	{
		if ( ! $action)
			return $this->_action;

		$this->_action = (string) $action;
		return $this;
	}

	/**
	 * Provides readonly access to the [Request_Client],
	 * useful for accessing the caching methods within the
	 * request client.
	 *
	 * @return  Request_Client
	 */
	public function get_client()
	{
		return $this->_client;
	}

	/**
	 * Gets and sets the requested with property, which should
	 * be relative to the x-requested-with pseudo header.
	 *
	 * @param   string    $requested_with Requested with value
	 * @return  mixed
	 */
	public function requested_with($requested_with = NULL)
	{
		if ($requested_with === NULL)
			return $this->_requested_with;

		$this->_requested_with = strtolower($requested_with);
		return $this;
	}

	/**
	 * Processes the request, executing the controller action that handles this
	 * request, determined by the [Route].
	 *
	 * 1. Before the controller action is called, the [Controller::before] method
	 * will be called.
	 * 2. Next the controller action will be called.
	 * 3. After the controller action is called, the [Controller::after] method
	 * will be called.
	 *
	 * By default, the output from the controller is captured and returned, and
	 * no headers are sent.
	 *
	 *     $request->execute();
	 *
	 * @return  Response
	 * @throws  Kohana_Exception
	 * @uses    [Kohana::$profiling]
	 * @uses    [Profiler]
	 */
	public function execute()
	{
		if ( ! $this->_client instanceof Kohana_Request_Client)
			throw new Kohana_Request_Exception('Unable to execute :uri without a Kohana_Request_Client', array(':uri', $this->_uri));

		return $this->_client->execute($this);
	}

	/**
	 * Returns whether this request is the initial request Kohana received.
	 * Can be used to test for sub requests.
	 *
	 *     if ( ! $request->is_initial())
	 *         // This is a sub request
	 *
	 * @return  boolean
	 */
	public function is_initial()
	{
		return ($this === Request::$initial);
	}

	/**
	 * Returns whether this is an ajax request (as used by JS frameworks)
	 *
	 * @return  boolean
	 */
	public function is_ajax()
	{
		return ($this->requested_with() === 'xmlhttprequest');
	}

	/**
	 * Generates an [ETag](http://en.wikipedia.org/wiki/HTTP_ETag) from the
	 * request response.
	 *
	 *     $etag = $request->generate_etag();
	 *
	 * [!!] If the request response is empty when this method is called, an
	 * exception will be thrown!
	 *
	 * @return string
	 * @throws Kohana_Request_Exception
	 */
	public function generate_etag()
	{
	    if ($this->_response === NULL)
		{
			throw new Kohana_Request_Exception('No response yet associated with request - cannot auto generate resource ETag');
		}

		// Generate a unique hash for the response
		return '"'.sha1($this->_response).'"';
	}

	/**
	 * Set or get the response for this request
	 *
	 * @param   Response  $response  Response to apply to this request
	 * @return  Response
	 * @return  void
	 */
	public function response(Response $response = NULL)
	{
		if ( ! $response)
			return $this->_response;

		$this->_response = $response;
		return $this;
	}

	/**
	 * Creates a response based on the type of request, i.e. an
	 * Request_Http will produce a Response_Http, and the same applies
	 * to CLI.
	 *
	 *      // Create a response to the request
	 *      $response = $request->create_response();
	 *
	 * @param   boolean  $bind  Bind to this request
	 * @return  Response
	 * @since   3.1.0
	 */
	public function create_response($bind = TRUE)
	{
		$response = new Response(array('_protocol' => $this->protocol()));

		if ( ! $bind)
			return $response;
		else
			return $this->_response = $response;
	}

	/**
	 * Gets or sets the Http method. Usually GET, POST, PUT or DELETE in
	 * traditional CRUD applications.
	 *
	 * @param   string   $method  Method to use for this request
	 * @return  mixed
	 */
	public function method($method = NULL)
	{
		if ($method === NULL)
			return $this->_method;

		$this->_method = strtoupper($method);
		return $this;
	}

	/**
	 * Gets or sets the HTTP protocol. The standard protocol to use
	 * is `http`.
	 *
	 * @param   string   $protocol  Protocol to set to the request/response
	 * @return  mixed
	 */
	public function protocol($protocol = NULL)
	{
		if ($protocol === NULL)
		{
			if ($this->_protocol === NULL)
			{
				$this->_protocol = Http::$protocol;
			}

			return $this->_protocol;
		}

		$this->_protocol = strtolower($protocol);
		return $this;
	}

	/**
	 * Gets or sets HTTP headers to the request or response. All headers
	 * are included immediately after the HTTP protocol definition during
	 * transmission. This method provides a simple array or key/value
	 * interface to the headers.
	 *
	 * @param   mixed   $key   Key or array of key/value pairs to set
	 * @param   string  $value Value to set to the supplied key
	 * @return  mixed
	 */
	public function headers($key = NULL, $value = NULL)
	{
		if ($key instanceof Http_Header)
		{
			$this->_header = $key;
			return $this;
		}
		elseif (is_array($key))
		{
			$this->_header->exchangeArray($key);
			return $this;
		}

		// We need to check for initial request (lazy load)
		if ($this === Request::$initial and $this->_header->count() < 1)
		{
			$this->_header = Http::request_headers();
		}

		if ($key === NULL)
		{
			return $this->_header;
		}
		elseif ($value === NULL)
		{
			return ($this->_header->offsetExists($key)) ? $this->_header->offsetGet($key) : NULL;
		}
		else
		{
			$this->_header[$key] = $value;
			return $this;
		}
	}

	/**
	 * Set and get cookies values for this request.
	 *
	 * @param   mixed    $key    Cookie name, or array of cookie values
	 * @param   string   $value  Value to set to cookie
	 * @return  string
	 * @return  [Request]
	 */
	public function cookie($key = NULL, $value = NULL)
	{
		if ($key === NULL)
			return $this->_cookies;

		if (is_array($key))
		{
			$this->_cookies = $key;
		}
		elseif ( ! $value)
		{
			return Arr::get($this->_cookies, $key);
		}
		else
		{
			$this->_cookies[$key] = (string) $value;
		}

		return $this;
	}

	/**
	 * Gets or sets the HTTP body to the request or response. The body is
	 * included after the header, separated by a single empty new line.
	 *
	 * @param   string  $content Content to set to the object
	 * @return  mixed
	 */
	public function body($content = NULL)
	{
		if ($content === NULL)
			return $this->_body;

		$this->_body = $content;
		return $this;
	}

	/**
	 * Renders the Http_Interaction to a string, producing
	 *
	 *  - Protocol
	 *  - Headers
	 *  - Body
	 *
	 *  If there are variables set to the `Kohana_Request::$_post`
	 *  they will override any values set to body.
	 *
	 * @param   boolean  $response  Return the rendered response, else returns the rendered request
	 * @return  string
	 */
	public function render($response = TRUE)
	{
		if ($response)
			return (string) $this->_response;

		if ( ! $this->_post)
		{
			$body = $this->_body;
		}
		else
		{
			$this->_header['content-type'] = 'application/x-www-form-urlencoded';
			$body = Http::www_form_urlencode($this->_post);
		}

		if ( ! $this->_get)
		{
			$query_string = '';
		}
		else
		{
			$query_string = '?'.Http::www_form_urlencode($this->query());
		}

		// Prepare cookies
		if ($this->_cookies)
		{
			$cookie_string = array();

			// Parse each
			foreach ($this->_cookies as $key => $value)
			{
				$cookie_string[] = $key.'='.$value;
			}

			// Create the cookie string
			$this->_header['cookie'] = implode('; ', $cookie_string);
		}

		$output = $this->_method.' '.$this->uri($this->param()).$query_string.' '.$this->protocol()."\n";
		$output .= (string) $this->_header;
		$output .= $body;

		return $output;
	}

	/**
	 * Gets or sets HTTP query string.
	 *
	 * @param   mixed   $key    Key or key value pairs to set
	 * @param   string  $value  Value to set to a key
	 * @return  mixed
	 */
	public function query($key = NULL, $value = NULL)
	{
		if ($key === NULL)
			return $this->_get;
		elseif ($value === NULL)
		{
			if (is_array($key))
			{
				$this->_get = $key;
				return $this;
			}

			return Arr::get($this->_get, $key);
		}
		else
		{
			$this->_get[$key] = $value;
			return $this;
		}
	}

	/**
	 * Gets or sets HTTP POST parameters to the request.
	 *
	 * @param   mixed  $key    Key or key value pairs to set
	 * @param   string $value  Value to set to a key
	 * @return  mixed
	 */
	public function post($key = NULL, $value = NULL)
	{
		if ($key === NULL)
			return $this->_post;
		elseif ($value === NULL)
		{
			if (is_array($key))
			{
				$this->_post = $key;
				return $this;
			}

			return Arr::get($this->_post, $key);
		}
		else
		{
			$this->_post[$key] = $value;
			return $this;
		}
	}
} // End Request
