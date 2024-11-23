<?php
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Joomla\Session\SessionInterface;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\Authentication\Authentication;

class PlgSystemSocialLoginCallBack extends CMSPlugin
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
            array('sociallogincallback')  // Custom category to identify your plugin’s messages
        );     
    }

    public function onUserAfterLogin($options)
    {
        
        Log::add('onAfterLogin...', Log::DEBUG, 'sociallogincallback');
        
        $app = Factory::getApplication();
        // Only execute in the frontend
        if (!$app->isClient('site')) {
            return;
        }
        $app->enqueueMessage("Welcome to OpenSAI...", 'success');
        $app->redirect('/bitacora');
    }

    public function onAfterInitialise()
    {

    }

    public function onAfterRoute()
    {
        $app = Factory::getApplication();
        $uri = Uri::getInstance();
        $path = $uri->getPath();
        
        // Only execute in the frontend
        if (!$app->isClient('site')) {
            return;
        }
        
        Log::add('onAfterRoute [' . $path . ']', Log::DEBUG, 'sociallogincallback');
                
        // The callback calling after the Google OAuth Authentication
        if (isset($_GET['code'])) {
            Log::add('onAfterRoute, GET code', Log::DEBUG, 'sociallogincallback');
            
            require_once __DIR__ . '/vendor/autoload.php'; // Load the Google Client Library

            $google_client_id = $this->params->get('google_client_id');
            Log::add('onAfterRoute, google_client_id [' . $google_client_id . ']', Log::DEBUG, 'sociallogincallback');
            $google_client_secret = $this->params->get('google_client_secret');
            Log::add('onAfterRoute, google_client_secret [' . $google_client_secret . ']', Log::DEBUG, 'sociallogincallback');
            $google_client_redirect_uri = $this->params->get('google_client_redirect_uri');
            Log::add('onAfterRoute, google_client_redirect_uri [' . $google_client_redirect_uri . ']', Log::DEBUG, 'sociallogincallback');
                    
            $client = new Google_Client();  
            $client->setClientId($google_client_id);
            $client->setClientSecret($google_client_secret);
            $client->setRedirectUri($google_client_redirect_uri);
            $client->addScope('email');
            $client->addScope('profile');          
            
            $google_token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
            Log::add('onAfterRoute, google token [' . implode(", ",$google_token) . ']', Log::DEBUG, 'sociallogincallback');
            
            // error in url string sended by Google OAuth
            if (array_key_exists('error', $google_token)) {        
                Log::add('onAfterRoute, Failed Authentication with Google service [' .  http_build_query($google_token,'',', ') . ']', Log::DEBUG, 'sociallogincallback');
                return;
            }

            $client->setAccessToken($google_token['access_token']);
            Log::add('onAfterRoute, google access token [' . $google_token['access_token'] . ']', Log::DEBUG, 'sociallogincallback');

            // Get user profile information
            $google_oauth = new Google_Service_Oauth2($client);
            $google_account_info = $google_oauth->userinfo->get();

            // Extract user details
            $email = $google_account_info->email;
            Log::add('onAfterRoute, google email [' . $email . ']', Log::DEBUG, 'sociallogincallback');
            $name = $google_account_info->name;
            Log::add('onAfterRoute, google name [' . $name . ']', Log::DEBUG, 'sociallogincallback');

            //validate Google OAuth returned CSRFToken
            $input = Factory::getApplication()->input;
            //$input = $app->input;
            $state = $input->get('state', '', 'string');
            parse_str($state, $stateParams);

            $csrfTokenName = $stateParams['csrfTokenName'] ?? '';
            Log::add('onAfterRoute, csrfTokenName [' . $csrfTokenName . ']', Log::DEBUG, 'sociallogincallback');
            $csrfTokenValue = $stateParams['csrfTokenValue'] ?? '';            
            Log::add('onAfterRoute, csrfTokenValue [' . $csrfTokenValue . ']', Log::DEBUG, 'sociallogincallback');

            
            // Validate the CSRF token
            if (empty($csrfTokenName) || $csrfTokenValue !== '1') {
                // Invalid CSRF token; handle the error
                Log::add('onAfterRoute, error in returned CSRF. Possible CSRF attack detected returnedToken [' . $csrfTokenName . ']', Log::DEBUG, 'sociallogincallback');
                return false;
            }else{
                // Find or create the user in Joomla
                $this->loginOrRegisterUser($email, $name, $csrfTokenName, $csrfTokenValue, $response);            
                Log::add('onAfterRoute, CSRF Token [' . $csrfTokenName . ']', Log::DEBUG, 'sociallogincallback');
            }            
        }
    }
    
    private function loginOrRegisterUser($email, $name, $csrfTokenName, $csrfTokenValue, &$response)
    {
        // Initialize application
        $app = Factory::getApplication();        
        
        // Initialize Joomla's database object
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__users'))
            ->where($db->quoteName('email') . ' = ' . $db->quote($email));

        // Set and execute the query
        $db->setQuery($query);
        $result = $db->loadObject();

        // if user exists...
        if ($result) {
            Log::add('loginOrRegisterUser, User exists!, must be authenticated...', Log::DEBUG, 'sociallogincallback');

            //$options = ['remember' => true];
            $options = ['silent' => true];
            
            // Load the user object by ID if a user with that email exists
            $user = new User($result->id);
            Log::add('loginOrRegisterUser, user id [' . $user->id . ']', Log::DEBUG, 'sociallogincallback');
            Log::add('loginOrRegisterUser, user name [' . $user->name . ']', Log::DEBUG, 'sociallogincallback');
            Log::add('loginOrRegisterUser, user username [' . $user->username . ']', Log::DEBUG, 'sociallogincallback');
            Log::add('loginOrRegisterUser, user password [' . $user->password . ']', Log::DEBUG, 'sociallogincallback');

            // Manual session start, password is not needed
            $credentials = [
                'username' => $user->username, 
                'password' => $user->password, 
                'csrfTokenName' => $csrfTokenName, 
                'csrfTokenValue' => $csrfTokenValue, 
                'newuser' => false
            ]; 

            // Joomla redirect this call to the onUserAuthenticate() of the authentication plugin sociallogin.php
            $result = $app->login($credentials, $options);
            Log::add('loginOrRegisterUser,  $result of $app->login() call[' . $result . ']', Log::DEBUG, 'sociallogincallback');
          

        } else {
            Log::add('loginOrRegisterUser, User doesnt exists!, must be created...', Log::DEBUG, 'sociallogincallback');

            $options = [
                'silent' => true,                
            ];
            // new user profile 
            $credentials = [
                'username' => $email,
                'name' => $name,
                'csrfTokenName' => $csrfTokenName, 
                'csrfTokenValue' => $csrfTokenValue, 
                'newuser' => true
            ];

            $userData = [
                'name' => $name,
                'username' => $email,
                'email' => $email,
                'password' => JUserHelper::genRandomPassword(), // Generate a random password
                'groups' => [2] //default registered Joomla group
            ];

            $user = new JUser;
            if (!$user->bind($userData) || !$user->save()) {
                $response->status = JAuthentication::STATUS_FAILURE;                
                Log::add('loginOrRegisterUser, Could not register user...', Log::DEBUG, 'sociallogincallback');
                return;
            }else{
                Log::add('loginOrRegisterUser, User .[' . $user->username. '] registered!', Log::DEBUG, 'sociallogincallback');
                $result = $app->login($credentials, $options);
                Log::add('loginOrRegisterUser,  NEW USER $result of $app->login() call[' . $result . ']', Log::DEBUG, 'sociallogincallback');                
            }

        }
        
    }

}
