<?php 
include 'db_connect.php';
if(isset($_GET['id'])){
	$qry = $conn->query("SELECT * FROM user_productivity where id = ".$_GET['id'])->fetch_array();
	foreach($qry as $k => $v){
		$$k = $v;
	}
	
	// Проверка прав для сотрудников: могут редактировать только свои записи
	if(isset($_SESSION['login_type']) && $_SESSION['login_type'] == 3){
		if(empty($user_id) || $user_id != $_SESSION['login_id']){
			echo '<div class="alert alert-danger">У вас нет прав на редактирование этой записи о прогрессе.</div>';
			exit;
		}
	}
}

// Проверка прав при передаче task_id через GET
if(isset($_GET['tid']) && isset($_SESSION['login_type']) && $_SESSION['login_type'] == 3){
	$task_check = $conn->query("SELECT assignee_id FROM task_list WHERE id = ".$_GET['tid'])->fetch_assoc();
	if(empty($task_check) || $task_check['assignee_id'] != $_SESSION['login_id']){
		echo '<div class="alert alert-danger">У вас нет прав на добавление прогресса для этой задачи.</div>';
		exit;
	}
}
?>
<div class="container-fluid">
	<form action="" id="manage-progress">
		<input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
		<input type="hidden" name="project_id" value="<?php echo isset($_GET['pid']) ? $_GET['pid'] : '' ?>">
		<div class="col-lg-12">
			<div class="row">
				<div class="col-md-5">
					<?php if(!isset($_GET['tid'])): ?>
					 <div class="form-group">
		              <label for="" class="control-label">Задача</label>
		              <select class="form-control form-control-sm select2" name="task_id" required>
		              	<option></option>
		              	<?php 
		              	// Для сотрудников показываем только свои задачи
		              	$task_query = "SELECT * FROM task_list where project_id = {$_GET['pid']}";
		              	if(isset($_SESSION['login_type']) && $_SESSION['login_type'] == 3){
		              		$task_query .= " AND assignee_id = {$_SESSION['login_id']}";
		              	}
		              	$task_query .= " order by task asc";
		              	$tasks = $conn->query($task_query);
		              	if($tasks->num_rows > 0):
		              		while($row= $tasks->fetch_assoc()):
		              	?>
		              	<option value="<?php echo $row['id'] ?>" <?php echo isset($task_id) && $task_id == $row['id'] ? "selected" : '' ?>><?php echo ucwords($row['task']) ?></option>
		              	<?php 
		              		endwhile;
		              	else:
		              	?>
		              	<option disabled>Нет доступных задач</option>
		              	<?php endif; ?>
		              </select>
		              <?php if(isset($_SESSION['login_type']) && $_SESSION['login_type'] == 3): ?>
		              <small class="text-muted">Показаны только задачи, назначенные на вас</small>
		              <?php endif; ?>
		            </div>
		            <?php else: ?>
					<input type="hidden" name="task_id" value="<?php echo isset($_GET['tid']) ? $_GET['tid'] : '' ?>">
		            <?php endif; ?>
					<div class="form-group">
						<label for="">Тема</label>
						<input type="text" class="form-control form-control-sm" name="subject" value="<?php echo isset($subject) ? $subject : '' ?>" required>
					</div>
					<div class="form-group">
						<label for="">Дата</label>
						<input type="date" class="form-control form-control-sm" name="date" value="<?php echo isset($date) ? date("Y-m-d",strtotime($date)) : '' ?>" required>
					</div>
					<div class="form-group">
						<label for="">Начало</label>
						<input type="time" class="form-control form-control-sm" name="start_time" value="<?php echo isset($start_time) ? date("H:i",strtotime("2020-01-01 ".$start_time)) : '' ?>" required>
					</div>
					<div class="form-group">
						<label for="">Окончание</label>
						<input type="time" class="form-control form-control-sm" name="end_time" value="<?php echo isset($end_time) ? date("H:i",strtotime("2020-01-01 ".$end_time)) : '' ?>" required>
					</div>
				</div>
				<div class="col-md-7">
					<div class="form-group">
						<label for="">Комментарий / описание прогресса</label>
						<textarea name="comment" id="" cols="30" rows="10" class="summernote form-control" required="">
							<?php echo isset($comment) ? $comment : '' ?>
						</textarea>
					</div>
				</div>
			</div>
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
     $('.select2').select2({
	    placeholder:"Выберите из списка",
	    width: "100%"
	  });
     })
    $('#manage-progress').submit(function(e){
    	e.preventDefault()
    	start_load()
    	$.ajax({
    		url:'ajax.php?action=save_progress',
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
				}else if(resp == 3){
					alert_toast('Недостаточно прав. Вы можете добавлять прогресс только для своих задач.',"error");
					end_load()
				}else{
					alert_toast('Ошибка при сохранении',"error");
					end_load()
				}
			},
			error: function(){
				alert_toast('Произошла ошибка',"error");
				end_load()
			}
    	})
    })
</script>