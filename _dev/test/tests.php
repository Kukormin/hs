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
		'NAME' => 'Авторизация по телефону',
		'VAR' => 'Заглушка (без отправки смс)',
		'METHOD' => 'POST',
		'URI' => '/auth/phone_debug',
		'DATA' => '{"phone":"79170010203"}',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Ввод кода из sms',
		'VAR' => 'Неправильный код',
		'METHOD' => 'POST',
		'URI' => '/auth/verify',
		'DATA' => '{"phone":"79170010203","code":"0001","user":0,"device":{"uuid":"0a89df6v7df6sv7r6s07f","pt":"df79b6sd8fbg6","x":320,"y":480}}',
		'NEED' => 400,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Ввод кода из sms',
		'VAR' => 'Неправильный id пользователя',
		'METHOD' => 'POST',
		'URI' => '/auth/verify',
		'DATA' => '{"phone":"79170010203","code":"0001","user":0,"device":{"uuid":"0a89df6v7df6sv7r6s07f","pt":"df79b6sd8fbg6","x":320,"y":480}}',
		'NEED' => 400,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Ввод кода из sms',
		'VAR' => '',
		'METHOD' => 'POST',
		'URI' => '/auth/verify',
		'DATA' => '{"phone":"79170010203","code":"0001","user":0,"device":{"uuid":"0a89df6v7df6sv7r6s07f","pt":"df79b6sd8fbg6","x":320,"y":480}}',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Добавление пуш токена',
		'VAR' => '',
		'METHOD' => 'POST',
		'URI' => '/auth/setpt',
		'DATA' => '{"pt":"df79b6sd8fbg6"}',
		'NEED' => 200,
		'AUTH' => 'x',
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
		'NAME' => 'Публичный профиль пользователя',
		'VAR' => '(другого пользователя)',
		'METHOD' => 'GET',
		'URI' => '/user/public/49',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Сделки в публичном профиле',
		'VAR' => '(в самом профиле возвращается только первая страница сделок)',
		'METHOD' => 'GET',
		'URI' => '/user/public/49/deals',
		'DATA' => 'max=3&count=2',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Объявления в публичном профиле',
		'VAR' => '(в самом профиле возвращается только первая страница)',
		'METHOD' => 'GET',
		'URI' => '/user/public/49/ads',
		'DATA' => 'max=3&count=2',
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
		'DATA' => '{"name":"Елизавета Смирнова","city":"Москва","nickname":"Pinkpanter","email":"pinkpanter@mail.ru","gender":"w","address":{"street":"ул.Ленина, д.12, стр.1","flat":"34","index":"523057","fio":"Елизавета Смирнова"},"brands":[80,81,82],"sections":[18,19],"sizes":[58,68]}',
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
		'NAME' => 'Редактирование профиля',
		'VAR' => 'удаление фото',
		'METHOD' => 'POST',
		'URI' => '/user/update',
		'DATA' => '{"photo":"delete"}',
		'NEED' => 200,
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
		'DATA' => '{"publisher":5891}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Подписка',
		'VAR' => '',
		'METHOD' => 'POST',
		'URI' => '/user/follow',
		'DATA' => '{"publisher":6799}',
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
		'VAR' => '',
		'METHOD' => 'POST',
		'URI' => '/user/unfollow',
		'DATA' => '{"publisher":6799}',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Отписка',
		'VAR' => 'Не подписан',
		'METHOD' => 'POST',
		'URI' => '/user/unfollow',
		'DATA' => '{"publisher":6799}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Добавление в избранное',
		'VAR' => 'Не задано объявление',
		'METHOD' => 'POST',
		'URI' => '/user/favorite/add',
		'DATA' => '{}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Добавление в избранное',
		'VAR' => 'Не найдено объявление',
		'METHOD' => 'POST',
		'URI' => '/user/favorite/add',
		'DATA' => '{"ad":1}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Добавление в избранное',
		'VAR' => '',
		'METHOD' => 'POST',
		'URI' => '/user/favorite/add',
		'DATA' => '{"ad":6790}',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Добавление в избранное',
		'VAR' => 'Уже добавлено',
		'METHOD' => 'POST',
		'URI' => '/user/favorite/add',
		'DATA' => '{"ad":6790}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Удаление из избранного',
		'VAR' => 'Не задано объявление',
		'METHOD' => 'POST',
		'URI' => '/user/favorite/remove',
		'DATA' => '{}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Удаление из избранного',
		'VAR' => 'Не найдено объявление',
		'METHOD' => 'POST',
		'URI' => '/user/favorite/remove',
		'DATA' => '{"ad":1}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Удаление из избранного',
		'VAR' => '',
		'METHOD' => 'POST',
		'URI' => '/user/favorite/remove',
		'DATA' => '{"ad":6790}',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Удаление из избранного',
		'VAR' => 'Не в избранном',
		'METHOD' => 'POST',
		'URI' => '/user/favorite/remove',
		'DATA' => '{"ad":6790}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Избранное',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/user/favorite/list',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Избранное',
		'VAR' => 'параметры постранички',
		'METHOD' => 'GET',
		'URI' => '/user/favorite/list',
		'DATA' => 'max=3&count=2',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Количество в избранном',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/user/favorite/count',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Поиск пользователей',
		'VAR' => '(по Никнейму)',
		'METHOD' => 'GET',
		'URI' => '/user/search',
		'DATA' => 'q=panter',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Поиск пользователей',
		'VAR' => 'только общее количество',
		'METHOD' => 'GET',
		'URI' => '/user/search',
		'DATA' => 'q=panter&type=count',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Поиск пользователей',
		'VAR' => 'короткий запрос',
		'METHOD' => 'GET',
		'URI' => '/user/search',
		'DATA' => 'q=pi',
		'NEED' => 400,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Поиск пользователей',
		'VAR' => 'параметры постранички',
		'METHOD' => 'GET',
		'URI' => '/user/search',
		'DATA' => 'q=panter&max=6549&count=1',
		'NEED' => 200,
		'AUTH' => '',
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
		'NAME' => 'Настройки',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/options',
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
		'URI' => '/catalog/size/151',
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
		'URI' => '/catalog/size/346',
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
		'VAR' => '(успешное добавление - заглушка)',
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
		'DATA' => '{"section":0,"brand":0,"gender":"x","condition":0,"color":0,"size":0,"material":"","features":"","purchase":0,"price":0,"payment":[""],"delivery":{}}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Добавление объявления',
		'VAR' => '+ файл',
		'METHOD' => 'POST',
		'URI' => '/ad/add',
		'DATA' => '{"section":213,"brand":474,"gender":"w","condition":13,"color":15,"size":70,"material":"кожа","features":"2 раза носил","purchase":20000,"price":10000,"payment":["agreement","application"],"delivery":["personal","courier","post"],"test_photo":true}',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Редактирование объявления',
		'VAR' => 'не задано объявление',
		'METHOD' => 'POST',
		'URI' => '/ad/update',
		'DATA' => '{}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Редактирование объявления',
		'VAR' => 'не найдено объявление',
		'METHOD' => 'POST',
		'URI' => '/ad/update',
		'DATA' => '{"ad":1}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Редактирование объявления',
		'VAR' => 'не свое объявление',
		'METHOD' => 'POST',
		'URI' => '/ad/update',
		'DATA' => '{"ad":6753}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Редактирование объявления',
		'VAR' => '(если фотки не поменялись, то лучше не передавать)',
		'METHOD' => 'POST',
		'URI' => '/ad/update',
		'DATA' => '{"ad":6817,"section":213,"brand":474,"condition":13,"color":15,"size":70,"material":"кожа","features":"2 раза носил","purchase":20000,"price":10000,"payment":["agreement","application"],"delivery":["personal","courier","post"]}',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Редактирование объявления',
		'VAR' => '+ файл',
		'METHOD' => 'POST',
		'URI' => '/ad/update',
		'DATA' => '{"ad":6817,"section":213,"brand":474,"condition":13,"color":15,"size":70,"material":"кожа","features":"2 раза носил","purchase":20000,"price":10000,"payment":["agreement","application"],"delivery":["personal","courier","post"]}',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Удаление объявления',
		'VAR' => 'не задано объявление',
		'METHOD' => 'POST',
		'URI' => '/ad/delete',
		'DATA' => '{}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Удаление объявления',
		'VAR' => 'не найдено объявление',
		'METHOD' => 'POST',
		'URI' => '/ad/delete',
		'DATA' => '{"ad":1}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Удаление объявления',
		'VAR' => 'не свое объявление',
		'METHOD' => 'POST',
		'URI' => '/ad/delete',
		'DATA' => '{"ad":6753}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Удаление объявления',
		'VAR' => 'активная сделка',
		'METHOD' => 'POST',
		'URI' => '/ad/delete',
		'DATA' => '{"ad":6753}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Удаление объявления',
		'VAR' => '',
		'METHOD' => 'POST',
		'URI' => '/ad/delete',
		'DATA' => '{"ad":x}',
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
		'VAR' => 'фильтр по полу',
		'METHOD' => 'GET',
		'URI' => '/ad/list',
		'DATA' => 'gender[]=w&gender[]=c',
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
		'VAR' => 'фильтр по отсутствию сделок',
		'METHOD' => 'GET',
		'URI' => '/ad/list',
		'DATA' => 'can_buy=Y',
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
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Лента "Всё и сразу"',
		'VAR' => 'параметры постранички',
		'METHOD' => 'GET',
		'URI' => '/feed',
		'DATA' => 'max=1209&count=1',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Причины жалоб',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/claim/reasons',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Отправка жалобы',
		'VAR' => '',
		'METHOD' => 'POST',
		'URI' => '/claim/add',
		'DATA' => '{"ad":6753,"reason":1220}',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Добавление комментария',
		'VAR' => 'Не задано объявление',
		'METHOD' => 'POST',
		'URI' => '/ad/comment',
		'DATA' => '{"ad":0,"message":"1"}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Добавление комментария',
		'VAR' => 'Не найдено объявление',
		'METHOD' => 'POST',
		'URI' => '/ad/comment',
		'DATA' => '{"ad":1,"message":"2"}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Добавление комментария',
		'VAR' => 'Пустое сообщение',
		'METHOD' => 'POST',
		'URI' => '/ad/comment',
		'DATA' => '{"ad":6753,"message":""}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Добавление комментария',
		'VAR' => 'ненормативная лексика',
		'METHOD' => 'POST',
		'URI' => '/ad/comment',
		'DATA' => '{"ad":6753,"message":"Будь мужиком, блеать!"}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Добавление комментария',
		'VAR' => '',
		'METHOD' => 'POST',
		'URI' => '/ad/comment',
		'DATA' => '{"ad":6753,"message":"Очень красивое! А что за бренд?"}',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Комментарии',
		'VAR' => 'Не задано объявление',
		'METHOD' => 'GET',
		'URI' => '/ad/comments',
		'DATA' => '',
		'NEED' => 400,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Комментарии',
		'VAR' => 'Не найдено объявление',
		'METHOD' => 'GET',
		'URI' => '/ad/comments/1',
		'DATA' => '',
		'NEED' => 400,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Комментарии',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/ad/comments/6753',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Комментарии',
		'VAR' => 'параметры постранички',
		'METHOD' => 'GET',
		'URI' => '/ad/comments/6753',
		'DATA' => 'max=1222&count=1',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Объявление',
		'VAR' => 'Не задано объявление',
		'METHOD' => 'GET',
		'URI' => '/ad/detail',
		'DATA' => '',
		'NEED' => 400,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Объявление',
		'VAR' => 'Не найдено объявление',
		'METHOD' => 'GET',
		'URI' => '/ad/detail/1',
		'DATA' => '',
		'NEED' => 400,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Объявление',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/ad/detail/6753',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Поделиться объявлением',
		'VAR' => 'Не задано объявление',
		'METHOD' => 'POST',
		'URI' => '/ad/share',
		'DATA' => '{}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Поделиться объявлением',
		'VAR' => 'Не найдено объявление',
		'METHOD' => 'POST',
		'URI' => '/ad/share',
		'DATA' => '{"ad":1}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Поделиться объявлением',
		'VAR' => 'Свое объявление',
		'METHOD' => 'POST',
		'URI' => '/ad/share',
		'DATA' => '{"ad":6817}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Поделиться объявлением',
		'VAR' => '',
		'METHOD' => 'POST',
		'URI' => '/ad/share',
		'DATA' => '{"ad":6753}',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Поделиться в соцсети',
		'VAR' => '',
		'METHOD' => 'POST',
		'URI' => '/ad/social',
		'DATA' => '{"ad":6753,"sn":"facebook"}',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Новости',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/user/news',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Новости',
		'VAR' => 'параметры постранички',
		'METHOD' => 'GET',
		'URI' => '/user/news',
		'DATA' => 'max=1256&count=3',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Мои объявления',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/user/myads',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Мои объявления',
		'VAR' => 'другие объявления',
		'METHOD' => 'GET',
		'URI' => '/user/myads/ads',
		'DATA' => 'max=1256&count=3',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Мои объявления',
		'VAR' => 'другие сделки',
		'METHOD' => 'GET',
		'URI' => '/user/myads/deals',
		'DATA' => 'max=1256&count=3',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Статусы сделки',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/deal/statuses',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => '',
	),
	array(
		'NAME' => 'Новая сделка',
		'VAR' => 'не указан способ оплаты',
		'METHOD' => 'POST',
		'URI' => '/deal/add',
		'DATA' => '{}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Новая сделка',
		'VAR' => 'не указан способ доставки',
		'METHOD' => 'POST',
		'URI' => '/deal/add',
		'DATA' => '{"payment":"application"}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Новая сделка',
		'VAR' => 'Не задано объявление',
		'METHOD' => 'POST',
		'URI' => '/deal/add',
		'DATA' => '{"payment":"application","delivery":"post"}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Новая сделка',
		'VAR' => 'Не найдено объявление',
		'METHOD' => 'POST',
		'URI' => '/deal/add',
		'DATA' => '{"payment":"application","delivery":"post","ads":[1]}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Новая сделка',
		'VAR' => 'Своё объявление',
		'METHOD' => 'POST',
		'URI' => '/deal/add',
		'DATA' => '{"payment":"application","delivery":"post","ads":[6817]}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Новая сделка',
		'VAR' => 'уже содержит сделку',
		'METHOD' => 'POST',
		'URI' => '/deal/add',
		'DATA' => '{"payment":"application","delivery":"post","ads":[6721]}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Новая сделка',
		'VAR' => 'продавец не указал такую оплату',
		'METHOD' => 'POST',
		'URI' => '/deal/add',
		'DATA' => '{"payment":"application","delivery":"post","ads":[5903]}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Новая сделка',
		'VAR' => 'продавец не указал такую доставку',
		'METHOD' => 'POST',
		'URI' => '/deal/add',
		'DATA' => '{"payment":"application","delivery":"post","ads":[5961]}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Новая сделка',
		'VAR' => 'Объявления разных продавцов',
		'METHOD' => 'POST',
		'URI' => '/deal/add',
		'DATA' => '{"payment":"application","delivery":"personal","ads":[5961,6057]}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Новая сделка',
		'VAR' => 'Не указан адрес',
		'METHOD' => 'POST',
		'URI' => '/deal/add',
		'DATA' => '{"payment":"application","delivery":"post","ads":[6057]}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Новая сделка',
		'VAR' => '(заглушка, сделку не создаем)',
		'METHOD' => 'POST',
		'URI' => '/deal/add',
		'DATA' => '{"payment":"application","delivery":"post","ads":[6057],"check":true,"address":"Адрес","debug":true}',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Добавление объявления к сделке',
		'VAR' => 'Возможные исключения:<br>' .
			'wrong_ad - не задано объявление<br>' .
			'ad_not_found - не найдено объявление<br>' .
			'self_ad - при попытке добавить свое объявление<br>' .
			'ad_with_deal - объявление уже прикреплено к сделке<br>' .
			'wrong_deal - не задана сделка<br>' .
			'deal_not_found - сделка не найдена<br>' .
			'not_your_deal - не своя сделка<br>' .
			'already_in_deal - объявление уже в этой сделке<br>' .
			'only_one_seller - при попытке добавить объявление другого продавца<br>' .
			'wrong_payment - если объявление не содержит способа оплаты сделки<br>' .
			'wrong_delivery - аналогично для доставки<br>',
		'METHOD' => 'POST',
		'URI' => '/deal/append',
		'DATA' => '{"ad":6520,"deal":6353}',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Удаление объявления из сделки',
		'VAR' => 'Возможные исключения:<br>' .
			'wrong_ad - не задано объявление<br>' .
			'wrong_deal - не задана сделка<br>' .
			'deal_not_found - сделка не найдена<br>' .
			'not_your_deal - не своя сделка<br>' .
			'not_in_deal - объявления нет в этой сделке<br>' .
			'last_ad - последнее объявление<br>',
		'METHOD' => 'POST',
		'URI' => '/deal/adremove',
		'DATA' => '{"ad":6520,"deal":6353}',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Смена статуса сделки',
		'VAR' => 'Нельзя сменить статус',
		'METHOD' => 'POST',
		'URI' => '/deal/update',
		'DATA' => '{"deal":1290,"status":"price","price":700}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Смена статуса сделки',
		'VAR' => 'Когда продавец меняет сделку на "товар отправлен", он может указать track-код посылки',
		'METHOD' => 'POST',
		'URI' => '/deal/update',
		'DATA' => '{"deal":1290,"status":"send","track":"RA644000001RU"}',
		'NEED' => 400,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Добавление кода посылки',
		'VAR' => 'Отдельный метод',
		'METHOD' => 'POST',
		'URI' => '/deal/addTrack',
		'DATA' => '{"deal":6591,"track":"RA644000001RU"}',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Проверка статуса доставки',
		'VAR' => '',
		'METHOD' => 'POST',
		'URI' => '/deal/track',
		'DATA' => '{"deal":6591}',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Добавление сообщения для службы поддержки',
		'VAR' => '',
		'METHOD' => 'POST',
		'URI' => '/user/support',
		'DATA' => '{"message":"Не могу создать объявление"}',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	/*array(
		'NAME' => 'Чат со службой поддержки',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/user/supportchat',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Чат со службой поддержки',
		'VAR' => 'постраничка',
		'METHOD' => 'GET',
		'URI' => '/user/supportchat',
		'DATA' => 'max=1315&count=3',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Добавление сообщения в чат сделки',
		'VAR' => '',
		'METHOD' => 'POST',
		'URI' => '/deal/message',
		'DATA' => '{"deal":1290,"message":"Посылка отправлена"}',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Добавление сообщения в чат службы поддержки',
		'VAR' => 'в рамках сделки',
		'METHOD' => 'POST',
		'URI' => '/deal/support',
		'DATA' => '{"deal":1290,"message":"Покупатель не отвечает"}',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Чат сделки',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/deal/chat/1290',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Чат сделки',
		'VAR' => 'постраничка',
		'METHOD' => 'GET',
		'URI' => '/deal/chat/1290',
		'DATA' => 'max=1315&count=3',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Чат со службой поддержки',
		'VAR' => 'в рамках сделки',
		'METHOD' => 'GET',
		'URI' => '/deal/supportchat/1290',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Чат со службой поддержки',
		'VAR' => 'постраничка',
		'METHOD' => 'GET',
		'URI' => '/deal/supportchat/1290',
		'DATA' => 'max=1315&count=3',
		'NEED' => 200,
		'AUTH' => 'x',
	),*/
	array(
		'NAME' => 'Мои сделки - продаю',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/deal/my/sell',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Мои сделки - продаю',
		'VAR' => 'параметры постранички',
		'METHOD' => 'GET',
		'URI' => '/deal/my/sell',
		'DATA' => 'status=new&max=1281&count=3',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Мои сделки - покупаю',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/deal/my/buy',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Мои сделки - покупаю',
		'VAR' => 'параметры постранички',
		'METHOD' => 'GET',
		'URI' => '/deal/my/buy',
		'DATA' => 'status=new&max=1281&count=3',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Сделка',
		'VAR' => '',
		'METHOD' => 'GET',
		'URI' => '/deal/detail/6353',
		'DATA' => '',
		'NEED' => 200,
		'AUTH' => 'x',
	),
	array(
		'NAME' => 'Оценка сделки',
		'VAR' => '',
		'METHOD' => 'POST',
		'URI' => '/deal/rating',
		'DATA' => '{"deal":6353,"rating":4}',
		'NEED' => 200,
		'AUTH' => 'x',
	),
);