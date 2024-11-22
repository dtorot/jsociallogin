<?php
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;

use Joomla\CMS\Authentication\Authentication;


class PlgAuthenticationSociallogin extends CMSPlugin //implements SubscriberInterface
{
    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);

        // Initialize the logger only once
        Log::addLogger(
            array(
                'text_file' => 'sociallogin.log.php',  // Name of the log file
                'text_file_path' => JPATH_ROOT . '/logs', // Directory for logs (usually Joomla's /logs)
                'text_entry_format' => '{DATE} {TIME} {CLIENTIP} {CATEGORY} {MESSAGE}'
            ),
            Log::ALL,  // Log all types of messages
            array('sociallogin')  // Custom category to identify your plugin’s messages
        );     
    }

    public function onAfterInitialise()
    {
        Log::add('onAfterInitialise...', Log::DEBUG, 'sociallogin');
    }

    public function onAfterRoute()
    {
        Log::add('onAfterRoute...', Log::DEBUG, 'sociallogin');
        // Check if the current request is the OAuth callback
        /*
        $app = Factory::getApplication();
        $input = $app->input;
        $uri = Uri::getInstance();*/
        
        //$path = $uri->getPath();

        /*
        if ($uri->getPath() === '/profile') {
            // Call your custom function here
            Log::add('onAfterRoute get /profile...', Log::DEBUG, 'sociallogin');
            // Optionally, stop further execution
            $app->close();
        }*/

        // Ensure we are handling the Google OAuth callback
        /*if (strpos($path,'/profile') !== false )
        {
            //$this->handleGoogleCallback();
            Log::add('onAfterRoute, /profile? call from google OAuth...', Log::DEBUG, 'sociallogin');
        }*/
        // Check if this is the Google OAuth callback
        /*if ($input->get('task') === 'googleauth.callback') {
            Log::add('onAfterRoute, /profile? call from google OAuth...', Log::DEBUG, 'sociallogin');
        }*/
    }

/*     public function onUserAfterLogin($options, $result)
    {
        $app = \Joomla\CMS\Factory::getApplication();

        // Redirect based on user role
        if ($result['status'] === true) {
            $user = $options['user'];
            if (in_array('Administrator', $user->groups)) {
                $app->redirect('administrator/index.php');
            } else {
                $app->redirect('index.php?option=com_users&view=profile');
            }
        }
    } */
    
    //empty $options parameter must be used by joomla onUserAuthenticate declaration
    public function onUserAuthenticate($credentials, $options, &$response)
    {   
        Log::add('onUserAuthenticate', Log::DEBUG, 'sociallogin');
        //Reviewing Session...
        $app = JFactory::getApplication();
        $app->getSession();
        Log::add('Testing Session object: ' . (Factory::getSession() ? 'Initialized' : 'Null'), Log::DEBUG, 'sociallogin');        

        /* $input = Factory::getApplication()->input;
        $customParam = $input->get('myParam', '', 'string');
        Log::add('Session state param: [' . $customParam . ']', Log::DEBUG, 'sociallogin'); */

        // First Step: Always get the actual session CSRF token
        $csrfTokenName = Session::getFormToken();
        $csrfTokenValue = '1';
        // save the CSRF values in the $state array to use with the Google OAuth Service
        $state = http_build_query([
            'csrfTokenName' => $csrfTokenName,
            'csrfTokenValue' => $csrfTokenValue,
        ]);
        
        // Retrieve the CSRF token value from the POST request
        //$input = Factory::getApplication()->input;
        //$jtoken = $input->get($csrfTokenName, '', 'string');
        
        //Log::add('onUserAuthenticate, JTokenName[' . $csrfTokenName . ']', Log::DEBUG, 'sociallogin');
        //Log::add('onUserAuthenticate, password [' . $credentials['password'] . ']', Log::DEBUG, 'sociallogin');
        
        // Validate Joomla security token
        /*
        if (!Session::checkToken('post')) { // Using 'get' because the token is in the URL
            $response->status = JAuthentication::STATUS_FAILURE;
            //$response->error_message = 'Invalid security token';
            Log::add('onUserAuthenticate, Invalid security token', Log::DEBUG, 'sociallogin');
            return;
        }*/
        //if ($_SESSION['oauth_token'] === $credentials['token']) { // Using 'get' because the token is in the URL
        
        // Called after the Google OAuth authentication from SocialLoginCallBack plugin
        if ($credentials['password']) { 
            if (!Session::checkToken('post')) 
                Log::add('onUserAuthenticate, NO token in POST method...', Log::DEBUG, 'sociallogin');
            if (!Session::checkToken('get')) 
                Log::add('onUserAuthenticate, NO token in GET method...', Log::DEBUG, 'sociallogin');
            //$jtoken=$credentials['csrfTokenName'].'='.$credentials['csrfTokenValue'];
            $jtoken=$credentials['csrfTokenName'];
            Log::add('onUserAuthenticate, returned jtoken to start session ['. $jtoken . ']', Log::DEBUG, 'sociallogin');
            $localcsrfTokenName = Session::getFormToken();
            Log::add('onUserAuthenticate, local jtoken to start session ['. $localcsrfTokenName . ']', Log::DEBUG, 'sociallogin');
            //if (!Session::checkToken($jtoken)) 
            //if (!$app->checkToken($jtoken)) 
            //    Log::add('onUserAuthenticate, jtoken passthrough does not work...', Log::DEBUG, 'sociallogin');


            
            //$response->error_message = 'Invalid security token';
            $response->username = $user->username;
            $response->email = $user->email;
            $response->fullname = $user->name;
            //$response->message = 'Sesión iniciada...';   
            
/*             $auth = new Authentication;        
            $auth->authenticate($credentials, $options); */

/*            if ($response->status === Authentication::STATUS_SUCCESS) {
                // Create user session
                $user = Factory::getUser($response->username);
                $app->setUser($user);
        
                // Prepare options and result for the event
                $options = ['remember' => $options['remember'] ?? false];
                $result = [
                    'status'  => true,
                    'message' => 'Login successful',
                    'user'    => $user
                ];
        
                // Dispatch the onUserAfterLogin event
                //$dispatcher = $app->getDispatcher();
                //$dispatcher->dispatch('onUserAfterLogin', [$options, $result]);
                Log::add('onUserAuthenticate, User logged in successfully...', Log::DEBUG, 'sociallogin');

                //return true;
            }*/



            $response->status = JAuthentication::STATUS_SUCCESS;
            
            Log::add('onUserAuthenticate, User logged in successfully...['. $response->status . ']', Log::DEBUG, 'sociallogin');
            //$app->enqueueMessage('Welcome back, ' . $response->username, 'success');


            /* $credentials = ['started' => true, 'username' => $response->username]; 
            $result = $app->login($credentials, ['silent' => false, 'return' => '/bitacora']);  */

            //$app->enqueueMessage('Welcome back...', 'success');
            /* $currentUri = Uri::getInstance();
            $cleanUrl = $currentUri->toString(['scheme', 'host', 'path']);
            Log::add('onUserAuthenticate, Clean Url ['. $cleanUrl . ']', Log::DEBUG, 'sociallogin'); */

            //$cleanUrl = "/bitacora";
            //$app->redirect(Route::_($cleanUrl, false));

           

            // URL of the user profile or desired page
            //$userProfileUrl = Route::_('/bitacora');

            // Redirect to the user profile
            //$app->redirect($userProfileUrl);
            //$app->redirect('/bitacora');

/*             $options = ['remember' => $options['remember'] ?? false];
            $result = [
                    'status'  => true,
                    'message' => 'Login successful',
                    'user'    => $user
                ];
            $dispatcher = $app->getDispatcher();
            $dispatcher->dispatch('onUserAfterLogin'); */


            // Trigger the `onUserAfterLogin` event
            //$dispatcher = Dispatcher::getInstance();
            /* $eventData = [
                'response' => $response,
                'user' => $user,
                'options' => $options,
            ]; */

            //$dispatcher->dispatch('onUserAfterLogin', $eventData);
            
            //$dispatcher = Joomla\CMS\Factory::getApplication()->getDispatcher();
            //$event = new Joomla\CMS\Event\User\AfterLoginEvent($evenData);
            //$dispatcher->dispatch('onUserAfterLogin', $eventData);


            return true;
        }

/*         if ($credentials['started'] == true) {
            $response->status = JAuthentication::STATUS_SUCCESS;
            //$response->error_message = 'Invalid security token';
            $response->username = $user->username;
            $response->email = $user->email;
            $response->fullname = $user->name;
            //$response->message = 'Sesión iniciada...';           
            
            Log::add('onUserAuthenticate, User logged in successfully...', Log::DEBUG, 'sociallogin');
            return;
        }  */

        
        // Detect the login provider (step 0 from the Joomla login module form)
        //if ($credentials['provider'] === 'google') {
        if (isset($_POST['provider']) && $_POST['provider'] === 'google') {
            Log::add('onUserAuthenticate, Google OAuth provider detected...', Log::DEBUG, 'sociallogin');
            $this->authenticateWithGoogle($credentials, $state, $response);            
        } elseif ($credentials['provider'] === 'facebook') {
            $this->authenticateWithFacebook($credentials, $response);
        } else {
            $response->status = JAuthentication::STATUS_FAILURE;
            //$response->error_message = 'Unknown provider';
            Log::add('onUserAuthenticate, Unknown provider', Log::DEBUG, 'sociallogin');
        }
    }

    private function authenticateWithGoogle($credentials, $state, &$response)
    {
        // Use Google API client to authenticate
        // Load your Google client library and set credentials
        // Verify token and get user info
        // Set Joomla user information and status in $response

        require_once __DIR__ . '/vendor/autoload.php'; // Load the Google Client Library

        // Get the plugin Google OAuth configured params
        $google_client_id = $this->params->get('google_client_id');
        Log::add('authenticateWithGoogle, google_client_id [' . $google_client_id . ']', Log::DEBUG, 'sociallogin');
        $google_client_secret = $this->params->get('google_client_secret');
        Log::add('authenticateWithGoogle, google_client_secret [' . $google_client_secret . ']', Log::DEBUG, 'sociallogin');
        $google_client_redirect_uri = $this->params->get('google_client_redirect_uri');
        Log::add('authenticateWithGoogle, google_client_redirect_uri [' . $google_client_redirect_uri . ']', Log::DEBUG, 'sociallogin');



        $client = new Google_Client();
        Log::add('authenticateWithGoogle, step [0]', Log::DEBUG, 'sociallogin');
        $client->setClientId($google_client_id);
        Log::add('authenticateWithGoogle, step [1]', Log::DEBUG, 'sociallogin');
        $client->setClientSecret($google_client_secret);
        Log::add('authenticateWithGoogle, step [2]', Log::DEBUG, 'sociallogin');
        $client->setRedirectUri($google_client_redirect_uri); // URL Google redirects back to after login
        Log::add('authenticateWithGoogle, step [3]', Log::DEBUG, 'sociallogin');
        $client->addScope('email');
        Log::add('authenticateWithGoogle, step [4]', Log::DEBUG, 'sociallogin');
        $client->addScope('profile');
        Log::add('authenticateWithGoogle, step [5]', Log::DEBUG, 'sociallogin');                
        $client->setState($state);
        Log::add('authenticateWithGoogle, JTokenValue [' . $state . ']', Log::DEBUG, 'sociallogin');

        $url = "http".(!empty($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
        Log::add('authenticateWithGoogle, current url query ['. $url .']', Log::DEBUG, 'sociallogin');
        
        
        // Check if there is a Google authorization code in the URL (returned of the Social Call Back plugin)
        if (isset($_GET['code'])) {
            Log::add('authenticateWithGoogle, step [6]', Log::DEBUG, 'sociallogin');
            Log::add('authenticateWithGoogle, google code? ', Log::DEBUG, 'sociallogin');
            $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
            Log::add('authenticateWithGoogle, google code [' . $token . ']', Log::DEBUG, 'sociallogin');
        
            if (isset($token['error'])) {
                $response->status = JAuthentication::STATUS_FAILURE;
                $response->error_message = 'Failed to obtain access token';
                return;
            }

            $client->setAccessToken($token['access_token']);
            Log::add('authenticateWithGoogle, google access token [' . $token['access_token'] . ']', Log::DEBUG, 'sociallogin');

            // Get user profile information
            $google_oauth = new Google_Service_Oauth2($client);
            $google_account_info = $google_oauth->userinfo->get();

            // Extract user details
            $email = $google_account_info->email;
            Log::add('authenticateWithGoogle, google email [' . $mail . ']', Log::DEBUG, 'sociallogin');
            $name = $google_account_info->name;
            Log::add('authenticateWithGoogle, google name [' . $name . ']', Log::DEBUG, 'sociallogin');

            // Find or create the user in Joomla
            $this->loginOrRegisterUser($email, $name, $response);

        } else {
            Log::add('authenticateWithGoogle, step [7]', Log::DEBUG, 'sociallogin');
            // Redirect to Google's OAuth 2.0 server !!
            $authUrl = $client->createAuthUrl();
            Log::add('authenticateWithGoogle, authUrl [' . $authUrl . ']', Log::DEBUG, 'sociallogin');
            header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
            exit;
        }

    }

    // Used after the returned call back login redirection and success Google OAuth authentication
    private function loginOrRegisterUser($email, $name, &$response)
    {
        // Use Joomla's User API to find or create the user
        $user = JUserHelper::getUserByEmail($email);

        if ($user) {
            // User exists, log them in
            $response->status = JAuthentication::STATUS_SUCCESS;
            $response->username = $user->username;
            $response->email = $user->email;
            $response->fullname = $user->name;
            $response->password_clear = ''; // Not used
        } else {
            // User doesn't exist, register them
            $userData = [
                'name' => $name,
                'username' => $email,
                'email' => $email,
                'password' => JUserHelper::genRandomPassword(), // Generate a random password
            ];

            $user = new JUser;
            if (!$user->bind($userData) || !$user->save()) {
                $response->status = JAuthentication::STATUS_FAILURE;
                $response->error_message = 'Could not register user';
                return;
            }

            // Successfully registered and logged in
            $response->status = JAuthentication::STATUS_SUCCESS;
            $response->username = $user->username;
            $response->email = $user->email;
            $response->fullname = $user->name;
            $response->password_clear = '';
        }
    }

    private function authenticateWithFacebook($credentials, &$response)
    {
        // Use Facebook SDK to authenticate
        // Load Facebook SDK and set credentials
        // Verify token and get user info
        // Set Joomla user information and status in $response
    } 
}
