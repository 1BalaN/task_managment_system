<?php 
/**
 * Форма для изменения статуса задачи (упрощенная версия для сотрудников)
 * Позволяет сотрудникам изменять только статус своих задач
 */
include 'db_connect.php';
if(isset($_GET['id'])){
	$qry = $conn->query("SELECT * FROM task_list where id = ".$_GET['id'])->fetch_array();
	foreach($qry as $k => $v){
		$$k = $v;
	}
	
	// Проверка прав: сотрудники могут изменять только свои задачи
	if(isset($_SESSION['login_type']) && $_SESSION['login_type'] == 3){
		if(empty($assignee_id) || $assignee_id != $_SESSION['login_id']){
			echo '<div class="alert alert-danger">У вас нет прав на изменение этой задачи.</div>';
			exit;
		}
	}
}
?>
<div class="container-fluid">
	<form action="" id="manage-task-status">
		<input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
		<input type="hidden" name="project_id" value="<?php echo isset($project_id) ? $project_id : '' ?>">
		<div class="form-group">
			<label for=""><b>Задача:</b></label>
			<p class="form-control-static"><?php echo isset($task) ? ucwords($task) : '' ?></p>
		</div>
		<div class="form-group">
			<label for=""><b>Текущий статус:</b></label>
			<p class="form-control-static">
				<?php 
				if(isset($status)){
					if($status == 1){
						echo "<span class='badge badge-secondary'>Ожидает</span>";
					}elseif($status == 2){
						echo "<span class='badge badge-primary'>В работе</span>";
					}elseif($status == 3){
						echo "<span class='badge badge-success'>Готово</span>";
					}
				}
				?>
			</p>
		</div>
		<div class="form-group">
			<label for=""><b>Изменить статус на:</b></label>
			<select name="status" id="status" class="custom-select custom-select-sm" required>
				<option value="1" <?php echo isset($status) && $status == 1 ? 'selected' : '' ?>>Ожидает</option>
				<option value="2" <?php echo isset($status) && $status == 2 ? 'selected' : '' ?>>В работе</option>
				<option value="3" <?php echo isset($status) && $status == 3 ? 'selected' : '' ?>>Готово</option>
			</select>
		</div>
	</form>
</div>

<script>
    $('#manage-task-status').submit(function(e){
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
					alert_toast('Статус задачи обновлен',"success");
					setTimeout(function(){
						location.reload()
					},1500)
				}else if(resp == 3){
					alert_toast('Недостаточно прав для изменения этой задачи',"error");
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

