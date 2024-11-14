<?php
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\Session\SessionInterface;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Event\AbstractEvent;

class PlgSystemSocialLoginCallBack extends CMSPlugin
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
            array('sociallogincallback')  // Custom category to identify your plugin’s messages
        );     
    }

    public function onAfterInitialise()
    {

    }

    public function onAfterRoute()
    {
        //Log::add('onAfterRoute...', Log::DEBUG, 'sociallogincallback');

        $app = Factory::getApplication();
        //$input = $app->input;
        $uri = Uri::getInstance();
        $path = $uri->getPath();
        
        // Only execute in the frontend
        if (!$app->isClient('site')) {
            return;
        }
        
        Log::add('onAfterRoute [' . $path . ']', Log::DEBUG, 'sociallogincallback');
        //$jtoken=$_SESSION['oauth_token'];
        //Log::add('loginOrRegisterUser,  [' . $user->id . ']', Log::DEBUG, 'sociallogincallback');
        // Check if the current request is the OAuth callback
        /*$app = Factory::getApplication();

        $input = $app->input;
        $uri = Uri::getInstance();
        
        $path = $uri->getPath();
        
        Log::add('onAfterRoute [' . $path . ']', Log::DEBUG, 'sociallogincallback');*/

        /*if ($uri->getPath() === '/profile') {
            // Call your custom function here
            Log::add('onAfterRoute get /profile...', Log::DEBUG, 'sociallogincallback');
            // Optionally, stop further execution
            //$app->close();
        }*/        
        
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
            
            $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
            Log::add('onAfterRoute, google token [' . implode(", ",$token) . ']', Log::DEBUG, 'sociallogincallback');
            //Log::add('onAfterRoute, google code [' .  http_build_query($token,'',', ') . ']', Log::DEBUG, 'sociallogincallback');

            // error in url string sended by Google OAuth
            if (array_key_exists('error', $token)) {        
            //if (isset($token['error'])) {
                //$response->status = JAuthentication::STATUS_FAILURE;
                //$response->error_message = 'Failed Authentication with Google service';
                //Log::add('onAfterRoute, error in google token [' . $token['error'] . ']', Log::DEBUG, 'sociallogincallback');
                Log::add('onAfterRoute, Failed Authentication with Google service [' .  http_build_query($token,'',', ') . ']', Log::DEBUG, 'sociallogincallback');
                return;
            }

            $client->setAccessToken($token['access_token']);
            Log::add('onAfterRoute, google access token [' . $token['access_token'] . ']', Log::DEBUG, 'sociallogincallback');

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
            //if (empty($csrfTokenName) || $csrfTokenValue !== '1' || !Session::checkToken(true)) {
            if (empty($csrfTokenName) || $csrfTokenValue !== '1') {
                // Invalid CSRF token; handle the error
                Log::add('onAfterRoute, Invalid CSRF token. Possible CSRF attack detected returnedToken [' . $csrfTokenName . ']', Log::DEBUG, 'sociallogincallback');
                return false;
            }else{
                // Find or create the user in Joomla
                $this->loginOrRegisterUser($email, $name, $csrfTokenName, $csrfTokenValue, $response);            
                Log::add('onAfterRoute, CSRF Token [' . $csrfTokenName . ']', Log::DEBUG, 'sociallogincallback');
            }


            

            
        }
    }
    
    // public function onUserAuthenticate($credentials, $options, &$response)
    // {   
        
    //     // Validate Joomla security token
    //     if (!Session::checkToken('post')) { // Using 'get' because the token is in the URL
    //         $response->status = JAuthentication::STATUS_FAILURE;
    //         //$response->error_message = 'Invalid security token';
    //         Log::add('onUserAuthenticate, Invalid security token', Log::DEBUG, 'sociallogin');
    //         return;
    //     }
        
        
    //     // Detect the login provider
    //     //if ($credentials['provider'] === 'google') {
    //     if (isset($_POST['provider']) && $_POST['provider'] === 'google') {
    //         $this->authenticateWithGoogle($credentials, $response);
    //     } elseif ($credentials['provider'] === 'facebook') {
    //         $this->authenticateWithFacebook($credentials, $response);
    //     } else {
    //         $response->status = JAuthentication::STATUS_FAILURE;
    //         //$response->error_message = 'Unknown provider';
    //         Log::add('onUserAuthenticate, Unknown provider', Log::DEBUG, 'sociallogin');
    //     }
    // }

    // private function authenticateWithGoogle($credentials, &$response)
    // {
    //     // Use Google API client to authenticate
    //     // Load your Google client library and set credentials
    //     // Verify token and get user info
    //     // Set Joomla user information and status in $response

    //     require_once __DIR__ . '/vendor/autoload.php'; // Load the Google Client Library

    //     $google_client_id = $this->params->get('google_client_id');
    //     Log::add('authenticateWithGoogle, google_client_id [' . $google_client_id . ']', Log::DEBUG, 'sociallogin');
        
    //     $google_client_secret = $this->params->get('google_client_secret');
    //     Log::add('authenticateWithGoogle, $google_client_secret [' . $google_client_secret . ']', Log::DEBUG, 'sociallogin');
        
    //     $google_client_redirect_uri = $this->params->get('google_client_redirect_uri');
    //     Log::add('authenticateWithGoogle, google_client_redirect_uri [' . $google_client_redirect_uri . ']', Log::DEBUG, 'sociallogin');

    //     $client = new Google_Client();
    //     Log::add('authenticateWithGoogle, step [0]', Log::DEBUG, 'sociallogin');
    //     $client->setClientId($google_client_id);
    //     Log::add('authenticateWithGoogle, step [1]', Log::DEBUG, 'sociallogin');
    //     $client->setClientSecret($google_client_secret);
    //     Log::add('authenticateWithGoogle, step [2]', Log::DEBUG, 'sociallogin');
    //     $client->setRedirectUri($google_client_redirect_uri); // URL Google redirects back to after login
    //     Log::add('authenticateWithGoogle, step [3]', Log::DEBUG, 'sociallogin');
    //     $client->addScope('email');
    //     Log::add('authenticateWithGoogle, step [4]', Log::DEBUG, 'sociallogin');
    //     $client->addScope('profile');
    //     Log::add('authenticateWithGoogle, step [5]', Log::DEBUG, 'sociallogin');

    //     $url = "http".(!empty($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
    //     Log::add('authenticateWithGoogle, current url query ['. $url .']', Log::DEBUG, 'sociallogin');
    //     // Check if there is a Google authorization code in the URL
    //     if (isset($_GET['code'])) {
    //         Log::add('authenticateWithGoogle, step [6]', Log::DEBUG, 'sociallogin');
    //         Log::add('authenticateWithGoogle, google code? ', Log::DEBUG, 'sociallogin');
    //         $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    //         Log::add('authenticateWithGoogle, google code [' . $token . ']', Log::DEBUG, 'sociallogin');
        
    //         if (isset($token['error'])) {
    //             $response->status = JAuthentication::STATUS_FAILURE;
    //             $response->error_message = 'Failed to obtain access token';
    //             return;
    //         }

    //         $client->setAccessToken($token['access_token']);
    //         Log::add('authenticateWithGoogle, google access token [' . $token['access_token'] . ']', Log::DEBUG, 'sociallogin');

    //         // Get user profile information
    //         $google_oauth = new Google_Service_Oauth2($client);
    //         $google_account_info = $google_oauth->userinfo->get();

    //         // Extract user details
    //         $email = $google_account_info->email;
    //         Log::add('authenticateWithGoogle, google email [' . $mail . ']', Log::DEBUG, 'sociallogin');
    //         $name = $google_account_info->name;
    //         Log::add('authenticateWithGoogle, google name [' . $name . ']', Log::DEBUG, 'sociallogin');

    //         // Find or create the user in Joomla
    //         $this->loginOrRegisterUser($email, $name, $response);

    //     } else {
    //         Log::add('authenticateWithGoogle, step [7]', Log::DEBUG, 'sociallogin');
    //         // Redirect to Google's OAuth 2.0 server
    //         $authUrl = $client->createAuthUrl();
    //         Log::add('authenticateWithGoogle, authUrl [' . $authUrl . ']', Log::DEBUG, 'sociallogin');
    //         header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
    //         exit;
    //     }

    // }

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


        if ($result) {
            Log::add('loginOrRegisterUser, User exists!, must be authenticated...', Log::DEBUG, 'sociallogincallback');

            //$options = ['remember' => true];
            $options = ['silent' => true];

            $token = UserHelper::genRandomPassword(32); 
            //$_SESSION['oauth_token'] = $token;
            //Log::add('loginOrRegisterUser,  [' . $user->id . ']', Log::DEBUG, 'sociallogincallback');

            // Load the user object by ID if a user with that email exists
            $user = new User($result->id);
            Log::add('loginOrRegisterUser, user id [' . $user->id . ']', Log::DEBUG, 'sociallogincallback');
            Log::add('loginOrRegisterUser, user name [' . $user->name . ']', Log::DEBUG, 'sociallogincallback');
            Log::add('loginOrRegisterUser, user username [' . $user->username . ']', Log::DEBUG, 'sociallogincallback');
            Log::add('loginOrRegisterUser, user password [' . $user->password . ']', Log::DEBUG, 'sociallogincallback');

            // Manual session start, password is not needed
            $credentials = ['username' => $user->username, 'password' => $token, 'csrfTokenName' => $csrfTokenName, 'csrfTokenValue' => $csrfTokenValue]; 

            //if (!Session::checkToken($returnedCSRFToken)) {
                // Token is invalid; handle the error
            //    Log::add('loginOrRegisterUser, Invalid Joomla Token [' . $returnedCSRFToken . ']', Log::DEBUG, 'sociallogincallback');
                //return false;
            //}else{
            // Joomla redirect this call to the onUserAuthenticate() of the authentication plugin sociallogin.php
            //$result = $app->login($credentials, $options);
            $result = $app->login($credentials, ['silent' => false, 'return' => 'index.php?option=com_users&view=profile']);
            //}
            //$response->status = JAuthentication::STATUS_SUCCESS;
            
            /*if ($result === true) {
                Log::add('loginOrRegisterUser, User [' . $user->username . '] logged in successfully!', Log::DEBUG, 'sociallogincallback');
                return true;
            } else {
                Log::add('loginOrRegisterUser, Failed to log in the user [' . $user->username . ']', Log::DEBUG, 'sociallogincallback');
                return false;
            }*/            

        } else {
            Log::add('loginOrRegisterUser, User doesnt exists!, must be created...', Log::DEBUG, 'sociallogincallback');
            // Handle the case where no user is found
            $user = null;

        }
        
        
        //if ($user) {
            // Prepare credentials
            //$credentials = ['username' => $username, 'password' => $password];


            // User exists, log them in
            /*
            $response->status = JAuthentication::STATUS_SUCCESS;
            $response->username = $user->username;
            $response->email = $user->email;
            $response->fullname = $user->name;
            $response->password_clear = ''; // Not used
            */
            

        //} else {
            
            // User doesn't exist, register them
            /*
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
            */
        //}
    }

    private function authenticateWithFacebook($credentials, &$response)
    {
        // Use Facebook SDK to authenticate
        // Load Facebook SDK and set credentials
        // Verify token and get user info
        // Set Joomla user information and status in $response
    }
}
