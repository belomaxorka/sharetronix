<div class="login-form">
	<h1 class="pagetitle"><?= $this->page->lang('signup_subtitle', array('#SITE_TITLE#'=>$C->SITE_TITLE)) ?></h1>
	
<form action="" method="POST">

	<label for="email"><?= $this->page->lang('signup_step1_form_email') ?></label>
	<input type="text" value="{%user_signup_email_value%}" name="email" id="email" data-status="focus"/>
	<div class="clear"></div>
		
	<?php if(!$D->captcha_widget): ?>
	<label for="captcha_word"><?= $this->page->lang('signup_step2_form_captcha') ?></label>
	<div class="captcha-fields">
		<input type="text" id="captcha_word" name="captcha_word" autocomplete="off" dir="ltr" {%autofocus%} />
	</div>
	<?php endif; ?>
	
	<div class="captcha-image">
		{%captcha_image%}
	</div>
	
	<div class="registration-buttons">
		<button type="submit" value="submit" name="submit" class="btn blue"><span><?= $this->page->lang('signup_step1_form_submit') ?></span></button>
		<div class="clear"></div>
	</div>
</form>

<div class="clear"></div>

</div>