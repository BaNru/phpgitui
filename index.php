<?php

/**
 * PHP GIT UI
 *
 * @author BaNru <admin@g63.ru>
 * @version 0.1 pre-alpha
 * @link https://github.com/BaNru
 *
 * @todo 
 *     - Объединить однотипные запросы
 *     - FETCH
 *     - Протестировать работу с удалёнными файлами
 */

// echo __DIR__;

// Защита. Проверка .htaccess и пароля
$htaccess = '.htaccess';
if (!file_exists($htaccess)) {
	echo "Создайте .htaccess и установите пароль на папку!<br>";
	exit;
}
$htaccess_text =  file_get_contents($htaccess);
if (!preg_match("/^AuthUserFile.*\.htpasswd$/m", $htaccess_text)){
	echo "Не установлен пароль на папку!<br>",
		 "Создайте .htpasswd и укажите путь до него";
	exit;
}

require_once "functions.php";
?>

<!DOCTYPE html>
<html>
<head><meta charset="utf-8">
<title>PHP GIT UI</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, target-densitydpi=device-dpi">
<link rel="stylesheet" href="style.css" type="text/css" media="all">
</head><body>
<header>
	<nav><ol>
		<li><a href="?">home</a></li>
		<li><a href="?status">status</a></li>
		<li><a href="?log">log</a></li>
		<li><a href="?gitignore">gitignore</a></li>
		<li><a href="?raw">raw</a></li>
	</ol></nav>
</header>

<?php

if (isset($_GET['add'])){
	$command = 'git add '.urldecode($_GET['add']);
	$get_ = executeCommand($command);
	show(['ADD', $command], $get_[0],$get_);
}
if (isset($_GET['rm'])){
	$get_ = executeCommand('git rm '.urldecode($_GET['rm']));
	show(['REMOVE',urldecode($_GET['rm'])], $get_[0],$get_);
}
if (isset($_GET['diff'])){
	$get_ = executeCommand('git diff '.urldecode($_GET['diff']));
	show('DIFF: '.urldecode($_GET['diff']), formatDiff(htmlspecialchars($get_[0])),$get_);
}
if (isset($_GET['reset'])){
	$get_ = executeCommand('git reset HEAD '.urldecode($_GET['reset']));
	show('RESET: '.urldecode($_GET['reset']), $get_[0], $get_);
}
if (isset($_GET['checkout'])){
	$get_ = executeCommand('git checkout -- '.urldecode($_GET['checkout']));
	show('RESET: '.urldecode($_GET['checkout']), $get_[0], $get_);
}
if(isset($_GET['status'])){
	$get_ = executeCommand('git status');
	show('STATUS', $get_[0], $get_);
}
if(isset($_GET['log'])){
	$get_ = executeCommand('git log');
	show('LOG', $get_[0], $get_,true);
}
if (isset($_GET['pull'])){
	//$get_url = executeCommand('git remote get-url '.$_GET['pull']);
	$get_url = executeCommand('git config --get remote.'.$_GET['pull'].'.url');
	if (isset($_POST['password'])){
		$get_url = preg_replace("#\[PASSWORD\]#",$_POST['password'],$get_url);
		$get_ = executeCommand('git pull '.$get_url[0]);
		show('PULL: git pull '.$get_url[0], $get_[0], $get_);
	} else if(preg_match("#\[PASSWORD\]#",$get_url[0])){
		echo '<form method="post">Введите пароль: <input name="password"></form>';
	} else {
		$get_ = executeCommand('git pull '.$_GET['pull'].' '.$_GET['b']);
		show('PULL: git pull ', $get_[0], $get_);
	}
}
if (isset($_GET['push'])){
	//$get_url = executeCommand('git remote get-url '.$_GET['push']);
	$get_url = executeCommand('git config --get remote.'.$_GET['push'].'.url');
	if (isset($_POST['password'])){
		$get_url = preg_replace("#\[PASSWORD\]#",$_POST['password'],$get_url);
		$get_ = executeCommand('git push '.$get_url[0]);
		show('PUSH: git push '.$get_url[0], $get_[0], $get_);
	} else if(preg_match("#\[PASSWORD\]#",$get_url[0])){
		echo '<form method="post">Введите пароль: <input name="password"></form>';
	} else {
		$get_ = executeCommand('git push '.$_GET['push'].' '.$_GET['b']);
		show('PUSH: git push ', $get_[0], $get_);
	}
}
if (isset($_GET['commit'])){
	if (isset($_POST['text']) && !empty($_POST['text'])){
		$get_ = executeCommand('git commit -m "'.str_replace('"',"'",$_POST['text']).'"');
		show('COMMIT', $get_[0], $get_);
		/* Исправление ошибки git config NAME and EMAIL */
		if(!empty($get_[2]) && $get_[2]==128){
			?><h2>Исправить ошибку с git config NAME and EMAIL</h2>
			<form action="?raw" method="post">
				<label>Укажите ваше имя: <input name="config_userDOTname_"></label><br>
				<label>Укажите ваше e-mail: <input name="config_userDOTemail_"></label><br>
				<button>ИСПРАВИТЬ</button>
			</form><?php
		}
	}
}
if (isset($_GET['raw'])){
	if (!empty($_POST)){
		foreach ($_POST as $key => $item) {
			$text = 'git '.str_replace('_', ' ', str_replace('DOT','.',$key)) . $item;
			echo $text;
		    $get_ = executeCommand($text);
			show('RAW: '.$text, $get_[0], $get_);
		}
	} else {

		if(empty($_GET['raw'])){
?>
			<form action="?raw" method="post">
				<label>Добавить сервер (указать название и сервер через пробел): <input name="remote_add_"></label><br>
				<button>Добавить</button>
			</form>
<?php
			/* Исправление ошибки git config NAME and EMAIL */
?>			<h2>Исправить ошибку с git config NAME and EMAIL</h2>
<?php
$get_ = executeCommand('git config user.name');
show('Запрос имени:', $get_[0], $get_);

$get_ = executeCommand('git config user.email');
show('Запрос e-mail:', $get_[0], $get_);
?>
			<form action="?raw" method="post">
				<label>Укажите ваше имя: <input name="config_userDOTname_"></label><br>
				<label>Укажите ваше e-mail: <input name="config_userDOTemail_"></label><br>
				<button>ИСПРАВИТЬ</button>
			</form>
<?php
/* RAW команды в консоль на свой страх и риск!
			?><form method="get">
				<label>Введите команду на свой страх и риск: <input name="config_userDOTname_"></label><br>
				<button>Отправить</button>
			</form>
			<?php
		} else {
		$get_ = executeCommand(urldecode($_GET['raw']));
		show('RAW', $get_[0], $get_);
*/
		}
	}
}

/*Добавлен эффект (анимация) золотого свечения на кнопку "в корзину"*/
if(isset($_GET['gitignore'])){
	$gitignore = '.gitignore';
	if (!file_exists($gitignore)) {
		$gitignore_file = file_get_contents('../'.$gitignore);
		if(!empty($_GET['gitignore'])){
			$gitignore_file .= PHP_EOL.urldecode($_GET['gitignore']);
			file_put_contents('../'.$gitignore, $gitignore_file);
		}
		show('Gitignore', $gitignore_file, [0,0,0]);
	} else {
		show('Gitignore', '', [0,'File .gitignore note found!',0]);
	}
}

if (empty($_GET)){
	git_status();
?>
	<form action="?commit" method="post">
		<textarea name="text"></textarea>
		<button>COMMIT</button>
	</form>
	<ol class="two_column"><li><strong>PULL</strong></li><li><strong>PUSH</strong></li>
<?php
	$remote = executeCommand('git remote');
	$branch = executeCommand('git branch');
	$remote_e = explode("\n", trim($remote[0]));
	$branch_e = explode("\n", trim($branch[0]));
	$remote_c = count($remote_e);
	$branch_c = count($branch_e);
	for($i = 0; $i < $remote_c; ++$i) {
		echo '<li class="color3">'.$remote_e[$i].'</li>',
			 '<li class="color3">'.$remote_e[$i].'</li>';
		for($a = 0; $a < $branch_c; ++$a) {
			echo '<li><a href="?pull='.urldecode($remote_e[$i].'&b='.trim(str_replace('*','',$branch_e[$a]))).'">'.$branch_e[$a].'</a></li>';
			echo '<li><a href="?push='.urldecode($remote_e[$i].'&b='.trim(str_replace('*','',$branch_e[$a]))).'">'.$branch_e[$a].'</a></li>';
		}
	}
	echo '</ol>';

	git_log();
	$gitbranch = executeCommand('git branch -v -v');
	show('Branch', $gitbranch[0], $gitbranch);
	
	$gitbranch = executeCommand('git remote -v');
	show('Remote', $gitbranch[0], $gitbranch);

}

?>
</body></html>