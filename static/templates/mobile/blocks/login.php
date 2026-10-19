<h1 class="pagetitle"><?= $this->page->lang('signin_form_caption') ?></h1>

<form action="" method="post">
	<label for="email"><?= $this->page->lang('signin_email_or_username') ?></label>
	<input type="text" id="email" name="email" autocapitalize="off" autocorrect="off" />
	
	<label for="password"><?= $this->page->lang('signin_form_password') ?></label>
	<input type="password" id="password" name="password" />
	
	<label class="checkbox" for="rememberme"><input type="checkbox" name="rememberme" id="rememberme" value="1" /> <?= $this->page->lang('signin_form_rem') ?></label>
	
	<button type="submit" class="btn"><span><?= $this->page->lang('signin_login_box_ttl') ?></span></button>
</form>

<div class="links">
	<a href="<?= $C->SITE_URL ?>signin/forgotten"><?= $this->page->lang('signin_form_forgotten'); ?></a>
	<a href="<?= $C->SITE_URL ?>signup"><?= $this->page->lang('signin_reg_title') . $this->page->lang('signin_reg_button'); ?></a>
</div>
