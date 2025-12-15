<?php
session_start();
ini_set('display_errors', 1);


Class Action {
	private $db; // Соединение с базой данных

	/**
	 * Инициализирует подключение к базе данных
	 */
	public function __construct() {
		ob_start();
   	include 'db_connect.php';
    
    $this->db = $conn; // Сохраняем соединение в свойство класса
	}
	
	/**
	 * Закрывает соединение с БД и завершает буферизацию
	 */
	function __destruct() {
	    $this->db->close();
	    ob_end_flush();
	}

	function login(){
		extract($_POST); // Извлекаем переменные из POST-запроса
		
		// Ищем пользователя по email
		$qry = $this->db->query("SELECT *,concat(firstname,' ',lastname) as name FROM users where email = '".$email."'");
		if($qry->num_rows > 0){
			$user = $qry->fetch_assoc();
			$stored_password = $user['password'];
			$password_valid = false;
			
			if(password_verify($password, $stored_password)){
				$password_valid = true;
			}
			elseif(md5($password) === $stored_password){
				$password_valid = true;
				$new_hash = password_hash($password, PASSWORD_DEFAULT);
				$this->db->query("UPDATE users SET password = '".$this->db->real_escape_string($new_hash)."' WHERE id = ".$user['id']);
			}
			
			if($password_valid){
				foreach ($user as $key => $value) {
					if($key != 'password' && !is_numeric($key))
						$_SESSION['login_'.$key] = $value;
				}
				return 1; // Успешная авторизация
			}
		}
		return 2; // Пользователь не найден или неверный пароль
	}
	
	function logout(){
		session_destroy(); // Уничтожаем сессию
		// Очищаем все переменные сессии
		foreach ($_SESSION as $key => $value) {
			unset($_SESSION[$key]);
		}
		header("location:login.php"); // Перенаправляем на страницу входа
	}
	
	/**
	 * Сохранение или обновление пользователя
	 * Создает нового пользователя или обновляет существующего
	 * @return int 1 - успешное сохранение, 2 - email уже существует
	 */
	function save_user(){
		extract($_POST); // Извлекаем переменные из POST-запроса
		$data = ""; // Строка для формирования SQL-запроса
		// Формируем строку данных для SQL-запроса, исключая служебные поля
		foreach($_POST as $k => $v){
			// Пропускаем id, cpass (подтверждение пароля) и password (обрабатывается отдельно)
			if(!in_array($k, array('id','cpass','password')) && !is_numeric($k)){
				if(empty($data)){
					$data .= " $k='$v' "; // Первое поле
				}else{
					$data .= ", $k='$v' "; // Последующие поля
				}
			}
		}
		// Если пароль указан, добавляем его хеш (password_hash) в запрос
		if(!empty($password)){
			$hashed_password = password_hash($password, PASSWORD_DEFAULT); // Используем безопасное хеширование
			$data .= ", password='".$this->db->real_escape_string($hashed_password)."' ";
		}
		// Проверяем, не существует ли уже пользователь с таким email
		$check = $this->db->query("SELECT * FROM users where email ='$email' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
		if($check > 0){
			return 2; // Email уже существует
			exit;
		}
		// Обработка загрузки аватара
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name']; // Генерируем уникальное имя файла
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname); // Перемещаем файл
			$data .= ", avatar = '$fname' "; // Добавляем имя файла в запрос

		}
		// Выполняем INSERT или UPDATE в зависимости от наличия id
		if(empty($id)){
			$save = $this->db->query("INSERT INTO users set $data"); // Создание нового пользователя
		}else{
			$save = $this->db->query("UPDATE users set $data where id = $id"); // Обновление существующего
		}

		if($save){
			return 1; // Успешное сохранение
		}
	}
	
	/**
	 * Обновление данных текущего пользователя
	 * Используется для редактирования профиля авторизованного пользователя
	 * @return int 1 - успешное обновление, 2 - email уже существует
	 */
	function update_user(){
		extract($_POST);
		$data = "";
		// Формируем данные для обновления, исключая служебные поля
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cpass','table','password')) && !is_numeric($k)){
				
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		// Проверка уникальности email
		$check = $this->db->query("SELECT * FROM users where email ='$email' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
		if($check > 0){
			return 2; // Email уже существует
			exit;
		}
		// Обработка аватара
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
			$data .= ", avatar = '$fname' ";

		}
		// Обновление пароля, если он указан
		if(!empty($password)){
			$hashed_password = password_hash($password, PASSWORD_DEFAULT); // Используем безопасное хеширование
			$data .= " ,password='".$this->db->real_escape_string($hashed_password)."' ";
		}
		// Выполнение запроса
		if(empty($id)){
			$save = $this->db->query("INSERT INTO users set $data");
		}else{
			$save = $this->db->query("UPDATE users set $data where id = $id");
		}

		if($save){
			// Обновляем данные в сессии
			foreach ($_POST as $key => $value) {
				if($key != 'password' && !is_numeric($key))
					$_SESSION['login_'.$key] = $value;
			}
			if(isset($_FILES['img']) && !empty($_FILES['img']['tmp_name']))
					$_SESSION['login_avatar'] = $fname;
			return 1; // Успешное обновление
		}
	}
	
	/**
	 * Удаление пользователя из системы
	 * @return int 1 - успешное удаление
	 */
	function delete_user(){
		extract($_POST); // Извлекаем id из POST
		$delete = $this->db->query("DELETE FROM users where id = ".$id); // Удаляем пользователя
		if($delete)
			return 1; // Успешное удаление
	}
	
	/**
	 * Сохранение настроек системы
	 * Обновляет или создает настройки системы (название, email, контакты и т.д.)
	 * @return int 1 - успешное сохранение
	 */
	function save_system_settings(){
		extract($_POST);
		$data = '';
		// Формируем строку данных для SQL-запроса
		foreach($_POST as $k => $v){
			if(!is_numeric($k)){
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		// Обработка загрузки обложки системы
		if($_FILES['cover']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['cover']['name'];
			$move = move_uploaded_file($_FILES['cover']['tmp_name'],'../assets/uploads/'. $fname);
			$data .= ", cover_img = '$fname' ";

		}
		// Проверяем, существуют ли уже настройки
		$chk = $this->db->query("SELECT * FROM system_settings");
		if($chk->num_rows > 0){
			$save = $this->db->query("UPDATE system_settings set $data where id =".$chk->fetch_array()['id']); // Обновляем существующие
		}else{
			$save = $this->db->query("INSERT INTO system_settings set $data"); // Создаем новые
		}
		if($save){
			// Обновляем настройки в сессии
			foreach($_POST as $k => $v){
				if(!is_numeric($k)){
					$_SESSION['system'][$k] = $v;
				}
			}
			if($_FILES['cover']['tmp_name'] != ''){
				$_SESSION['system']['cover_img'] = $fname;
			}
			return 1; // Успешное сохранение
		}
	}
	
	/**
	 * Сохранение или обновление проекта
	 * Создает новый проект или обновляет существующий
	 * @return int 1 - успешное сохранение
	 */
	function save_project(){
		extract($_POST);
		$data = "";
		// Формируем данные для сохранения проекта
		foreach($_POST as $k => $v){
			// Пропускаем id и user_ids (обрабатывается отдельно)
			if(!in_array($k, array('id','user_ids')) && !is_numeric($k)){
				// Экранируем специальные символы в описании
				if($k == 'description')
					$v = htmlentities(str_replace("'","&#x2019;",$v));
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		// Обрабатываем список участников проекта (массив ID пользователей)
		if(isset($user_ids)){
			$data .= ", user_ids='".implode(',',$user_ids)."' "; // Объединяем ID через запятую
		}
		// Выполняем INSERT или UPDATE
		if(empty($id)){
			$save = $this->db->query("INSERT INTO project_list set $data"); // Создание нового проекта
		}else{
			$save = $this->db->query("UPDATE project_list set $data where id = $id"); // Обновление существующего
		}
		if($save){
			return 1; // Успешное сохранение
		}
	}
	
	/**
	 * Удаление проекта из системы
	 * @return int 1 - успешное удаление
	 */
	function delete_project(){
		extract($_POST); // Извлекаем id из POST
		$delete = $this->db->query("DELETE FROM project_list where id = $id"); // Удаляем проект
		if($delete){
			return 1; // Успешное удаление
		}
	}
	
	/**
	 * Сохранение или обновление задачи
	 * Создает новую задачу или обновляет существующую
	 * Для сотрудников (type=3) разрешено изменять только статус своих задач
	 * @return int 1 - успешное сохранение, 3 - недостаточно прав
	 */
	function save_task(){
		extract($_POST);
		
		// Проверка прав для сотрудников
		if(isset($_SESSION['login_type']) && $_SESSION['login_type'] == 3 && !empty($id)){
			// Сотрудники могут изменять только свои задачи
			$task_check = $this->db->query("SELECT assignee_id FROM task_list WHERE id = $id")->fetch_assoc();
			if(empty($task_check) || $task_check['assignee_id'] != $_SESSION['login_id']){
				return 3; // Недостаточно прав - задача не назначена на этого сотрудника
			}
			
			// Сотрудники могут изменять только статус, остальные поля блокируем
			$allowed_fields = array('id', 'status');
			$data = "";
			foreach($_POST as $k => $v){
				if(!in_array($k, $allowed_fields) && !is_numeric($k)){
					continue; // Пропускаем все поля кроме статуса
				}
				if($k == 'status'){
					if(empty($data)){
						$data .= " $k='$v' ";
					}else{
						$data .= ", $k='$v' ";
					}
				}
			}
			// Обновляем только статус
			$save = $this->db->query("UPDATE task_list set $data where id = $id");
			if($save){
				return 1; // Успешное сохранение
			}
		}else{
			// Для администраторов и менеджеров - полный доступ
			$data = "";
			// Формируем данные для сохранения задачи
			foreach($_POST as $k => $v){
				if(!in_array($k, array('id')) && !is_numeric($k)){
					// Экранируем специальные символы в описании
					if($k == 'description')
						$v = htmlentities(str_replace("'","&#x2019;",$v));
					if(empty($data)){
						$data .= " $k='$v' ";
					}else{
						$data .= ", $k='$v' ";
					}
				}
			}
			// Выполняем INSERT или UPDATE
			if(empty($id)){
				$save = $this->db->query("INSERT INTO task_list set $data"); // Создание новой задачи
			}else{
				$save = $this->db->query("UPDATE task_list set $data where id = $id"); // Обновление существующей
			}
			if($save){
				return 1; // Успешное сохранение
			}
		}
	}
	
	/**
	 * Удаление задачи из системы
	 * @return int 1 - успешное удаление
	 */
	function delete_task(){
		extract($_POST); // Извлекаем id из POST
		$delete = $this->db->query("DELETE FROM task_list where id = $id"); // Удаляем задачу
		if($delete){
			return 1; // Успешное удаление
		}
	}
	
	/**
	 * Сохранение или обновление записи о прогрессе работы
	 * Создает новую запись о прогрессе или обновляет существующую
	 * Автоматически вычисляет время работы на основе start_time и end_time
	 * Для сотрудников (type=3) разрешено добавлять прогресс только для своих задач
	 * @return int 1 - успешное сохранение, 3 - недостаточно прав (задача не назначена на сотрудника)
	 */
	function save_progress(){
		extract($_POST);
		
		// Проверка прав для сотрудников
		if(isset($_SESSION['login_type']) && $_SESSION['login_type'] == 3){
			// Проверяем, назначена ли задача на этого сотрудника
			if(!empty($task_id)){
				$task_check = $this->db->query("SELECT assignee_id FROM task_list WHERE id = $task_id")->fetch_assoc();
				if(empty($task_check) || $task_check['assignee_id'] != $_SESSION['login_id']){
					return 3; // Недостаточно прав - задача не назначена на этого сотрудника
				}
			}
			
			// При обновлении существующей записи проверяем, что она принадлежит текущему пользователю
			if(!empty($id)){
				$progress_check = $this->db->query("SELECT user_id FROM user_productivity WHERE id = $id")->fetch_assoc();
				if(empty($progress_check) || $progress_check['user_id'] != $_SESSION['login_id']){
					return 3; // Недостаточно прав - запись о прогрессе принадлежит другому пользователю
				}
			}
		}
		
		$data = "";
		// Формируем данные для сохранения прогресса
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id')) && !is_numeric($k)){
				// Экранируем специальные символы в комментарии
				if($k == 'comment')
					$v = htmlentities(str_replace("'","&#x2019;",$v));
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		// Вычисляем продолжительность работы в часах
		// Используем фиктивную дату для корректного вычисления разницы времени
		$dur = abs(strtotime("2020-01-01 ".$end_time)) - abs(strtotime("2020-01-01 ".$start_time));
		$dur = $dur / (60 * 60); // Преобразуем секунды в часы
		$data .= ", time_rendered='$dur' "; // Добавляем вычисленное время
		// Выполняем INSERT или UPDATE
		if(empty($id)){
			$data .= ", user_id={$_SESSION['login_id']} "; // Для новой записи добавляем ID текущего пользователя
			
			$save = $this->db->query("INSERT INTO user_productivity set $data"); // Создание новой записи
		}else{
			$save = $this->db->query("UPDATE user_productivity set $data where id = $id"); // Обновление существующей
		}
		if($save){
			return 1; // Успешное сохранение
		}
	}
	
	/**
	 * Удаление записи о прогрессе работы
	 * @return int 1 - успешное удаление
	 */
	function delete_progress(){
		extract($_POST); // Извлекаем id из POST
		$delete = $this->db->query("DELETE FROM user_productivity where id = $id"); // Удаляем запись о прогрессе
		if($delete){
			return 1; // Успешное удаление
		}
	}
	
	/**
	 * Получение событий проекта для календаря
	 * Формирует JSON-массив событий из задач проекта для отображения в FullCalendar
	 * @return string JSON-строка с массивом событий или пустой массив, если проект не указан
	 */
	function project_events(){
		// Получаем ID проекта из GET или POST запроса
		$pid = isset($_GET['pid']) ? (int) $_GET['pid'] : (isset($_POST['pid']) ? (int) $_POST['pid'] : 0);
		$events = array(); // Массив для хранения событий
		if($pid <= 0){
			return json_encode($events); // Возвращаем пустой массив, если проект не указан
		}
		// Получаем все задачи проекта с информацией об исполнителе
		$qry = $this->db->query("SELECT t.*,concat(u.firstname,' ',u.lastname) as assignee FROM task_list t LEFT JOIN users u ON u.id = t.assignee_id WHERE t.project_id = {$pid}");
		// Маппинг статусов задач для отображения
		$status_map = array(
			1 => 'Ожидает',
			2 => 'В работе',
			3 => 'Готово'
		);
		// Формируем массив событий для календаря
		while($row = $qry->fetch_assoc()){
			if(empty($row['start_date'])){
				continue; // Пропускаем задачи без даты начала
			}
			$events[] = array(
				'id' => $row['id'], // ID задачи
				'title' => $row['task'], // Название задачи
				'start' => $row['start_date'], // Дата начала
				'end' => !empty($row['end_date']) ? date('Y-m-d', strtotime($row['end_date'].' +1 day')) : $row['start_date'], // Дата окончания (+1 день для корректного отображения в календаре)
				'extendedProps' => array( // Дополнительные свойства события
					'description' => html_entity_decode($row['description']), // Описание задачи
					'assignee' => $row['assignee'], // Имя исполнителя
					'assignee_id' => $row['assignee_id'], // ID исполнителя
					'status' => $status_map[$row['status']] ?? 'Ожидает' // Статус задачи
				)
			);
		}
		return json_encode($events); // Возвращаем JSON-строку
	}
}