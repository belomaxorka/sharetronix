<?php

require_once($C->INCPATH . 'libraries/twitter/OAuth2_Client.php');
require_once($C->INCPATH . 'libraries/twitter/Twitter.php');

class twitter_module implements OAuth2_Client {
	
    private $twitter = null;
    private $session = null;

    private $appID;
    private $secret;

    public function __construct() {
    	global $C;
    	
    	$this->appID = $C->TWITTER_CONSUMER_KEY;
    	$this->secret = $C->TWITTER_CONSUMER_SECRET;
    	
        $this->twitter = new Twitter(array(
                    'appId' => $this->appID,
                    'secret' => $this->secret
                ));
    }

    public function is_logged_in() {
        return isset($_SESSION['tw_data_token'])? $_SESSION['tw_data_token'] : false;
    }

    public function get_login_url($redirect_url = '') {

        $this->twitter->init($this->appID, $this->secret);

        $token = $this->twitter->getRequestToken($redirect_url);

        $_SESSION['tw_data_token'] = $token;

        return $this->twitter->getAuthorizeURL($token);
    }

    public function get_profile($verifier) {
        $token = $_SESSION['tw_data_token'];
        $this->twitter->init($this->appID, $this->secret, $token['oauth_token'], $token['oauth_token_secret']);
        $access_token = $this->twitter->getAccessToken($verifier);
        return $this->twitter->get('account/verify_credentials');
    }

    public function get_friends($user) {
        //@todo: make it work
        return array();
    }

    public function send_message($to_id, $message) {

        $token = $_SESSION['tw_data_token'];
        if( isset($token['oauth_token'], $token['oauth_token_secret']) ){
            $this->twitter->init($this->appID, $this->secret, $token['oauth_token'], $token['oauth_token_secret']);
            return $this->twitter->post('direct_messages/new', array('user_id' =>$to_id, 'text' => $message));
        }

        return false;
    }

    public function get_followers($verifier)
    {
        $token = $_SESSION['tw_data_token'];
        $this->twitter->init($this->appID, $this->secret, $token['oauth_token'], $token['oauth_token_secret']);
        $access_token = $this->twitter->getAccessToken($verifier);
        $_SESSION['tw_data_token'] = $access_token;

        return $this->twitter->get('followers/ids');
    }

    public function get_users_lookup($followers)
    {
        $token = $_SESSION['tw_data_token'];

        if( isset($token['oauth_token'], $token['oauth_token_secret']) ){
            $this->twitter->init($this->appID, $this->secret, $token['oauth_token'], $token['oauth_token_secret']);
            return $this->twitter->get('users/lookup', array('user_id' =>$followers));
        }

        return false;
    }
    
    public function get_photos($user){
    	//@TODO
    	
    	return false;
    }

}

?>
