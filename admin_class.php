<?php
session_start();
ini_set('display_errors', 1);


Class Action {
	private $db; 

	public function __construct() {
		ob_start();
   	include 'db_connect.php';
    
    $this->db = $conn; 
	}
	
	function __destruct() {
	    $this->db->close();
	    ob_end_flush();
	}

	function login(){
		extract($_POST); 
		
		
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
				return 1; 
			}
		}
		return 2; 
	}
	
	function logout(){
		session_destroy(); 
		foreach ($_SESSION as $key => $value) {
			unset($_SESSION[$key]);
		}
		header("location:login.php");
	}
	
	function save_user(){
		extract($_POST); 
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cpass','password')) && !is_numeric($k)){
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' "; 
				}
			}
		}
		if(!empty($password)){
			$hashed_password = password_hash($password, PASSWORD_DEFAULT); 
			$data .= ", password='".$this->db->real_escape_string($hashed_password)."' ";
		}
		$check = $this->db->query("SELECT * FROM users where email ='$email' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
		if($check > 0){
			return 2; 
			exit;
		}
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name']; 
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname); 
			$data .= ", avatar = '$fname' "; 

		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO users set $data"); 
		}else{
			$save = $this->db->query("UPDATE users set $data where id = $id");
		}

		if($save){
			return 1; 
		}
	}
	
	function update_user(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cpass','table','password')) && !is_numeric($k)){
				
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		$check = $this->db->query("SELECT * FROM users where email ='$email' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
		if($check > 0){
			return 2; 
			exit;
		}
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
			$data .= ", avatar = '$fname' ";

		}
		if(!empty($password)){
			$hashed_password = password_hash($password, PASSWORD_DEFAULT);
			$data .= " ,password='".$this->db->real_escape_string($hashed_password)."' ";
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO users set $data");
		}else{
			$save = $this->db->query("UPDATE users set $data where id = $id");
		}

		if($save){
			foreach ($_POST as $key => $value) {
				if($key != 'password' && !is_numeric($key))
					$_SESSION['login_'.$key] = $value;
			}
			if(isset($_FILES['img']) && !empty($_FILES['img']['tmp_name']))
					$_SESSION['login_avatar'] = $fname;
			return 1;
		}
	}
	
	function delete_user(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM users where id = ".$id); 
		if($delete)
			return 1;
	}
	
	
	function save_system_settings(){
		extract($_POST);
		$data = '';
		foreach($_POST as $k => $v){
			if(!is_numeric($k)){
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		if($_FILES['cover']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['cover']['name'];
			$move = move_uploaded_file($_FILES['cover']['tmp_name'],'../assets/uploads/'. $fname);
			$data .= ", cover_img = '$fname' ";

		}
		$chk = $this->db->query("SELECT * FROM system_settings");
		if($chk->num_rows > 0){
			$save = $this->db->query("UPDATE system_settings set $data where id =".$chk->fetch_array()['id']); // Обновляем существующие
		}else{
			$save = $this->db->query("INSERT INTO system_settings set $data");
		}
		if($save){
			foreach($_POST as $k => $v){
				if(!is_numeric($k)){
					$_SESSION['system'][$k] = $v;
				}
			}
			if($_FILES['cover']['tmp_name'] != ''){
				$_SESSION['system']['cover_img'] = $fname;
			}
			return 1;
		}
	}
	
	function save_project(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','user_ids')) && !is_numeric($k)){
				if($k == 'description')
					$v = htmlentities(str_replace("'","&#x2019;",$v));
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		if(isset($user_ids)){
			$data .= ", user_ids='".implode(',',$user_ids)."' ";
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO project_list set $data"); 
		}else{
			$save = $this->db->query("UPDATE project_list set $data where id = $id"); 
		}
		if($save){
			return 1;
		}
	}
	
	function delete_project(){
		extract($_POST); 
		$delete = $this->db->query("DELETE FROM project_list where id = $id"); 
		if($delete){
			return 1;
		}
	}
	
	function save_task(){
		extract($_POST);
		
		if(isset($_SESSION['login_type']) && $_SESSION['login_type'] == 3 && !empty($id)){
			$task_check = $this->db->query("SELECT assignee_id FROM task_list WHERE id = $id")->fetch_assoc();
			if(empty($task_check) || $task_check['assignee_id'] != $_SESSION['login_id']){
				return 3; 
			}
			
			$allowed_fields = array('id', 'status');
			$data = "";
			foreach($_POST as $k => $v){
				if(!in_array($k, $allowed_fields) && !is_numeric($k)){
					continue;
				}
				if($k == 'status'){
					if(empty($data)){
						$data .= " $k='$v' ";
					}else{
						$data .= ", $k='$v' ";
					}
				}
			}
			$save = $this->db->query("UPDATE task_list set $data where id = $id");
			if($save){
				return 1;
			}
		}else{
			$data = "";
			
			foreach($_POST as $k => $v){
				if(!in_array($k, array('id')) && !is_numeric($k)){
					if($k == 'description')
						$v = htmlentities(str_replace("'","&#x2019;",$v));
					if(empty($data)){
						$data .= " $k='$v' ";
					}else{
						$data .= ", $k='$v' ";
					}
				}
			}
			if(empty($id)){
				$save = $this->db->query("INSERT INTO task_list set $data"); 
			}else{
				$save = $this->db->query("UPDATE task_list set $data where id = $id"); 
			}
			if($save){
				return 1;
			}
		}
	}
	
	function delete_task(){
		extract($_POST); 
		$delete = $this->db->query("DELETE FROM task_list where id = $id"); 
		if($delete){
			return 1; 
		}
	}
	
	function save_progress(){
		extract($_POST);
		
		if(isset($_SESSION['login_type']) && $_SESSION['login_type'] == 3){
			if(!empty($task_id)){
				$task_check = $this->db->query("SELECT assignee_id FROM task_list WHERE id = $task_id")->fetch_assoc();
				if(empty($task_check) || $task_check['assignee_id'] != $_SESSION['login_id']){
					return 3; 
				}
			}
			
			
			if(!empty($id)){
				$progress_check = $this->db->query("SELECT user_id FROM user_productivity WHERE id = $id")->fetch_assoc();
				if(empty($progress_check) || $progress_check['user_id'] != $_SESSION['login_id']){
					return 3; 
				}
			}
		}
		
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id')) && !is_numeric($k)){
				if($k == 'comment')
					$v = htmlentities(str_replace("'","&#x2019;",$v));
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		$dur = abs(strtotime("2020-01-01 ".$end_time)) - abs(strtotime("2020-01-01 ".$start_time));
		$dur = $dur / (60 * 60); 
		$data .= ", time_rendered='$dur' "; 
		if(empty($id)){
			$data .= ", user_id={$_SESSION['login_id']} "; 
			
			$save = $this->db->query("INSERT INTO user_productivity set $data"); 
		}else{
			$save = $this->db->query("UPDATE user_productivity set $data where id = $id"); 
		}
		if($save){
			return 1; 
		}
	}
	
	function delete_progress(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM user_productivity where id = $id");
		if($delete){
			return 1; 
		}
	}
	
	function project_events(){
		
		$pid = isset($_GET['pid']) ? (int) $_GET['pid'] : (isset($_POST['pid']) ? (int) $_POST['pid'] : 0);
		$events = array(); 
		if($pid <= 0){
			return json_encode($events); 
		}
		
		$qry = $this->db->query("SELECT t.*,concat(u.firstname,' ',u.lastname) as assignee FROM task_list t LEFT JOIN users u ON u.id = t.assignee_id WHERE t.project_id = {$pid}");
		$status_map = array(
			1 => 'Ожидает',
			2 => 'В работе',
			3 => 'Готово'
		);
		
		while($row = $qry->fetch_assoc()){
			if(empty($row['start_date'])){
				continue;
			}
			$events[] = array(
				'id' => $row['id'], 
				'title' => $row['task'], 
				'start' => $row['start_date'], 
				'end' => !empty($row['end_date']) ? date('Y-m-d', strtotime($row['end_date'].' +1 day')) : $row['start_date'], // Дата окончания (+1 день для корректного отображения в календаре)
				'extendedProps' => array( 
					'description' => html_entity_decode($row['description']), 
					'assignee' => $row['assignee'], 
					'assignee_id' => $row['assignee_id'], 
					'status' => $status_map[$row['status']] ?? 'Ожидает' 
				)
			);
		}
		return json_encode($events);
	}
}