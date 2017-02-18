
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
		Chat.ws = new WebSocket("ws://109.197.195.38:2346/admin/");
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
		console.log(data);
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
			console.log(key);
			console.log(val);
			var data = {
				'key': key,
				'message': val
			}
			Chat.ws.send(JSON.stringify(data));
		}
		return false;
	}
};

$(function(){
	Chat.init();
});