<?php

/**
 * WP_AJAX
 *
 * A simple class for creating active
 * record, eloquent-esque models of WordPress Posts.
 *
 * @author     AnthonyBudd <anthonybudd94@gmail.com>
 */
Abstract Class WP_AJAX
{	
	protected $action;
	public $request;
	public $wp;
	public $user;

	
	abstract protected function run();

	public function __construct()
	{ 	
		global $wp;
		$this->wp = $wp;
		$this->request = $_REQUEST;

		if($this->isLoggedIn()){
			$this->user = wp_get_current_user();
		}
	}

	public static function boot()
	{ 	
		$class = Self::getClassName();
		$action = new $class;
		$action->run();
		die();
	}

	public static function listen($public = TRUE)
	{
		$actionName = Self::getActionName();
		$className = Self::getClassName();
		add_action("wp_ajax_{$actionName}", [$className, 'boot']);
		
		if($public){
			add_action("wp_ajax_nopriv_{$actionName}", [$className, 'boot']);
		}
	}


	// -----------------------------------------------------
	// UTILITY METHODS
	// -----------------------------------------------------
	public static function getClassName()
	{
		return get_called_class();
	}

	public static function getActionName()
	{
		$class = Self::getClassName();
		$reflection = new ReflectionClass($class);
		$action = $reflection->newInstanceWithoutConstructor();
		if(!isset($action->action)){
			throw new Exception("Public property \$action not provied");
		}

		return $action->action;
	}

	// -----------------------------------------------------
	// JSONResponse
	// -----------------------------------------------------
	public function JSONResponse($response)
	{
		wp_send_json($response);
	}

	// -----------------------------------------------------
	// Helpers
	// -----------------------------------------------------
	public static function ajaxURL()
	{
		?>
			<script type="text/javascript">
				var ajaxurl = '<?php echo admin_url('/admin-ajax.php'); ?>';
			</script>
		<?php
	}

	public static function WP_HeadAjaxURL()
	{
		add_action('wp_head', ['WP_AJAX', 'ajaxURL']);
	}

	public static function url()
	{
		return sprintf('%s?action=%s', admin_url('/admin-ajax.php'), ((new static() )->action));
	}

	public function isLoggedIn()
	{
		return is_user_logged_in();
	}

	public function has($key)
	{
		if(isset($this->request[$key])){
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * [get description]
	 * @param  string $key     [description]
	 * @param  string $default [description]
	 * @return strin
	 */
	public function get($key, $default = NULL)
	{
		if($this->has($key)){
			return $this->request[$key];
		}

		return $default;
	}

	/**
	 * @param string|array $type The type of request you want to check. If an array
	     *   this method will return true if the request matches any type.
	 * @return [type]              [description]
	 */
	public function requestType($requestType = NULL)
	{
		if(!is_null($requestType)){

			if(is_array($requestType)){
				return in_array($_SERVER['REQUEST_METHOD'], array_map('strtoupper', $requestType));
			}

			return ($_SERVER['REQUEST_METHOD'] === strtoupper($requestType));
		}

		return $_SERVER['REQUEST_METHOD'];
	}
}