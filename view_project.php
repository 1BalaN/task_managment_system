<?php
include 'db_connect.php';
$stat = array("Ожидает","Стартовал","В работе","Пауза","Просрочен","Завершен");
$qry = $conn->query("SELECT * FROM project_list where id = ".$_GET['id'])->fetch_array();
foreach($qry as $k => $v){
	$$k = $v;
}
$tprog = $conn->query("SELECT * FROM task_list where project_id = {$id}")->num_rows;
$cprog = $conn->query("SELECT * FROM task_list where project_id = {$id} and status = 3")->num_rows;
$prog = $tprog > 0 ? ($cprog/$tprog) * 100 : 0;
$prog = $prog > 0 ?  number_format($prog,2) : $prog;
$prod = $conn->query("SELECT * FROM user_productivity where project_id = {$id}")->num_rows;
if($status == 0 && strtotime(date('Y-m-d')) >= strtotime($start_date)):
if($prod  > 0  || $cprog > 0)
  $status = 2;
else
  $status = 1;
elseif($status == 0 && strtotime(date('Y-m-d')) > strtotime($end_date)):
$status = 4;
endif;
$manager = $conn->query("SELECT *,concat(firstname,' ',lastname) as name FROM users where id = $manager_id");
$manager = $manager->num_rows > 0 ? $manager->fetch_array() : array();
?>
<link rel="stylesheet" href="assets/plugins/fullcalendar/main.min.css">
<script src="assets/plugins/fullcalendar/main.min.js"></script>
<div class="col-lg-12">
	<div class="row">
		<div class="col-md-12">
			<div class="callout callout-info">
				<div class="col-md-12">
					<div class="row">
						<div class="col-sm-6">
							<dl>
<dt><b class="border-bottom border-primary">Название проекта</b></dt>
								<dd><?php echo ucwords($name) ?></dd>
<dt><b class="border-bottom border-primary">Описание</b></dt>
								<dd><?php echo html_entity_decode($description) ?></dd>
							</dl>
						</div>
						<div class="col-md-6">
							<dl>
<dt><b class="border-bottom border-primary">Дата начала</b></dt>
								<dd><?php echo date("d.m.Y",strtotime($start_date)) ?></dd>
							</dl>
							<dl>
<dt><b class="border-bottom border-primary">Дедлайн</b></dt>
								<dd><?php echo date("d.m.Y",strtotime($end_date)) ?></dd>
							</dl>
							<dl>
<dt><b class="border-bottom border-primary">Статус</b></dt>
								<dd>
									<?php
									  if($stat[$status] =='Ожидает'){
									  	echo "<span class='badge badge-secondary'>{$stat[$status]}</span>";
									  }elseif($stat[$status] =='Стартовал'){
									  	echo "<span class='badge badge-primary'>{$stat[$status]}</span>";
									  }elseif($stat[$status] =='В работе'){
									  	echo "<span class='badge badge-info'>{$stat[$status]}</span>";
									  }elseif($stat[$status] =='Пауза'){
									  	echo "<span class='badge badge-warning'>{$stat[$status]}</span>";
									  }elseif($stat[$status] =='Просрочен'){
									  	echo "<span class='badge badge-danger'>{$stat[$status]}</span>";
									  }elseif($stat[$status] =='Завершен'){
									  	echo "<span class='badge badge-success'>{$stat[$status]}</span>";
									  }
									?>
								</dd>
							</dl>
							<dl>
<dt><b class="border-bottom border-primary">Руководитель</b></dt>
								<dd>
									<?php if(isset($manager['id'])) : ?>
									<div class="d-flex align-items-center mt-1">
										<img class="img-circle img-thumbnail p-0 shadow-sm border-info img-sm mr-3" src="assets/uploads/<?php echo $manager['avatar'] ?>" alt="Аватар">
										<b><?php echo ucwords($manager['name']) ?></b>
									</div>
									<?php else: ?>
<small><i>Менеджер удален</i></small>
									<?php endif; ?>
								</dd>
							</dl>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<div class="card card-outline card-primary">
				<div class="card-header">
					<b>Календарь проекта</b>
				</div>
				<div class="card-body">
					<div id="project-calendar"></div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-4">
			<div class="card card-outline card-primary">
				<div class="card-header">
					<span><b>Участники команды:</b></span>
					<div class="card-tools">
					</div>
				</div>
				<div class="card-body">
					<ul class="users-list clearfix">
						<?php 
						if(!empty($user_ids)):
							$members = $conn->query("SELECT *,concat(firstname,' ',lastname) as name FROM users where id in ($user_ids) order by concat(firstname,' ',lastname) asc");
							while($row=$members->fetch_assoc()):
						?>
								<li>
			                        <img src="assets/uploads/<?php echo $row['avatar'] ?>" alt="Изображение пользователя">
			                        <a class="users-list-name" href="javascript:void(0)"><?php echo ucwords($row['name']) ?></a>
		                    	</li>
						<?php 
							endwhile;
						endif;
						?>
					</ul>
				</div>
			</div>
		</div>
		<div class="col-md-8">
			<div class="card card-outline card-primary">
				<div class="card-header">
					<span><b>Список задач:</b></span>
					<?php if($_SESSION['login_type'] != 3): ?>
					<div class="card-tools">
						<button class="btn btn-primary bg-gradient-primary btn-sm" type="button" id="new_task"><i class="fa fa-plus"></i> Новая задача</button>
					</div>
				<?php endif; ?>
				</div>
				<div class="card-body p-0">
					<div class="table-responsive">
					<table class="table table-condensed m-0 table-hover">
						<colgroup>
							<col width="5%">
							<col width="25%">
							<col width="30%">
							<col width="20%">
							<col width="10%">
							<col width="15%">
						</colgroup>
						<thead>
							<th>#</th>
							<th>Задача</th>
							<th>Описание</th>
							<th>Исполнитель</th>
							<th>Статус</th>
							<th>Действия</th>
						</thead>
						<tbody>
							<?php 
							$i = 1;
							$tasks = $conn->query("SELECT t.*, concat(u.firstname,' ',u.lastname) as assignee_name FROM task_list t left join users u on u.id = t.assignee_id where project_id = {$id} order by task asc");
							while($row=$tasks->fetch_assoc()):
								$trans = get_html_translation_table(HTML_ENTITIES,ENT_QUOTES);
								unset($trans["\""], $trans["<"], $trans[">"], $trans["<h2"]);
								$desc = strtr(html_entity_decode($row['description']),$trans);
								$desc=str_replace(array("<li>","</li>"), array("",", "), $desc);
							?>
								<tr>
			                        <td class="text-center"><?php echo $i++ ?></td>
			                        <td class=""><b><?php echo ucwords($row['task']) ?></b></td>
			                        <td class=""><p class="truncate"><?php echo strip_tags($desc) ?></p></td>
			                        <td class="">
			                        	<?php if(!empty($row['assignee_name'])): ?>
				                        	<span class="badge badge-light border"><?php echo ucwords($row['assignee_name']) ?></span>
				                        	<?php if(isset($_SESSION['login_id']) && $row['assignee_id'] == $_SESSION['login_id']): ?>
				                        		<span class="badge badge-success">Моя задача</span>
				                        	<?php endif; ?>
			                        	<?php else: ?>
			                        		<span class="text-muted">Не назначено</span>
			                        	<?php endif; ?>
			                        </td>
			                        <td>
			                        	<?php 
			                        	if($row['status'] == 1){
									  		echo "<span class='badge badge-secondary'>Ожидает</span>";
			                        	}elseif($row['status'] == 2){
									  		echo "<span class='badge badge-primary'>В работе</span>";
			                        	}elseif($row['status'] == 3){
									  		echo "<span class='badge badge-success'>Готово</span>";
			                        	}
			                        	?>
			                        </td>
			                        <td class="text-center">
										<button type="button" class="btn btn-default btn-sm btn-flat border-info wave-effect text-info dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
					                      Действия
					                    </button>
					                    <div class="dropdown-menu" style="">
					                      <a class="dropdown-item view_task" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"  data-task="<?php echo $row['task'] ?>">Открыть</a>
					                      <div class="dropdown-divider"></div>
					                      <?php if($_SESSION['login_type'] != 3): ?>
					                      <a class="dropdown-item edit_task" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"  data-task="<?php echo $row['task'] ?>">Изменить</a>
					                      <div class="dropdown-divider"></div>
					                      <a class="dropdown-item delete_task" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">Удалить</a>
					                      <?php elseif($_SESSION['login_type'] == 3 && isset($row['assignee_id']) && $row['assignee_id'] == $_SESSION['login_id']): ?>
					                      <a class="dropdown-item change_task_status" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"  data-task="<?php echo $row['task'] ?>">Изменить статус</a>
					                      <?php endif; ?>
					                    </div>
									</td>
		                    	</tr>
							<?php 
							endwhile;
							?>
						</tbody>
					</table>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<div class="card">
				<div class="card-header">
					<b>Прогресс участников</b>
					<div class="card-tools">
						<button class="btn btn-primary bg-gradient-primary btn-sm" type="button" id="new_productivity"><i class="fa fa-plus"></i> Новый прогресс</button>
					</div>
				</div>
				<div class="card-body">
					<?php 
					$progress = $conn->query("SELECT p.*,concat(u.firstname,' ',u.lastname) as uname,u.avatar,t.task FROM user_productivity p inner join users u on u.id = p.user_id inner join task_list t on t.id = p.task_id where p.project_id = $id order by unix_timestamp(p.date_created) desc ");
					while($row = $progress->fetch_assoc()):
					?>
						<div class="post">

		                      <div class="user-block">
		                      	<?php if($_SESSION['login_id'] == $row['user_id']): ?>
		                      	<span class="btn-group dropleft float-right">
								  <span class="btndropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="cursor: pointer;">
								    <i class="fa fa-ellipsis-v"></i>
								  </span>
								  <div class="dropdown-menu">
								  	<a class="dropdown-item manage_progress" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"  data-task="<?php echo $row['task'] ?>">Изменить</a>
			                      	<div class="dropdown-divider"></div>
				                     <a class="dropdown-item delete_progress" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">Удалить</a>
								  </div>
								</span>
								<?php endif; ?>
		                        <img class="img-circle img-bordered-sm" src="assets/uploads/<?php echo $row['avatar'] ?>" alt="Аватар пользователя">
		                        <span class="username">
		                          <a href="#"><?php echo ucwords($row['uname']) ?>[ <?php echo ucwords($row['task']) ?> ]</a>
		                        </span>
		                        <span class="description">
		                        	<span class="fa fa-calendar-day"></span>
		                        	<span><b><?php echo date('d.m.Y',strtotime($row['date'])) ?></b></span>
		                        	<span class="fa fa-user-clock"></span>
                      				<span>Старт: <b><?php echo date('H:i',strtotime($row['date'].' '.$row['start_time'])) ?></b></span>
		                        	<span> | </span>
                      				<span>Окончание: <b><?php echo date('H:i',strtotime($row['date'].' '.$row['end_time'])) ?></b></span>
	                        	</span>

	                        	

		                      </div>
		                      <!-- /.user-block -->
		                      <div>
		                       <?php echo html_entity_decode($row['comment']) ?>
		                      </div>
	                    </div>
	                    <div class="post clearfix"></div>
                    <?php endwhile; ?>
				</div>
			</div>
		</div>
	</div>
</div>
<style>
	.users-list>li img {
	    border-radius: 50%;
	    height: 67px;
	    width: 67px;
	    object-fit: cover;
	}
	.users-list>li {
		width: 33.33% !important
	}
	.truncate {
		-webkit-line-clamp:1 !important;
	}
	.fc-event.my-task {
		background-color: #28a745 !important;
		border-color: #1f8c39 !important;
		color: #fff !important;
	}
</style>
<script>
	const CURRENT_USER_ID = <?php echo isset($_SESSION['login_id']) ? (int)$_SESSION['login_id'] : 0; ?>;
	$('#new_task').click(function(){
		uni_modal("Новая задача для <?php echo ucwords($name) ?>","manage_task.php?pid=<?php echo $id ?>","mid-large")
	})
	$('.edit_task').click(function(){
		uni_modal("Изменить задачу: "+$(this).attr('data-task'),"manage_task.php?pid=<?php echo $id ?>&id="+$(this).attr('data-id'),"mid-large")
	})
	$('.change_task_status').click(function(){
		uni_modal("Изменить статус задачи: "+$(this).attr('data-task'),"manage_task_status.php?id="+$(this).attr('data-id'),"mid-large")
	})
	$('.view_task').click(function(){
		uni_modal("Детали задачи","view_task.php?id="+$(this).attr('data-id'),"mid-large")
	})
	$('#new_productivity').click(function(){
		<?php if(isset($_SESSION['login_type']) && $_SESSION['login_type'] == 3): ?>
		// Для сотрудников показываем только свои задачи
		uni_modal("<i class='fa fa-plus'></i> Новый прогресс (только для ваших задач)","manage_progress.php?pid=<?php echo $id ?>",'large')
		<?php else: ?>
		uni_modal("<i class='fa fa-plus'></i> Новый прогресс","manage_progress.php?pid=<?php echo $id ?>",'large')
		<?php endif; ?>
	})
	$('.manage_progress').click(function(){
		uni_modal("<i class='fa fa-edit'></i> Изменить прогресс","manage_progress.php?pid=<?php echo $id ?>&id="+$(this).attr('data-id'),'large')
	})
	$('.delete_progress').click(function(){
	_conf("Удалить запись о прогрессе?","delete_progress",[$(this).attr('data-id')])
	})
	function delete_progress($id){
		start_load()
		$.ajax({
			url:'ajax.php?action=delete_progress',
			method:'POST',
			data:{id:$id},
			success:function(resp){
				if(resp==1){
					alert_toast("Запись удалена",'success')
					setTimeout(function(){
						location.reload()
					},1500)

				}
			}
		})
	}
	document.addEventListener('DOMContentLoaded', function() {
		var calendarEl = document.getElementById('project-calendar');
		var calendar = new FullCalendar.Calendar(calendarEl, {
			initialView: 'dayGridMonth',
			headerToolbar: {
				left: 'prev,next today',
				center: 'title',
				right: 'dayGridMonth,timeGridWeek,timeGridDay'
			},
			events: {
				url: 'ajax.php?action=project_events&pid=<?php echo $id ?>',
				failure: function() {
					alert_toast('Не удалось загрузить данные календаря','error');
				}
			},
			eventClick: function(info){
				info.jsEvent.preventDefault();
				uni_modal("Детали задачи","view_task.php?id="+info.event.id,"mid-large");
			},
			eventDidMount: function(info){
				var props = info.event.extendedProps || {};
				if(CURRENT_USER_ID && props.assignee_id && parseInt(props.assignee_id) === CURRENT_USER_ID){
					info.el.classList.add('my-task');
				}
				var title = info.event.title;
				var assignee = props.assignee ? 'Исполнитель: '+props.assignee+'\n' : '';
				var status = props.status ? 'Статус: '+props.status+'\n' : '';
				var desc = props.description ? $('<div>').html(props.description).text() : '';
				info.el.setAttribute('title', title+"\n"+assignee+status+desc);
			}
		});
		calendar.render();
	});
</script>