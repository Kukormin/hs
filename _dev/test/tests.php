<?

$arTests = array(
	array(
		'NAME' => 'Ошибка в url',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/url',
		'DATA' => '',
		'NEED' => 404,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Авторизация по телефону',
		'VAR' => 'пользователь заблокирован',
		'METHOD' => 'POST',
		'URI' => '/auth/phone',
		'DATA' => '{"phone":"79171111111"}',
		'NEED' => 403,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Авторизация по телефону',
		'VAR' => 'не задан телефон',
		'METHOD' => 'POST',
		'URI' => '/auth/phone',
		'DATA' => '{}',
		'NEED' => 400,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Авторизация по телефону',
		'VAR' => 'ошибка в формате',
		'METHOD' => 'POST',
		'URI' => '/auth/phone',
		'DATA' => '{"phone":"9170010203"}',
		'NEED' => 400,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Авторизация по телефону',
		'VAR' => '',
		'METHOD' => 'POST',
		'URI' => '/auth/phone',
		'DATA' => '{"phone":"79170010203"}',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Ввод кода из sms',
		'VAR' => 'Неправильный код',
		'METHOD' => 'POST',
		'URI' => '/auth/verify',
		'DATA' => '{"phone":"79170010203","code":"0001","user":0,"device":{"uuid":"0a89df6v7df6sv7r6s07f","x":320,"y":480}}',
		'NEED' => 400,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Ввод кода из sms',
		'VAR' => 'Неправильный id пользователя',
		'METHOD' => 'POST',
		'URI' => '/auth/verify',
		'DATA' => '{"phone":"79170010203","code":"0001","user":0,"device":{"uuid":"0a89df6v7df6sv7r6s07f","x":320,"y":480}}',
		'NEED' => 400,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Ввод кода из sms',
		'VAR' => '',
		'METHOD' => 'POST',
		'URI' => '/auth/verify',
		'DATA' => '{"phone":"79170010203","code":"0001","user":0,"device":{"uuid":"0a89df6v7df6sv7r6s07f","x":320,"y":480}}',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Профиль пользователя',
		'VAR' => 'Ошибка авторизации',
		'METHOD' => 'GET',
		'URI' => '/user/profile',
		'DATA' => '',
		'NEED' => 401,
		'AUTH' => 'd34a628abc31',
	),
	array(
		'NAME' => 'Профиль пользователя',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/user/profile',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Проверка Никнейма',
		'VAR' => '',
		'METHOD' => 'POST',
		'URI' => '/user/nickname',
		'DATA' => '{"nickname":"Pinkpanter"}',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Редактирование профиля',
		'VAR' => 'Ошибка авторизации',
		'METHOD' => 'POST',
		'URI' => '/user/update',
		'DATA' => '{"name":"Елизавета Смирнова","city":"Москва","nickname":"Pinkpanter","email":"pinkpanter@mail.ru"}',
		'NEED' => 401,
		'AUTH' => 'd34a628abc31',
	),
	array(
		'NAME' => 'Редактирование профиля',
		'VAR' => '+ файл',
		'METHOD' => 'POST',
		'URI' => '/user/update',
		'DATA' => '{"name":"Елизавета Смирнова","city":"Москва","nickname":"Pinkpanter","email":"pinkpanter@mail.ru","gender":"w","address":{"street":"ул.Ленина, д.12, стр.1","flat":"34","index":"523057","fio":"Елизавета Смирнова"},
		"brands":[80,81,82],"sections":[10,18,19],"sizes":[58,68]}',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Редактирование профиля',
		'VAR' => 'никнейм занят',
		'METHOD' => 'POST',
		'URI' => '/user/update',
		'DATA' => '{"nickname":"Whitepanter"}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Подписка',
		'VAR' => 'Не задан "издатель"',
		'METHOD' => 'POST',
		'URI' => '/user/follow',
		'DATA' => '{}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Подписка',
		'VAR' => 'Не найден "издатель"',
		'METHOD' => 'POST',
		'URI' => '/user/follow',
		'DATA' => '{"publisher":1}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Подписка',
		'VAR' => 'Уже подписан',
		'METHOD' => 'POST',
		'URI' => '/user/follow',
		'DATA' => '{"publisher":49}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Подписка',
		'VAR' => '',
		'METHOD' => 'POST',
		'URI' => '/user/follow',
		'DATA' => '{"publisher":1192}',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Отписка',
		'VAR' => 'Не задан "издатель"',
		'METHOD' => 'POST',
		'URI' => '/user/unfollow',
		'DATA' => '{}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Отписка',
		'VAR' => 'Не найден "издатель"',
		'METHOD' => 'POST',
		'URI' => '/user/unfollow',
		'DATA' => '{"publisher":1}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Отписка',
		'VAR' => 'Не подписан',
		'METHOD' => 'POST',
		'URI' => '/user/unfollow',
		'DATA' => '{"publisher":1193}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Отписка',
		'VAR' => '',
		'METHOD' => 'POST',
		'URI' => '/user/unfollow',
		'DATA' => '{"publisher":1192}',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'FAQ',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/faq',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Состояния (товара)',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/catalog/condition',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Цвета',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/catalog/color',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Разделы каталога',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/catalog/section',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Размеры',
		'VAR' => 'все',
		'METHOD' => 'GET',
		'URI' => '/catalog/size',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Размеры',
		'VAR' => 'для указанного раздела',
		'METHOD' => 'GET',
		'URI' => '/catalog/size/16',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Размеры',
		'VAR' => 'несуществующий раздел',
		'METHOD' => 'GET',
		'URI' => '/catalog/size/1',
		'DATA' => '',
		'NEED' => 404,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Размеры',
		'VAR' => 'раздел без размеров',
		'METHOD' => 'GET',
		'URI' => '/catalog/size/13',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Способы оплаты',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/catalog/payment',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Способы отправки',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/catalog/delivery',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Пол',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/catalog/gender',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Бренды',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/catalog/brand',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Добавление бренда',
		'VAR' => 'Ошибка авторизации',
		'METHOD' => 'POST',
		'URI' => '/catalog/addbrand',
		'DATA' => '{"name":"Новый бренд"}',
		'NEED' => 401,
		'AUTH' => 'd34a628abc31',
	),
	array(
		'NAME' => 'Добавление бренда',
		'VAR' => 'Не задано название',
		'METHOD' => 'POST',
		'URI' => '/catalog/addbrand',
		'DATA' => '{"name":""}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Добавление бренда',
		'VAR' => 'Уже есть',
		'METHOD' => 'POST',
		'URI' => '/catalog/addbrand',
		'DATA' => '{"name":"Versace"}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Добавление бренда',
		'VAR' => '',
		'METHOD' => 'POST',
		'URI' => '/catalog/addbrand',
		'DATA' => '{"name":"Новый бренд"}',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Добавление объявления',
		'VAR' => 'Ошибка авторизации',
		'METHOD' => 'POST',
		'URI' => '/ad/add',
		'DATA' => '',
		'NEED' => 401,
	    'AUTH' => 'd34a628abc31',
	),
	array(
		'NAME' => 'Добавление объявления',
		'VAR' => 'Ошибки в данных',
		'METHOD' => 'POST',
		'URI' => '/ad/add',
		'DATA' => '{"section":0,"brand":0,"condition":0,"color":0,"size":0,"material":"","features":"","purchase":0,"price":0,"payment":[""],"delivery":{}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Добавление объявления',
		'VAR' => '+ файл',
		'METHOD' => 'POST',
		'URI' => '/ad/add',
		'DATA' => '{"section":17,"brand":474,"condition":13,"color":15,"size":65,"material":"кожа","features":"2 раза носил","purchase":20000,"price":10000,"payment":["agreement","application"],"delivery":{"personal":0,"courier":500,"post":400}}',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Список объявлений',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/ad/list',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Список объявлений',
		'VAR' => 'параметры постранички',
		'METHOD' => 'GET',
		'URI' => '/ad/list',
		'DATA' => 'max=1202&count=3',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Список объявлений',
		'VAR' => 'фильтр по пользователям',
		'METHOD' => 'GET',
		'URI' => '/ad/list',
		'DATA' => 'user[]=54',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Список объявлений',
		'VAR' => 'фильтр по категориям',
		'METHOD' => 'GET',
		'URI' => '/ad/list',
		'DATA' => 'section[]=17&section[]=19',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Список объявлений',
		'VAR' => 'фильтр по брендам',
		'METHOD' => 'GET',
		'URI' => '/ad/list',
		'DATA' => 'brand[]=474&brand[]=98',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Список объявлений',
		'VAR' => 'фильтр по состоянию',
		'METHOD' => 'GET',
		'URI' => '/ad/list',
		'DATA' => 'condition[]=11&condition[]=12',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Список объявлений',
		'VAR' => 'фильтр по цвету',
		'METHOD' => 'GET',
		'URI' => '/ad/list',
		'DATA' => 'color[]=26&color[]=20',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Список объявлений',
		'VAR' => 'фильтр по размерам',
		'METHOD' => 'GET',
		'URI' => '/ad/list',
		'DATA' => 'size[]=58&size[]=65',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Список объявлений',
		'VAR' => 'фильтр по цене',
		'METHOD' => 'GET',
		'URI' => '/ad/list',
		'DATA' => 'price_from=2000&price_to=5000',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Список объявлений',
		'VAR' => 'фильтр по оплате',
		'METHOD' => 'GET',
		'URI' => '/ad/list',
		'DATA' => 'payment=agreement',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Список объявлений',
		'VAR' => 'фильтр по доставке',
		'METHOD' => 'GET',
		'URI' => '/ad/list',
		'DATA' => 'delivery[]=personal&delivery[]=courier',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Список объявлений',
		'VAR' => 'разные фильты',
		'METHOD' => 'GET',
		'URI' => '/ad/list',
		'DATA' => 'user[]=54&section[]=17&section[]=19&brand[]=474&brand[]=98&delivery[]=personal&condition[]=11&condition[]=13&size[]=58&size[]=65',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Лента "Всё и сразу"',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/feed',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Лента "Всё и сразу"',
		'VAR' => 'параметры постранички',
		'METHOD' => 'GET',
		'URI' => '/feed',
		'DATA' => 'max=1209&count=1',
		'NEED' => 200,
		'AUTH' => '',
	),
);