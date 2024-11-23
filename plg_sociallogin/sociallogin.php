<?php
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;


class PlgAuthenticationSociallogin extends CMSPlugin 
{
    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);

        // Initialize the logger only once
        Log::addLogger(
            array(
                'text_file' => 'everything.php',  // Name of the log file
                'text_file_path' => JPATH_ROOT . '/logs', // Directory for logs (usually Joomla's /logs)
                'text_entry_format' => '{DATE} {TIME} {CLIENTIP} {CATEGORY} {MESSAGE}'
            ),
            Log::ALL,  // Log all types of messages
            array('sociallogin')  // Custom category to identify your plugin’s messages
        );     
    }

    public function onAfterInitialise() //test
    {
        Log::add('onAfterInitialise...', Log::DEBUG, 'sociallogin');
    }

    public function onAfterRoute() //test
    {
        Log::add('onAfterRoute...', Log::DEBUG, 'sociallogin');
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
    
    // In Joomla the login form of the main login module trigger onUserAuthenticate() event...
    //empty $options parameter must be used by joomla onUserAuthenticate declaration
    public function onUserAuthenticate($credentials, $options, &$response)
    {   
        Log::add('onUserAuthenticate', Log::DEBUG, 'sociallogin');
        
        
        $app = JFactory::getApplication();
        $app->getSession();

        //Reviewing Session...
        Log::add('Testing Session object: ' . (Factory::getSession() ? 'Initialized' : 'Null'), Log::DEBUG, 'sociallogin');        

        // First Step: Always get the actual session CSRF token, default = 1
        $csrfTokenName = Session::getFormToken();
        $csrfTokenValue = '1';
        
        // save the CSRF values in the $state array to use with the Google OAuth Service
        $state = http_build_query([
            'csrfTokenName' => $csrfTokenName,
            'csrfTokenValue' => $csrfTokenValue,
        ]);
        
        // Detect the login provider (step 0 from the Joomla login module form)
        if (isset($_POST['provider']) && $_POST['provider'] === 'google' ) {
            Log::add('onUserAuthenticate, Google OAuth provider detected...', Log::DEBUG, 'sociallogin');
            $this->authenticateWithGoogle($credentials, $state, $response);            
        } elseif ($credentials['provider'] === 'facebook') {
            $this->authenticateWithFacebook($credentials, $response);
        } else {
            $response->status = JAuthentication::STATUS_FAILURE;
            Log::add('onUserAuthenticate, local provider or unknown provider', Log::DEBUG, 'sociallogin');
        }

        // Called after the Google OAuth authentication from SocialLoginCallBack plugin
        //if ($credentials['password']) { 
        if (!$credentials['newuser']) { 
            if (!Session::checkToken('post')) 
                Log::add('onUserAuthenticate, NO token in POST method...', Log::DEBUG, 'sociallogin');
            if (!Session::checkToken('get')) 
                Log::add('onUserAuthenticate, NO token in GET method...', Log::DEBUG, 'sociallogin');

            $returned_jtoken=$credentials['csrfTokenName'];
            Log::add('onUserAuthenticate, returned jtoken to start session ['. $returned_jtoken . ']', Log::DEBUG, 'sociallogin');
            $localcsrfTokenName = Session::getFormToken();
            Log::add('onUserAuthenticate, local jtoken to start session ['. $localcsrfTokenName . ']', Log::DEBUG, 'sociallogin');

            $response->username = $user->username;
            $response->email = $user->email;
            $response->fullname = $user->name;
            $response->message = "Welcome Back!";
            $response->status = JAuthentication::STATUS_SUCCESS;
            
            Log::add('onUserAuthenticate, User logged in successfully...['. $response->status . ']', Log::DEBUG, 'sociallogin');

            return true;

        }else{ 

            $returned_jtoken=$credentials['csrfTokenName'];
            Log::add('onUserAuthenticate, NEW USER returned jtoken to start session ['. $returned_jtoken . ']', Log::DEBUG, 'sociallogin');
            $localcsrfTokenName = Session::getFormToken();
            Log::add('onUserAuthenticate, NEW USER local jtoken to start session ['. $localcsrfTokenName . ']', Log::DEBUG, 'sociallogin');

            $response->username = $user->username;
            $response->email = $user->email;
            $response->fullname = $user->name;
            $response->message = "Welcome to OpenSAI!";
            $response->status = JAuthentication::STATUS_SUCCESS;
            
            Log::add('onUserAuthenticate, NEW USER User logged in successfully...['. $response->status . ']', Log::DEBUG, 'sociallogin');            

            return true;
            
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
        Log::add('authenticateWithGoogle, Local JToken Value [' . $state . ']', Log::DEBUG, 'sociallogin');

        $url = "http".(!empty($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
        Log::add('authenticateWithGoogle, current url query ['. $url .']', Log::DEBUG, 'sociallogin');

        Log::add('authenticateWithGoogle, step [6]', Log::DEBUG, 'sociallogin');
        
        // Redirect to Google's OAuth 2.0 server !!
        $authUrl = $client->createAuthUrl();
        Log::add('authenticateWithGoogle, authUrl [' . $authUrl . ']', Log::DEBUG, 'sociallogin');
        header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));

        exit; //end the app, the sociallogincallback will wait to the Google OAuth response...

    }

    private function authenticateWithFacebook($credentials, &$response)
    {
        // Use Facebook SDK to authenticate
        // Load Facebook SDK and set credentials
        // Verify token and get user info
        // Set Joomla user information and status in $response
    } 
}
