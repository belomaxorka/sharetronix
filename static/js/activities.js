// Sharetronix Activities namespace
var Activities = function () {

    // --- declare private methods --- //

	var checknewTimeoutId = null;
	var checknewInterval = 15000;
	var pageTitle = '';
	
    function showLoading() { $('.activity-feed').find('.loading-container:first').show().css('left', '0'); }

    function hideLoading() { $('.activity-feed').find('.loading-container:first').hide(); }

    function postSuccess(response, context) {
    	if(response.status == 'ERROR'){
    		alert(response.message);
    		return;
    	}
        var editor = $(context).parents('.data-content-placeholder').find('.htmlarea textarea');
        var activityContent = $(response.html).css('display', 'none');
        var counter = $(editor).parents('.status-editor-container').find('.characters-counter');
        counter.text(counter.data('value'));
        
        if ($('.noposts').length > 0) $('.noposts').fadeOut();
        
        $('.activity-feed-list').prepend(activityContent);

        if ($('.new-activities-count').length > 0) {
            $('.new-activities-count').after(activityContent);
        }

        
        
        activityContent.animate({ height: 'show' });
        activityContent.find('.activity').effect('highlight', {}, 3000);

        Htmlarea.reset(editor, 'activity');
        //STX.colorboxInit(activityContent);
        STX.colorboxInit();
        Attachments.updateToken($(context).parents('.data-content-placeholder'), 'status');
        
        //$('#last_activity').val(response.inserted_activities_id)
        
        if ($('#activities_type').length > 0 && $('#last_activity').length > 0) 
        	resetChecknewTimer();
        
        if($('.activity-feed-list .activity-feed-list').css('display') == 'none'){
        	$('.activity-feed-list .activity-feed-list').show();
        }
        
        if($('.attachment-group-field-container').length > 0){
        	$('.attachment-group-field').val('');
            $('.attachment-button.group').css('background-image', 'url('+siteurl+'/static/images/icon-add.png)');
            $('.attachment-group-field-container').find('.attachment-group-field').css('color', 'black');
            $('.attachment-button.group').text(stxText('js_group_btn', 'Group'));
        }
        
    }
    
    function editSuccess(response, context) {
		if(response.status == 'ERROR'){
    		commandFail(response, '');
    		return;
    	}
		STX.showMessage(stxText('js_post_edited', 'Post edited successfully'), "success");
		
		var post_selector = $(context).parents('.activity-footer').siblings('.activity-content');
		var text = $(post_selector).children('textarea').val();
		$(post_selector).children('textarea').remove();
		$(post_selector).text(text);
		
		$('#cancel_editing').remove();
		
		$(context).replaceWith('<a href="" class="edit_post" data-role="services" data-namespace="activities" data-action="show_edit_box" data-posttype="'+ posttype +'" data-postid="'+ postid +'">Edit post</a>');
	}

    
    function deleteSuccess(response, context) {
    	if (response.status == 'OK') {
    		$.browser.msie ?
    			$(context).parents('.activity:first').animate({ height: 'toggle' }, 'slow'):
    			$(context).parents('.activity:first').animate({ opacity: 'toggle', height: 'toggle' });
    	} else {
    		STX.showMessage(response.message, "error");
    	}
    	
    }
    
    function commandFail(response) {
        STX.showMessage(response.message, "error");
		//console.log(error);
    };
    
    function bookmarkSuccess(response, context) {
    	context.hasClass('empty') ?
    		context.removeClass('empty'):
    		context.addClass('empty');
    }
    
    function getallSuccess(response, context) {
    	var result = $(response.html).html();
    	$('.activity-feed-list').append(result);
    	var value = $(context).data('value');
    	if (response.last_activities_id != 0) {
    		value.activities_id = response.last_activities_id;
    		$(context).data('value', value);
    	} else {
    		$('.show-more-container').remove();
    	}
    	STX.colorboxInit();
    }

    
    
    
    function clearChecknewTimer() {
        if (checknewTimeoutId != null) {
            clearTimeout(checknewTimeoutId);
            checknewTimeoutId = null;
        }
    }
    
    function setChecknewTimer() { checknewTimeoutId = setTimeout(checknew, checknewInterval); }
    
    function resetChecknewTimer() {
    	clearChecknewTimer();
    	setChecknewTimer();
    }
    
    function checknew() {
        if (pageTitle == '') pageTitle = $(document).attr('title');
        
    	var data = {
			activities_type: $('#activities_type').val(),
			last_activity: $('#last_activity').val(),
			activities_tab: $('#activities_tab').val(),
			activities_group: $('#activities_group').val()
		}
		
		var args = {
				//type: 'post',
				module: 'activities',
				action: 'checknew',
				data: data
		}

		Services.invoke(args, checknewSuccess, commandFail);
        
        //clearChecknewTimer();
        
    }
  
    
    
    
    
    function getnewSuccess(response, context) {
    	//console.log(context);
    	$(context).remove();
    	var result = $(response.html).html();
    	
    	htmlContent = $('<div />').html(result).css('display', 'none');
    	$('.activity-feed-list').prepend(htmlContent);
    	$(htmlContent).animate({ height: 'show' });
        $(htmlContent).find('.activity').effect('highlight', {}, 3000);
        //
    	
        $(document).attr('title', pageTitle);
        
        $('#last_activity').val(response.first_activities_id)
        
    	/*
    	htmlContent = $('<div />').html(newActivitiesHtml).css('display', 'none');
        $('.activity-feed-list').prepend($(htmlContent));
        $(htmlContent).animate({ height: 'show' });
        $(htmlContent).find('.activity').effect('highlight', {}, 3000);
        $(this).remove();

        newActivitiesHtml = '';
        newActivitiesCount = 0;
        $(document).attr('title', pageTitle);
        */
        
        //STX.colorboxInit(htmlContent);
        STX.colorboxInit();

    }
    
    function likeSuccess(response, context) {
		$(context).parents('.like-list').replaceWith(response.html);
	}
	
	function showLikesSuccess(response, context) {
		Dialogs.alert(response.html, "Show");
	}

	function unlikeSuccess(response, context) {
		$(context).parents('.like-list').replaceWith(response.html);
	}
    
    
    
    function checknewSuccess(response, context) {
		//console.log(response);
    	newActivitiesCount = response.new_activities_dashboard;
    	
    	new_activities_tab_at = response.new_activities_tab_at;
    	new_activities_tab_commented = response.new_activities_tab_commented;
    	new_messages = response.new_messages;
    	new_notifications = response.new_notifications;
    	
    	
    	if (newActivitiesCount != 0) {
    	
	        $(document).attr('title', '(' + newActivitiesCount + ') ' + pageTitle);
	        if ($('.new-activities-count').length > 0) {
	            $('.new-activities-count').html(response.html);
	        } else {
	            statusContainer = $('<div />').addClass('new-activities-count').html(response.html).css('display', 'none');
	            
	            statusContainer.click(function () {
	            
	            	var data = {
	        				activities_type: $('#activities_type').val(),
	        				last_activity: $('#last_activity').val(),
	        				activities_tab: $('#activities_tab').val(),
	        				activities_group: $('#activities_group').val()
	        		}
	            	
	            	var args = {
	    					//type: 'post',
	    					module: 'activities',
	    					action: 'getnew',
	    					data: data
	    				}
	
	    			Services.invoke(args, getnewSuccess, commandFail, $(this));
	            	
	            	
	            });
	            
	            $('.activity-feed-list').prepend(statusContainer);
	            $(statusContainer).animate({ height: 'show' });
	        }
    	    
    	}
    	
    	if (new_activities_tab_at != 0) {
    		if ($('.feed-navigation .at .new-items-count').length > 0) {
    			$('.feed-navigation .at .new-items-count span').text(new_activities_tab_at)
    		} else {
    			var new_items_count = $('<span />').addClass('new-items-count');
    			var new_items_count_content = $('<span />').text(new_activities_tab_at);
    			new_items_count.append(new_items_count_content);
    			$('.feed-navigation .at').append(new_items_count)
    			
    		}
    	}
    	
    	if (new_activities_tab_commented != 0) {
    		if ($('.feed-navigation .comments .new-items-count').length > 0) {
    			$('.feed-navigation .comments .new-items-count span').text(new_activities_tab_commented)
    		} else {
    			var new_items_count = $('<span />').addClass('new-items-count');
    			var new_items_count_content = $('<span />').text(new_activities_tab_commented);
    			new_items_count.append(new_items_count_content);
    			$('.feed-navigation .comments').append(new_items_count)
    			
    		}
    		
    	}
    	  	
    	if (new_messages != 0) {
    		$('#ctl00_uxHeader_lblPrivateCount').text(new_messages).css('display','block');
    		count = parseInt(new_messages) + parseInt(new_notifications);
    		$('#ctl00_uxHeader_lblTotalCount').text(count);
    	}
    	
    	if (new_notifications != 0) {    		
    		$('#ctl00_uxHeader_lblNotifCount').text(new_notifications).css('display','block');
    		count = parseInt(new_messages) + parseInt(new_notifications);
    		$('#ctl00_uxHeader_lblTotalCount').text(count);
    	}
    	if(new_messages != 0 || new_notifications != 0){
    		$('#ctl00_uxHeader_hlNotifications').addClass('full');
    	}
    	
    	setChecknewTimer();
	}
	
	function _init() {
		if ($('#activities_type').length > 0 && $('#last_activity').length > 0) {
			setChecknewTimer();
			STX.colorboxInit();
		} else {
			STX.colorboxInit();
		}
	}
    
    // --- declare public methods --- //
    return {
 
    	init: _init,
    	
        set: function(el, value, event) {
        	var editor = $(el).parents('.data-content-placeholder').find('.htmlarea textarea');
        	var token = $(el).parents('.data-content-placeholder').attr('data-token');
        	var activityContent = editor.val().trim();
        	//console.log(value);
        	
            if (activityContent == editor.data('placeholder') || activityContent == '') { 
            	activityContent = '';
            	el.data('status', 'enabled').removeClass('disabled');
            	editor.focus();
            } else {
            	
    			var data = { 
    					activities_text: activityContent,
    					activities_type: value.activities_type,
    					activities_group: value.activities_group,
    					activities_username: value.activities_username,
    					token: token
    				}
    			var args = {
    					//type: 'post',
    					module: 'activities',
    					action: 'set',
    					data: data
    				}
    			Services.invoke(args, postSuccess, commandFail, el);
            }
        },
        
        deleteActivity: function(el, value, event) {

        	Dialogs.confirm(
        			stxText('js_delete_post_confirm', 'Are you sure you want to delete this post?'), 
        			function() {
		        		var args = {
		    					//type: 'post',
		    					module: 'activities',
		    					action: 'delete',
		    					data: value
		    				}
		    			Services.invoke(args, deleteSuccess, commandFail, el);
        			}
        	);
        	
        	
        	
        },
        
        
        bookmark: function(el, value, event) {
			var args = {
					//type: 'post',
					module: 'activities',
					action: 'bookmark',
					data: value
				}
			Services.invoke(args, bookmarkSuccess, commandFail, el);
        },
        
        getMore: function(el, value, event) {
        	//console.log('loading');
        	//Dialogs.alert('Loading ...');
        	
        	
        	var args = {
					//type: 'post',
					module: 'activities',
					action: 'getall',
					data: value
				}
			Services.invoke(args, getallSuccess, commandFail, el);
        	
        },
    
        like: function(el, value, event) {
    		var args = {
					module: 'activities',
					action: 'like',
					data: value
				}
			Services.invoke(args, likeSuccess, commandFail, el);
        	
        },
        
        unlike: function(el, value, event) {
        	var args = {
					module: 'activities',
					action: 'unlike',
					data:  value
				}
			Services.invoke(args, unlikeSuccess, commandFail, el);
        },
        
        showlikes: function(el, value, event) {
        	var args = {
					module: 'activities',
					action: 'showlikes',
					data:  value
				}
			Services.invoke(args, showLikesSuccess, commandFail, el);
        },
        
        show_edit_box: function(el, value, event) {
    		var post_content_selector = $(el).parents('.activity-footer').siblings('.activity-content');
    		var text = $(post_content_selector).text();	

    		$(post_content_selector).html('<textarea id="post_edit_text" cols="50" rows="5" style="width: 100%; height: 60px;">'+ text +'</textarea>');	    		
    		$(el).replaceWith('<a href="" class="edit_post" data-role="services" data-namespace="activities" data-action="edit">Save Changes</a> <a href="" id="cancel_editing" data-role="services" data-namespace="activities" data-action="cancel">Cancel editing</a>');
    		
    		postid = $(el).data('postid');
    		posttype = $(el).data('posttype');
    	},
        
        edit: function(el, value, event) {
    		var args = {
					module: 'activities',
					action: 'edit',
					data: {activities_id: postid, activities_type: posttype, message: $('#post_edit_text').val()}
				}
			Services.invoke(args, editSuccess, commandFail, el);
        	
        },
        
        cancel: function(el, value, event){
        	var post_selector = $(el).parents('.activity-footer').siblings('.activity-content');
    		var text = $(post_selector).children('textarea').val();
    		$(post_selector).children('textarea').remove();
    		$(post_selector).text(text);
    		
    		$(el).siblings('.edit_post').replaceWith('<a href="" class="edit_post" data-role="services" data-namespace="activities" data-action="show_edit_box" data-posttype="'+ posttype +'" data-postid="'+ postid +'">Edit post</a>');
    		$(el).remove();
        }

    }
} ();

//--- declare page load events --- //
$(document).ready(function () {
    Activities.init();
});