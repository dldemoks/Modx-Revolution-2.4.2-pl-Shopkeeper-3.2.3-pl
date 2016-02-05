<?php
define('PAYEER_MERCHANT_URL', 'https://payeer.com/merchant/'); // URL мерчанта
define('PAYEER_MERCHANT_ID', ''); // Идентификатор магазина
define('PAYEER_SECRET_KEY', ''); // Секретный ключ магазина
define('PAYEER_CURRENCY_CODE', ''); // Валюта магазина (поддерживаются RUB, EUR, USD)
define('PAYEER_IPFILTER', ''); // IP фильтр - доверенные ip обработчика через запятую (можно указать маску)
define('PAYEER_EMAILERR', ''); // Email администратора для отправки ошибок оплаты
define('PAYEER_LOGFILE', ''); // Путь файла логов, где идет запись оплат заказов (например, /payeer_orders.log)
?>