<?php
	
	if( $this->network->id && $this->user->is_logged ) {
		$this->redirect('dashboard');
	}
	
	$this->load_langfile('outside/signin.php');
	$this->load_langfile('outside/global.php');
	$this->load_langfile('outside/signup.php');
	
	//require_once( $C->INCPATH.'helpers/func_images.php' );
	
	$page_title	= $this->lang('signinforg_page_title', array('#SITE_TITLE#'=>$C->SITE_TITLE));
	
	$captcha = new captcha('forgotten_pass');
	
	$have_key	= FALSE;
	
	if( $this->param('key') )
	{
		$have_key	= TRUE;
		$error_key	= FALSE;
		
		$key	= $this->db2->e(trim($this->param('key')));
		$this->db2->query('SELECT id FROM users WHERE active=1 AND pass_reset_key="'.$key.'" AND pass_reset_valid>="'.time().'" LIMIT 1');
		if( ! $u = $this->db2->fetch_object() ) {
			$error_key	= TRUE;
		}
		else {
			$submit	= FALSE;
			$error	= FALSE;
			$errmsg	= '';
			
			if( isset($_POST['pass1'], $_POST['pass2']) && is_string($_POST['pass1']) && is_string($_POST['pass2']) ) {
				$submit = TRUE;
				
				$pass	= trim($_POST['pass1']);
				if( strlen($pass)<5 ) {
					$error	= TRUE;
					$errmsg	= 'signinforg_err_passwdlen';
				}
				elseif( $pass != trim($_POST['pass2']) ) {
					$error	= TRUE;
					$errmsg	= 'signinforg_err_passdiff';
				}
				else {
					$pass	= md5($pass);
					$this->db2->query('UPDATE users SET password="'.$this->db2->e($pass).'", pass_reset_key="", pass_reset_valid="" WHERE id="'.$u->id.'" LIMIT 1');
					$u	= $this->network->get_user_by_id($u->id, TRUE);
					$this->redirect('signin/pass:changed');
				}
			}
		}
	}
	else
	{
		$submit	= FALSE;
		$error	= FALSE;
		$errmsg	= '';
		$email	= '';
		
		if( isset($_POST['email']) ) {
			$submit	= TRUE;
			
			$email	= strtolower(trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL)));
			
			
			if( ! is_valid_email($email) ) {
				$error	= TRUE;
				$errmsg	= 'signinforg_err_email';
			}
			$u	= FALSE;
			if( ! $error ) {
				$this->db2->query('SELECT id, active FROM users WHERE email="'.$this->db2->e($email).'" LIMIT 1');
				if( ! $u = $this->db2->fetch_object() ) {
					$error	= TRUE;
					$errmsg	= 'signinforg_err_email2';
				}
				elseif( $u->active == "0" ) {
					$error	= TRUE;
					$errmsg	= 'signinforg_err_banned';
				}
				elseif( ! $u = $this->network->get_user_by_id($u->id) ) {
					$error	= TRUE;
					$errmsg	= 'signinforg_err_email2';
				}
			}
			
			if( !$error && !$captcha->verify() ) {
				$error	= TRUE;
				$errtxt	= $captcha->get_error_message('signup_err_captcha');
				$wrong_captcha = TRUE;
			}
			
			if( ! $error ) {
				if( $this->user->is_logged ) {
					$this->user->logout();
				}
				$key		= md5('akey_'.$u->id.'_'.(rand().time().rand()));
				$valid	= time() + 48*60*60;
				$this->db2->query('UPDATE users SET pass_reset_key="'.$key.'", pass_reset_valid="'.$valid.'" WHERE id="'.$u->id.'" LIMIT 1');
				// The address without the mobile prefix: a phone opens it as a mobile page anyway.
				$D->recover_link	= $C->OUTSIDE_SITE_URL.'signin/forgotten/key:'.$key;
				$this->load_langfile('email/signin.php');
				$subject	= $this->lang('signinforg_email_subject', array('#SITE_TITLE#'=>$C->SITE_TITLE));
				// The e-mail templates of static/templates/email/, like every other message.
				$msgtxt	= $this->load_single_block('email/signinforg_txt', FALSE, TRUE);
				$msghtml	= $this->load_single_block('email/signinforg_html', FALSE, TRUE);
				do_send_mail_html($email, $subject, $msgtxt, $msghtml);
			}
		}
	}
	
	$tpl = new template( array('page_title' => $this->lang('signinforg_page_title', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'c') );
	
	$show_step1_form = TRUE;
	
	if( $submit && $error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('signinforg_err'), isset($errtxt) ? $errtxt : $this->lang($errmsg) ) );
	}else if( $submit && !$error ){
		if( $have_key ){
			$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('signinforg_alldone_ttl'), $this->lang('signinforg_alldone_txt') ) );
		}else{
			$show_step1_form = FALSE;
			$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('signinforg_sentmail_ttl'), $this->lang('signinforg_sentmail_txt', array('#EMAIL#'=>$email)) ) );
		}
	}

	if( !$have_key ){
		
		if( $show_step1_form ){
			$tpl->layout->useBlock('user-forgotten-pass-step1');
			$tpl->layout->block->setVar('user_forgotten_email_value', isset($email)? htmlspecialchars($email) : '');
			$captcha->set_template_vars($tpl->layout->block);
				
			$tpl->layout->block->setVar('autofocus','');
			if(isset($wrong_captcha)){
				$tpl->layout->block->setVar('autofocus','data-status="focus"');
			}
			
			$tpl->layout->block->save('main_content');
		}
	
	}else{
		$tpl->layout->useBlock('user-forgotten-pass-step2');

		$tpl->layout->block->save('main_content');
	}
	
	$tpl->display();
?>