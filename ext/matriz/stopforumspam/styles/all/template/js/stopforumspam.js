var stopforumspam = {
	'init': function() {
		$.ajax({
			'url': 'https://www.stopforumspam.com/api?' + stopforumspam_config.p + '&f=jsonp&callback=?',
			'dataType': 'jsonp',
			'jsonpCallback': 'matriz_stopforumspam_jsonp_callback',
			'success': function(res) {
				if (res && res.success && res.success == 1) {
					var l = res.email.length, i = 0,
						users = stopforumspam_config.users_json, users_l = users.length, j = 0,
						ban_emails = [], td = null;
					for (i = 0; i < l; i++) {
						for (j = 0; j < users_l; j++) {
							if (users[j].USER_EMAIL == res.email[i].value) {
								td = $('#sfs_result_' + users[j].USER_ID);
								if (td.length > 0) {
									td.removeClass('fontelico-spin4');
									if (res.email[i].appears == 1) {
										td.addClass('fontelico-emo-angry').css('color', '#F00').append($('<a />').attr('href', '#').addClass('button2').click(function() {
											var a = $(this);
											stopforumspam.deleteUser(a.data('user_id'), a.closest('tr'));
											return false;
										}).data('user_id', users[j].USER_ID).text(stopforumspam_config.button_text));
										ban_emails.push(res.email[i]['value']);
									} else {
										td.addClass('fontelico-emo-saint');
										if (stopforumspam_config.api_key != '') {
											td.append($('<a />').attr('href', '#').addClass('button2').click(function() {
												var a = null;
												if (confirm(stopforumspam_config.confirm_text)) {
													a = $(this);
													stopforumspam.sendSpamData(a.data('user_id'), a.data('username'), a.data('user_email'), a.data('user_ip'), a.closest('tr'));
												}
												return false;
											}).data({
												'user_id': users[j].USER_ID,
												'username': users[j].USERNAME,
												'user_email': users[j].USER_EMAIL,
												'user_ip': users[j].USER_IP
											}).text(stopforumspam_config.button_text));
										}
									}
								}
								break;
							}
						}
					}
				}
			}
		});
	},
	'deleteUser': function(user_id, tr) {
		$.ajax({
			'url': stopforumspam_config.users_delete_url,
			'type': 'post',
			'data': {
				'delete_user': user_id
			},
			'dataType': 'json',
			'success': function(res) {
				if (res && res.ok && res.ok == 1) {
					$(tr).css('opacity', 0.5);
				}
			}
		});
	},
	'sendSpamData': function(user_id, username, email, ip, tr) {
		$.ajax({
			'url': 'https://www.stopforumspam.com/add.php',
			'type': 'post',
			'data': {
				'username': username,
				'ip_addr': ip,
				'email': email,
				'api_key': stopforumspam_config.api_key
			},
			'complete': function() {
				stopforumspam.deleteUser(user_id, tr);
			}
		});
	}
};

$(document).ready(function() {
	stopforumspam.init();
});