<div class="plugin-information-block">
	<p><b><?= str_replace('#NAME#', htmlspecialchars($D->plugin), $this->page->lang($D->action == 'install' ? 'admin_plugins_confirm_install' : 'admin_plugins_confirm_uninstall')) ?></b></p>
	<?php if( !$D->has_installer ): ?>
		<p><?= $this->page->lang('admin_app_install_warn_db') ?></p>
	<?php endif; ?>
</div>

<?php if( !empty($D->errors) ): ?>
<div class="plugin-information-block">
	<strong><?= $this->page->lang('admin_appsinstall_errors') ?></strong>
	<ul>
	<?php foreach( $D->errors as $e ): ?>
		<li><?= $e ?></li>
	<?php endforeach; ?>
	</ul>
</div>
<?php endif; ?>

<?php if( !empty($D->messages) ): ?>
<div class="plugin-information-block">
	<strong><?= $this->page->lang('admin_appsinstall_notices') ?></strong>
	<ul>
	<?php foreach( $D->messages as $m ): ?>
		<li><?= $m ?></li>
	<?php endforeach; ?>
	</ul>
</div>
<?php endif; ?>

<div style="text-align: center; margin-top: 20px;">
	<?php if( empty($D->errors) ): ?>
	<form method="post" action="<?= $C->SITE_URL ?>admin/plugins/tab:confirm_<?= $D->action ?>" style="display:inline;">
		<input type="hidden" name="token" value="<?= htmlspecialchars($D->token) ?>" />
		<input type="hidden" name="plugin" value="<?= htmlspecialchars($D->plugin) ?>" />
		<button type="submit" class="btn blue"><span><?= $this->page->lang($D->action == 'install' ? 'admin_plugins_btn_install' : 'admin_plugins_btn_uninstall') ?></span></button>
	</form>
	<?php else: ?>
	<div class="plugin-information-block">
		<p><b><?= $this->page->lang('admin_appsinstall_error_msg_conflicts') ?></b></p>
	</div>
	<?php endif; ?>
	<a href="<?= $C->SITE_URL ?>admin/plugins" class="btn"><span><?= $this->page->lang('admin_plugins_btn_cancel') ?></span></a>
</div>
