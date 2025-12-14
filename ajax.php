<?php
/**
 * Файл ajax.php - обработчик AJAX-запросов
 * Маршрутизирует запросы к соответствующим методам класса Action
 * Все запросы должны содержать параметр action в GET-запросе
 */

ob_start(); // Включаем буферизацию вывода
date_default_timezone_set("Asia/Manila"); // Устанавливаем часовой пояс

$action = $_GET['action']; // Получаем тип действия из GET-параметра
include 'admin_class.php'; // Подключаем класс с бизнес-логикой
$crud = new Action(); // Создаем экземпляр класса
// Обработка авторизации пользователя
if($action == 'login'){
	$login = $crud->login();
	if($login)
		echo $login; // Возвращаем результат: 1 - успех, 2 - ошибка
}

// Выход пользователя из системы
if($action == 'logout'){
	$logout = $crud->logout();
	if($logout)
		echo $logout;
}

// Сохранение пользователя (создание или обновление)
if($action == 'save_user'){
	$save = $crud->save_user();
	if($save)
		echo $save; // Возвращаем результат: 1 - успех, 2 - email уже существует
}

// Обновление данных пользователя
if($action == 'update_user'){
	$save = $crud->update_user();
	if($save)
		echo $save;
}

// Удаление пользователя
if($action == 'delete_user'){
	$save = $crud->delete_user();
	if($save)
		echo $save; // Возвращаем 1 при успешном удалении
}

// Сохранение проекта (создание или обновление)
if($action == 'save_project'){
	$save = $crud->save_project();
	if($save)
		echo $save; // Возвращаем 1 при успешном сохранении
}

// Удаление проекта
if($action == 'delete_project'){
	$save = $crud->delete_project();
	if($save)
		echo $save;
}

// Сохранение задачи (создание или обновление)
if($action == 'save_task'){
	$save = $crud->save_task();
	if($save)
		echo $save; // Возвращаем результат: 1 - успех, 3 - недостаточно прав (для сотрудников)
}

// Удаление задачи
if($action == 'delete_task'){
	$save = $crud->delete_task();
	if($save)
		echo $save;
}

// Сохранение записи о прогрессе работы
if($action == 'save_progress'){
	$save = $crud->save_progress();
	if($save)
		echo $save; // Возвращаем результат: 1 - успех, 3 - недостаточно прав (задача не назначена на сотрудника)
}

// Удаление записи о прогрессе
if($action == 'delete_progress'){
	$save = $crud->delete_progress();
	if($save)
		echo $save;
}

// Получение событий проекта для календаря (JSON)
if($action == 'project_events'){
	$events = $crud->project_events();
	if($events)
		echo $events; // Возвращаем JSON-строку с событиями
}
ob_end_flush();
?>
