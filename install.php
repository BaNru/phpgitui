<!DOCTYPE html>
<html>
<head><meta charset="utf-8">
<title>Установка PHP GIT UI</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, target-densitydpi=device-dpi">
<link rel="stylesheet" href="https://cdn.rawgit.com/BaNru/phpgitui/master/style.css" type="text/css" media="all">
</head><body>

<?php

// Проверка proc_open()
if(!function_exists('proc_open')){
	echo 'Функция proc_open отсутсвует на сервере.';
	exit;
}


function executeCommand($command){
	$descriptors = array(
		0 => array("pipe", "r"), // stdin - read channel
		1 => array("pipe", "w"), // stdout - write channel
		2 => array("pipe", "w"), // stdout - error channel
		3 => array("pipe", "r"), // stdin - This is the pipe we can feed the password into
	);

	$process = proc_open("cd ../ &&" . $command, $descriptors, $pipes);

	if (!is_resource($process)) {
		die("Can't open resource with proc_open.");
	}

	// Nothing to push to input.
	fclose($pipes[0]);

	$output = stream_get_contents($pipes[1]);
	fclose($pipes[1]);

	$error = stream_get_contents($pipes[2]);
	fclose($pipes[2]);

	// TODO: Write passphrase in pipes[3].
	fclose($pipes[3]);

	// Close all pipes before proc_close!
	$code = proc_close($process);

	return array($output, $error, $code);
}


$files = scandir(__DIR__);
if(count($files)>3){
	echo '<h4 class="color4 t_c">Для установки необходима пустая директория! Удалите все файлы, кроме install.php</h4>';
	$files_er = true;
}


if(isset($_POST['login']) && isset($_POST['password'])){

	// Разворачиваем проект
	if(!empty($files_er)) {
		echo '<p><b>Пропускаем клонирование проекта.</b></p><p><small>Установщик обнаружил файлы в каталоге.<br>
			Чтобы переустановить PHP GIT UI - удалите все файлы, кроме install.php, из папки куда производится установка PHP GIT UI.</small></p>';
	}else{
		// Удаляем установщик. Так надо!
		$rm = executeCommand('cd '.__DIR__.' && rm '.__DIR__.'/install.php');
		if(!empty($rm[2])){
			echo '<p>Произошла ошибка при установке.</p><pre>';
			print_r($rm);
			echo '</pre>';
			exit;
		}

		$clone = executeCommand('cd '.__DIR__.' && git clone https://github.com/BaNru/phpgitui.git .');
		if(empty($clone[2])){
			echo '<p>PHP GIT UI склонирован на сервер</p>';
		}else{
			echo '<p>Ошибка клонирования проекта<br>Обновите страницу и повторите попытку.</p><pre>';
			print_r($clone);
			echo '</pre>';
			copy('https://raw.githubusercontent.com/BaNru/phpgitui/master/install.php', __DIR__.'/install.php');
			exit;
		}
	}

	// .htpasswd
	if(file_exists(__DIR__.'/.htpasswd') && !isset($_POST['c'])) { // Пропускаем
		$htpasswd = array('','','Пропускаем');
		echo '<p>Пропускаем создание файла авторизации (.htpasswd)</p>';
	}else{ // Создаём/перезаписываем файл
		if(empty($_POST['login']) && empty($_POST['password'])){
			echo '<p>Ошибка: логин и пароль не должны быть пустными!</p>';
		}else{
			$htpasswd = executeCommand('htpasswd -bc '.__DIR__.'/.htpasswd '.$_POST['login'].' '.$_POST['password']);
			if(empty($htpasswd[2])){
				echo '<p>Файл авторизации (.htpasswd) успешно создан/перезаписан</p>';
			}else{
				echo '<p>Ошибка создания файла авторизации (.htpasswd).</p><pre>';
				print_r($htpasswd);
				echo "</pre>";
			}
		}
	}

	// .htaccess
$htaccess = '### Доступ CMD GIT
AuthUserFile '.__DIR__.'/.htpasswd
AuthName "Private access"
AuthType Basic
Require valid-user

### Закрываем доступ к инсталятору
<Files "install.php">
Order Allow,Deny
Deny from all
</Files>

### Закрыть доступ к папке
#deny from all';
	file_put_contents('.htaccess', $htaccess);
	echo '<p>Файл .htaccess успешно создан.</p>';

	if(isset($_POST['gitignore'])){
		$dir = basename(__DIR__).'/';
		if(file_exists(__DIR__.'/../.gitignore')){
			$gitignore = file_get_contents(__DIR__.'/../.gitignore');
			if(preg_match("~^$dir$~m", $gitignore)){
				echo '<p>Папка '.$dir.' уже была в исключение. Пропускаем.</p>';
			}else{
				echo '<p>Папка '.$dir.' добавлена в исключение.</p>';
				file_put_contents(__DIR__.'/../.gitignore', PHP_EOL.$dir.PHP_EOL, FILE_APPEND);
			}
		}else{
			file_put_contents(__DIR__.'/../.gitignore', $dir);
			echo '<p>.gitignore создан и папка '.$dir.' добавлена в исключение.</p>';
		}
	}

	echo '<p>Установка завершена успешно.</p>';
	exit;
}

?>

<h1>Настройка авторизации для PHP GIT UI</h1>
<form action="#" method="POST" style="margin:0 auto;max-width:600px;">
	<label>Логин: <input type="text" name="login"></label><br><br>
	<label>Пароль: <input type="password" name="password"></label><br><br>
<?php
if(file_exists(__DIR__.'/.htpasswd')){
	echo '<label><input type="checkbox" name="c">Обнаружен файл .htpasswd, поставьте галочку, чтобы его перезаписать</label><br><br>';
}
?>
	<label><input type="checkbox" name="gitignore">Добавить PHP GIT UI в .gitignore</label><br><br>
	<input type="submit" value="Установить">
</form>