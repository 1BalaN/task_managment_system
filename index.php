<!DOCTYPE html>
<html lang="ru">
<?php session_start() // Запускаем сессию для работы с пользовательскими данными ?>
<?php 
	// Проверка авторизации: если пользователь не авторизован, перенаправляем на страницу входа
	if(!isset($_SESSION['login_id']))
	    header('location:login.php');
    include 'db_connect.php'; // Подключаемся к базе данных
    ob_start(); // Включаем буферизацию вывода
  // Загружаем настройки системы в сессию, если они еще не загружены
  if(!isset($_SESSION['system'])){

    $system = $conn->query("SELECT * FROM system_settings")->fetch_array(); // Получаем настройки из БД
    foreach($system as $k => $v){
      $_SESSION['system'][$k] = $v; // Сохраняем каждую настройку в сессию
    }
  }
  ob_end_flush(); // Завершаем буферизацию

	include 'header.php' // Подключаем шапку сайта с мета-тегами и стилями
?>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
  <?php include 'topbar.php' ?>
  <?php include 'sidebar.php' ?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
  	 <div class="toast" id="alert_toast" role="alert" aria-live="assertive" aria-atomic="true">
	    <div class="toast-body text-white">
	    </div>
	  </div>
    <div id="toastsContainerTopRight" class="toasts-top-right fixed"></div>
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0"><?php echo $title ?></h1>
          </div><!-- /.col -->

        </div><!-- /.row -->
            <hr class="border-primary">
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
         <?php 
            // Роутинг: определяем, какую страницу нужно отобразить
            // Если параметр page не указан, по умолчанию показываем home
            $page = isset($_GET['page']) ? $_GET['page'] : 'home';
            // Проверяем существование файла страницы
            if(!file_exists($page.".php")){
                include '404.html'; // Если файл не найден, показываем страницу 404
            }else{
            include $page.'.php'; // Подключаем файл страницы

            }
          ?>
      </div><!--/. container-fluid -->
    </section>
    <!-- /.content -->
    <div class="modal fade" id="confirm_modal" role='dialog'>
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title">Подтверждение</h5>
      </div>
      <div class="modal-body">
        <div id="delete_content"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id='confirm' onclick="">Продолжить</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Закрыть</button>
      </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="uni_modal" role='dialog'>
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title"></h5>
      </div>
      <div class="modal-body">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id='submit' onclick="$('#uni_modal form').submit()">Сохранить</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
      </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="uni_modal_right" role='dialog'>
    <div class="modal-dialog modal-full-height  modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title"></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Закрыть">
          <span class="fa fa-arrow-right"></span>
        </button>
      </div>
      <div class="modal-body">
      </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="viewer_modal" role='dialog'>
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
              <button type="button" class="btn-close" data-dismiss="modal"><span class="fa fa-times"></span></button>
              <img src="" alt="">
      </div>
    </div>
  </div>
  </div>
  <!-- /.content-wrapper -->

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->

  <!-- Main Footer -->
  <footer class="main-footer">
    <strong>Copyright &copy; 2025.</strong>
    Все права защищены.
    <div class="float-right d-none d-sm-inline-block">
      <b><?php echo $_SESSION['system']['name'] ?></b>
    </div>
  </footer>
</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->
<!-- jQuery -->
<!-- Bootstrap -->
<?php include 'footer.php' ?>
</body>
</html>
