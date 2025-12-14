<?php 
include 'db_connect.php';

// Проверка прав: сотрудники не могут редактировать задачи через эту форму
if(isset($_SESSION['login_type']) && $_SESSION['login_type'] == 3 && isset($_GET['id'])){
	$task_check = $conn->query("SELECT assignee_id FROM task_list WHERE id = ".$_GET['id'])->fetch_assoc();
	if(empty($task_check) || $task_check['assignee_id'] != $_SESSION['login_id']){
		echo '<div class="alert alert-danger">У вас нет прав на редактирование этой задачи. Используйте форму изменения статуса.</div>';
		exit;
	}
	// Если это их задача, перенаправляем на упрощенную форму
	echo '<script>location.href="manage_task_status.php?id='.$_GET['id'].'"</script>';
	exit;
}

$project_users = array();
if(isset($_GET['pid'])){
	$proj = $conn->query("SELECT manager_id,user_ids FROM project_list where id = ".$_GET['pid']);
	if($proj->num_rows){
		$pdata = $proj->fetch_assoc();
		$ids = array_filter(explode(',',$pdata['user_ids'] ?? ''));
		if(!in_array($pdata['manager_id'],$ids)){
			$ids[] = $pdata['manager_id'];
		}
		if(count($ids)){
			$id_list = implode(',',array_map('intval',$ids));
			$users = $conn->query("SELECT *,concat(firstname,' ',lastname) as name FROM users where id in ($id_list) order by name asc");
			while($row = $users->fetch_assoc()){
				$project_users[$row['id']] = $row['name'];
			}
		}
	}
}
if(isset($_GET['id'])){
	$qry = $conn->query("SELECT * FROM task_list where id = ".$_GET['id'])->fetch_array();
	foreach($qry as $k => $v){
		$$k = $v;
	}
}
?>
<div class="container-fluid">
	<form action="" id="manage-task">
		<input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
		<input type="hidden" name="project_id" value="<?php echo isset($_GET['pid']) ? $_GET['pid'] : '' ?>">
		<div class="form-group">
			<label for="">Задача</label>
			<input type="text" class="form-control form-control-sm" name="task" value="<?php echo isset($task) ? $task : '' ?>" required>
		</div>
		<div class="form-group">
			<label for="">Описание</label>
			<textarea name="description" id="" cols="30" rows="10" class="summernote form-control">
				<?php echo isset($description) ? $description : '' ?>
			</textarea>
		</div>
		<div class="form-row">
			<div class="form-group col-md-6">
				<label for="">Дата начала</label>
				<input type="text" class="form-control form-control-sm date-field" autocomplete="off" name="start_date" value="<?php echo isset($start_date) ? $start_date : '' ?>" placeholder="ГГГГ-ММ-ДД">
			</div>
			<div class="form-group col-md-6">
				<label for="">Дедлайн</label>
				<input type="text" class="form-control form-control-sm date-field" autocomplete="off" name="end_date" value="<?php echo isset($end_date) ? $end_date : '' ?>" placeholder="ГГГГ-ММ-ДД">
			</div>
		</div>
		<div class="form-group">
			<label for="">Исполнитель</label>
			<select name="assignee_id" class="custom-select custom-select-sm">
				<option value="">-- Не назначено --</option>
				<?php foreach($project_users as $uid => $uname): ?>
					<option value="<?php echo $uid ?>" <?php echo isset($assignee_id) && $assignee_id == $uid ? 'selected' : '' ?>><?php echo ucwords($uname) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="form-group">
			<label for="">Статус</label>
			<select name="status" id="status" class="custom-select custom-select-sm">
				<option value="1" <?php echo isset($status) && $status == 1 ? 'selected' : '' ?>>Ожидает</option>
				<option value="2" <?php echo isset($status) && $status == 2 ? 'selected' : '' ?>>В работе</option>
				<option value="3" <?php echo isset($status) && $status == 3 ? 'selected' : '' ?>>Готово</option>
			</select>
		</div>
	</form>
</div>

<script>
	$(document).ready(function(){


	$('.summernote').summernote({
        height: 200,
        toolbar: [
            [ 'style', [ 'style' ] ],
            [ 'font', [ 'bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear'] ],
            [ 'fontname', [ 'fontname' ] ],
            [ 'fontsize', [ 'fontsize' ] ],
            [ 'color', [ 'color' ] ],
            [ 'para', [ 'ol', 'ul', 'paragraph', 'height' ] ],
            [ 'table', [ 'table' ] ],
            [ 'view', [ 'undo', 'redo', 'fullscreen', 'codeview', 'help' ] ]
        ]
    })
    $('.date-field').datetimepicker({
		timepicker:false,
		format:'Y-m-d'
	})
     })
    
    $('#manage-task').submit(function(e){
    	e.preventDefault()
    	start_load()
    	$.ajax({
    		url:'ajax.php?action=save_task',
			data: new FormData($(this)[0]),
		    cache: false,
		    contentType: false,
		    processData: false,
		    method: 'POST',
		    type: 'POST',
			success:function(resp){
				if(resp == 1){
					alert_toast('Данные сохранены',"success");
					setTimeout(function(){
						location.reload()
					},1500)
				}
			}
    	})
    })
</script>