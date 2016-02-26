$(document).ready(function() {
	var bAll = false;
	$('tr.test input[type=button]').click(function() {
		var sHost = $('#url').val();
		var jqRow = $(this).closest('tr.test');
		var rowId = parseInt(jqRow.attr('id').substr(1), 10);
		var jqResultRow = jqRow.next();
		var sVariant = jqRow.children('td:eq(1)').text();
		var sType = jqRow.children('td:eq(2)').text();
		var sUri = jqRow.children('td:eq(3)').text();
		var sAuth = jqRow.children('td:eq(4)').data('auth');
		var headers = {};
		if (sAuth != '')
			headers = {
				'x-auth': sAuth
			}
		var sData = jqRow.children('td:eq(5)').children('input').val();
		jqRow.children('td:eq(7)').html('');
		jqRow.children('td:eq(8)').text('...');
		jqRow.removeClass('ok');
		jqRow.removeClass('error');
		jqResultRow.html('');

		var dataType = 'json';
		var contentType = 'application/json; charset=utf-8';
		var processData = true;
		var formData = sData;
		if (sVariant == '+ файл') {
			formData = new FormData();
			formData.append('data', sData);
			$.each($('#picture')[0].files, function (i, file) {
				formData.append('file-' + i, file);
			});
			dataType = 'text';
			contentType = false;
			processData = false;
		}

		$.ajax({
			url: sHost + sUri,
			type: sType,
			data: formData,
			headers: headers,
			dataType: dataType,
			contentType: contentType,
			processData: processData,
			complete: function(response){
				jqRow.children('td:eq(7)').html('<a href="#">Ответ</a>');
				jqRow.children('td:eq(8)').text(response.status);
				if (response.status == jqRow.children('td:eq(8)').data('need')) {
					jqRow.addClass('ok');
				}
				else {
					jqRow.addClass('error');
				}
				var sText = response.responseText ? response.responseText : '(пустая строка)';
				jqResultRow.html('<td colspan="9">' + sText + '</td>');

				if (bAll) {
					var jqNext = jqResultRow.next();
					if (jqNext.length) {
						jqNext.find('input[type=button]').click();
					}
					else {
						bAll = false;
					}
				}
				if (response.responseText.length > 4) {
					var jsonData = jQuery.parseJSON(response.responseText);
					console.log(jsonData);
					if (sUri == '/auth/phone' && sData == '{"phone":"79170010203"}') {
						jqRow.siblings('#r' + (rowId + 2)).children('td:eq(5)').children('input').val('{"phone":"79170010203","code":"0001","user":' +  jsonData.user + ',"device":{"uuid":"0a89df6v7df6sv7r6s07f","x":320,"y":480}}');
						jqRow.siblings('#r' + (rowId + 3)).children('td:eq(5)').children('input').val('{"phone":"79170010203","code":"' + jsonData.sms + '","user":' +  jsonData.user + ',"device":{"uuid":"0a89df6v7df6sv7r6s07f","x":320,"y":480}}');
					}
					if (sUri == '/auth/verify' && jsonData.token) {
						jqRow.siblings().each(function() {
							var td = $(this).children('td:eq(4)');
							if (td.data('na') == '1') {
								td.data('auth', jsonData.token);
								td.html('<b>' + jsonData.token.substr(0, 6) + '...</b>');
							}
						});
					}
				}
			}
		});
		return false;
	});
	$('tr.test').on('click', 'a', function() {
		var jqRow = $(this).closest('tr.test');
		var jqResultRow = jqRow.next();
		jqResultRow.toggleClass('hidden');
		return false;
	});
	$('#test_all').on('click', function() {
		bAll = true;
		var jqBtn = $('tr.test:first input[type=button]');
		jqBtn.click();
		return false;
	});
	$('#del_test_user').on('click', function() {
		var sHost = $('#url').val();
		$.ajax({
			url: sHost + '/del_test_user.php',
			type: 'GET',
			cache: false
		});
		return false;
	});
});