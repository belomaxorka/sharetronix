<p style="margin-bottom:15px;"><?= $this->page->lang('admin_plugins_intro') ?></p>

<?php if( empty($D->plugins) ): ?>
	<div class="plugin-information-block">
		<p><?= $this->page->lang('admin_plugins_none') ?></p>
	</div>
<?php else: ?>
	<table class="languages">
		<tr>
			<th width="25%"> <?= $this->page->lang('admin_plugins_col_plugin') ?> </th>
			<th width="40%"> <?= $this->page->lang('admin_plugins_col_descr') ?> </th>
			<th width="15%"> <?= $this->page->lang('admin_plugins_col_state') ?> </th>
			<th width="20%"> <?= $this->page->lang('admin_languages_act_col') ?> </th>
		</tr>
		<?php foreach( $D->plugins as $p ): ?>
		<?php
			if( !$p->installed ){
				$state	= $this->page->lang('admin_plugins_state_available');
				$buttons	= $p->error === '' ? array('install' => $this->page->lang('admin_plugins_btn_install')) : array();
			}else{
				$state	= $this->page->lang(!$p->enabled ? 'admin_plugins_state_disabled' : ($p->loaded ? 'admin_plugins_state_enabled' : 'admin_plugins_state_failed'));
				$buttons	= $p->enabled ? array('disable' => $this->page->lang('admin_plugins_btn_disable')) : array('enable' => $this->page->lang('admin_plugins_btn_enable'));
				$buttons['uninstall']	= $this->page->lang('admin_plugins_btn_uninstall');
			}
		?>
		<tr>
			<td>
				<strong><?= htmlspecialchars($p->name) ?></strong> <?= htmlspecialchars($p->version) ?>
				<?php if( $p->author !== '' ): ?><br /><?= htmlspecialchars($p->author) ?><?php endif; ?>
			</td>
			<td>
				<?= htmlspecialchars($p->descr) ?>
				<?php if( $p->error !== '' ): ?><br /><em><?= $this->page->lang('admin_plugins_invalid') ?> <?= htmlspecialchars($p->error) ?></em><?php endif; ?>
			</td>
			<td> <?= $state ?> </td>
			<td>
				<?php foreach( $buttons as $tab => $label ): ?>
				<form method="post" action="<?= $C->SITE_URL ?>admin/plugins/tab:<?= $tab ?>" style="display:inline;">
					<input type="hidden" name="token" value="<?= htmlspecialchars($D->token) ?>" />
					<input type="hidden" name="plugin" value="<?= htmlspecialchars($p->name) ?>" />
					<button type="submit" class="btn<?= $tab == 'install' ? ' blue' : '' ?>"><span><?= $label ?></span></button>
				</form>
				<?php endforeach; ?>
			</td>
		</tr>
		<?php endforeach; ?>
	</table>
<?php endif; ?>
