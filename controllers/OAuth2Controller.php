<?php

class OAuth2Controller extends Controller
{
	public function actionInstall()
	{
		if(!$installed){

			//安装数据库
		}

		Yii::app()->end();
	}

	/**
	 * @var OAuth2\Server
	 */
	public $service;

	public function init()
	{
		parent::init();

		Yii::import($this->module->id . '.vendor.*');
		require_once('autoload.php');

		//config
		$db = Yii::app()->db;
		$dsn      = $db->connectionString;
		$username = $db->username;
		$password = $db->password;

		// $dsn is the Data Source Name for your database, for exmaple "mysql:dbname=my_oauth2_db;host=localhost"
		$storage = new OAuth2\Storage\Pdo(array('dsn' => $dsn, 'username' => $username, 'password' => $password));

		// Pass a storage object or array of storage objects to the OAuth2 server class
		$server = new OAuth2\Server($storage);

		// Add the "Client Credentials" grant type (it is the simplest of the grant types)
		$server->addGrantType(new OAuth2\GrantType\ClientCredentials($storage));

		// Add the "Authorization Code" grant type (this is where the oauth magic happens)
		$server->addGrantType(new OAuth2\GrantType\AuthorizationCode($storage));

		// add the grant type to your OAuth server
		$server->addGrantType(new OAuth2\GrantType\RefreshToken($storage));

		$this->service = $server;
	}

	/**
	 * Token
	 */
	public function actionToken(){

		$server = $this->service;
		$server->handleTokenRequest(OAuth2\Request::createFromGlobals())->send();
	}

	/**
	 * Authorize
	 */
	public function actionAuthorize(){
		// include our OAuth2 Server object
		$server = $this->service;

		$request = OAuth2\Request::createFromGlobals();
		$response = new OAuth2\Response();

		// validate the authorize request
		if (!$server->validateAuthorizeRequest($request, $response)) {
			$response->send();
			die;
		}

		// display an authorization form
		if (isset($_POST['authorized'])) {
			// print the authorization code if the user has authorized your client
			$is_authorized = ($_POST['authorized'] === 'yes');
			$server->handleAuthorizeRequest($request, $response, $is_authorized);
			if ($is_authorized) {
				// this is only here so that you get to see your code in the cURL request. Otherwise, we'd redirect back to the client
				$code = substr($response->getHttpHeader('Location'), strpos($response->getHttpHeader('Location'), 'code=')+5, 40);
				//exit("SUCCESS! Authorization Code: $code");
				$response->send();
			}
		}

		$this->render('authorize');
	}

	/**
	 * Resource
	 */
	public function actionResource(){
		// include our OAuth2 Server object
		$this->initService();
		$server = $this->service;

		// Handle a request for an OAuth2.0 Access Token and send the response to the client
		if (!$server->verifyResourceRequest(OAuth2\Request::createFromGlobals())) {
			$server->getResponse()->send();
			die;
		}

		echo CJson::encode(array(
			'success' => true,
			'message' => 'You accessed my APIs!'
		));
	}
}