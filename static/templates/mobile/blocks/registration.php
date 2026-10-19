<h1 class="pagetitle"><?= $this->page->lang('signup_subtitle', array('#SITE_TITLE#' => $C->SITE_TITLE)) ?></h1>

{%login_form_additional_info%}

<form action="" method="post" autocomplete="off">
	<label for="email"><?= $this->page->lang('signup_step2_form_email') ?></label>
	<? if($D->email_confirm):?>
	<input type="email" id="email" value="{%registration_email%}" disabled="disabled" />
	<input type="hidden" name="email" value="{%registration_email%}" />
	<? else: ?>
	<input type="email" id="email" name="email" value="{%registration_email%}" autocapitalize="off" autocorrect="off" />
	<? endif; ?>
	
	<label for="fullname"><?= $this->page->lang('signup_step2_form_fullname') ?></label>
	<input type="text" id="fullname" name="fullname" value="{%registration_fullname%}" />
	<input type="text" id="hmmm" name="realname" value="" tabindex="-1" autocomplete="off" />
	
	<label for="username"><?= $this->page->lang('signup_step2_form_username') ?></label>
	<input type="text" id="username" name="username" value="{%registration_username%}" autocapitalize="off" autocorrect="off" />
	
	<label for="password"><?= $this->page->lang('signup_step2_form_password') ?></label>
	<input type="password" id="password" name="password" value="{%registration_password%}" autocomplete="new-password" />
	
	<label for="password2"><?= $this->page->lang('signup_step2_form_password2') ?></label>
	<input type="password" id="password2" name="password2" value="{%registration_password2%}" autocomplete="new-password" />
	
	<div class="captcha-image">
		{%captcha_image%}
	</div>
	
	<?php if(!$D->captcha_widget): ?>
	<label for="captcha_word"><?= $this->page->lang('signup_step2_form_captcha') ?></label>
	<input type="text" id="captcha_word" name="captcha_word" autocomplete="off" autocapitalize="off" autocorrect="off" dir="ltr" />
	<?php endif; ?>
	
	{%registration_terms_of_use%}
	
	<button class="btn" type="submit" name="submit"><span><?= $this->page->lang('signup_step2_form_submit') ?></span></button>
</form>
