<div class="invite-user-container"    data-action="toggleSelectedUsersContainer" data-value="{%single_invite_user_id%}" data-namespace="users" data-role="services"> <!-- left-container, right-container -->

	<input type="checkbox" name="invite_users[]" {%invite_checkbox_checked%} value="{%single_invite_user_id%}" data-value="{%invite_data_value%}" data-role="services" data-namespace="groups" data-action="invite"/>
	<div class="avatar">{%single_user_avatar%}</div>

	<div class="user-details">
	
		<div class="title">{%single_user_username%}</div>
		<!--<div class="statistics">{%single_user_activity%}</div>
		<div class="content personal-information">{%single_user_email%}</div>-->

	</div>

	<div class="clear"></div>
</div>