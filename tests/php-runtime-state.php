<?php

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$root = dirname(__DIR__);
require $root.'/system/helpers/func_main.php';
$C = (object) array('INCPATH' => $root.'/system/', 'SITE_URL' => 'http://localhost.test/', 'THEME' => 'default', 'LANGUAGE' => 'en', 'API_ID' => 0, 'MASS_MAILING' => FALSE, 'DEBUG_MODE' => FALSE);
$cache = $db1 = $db2 = new stdClass();
$network = new network();
$user = new user();
$network->id = 1;
$user->id = 7;
$user->is_logged = TRUE;
$user->info = (object) array('id' => 7, 'network_id' => 1);
$page = new page();
$page->request = array('dashboard');
$page->load_langfile('inside/global.php');
$csrf = new csrf('regression');
$token = $csrf->get_value();
if (!$csrf->check_if_valid($token) || $csrf->check_if_valid('invalid')) {
    throw new RuntimeException('Retain valid-token acceptance and invalid-token rejection.');
}
$saved = unserialize(serialize(array($network, $user, $page)));
if ($saved[1]->id !== 7 || $saved[1]->sess['regression']->value !== $token || $saved[0]->db2 !== $saved[1]->db2) {
    throw new RuntimeException('Cached object graphs must retain user state, tokens, and shared database references.');
}
class RegressionRuntimePlugins
{
    public function getVars() { return array(); }
}
$plugins_manager = new RegressionRuntimePlugins();
$template = new template(array(), FALSE);
$block = new htmlBlock('empty', FALSE, $template->layout);
if (strpos($block->html, '{%empty_block_content%}') === FALSE) {
    throw new RuntimeException('The existing template block must load.');
}
$template->layout->setVar('main_content', 'runtime-state-fixture');
if (trim($template->display(TRUE)) !== 'runtime-state-fixture') {
    throw new RuntimeException('The clean layout must render assigned content.');
}
$notifier = new notifier();
$notifier->set_notification_obj('user', 7);
$post = new post('public');
$comment = new newpostcomment($post);
if ($comment->comment_date > time() || $comment->comment_user->id !== 7) {
    throw new RuntimeException('New comments must retain their author and creation date.');
}
restore_error_handler();
echo "PASS: runtime construction, session/cache serialization, CSRF, templates, and comment state without diagnostics.\n";
