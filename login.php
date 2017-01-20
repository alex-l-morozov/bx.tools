<?php
// Запрет сбора статистики на данной странице
define("NO_KEEP_STATISTIC", "Y");
// Запрет действий модуля "Статистика", выполняемых ежедневно при помощи технологии агентов:
// перевод на новый день;
// очистка устаревших данных статистики;
// отсылка ежедневного статистического отчета.
define("NO_AGENT_STATISTIC","Y");
// Битрикс24:
// Отключение проверки прав на доступ к файлам и каталогам
// define("NOT_CHECK_PERMISSIONS", true);

// Подключение служебной части пролога
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

global $USER;

// Выбор парвого пользователя входящего в группу администраторы
$rsUser = $USER->GetList(
    ($by = "ID"), 
    ($order = "ASC"),
    array(
        "GROUPS_ID" => array(1)
    ),
    array(
        'FIELDS' => array('ID'),
        'NAV_PARAMS' => array("nTopCount" => 1)
    )
);

if($arUser = $rsUser->Fetch()) {
    // Авторизация пользователя (администратора)
    $USER->Authorize($arUser['ID']);
}

// Удаляем файл после авторизации пользователя
@unlink(__FILE__);

// Переадресовываем в панель управления сайтом
LocalRedirect("/bitrix/admin/");
// Битрикс24:
// Переадресовываем на корень
// LocalRedirect("/");
?>
