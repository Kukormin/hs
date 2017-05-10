
var Chat = {
	oid: 0,
	init: function() {
		this.statusDiv = $('#ws_status');
		if (!this.statusDiv.length)
			return false;

		this.ul = $('#chats-menu');
		this.cont = $('#messages-cont');

		var a = this.ul.find('li.active a');
		this.loadMessages(a);

		this.connect();

		this.statusDiv.click(Chat.test);
		this.cont.on('keypress', 'textarea', Chat.kp);
		this.cont.on('click', '.hero-unit .btn', Chat.newChat);
		this.ul.on('click', 'li a', Chat.menuClick);
	},
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
	connect: function() {
		Chat.ws = new WebSocket("ws://hi-shopper-app.ru:2346/admin/");
		//Chat.ws = new WebSocket("ws://" + location.host + ":2346/admin/");
		Chat.ws.onopen = Chat.onOpen;
		Chat.ws.onclose = Chat.onClose;
		Chat.ws.onmessage = Chat.onMessage;
	},
	onOpen: function() {
		Chat.statusDiv.addClass('connected');
		Chat.statusDiv.attr('title', 'Установлено соединение с сервером');
	},
	onClose: function() {
		Chat.statusDiv.removeClass('connected');
		Chat.statusDiv.attr('title', 'Соединение с сервером отсутствует');
		setTimeout(Chat.connect, 10000);
	},
	onMessage: function(data) {
		var message = JSON.parse(data.data);
		if (message.type == 'new') {
			var oid = message.oid;
			if (oid == Chat.oid) {
				var div = $('#chat' + message.suffix);
				if (div.length) {
					var userName = 'Служба поддержки';
					var cl = 'support';
					if (message.user) {
						userName = message.nickname;
						cl = message.role == 1 ? 'seller' : 'buyer';
					}

					var html = '<dl class="' + cl + '">' +
						'<dt>[' + message.datef + '] <b>' + userName + '</b></dt><dd>' + message.message + '</dd></dl>';
					div.prepend(html);
				}
			}
			Chat.goChat(oid, false);
		}
	},
	newChat: function() {
		var oid = $(this).data('oid');
		Chat.goChat(oid, true);
	},
	goChat: function(oid, activate) {
		var a = Chat.ul.find('a[data-id=' + oid + ']');
		if (a.length) {
			var li = a.parent();
			li.prependTo(Chat.ul);
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
	test: function() {
		var d = new Date();
		var s = d.getSeconds();
		Chat.ws.send('Проверка ' + s);
		Chat.ws.send('Еще ' + s);
	},
	kp: function(e) {
		var code = (e.keyCode) ? e.keyCode : e.which;
		if (code == 13)
		{
			var ta = $(this);
			var val = ta.val();
			if (e.ctrlKey)
				ta.val(val + "\n");
			else {
				var form = $(this).closest('form');
				var key = form.find('input[name=KEY]').val();
				if (val) {
					var data = {
						'key': key,
						'message': val
					};
					Chat.ws.send(JSON.stringify(data));
					ta.val('');

					return false;
				}
			}
		}
	}
};

$(function(){
	Chat.init();
});