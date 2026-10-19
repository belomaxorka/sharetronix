<p style="margin-bottom:15px;"><?= $this->page->lang('admin_languages_intro') ?></p>

<table class="languages">
	<tr>
		<th width="60%"> <?= $this->page->lang('admin_languages_langs_col') ?> </th>
		<th width="20%"> <?= $this->page->lang('admin_languages_abrv_col') ?> </th>
		<th width="20%"> <?= $this->page->lang('admin_languages_default_col') ?> </th>
	</tr>
	<?php foreach( $D->languages as $lang ): ?>
	<tr>
		<td> <?= htmlspecialchars($lang['lang_name']) ?> </td>
		<td style="text-transform: uppercase;"> <?= htmlspecialchars($lang['langkey']) ?> </td>
		<td> <?= $lang['default'] ? $this->page->lang('admin_languages_default_yes') : '' ?> </td>
	</tr>
	<?php endforeach; ?>
</table>
