<?php


ob_start(); 
date_default_timezone_set("Asia/Manila");

$action = $_GET['action'];
include 'admin_class.php'; 
$crud = new Action(); 
if($action == 'login'){
	$login = $crud->login();
	if($login)
		echo $login; // Возвращаем результат: 1 - успех, 2 - ошибка
}

if($action == 'logout'){
	$logout = $crud->logout();
	if($logout)
		echo $logout;
}

if($action == 'save_user'){
	$save = $crud->save_user();
	if($save)
		echo $save; // Возвращаем результат: 1 - успех, 2 - email уже существует
}

if($action == 'update_user'){
	$save = $crud->update_user();
	if($save)
		echo $save;
}

if($action == 'delete_user'){
	$save = $crud->delete_user();
	if($save)
		echo $save; 
}

if($action == 'save_project'){
	$save = $crud->save_project();
	if($save)
		echo $save; // Возвращаем 1 при успешном сохранении
}

if($action == 'delete_project'){
	$save = $crud->delete_project();
	if($save)
		echo $save;
}

if($action == 'save_task'){
	$save = $crud->save_task();
	if($save)
		echo $save; // Возвращаем результат: 1 - успех, 3 - недостаточно прав (для сотрудников)
}

if($action == 'delete_task'){
	$save = $crud->delete_task();
	if($save)
		echo $save;
}

if($action == 'save_progress'){
	$save = $crud->save_progress();
	if($save)
		echo $save; // Возвращаем результат: 1 - успех, 3 - недостаточно прав (задача не назначена на сотрудника)
}

if($action == 'delete_progress'){
	$save = $crud->delete_progress();
	if($save)
		echo $save;
}

if($action == 'project_events'){
	$events = $crud->project_events();
	if($events)
		echo $events; // Возвращаем JSON-строку с событиями
}
ob_end_flush();
?>
