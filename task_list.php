<?php include'db_connect.php' ?>
<div class="col-lg-12">
	<div class="card card-outline card-success">
		<div class="card-header">
			<div class="card-tools">
				<a class="btn btn-block btn-sm btn-default btn-flat border-primary" href="./index.php?page=new_project"><i class="fa fa-plus"></i> Добавить проект</a>
			</div>
		</div>
		<div class="card-body">
			<table class="table tabe-hover table-condensed" id="list">
				<colgroup>
					<col width="5%">
					<col width="15%">
					<col width="20%">
					<col width="13%">
					<col width="18%">
					<col width="13%">
					<col width="13%">
					<col width="13%">
					<col width="10%">
					<col width="10%">
					<col width="10%">
				</colgroup>
				<thead>
					<tr>
						<th class="text-center">#</th>
						<th>Проект</th>
						<th>Задача</th>
						<th>Старт проекта</th>
						<th>Дедлайн проекта</th>
						<th>Статус проекта</th>
						<th>Исполнитель</th>
						<th>Статус задачи</th>
						<th>Действия</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$i = 1;
					$where = "";
					if($_SESSION['login_type'] == 2){
						$where = " where p.manager_id = '{$_SESSION['login_id']}' ";
					}elseif($_SESSION['login_type'] == 3){
						$where = " where concat('[',REPLACE(p.user_ids,',','],['),']') LIKE '%[{$_SESSION['login_id']}]%' ";
					}
					
					$stat = array("Ожидает","Стартовал","В работе","Пауза","Просрочен","Завершен");
					$qry = $conn->query("SELECT t.*,p.name as pname,p.start_date,p.status as pstatus, p.end_date,p.id as pid, concat(u.firstname,' ',u.lastname) as assignee_name FROM task_list t inner join project_list p on p.id = t.project_id left join users u on u.id = t.assignee_id $where order by p.name asc");
					while($row= $qry->fetch_assoc()):
						$trans = get_html_translation_table(HTML_ENTITIES,ENT_QUOTES);
						unset($trans["\""], $trans["<"], $trans[">"], $trans["<h2"]);
						$desc = strtr(html_entity_decode($row['description']),$trans);
						$desc=str_replace(array("<li>","</li>"), array("",", "), $desc);
						$tprog = $conn->query("SELECT * FROM task_list where project_id = {$row['pid']}")->num_rows;
		                $cprog = $conn->query("SELECT * FROM task_list where project_id = {$row['pid']} and status = 3")->num_rows;
						$prog = $tprog > 0 ? ($cprog/$tprog) * 100 : 0;
		                $prog = $prog > 0 ?  number_format($prog,2) : $prog;
		                $prod = $conn->query("SELECT * FROM user_productivity where project_id = {$row['pid']}")->num_rows;
		                if($row['pstatus'] == 0 && strtotime(date('Y-m-d')) >= strtotime($row['start_date'])):
		                if($prod  > 0  || $cprog > 0)
		                  $row['pstatus'] = 2;
		                else
		                  $row['pstatus'] = 1;
		                elseif($row['pstatus'] == 0 && strtotime(date('Y-m-d')) > strtotime($row['end_date'])):
		                $row['pstatus'] = 4;
		                endif;


					?>
					<tr>
						<td class="text-center"><?php echo $i++ ?></td>
						<td>
							<p><b><?php echo ucwords($row['pname']) ?></b></p>
						</td>
						<td>
							<p><b><?php echo ucwords($row['task']) ?></b></p>
							<p class="truncate"><?php echo strip_tags($desc) ?></p>
						</td>
						<td><b><?php echo date("d.m.Y",strtotime($row['start_date'])) ?></b></td>
						<td><b><?php echo date("d.m.Y",strtotime($row['end_date'])) ?></b></td>
						<td class="text-center">
							<?php
							  if($stat[$row['pstatus']] =='Ожидает'){
							  	echo "<span class='badge badge-secondary'>{$stat[$row['pstatus']]}</span>";
							  }elseif($stat[$row['pstatus']] =='Стартовал'){
							  	echo "<span class='badge badge-primary'>{$stat[$row['pstatus']]}</span>";
							  }elseif($stat[$row['pstatus']] =='В работе'){
							  	echo "<span class='badge badge-info'>{$stat[$row['pstatus']]}</span>";
							  }elseif($stat[$row['pstatus']] =='Пауза'){
							  	echo "<span class='badge badge-warning'>{$stat[$row['pstatus']]}</span>";
							  }elseif($stat[$row['pstatus']] =='Просрочен'){
							  	echo "<span class='badge badge-danger'>{$stat[$row['pstatus']]}</span>";
							  }elseif($stat[$row['pstatus']] =='Завершен'){
							  	echo "<span class='badge badge-success'>{$stat[$row['pstatus']]}</span>";
							  }
							?>
						</td>
						<td>
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
			                      <?php if(isset($_SESSION['login_type']) && $_SESSION['login_type'] == 3 && isset($row['assignee_id']) && $row['assignee_id'] == $_SESSION['login_id']): ?>
			                      <a class="dropdown-item change_task_status" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"  data-task="<?php echo ucwords($row['task']) ?>">Изменить статус</a>
			                      <div class="dropdown-divider"></div>
			                      <a class="dropdown-item new_productivity" data-pid = '<?php echo $row['pid'] ?>' data-tid = '<?php echo $row['id'] ?>'  data-task = '<?php echo ucwords($row['task']) ?>'  href="javascript:void(0)">Добавить прогресс</a>
			                      <?php elseif(isset($_SESSION['login_type']) && $_SESSION['login_type'] != 3): ?>
			                      <a class="dropdown-item new_productivity" data-pid = '<?php echo $row['pid'] ?>' data-tid = '<?php echo $row['id'] ?>'  data-task = '<?php echo ucwords($row['task']) ?>'  href="javascript:void(0)">Добавить прогресс</a>
			                      <?php endif; ?>
								</div>
						</td>
					</tr>	
				<?php endwhile; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<style>
	table p{
		margin: unset !important;
	}
	table td{
		vertical-align: middle !important
	}
</style>
<script>
	$(document).ready(function(){
		$('#list').dataTable()
	$('.new_productivity').click(function(){
		uni_modal("<i class='fa fa-plus'></i> Новый прогресс для: "+$(this).attr('data-task'),"manage_progress.php?pid="+$(this).attr('data-pid')+"&tid="+$(this).attr('data-tid'),'large')
	})
	$('.change_task_status').click(function(){
		uni_modal("Изменить статус задачи: "+$(this).attr('data-task'),"manage_task_status.php?id="+$(this).attr('data-id'),"mid-large")
	})
	})
	function delete_project($id){
		start_load()
		$.ajax({
			url:'ajax.php?action=delete_project',
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
</script>