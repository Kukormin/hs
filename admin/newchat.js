
var Chat = {
	oid: 0,
	init: function() {
		this.ul = $('#chats-menu');
		this.cont = $('#messages-cont');
		this.max = this.cont.data('max');

		// Загрузка первого чата
		var a = this.ul.find('li.active a');
		this.loadMessages(a);

		// Отправка сообщения при нажатии на Enter
		this.cont.on('keypress', 'textarea', Chat.kp);
		// После жалобы на объявление - начать чат с продавцом
		this.cont.on('click', '.hero-unit .btn', Chat.newChat);
		// Переход к выбранному чату
		this.ul.on('click', 'li a', Chat.menuClick);

		setInterval(this.checkUpdates, 2000);
	},
	// Переход к выбранному чату
	menuClick: function() {
		var a = $(this);
		var li = a.parent();
		Chat.activateLi(li, a);

		return false;
	},
	activateLi: function(li, a) {
		if (!li.is('.active'))
		{
			li.addClass('active').siblings('.active').removeClass('active');
			Chat.loadMessages(a);
		}
	},
	loadMessages: function(a) {
		var id = a.data('id');
		$.post('/admin/messages.php', {
			'id': id
		}, function (html) {
			Chat.oid = id;
			Chat.cont.html(html);

			return false;
		});
	},
	// После жалобы на объявление - начать чат с продавцом
	newChat: function() {
		var oid = $(this).data('oid');
		Chat.goChat(oid, true, false);
	},
	goChat: function(oid, activate, hideIcon) {
		var a = Chat.ul.find('a[data-id=' + oid + ']');
		if (a.length) {
			var li = a.parent();
			li.prependTo(Chat.ul);
			if (hideIcon && a.hasClass('na'))
				a.removeClass('na');
			if (!hideIcon && !a.hasClass('na'))
				a.addClass('na');
			if (activate)
				Chat.activateLi(li, a);
		}
		else {
			$.post('/admin/get_li.php', {
				'id': oid
			}, function (html) {
				li = $(html);
				li.prependTo(Chat.ul);
				var a = li.children('a');
				if (activate)
					Chat.activateLi(li, a);
				return false;
			});
		}
	},
	// Отправка сообщения при нажатии на Enter
	kp: function(e) {
		var code = (e.keyCode) ? e.keyCode : e.which;
		if (code === 13)
		{
			var ta = $(this);
			var message = ta.val();
			if (e.ctrlKey)
				ta.val(message + "\n");
			else {
				var form = $(this).closest('form');
				var key = form.find('input[name=KEY]').val();
				var div = form.find('.chat');
				if (message) {
					Chat.send(key, message, div);
					ta.val('');

					return false;
				}
			}
		}
	},
	send: function(key, message, div) {
		$.post('/admin/send.php', {
			'key': key,
			'message': message
		}, function (html) {
			if (html) {
				div.prepend(html);
				Chat.ul.find('li.active a').removeClass('na');
			}
		});
	},
	checkUpdates: function() {
		$.post('/admin/check_menu.php', {}, function (html) {
			var max = parseInt(html);
			if (max > Chat.max) {
				$.post('/admin/update.php', {
					'id': Chat.oid
				}, function (data) {
					for (var key in data) {
						if (data.hasOwnProperty(key)) {
							if (key === 'MENU')
								Chat.ul.html(data[key]);
							else if (key === 'MAX')
								Chat.max = data[key];
							else {
								var chatId = '#chat' + key;
								var div = $(chatId);
								div.html(data[key]);
							}
						}
					}
				});
			}
		});
	}
};

$(function(){
	Chat.init();
});