<h1 class="pagetitle"><?= $this->page->lang('signinforg_form_title'); ?></h1>

<form action="" method="post">
	<label for="pass1"><?= $this->page->lang('signinforg_form_password'); ?></label>
	<input type="password" id="pass1" name="pass1" value="{%user_forgotten_pass1_value%}" autocomplete="new-password" />
	
	<label for="pass2"><?= $this->page->lang('signinforg_form_password2'); ?></label>
	<input type="password" id="pass2" name="pass2" value="{%user_forgotten_pass2_value%}" autocomplete="new-password" />
	
	<button type="submit" value="submit" name="submit" class="btn"><span><?= $this->page->lang('signinforg_form_submit2'); ?></span></button>
</form>
