<?php

interface OAuth2_Client {
    public function is_logged_in();
    public function get_login_url($redirect_url = '');
    public function get_profile($user);
    public function get_friends($user);
    public function get_photos($user);
    public function send_message($user_id, $message);
}

?>
