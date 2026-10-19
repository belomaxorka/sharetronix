<h1 class="pagetitle"><?= $this->page->lang('signup_subtitle', array('#SITE_TITLE#'=>$C->SITE_TITLE)) ?></h1>

<form action="" method="post">
	<label for="email"><?= $this->page->lang('signup_step1_form_email') ?></label>
	<input type="email" id="email" name="email" value="{%user_signup_email_value%}" autocapitalize="off" autocorrect="off" />
	
	<div class="captcha-image">
		{%captcha_image%}
	</div>
	
	<?php if(!$D->captcha_widget): ?>
	<label for="captcha_word"><?= $this->page->lang('signup_step2_form_captcha') ?></label>
	<input type="text" id="captcha_word" name="captcha_word" autocomplete="off" autocapitalize="off" autocorrect="off" dir="ltr" />
	<?php endif; ?>
	
	<button type="submit" value="submit" name="submit" class="btn"><span><?= $this->page->lang('signup_step1_form_submit') ?></span></button>
</form>

<div class="links">
	<a href="<?= $C->SITE_URL ?>"><?= $this->page->lang('os_login_ttl') ?></a>
</div>
