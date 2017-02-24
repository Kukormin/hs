
var Chat = {
	init: function() {
		this.statusDiv = $('#ws_status');
		if (!this.statusDiv.length)
			return false;

		this.connect();

		this.statusDiv.click(Chat.test);
		$('.chat_form').on('submit', Chat.message);
	},
	connect: function() {
		Chat.ws = new WebSocket("ws://" + location.host + ":2346/admin/");
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
			var div =  $('#chat' + message.suffix + ' .chat');
			if (div.length) {
				var cl = 'support';
				var userName = 'Служба поддержки';
				var userId = message.user;
				if (userId) {
					var url = '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=9&type=user&ID=' + userId +
						'&lang=ru&find_section_section=-1';
					userName = '<a href="' + url + '">' + message.nickname + '</a> (' + message.name + ')';
					cl = message.role == 1 ? 'seller' : 'buyer';
				}

				var html = '<dl class="' + cl + '">' +
					'<dt>[' + message.datef + '] <b>' + userName + '</b></dt><dd>' + message.message + '</dd></dl>';
				div.append(html);
			}
			else {
				$('#tbl_chats_filterset_filter').click();
			}
		}
	},
	test: function() {
		var d = new Date();
		var s = d.getSeconds();
		Chat.ws.send('Проверка ' + s);
		Chat.ws.send('Еще ' + s);
	},
	message: function() {
		var form = $(this);
		var div = form.closest('.adm-detail-content');
		var ta = div.find('textarea');
		var val = ta.val();
		var key = div.find('input[name=KEY]').val();
		if (val) {
			var data = {
				'key': key,
				'message': val
			}
			Chat.ws.send(JSON.stringify(data));
			ta.val('');
		}
		return false;
	}
};

$(function(){
	Chat.init();
});