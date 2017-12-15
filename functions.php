<?php
/**
 * Functions for PHP GIT UI
 *
 * @author BaNru <admin@g63.ru>
 * @link https://github.com/BaNru
 */


/**
 * Console / executeCommand
 * Функция ввода/вывода в ОС
 *
 * @author Anton Medvedev <anton@elfet.ru>
 * @link https://github.com/elfet/console
 *
 * @param string $command
 */
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

/**
 * Console / formatDiff
 * Оформление вывода diff
 *
 * @author Andrew8xx8
 * @link https://github.com/antonmedv/console/commit/8c97367b28eaeca15667338658ce69f4110e7c02
 *
 * @param string $output
 */
function formatDiff($output){
	$lines = explode("\n", $output);
	foreach ($lines as $key => $line) {
		if (strpos($line, "-") === 0) {
			$lines[$key] = '<span class="diff-deleted">' . $line . '</span>';
		}
		else if (strpos($line, "+") === 0) {
			if (preg_match("/.*[[:blank:]]$/is", $lines[$key])) {
				$lines[$key] = preg_replace("/(.*?)([[:blank:]]+)$/is",
							'$1<span class="diff-empty">$2</span>', $lines[$key]);
			}
			$lines[$key] = '<span class="diff-added">' . $lines[$key] . '</span>';
		}
		else if (preg_match("%^@@.*?@@%is", $line)) {
			$lines[$key] = '<span class="diff-sub-header">' . $line . '</span>';
		}
		else if (preg_match("%^index\s[^.]*?\.\.\S*?\s\S*?%is", $line) || preg_match("%^diff.*?a.*?b%is", $line)) {
			$lines[$key] = '<span class="diff-header">' . $line . '</span>';
		}
		else {
			$lines[$key] = $line;
		}
	}

	return implode("\n", $lines);
}


/**
 * Вывод текста на страницу

 * @param string or array $h - Заголовок
 *   array[Заголовок, Команда]
 * @param string $text - Обработанный вывод
 * @param string $r - Array из executeCommand()
 * @param boolean $rw- htmlspecialchars true/false, не обязательный, по умолчанию false
 * @param boolean $options[1] - вывод комманды на экран, не обязательный, по умолчанию true
 */
function show($h,$text,$r,$rw=false){
	if(is_array($h)){
		if(!empty($h[0])){
			echo '<h1>'.$h[0].'</h1>';
		} else {
			echo '<br>';
		}
		echo '<small style="padding-left:2em">> '.$h[1].'</small>';
	} else {
		echo '<h1>'.$h.'</h1>';
	}
	if ($r[1]) {
		echo '<pre class="color4">'.trim($r[1]).'</pre> Error code: <span class="color5">'.$r[2].'</span>';
	} else {
		if(!empty($text)){
			if($rw){
				$text = htmlspecialchars($text);
			}
			echo '<pre>'.$text.'</pre>';
		}
	}
}

/**
 * Команда git status
 */
function git_status(){
	$status = executeCommand('git status --porcelain');
	/* Для status без опций */
	/*
	$text = preg_replace_callback(
		'/(modified:)(.*)$/m',
		function ($m) {
			return '<span class="color4">'.$m[1].'</span> <strong class="color4">'.$m[2].'</strong> <a href="?add='.urldecode(trim($m[2])).'">add</a> <a href="?diff='.urldecode(trim($m[2])).'">diff</a>';
		},
		$status[0]
	);
	*/
	$newtext = [];
	$newtext[0] = ""; // add
	$newtext[1] = ""; // 
	$newtext[2] = "";
	/* Добавленные */
	$text = preg_replace_callback(
		'/^A\s\s(.*)$/m',
		function ($m) use (&$newtext) {
			$newtext[0] .= '  add new: <span class="color3">'.$m[1].'</span> <a href="?reset='.urldecode(trim($m[1])).'">reset</a>'.PHP_EOL;
		},
		$status[0]
	);
	$text = preg_replace_callback(
		'/^D\s\s(.*)$/m',
		function ($m) use (&$newtext) {
			$newtext[0] .= '  deleted: <span class="color3">'.$m[1].'</span> <a href="?reset='.urldecode(trim($m[1])).'">reset</a>'.PHP_EOL;
		},
		$status[0]
	);
	$text = preg_replace_callback(
		'/^M\s\s(.*)$/m',
		function ($m) use (&$newtext) {
			$newtext[0] .= '  modified: <span class="color3">'.$m[1].'</span> <a href="?reset='.urldecode(trim($m[1])).'">reset</a>'.PHP_EOL;
		},
		$status[0]
	);
	/* Не добавленные */
	$text = preg_replace_callback(
		'/^\sM\s(.*)$/m',
		function ($m) use (&$newtext) {
			$newtext[1] .= '  modified: <span class="color4">'.$m[1].'</span> <a href="?add='.urldecode(trim($m[1])).'">add</a> <a href="?checkout='.urldecode(trim($m[1])).'">checkout</a> <a href="?diff='.urldecode(trim($m[1])).'">diff</a>'.PHP_EOL;
		},
		$status[0]
	);
	/* Удалённые */
	$text = preg_replace_callback(
		'/^\sD\s(.*)$/m',
		function ($m) use (&$newtext) {
			$newtext[1] .= '  deleted: <span class="color4">'.$m[1].'</span> <a href="?add='.urldecode(trim($m[1])).'">add</a> <a href="?checkout='.urldecode(trim($m[1])).'">checkout</a> <a href="?rm='.urldecode(trim($m[1])).'">rm</a>'.PHP_EOL;
		},
		$status[0]
	);
	/* Не отслеживаемые */
	$text = preg_replace_callback(
		'/^\?\?\s(.*)$/m',
		function ($m) use (&$newtext) {
			$newtext[2] .= '  <span class="color4">'.$m[1].'</span> <a href="?add='.urldecode(trim($m[1])).'">add</a></a> <a href="?checkout='.urldecode(trim($m[1])).'">checkout</a> <a href="?gitignore='.urldecode(trim($m[1])).'">gitignore</a>'.PHP_EOL;
		},
		$status[0]
	);

	$newtext2 = '';
	if(!empty($newtext[0])){
		$newtext2 .= '<strong class="color3">Add to committed</strong>:'.PHP_EOL.$newtext[0].PHP_EOL;
	}
	if(!empty($newtext[1])){
		$newtext2 .= '<strong class="color4">Not updated</strong>:'.PHP_EOL.$newtext[1].PHP_EOL;
	}
	if(!empty($newtext[2])){
		$newtext2 .= '<strong class="color4">Untracked</strong>:'.PHP_EOL.$newtext[2];
	}

	show('Status', $newtext2, $status);
}

/**
 * Команда git log
 */
function git_log(){
	$log = executeCommand('git log -3  --graph --abbrev-commit --decorate --all --pretty=format:"<strong class="color1">%d%n</strong> <strong><a href="?checkout_c=%h">%h</a></strong> <strong class="color3">%an</strong> - <span class="color5">%aD</span> (%ar)<strong class="color1">%n</strong> <span class="color4">%s</span>"');
	show('LOG',$log[0],$log);
}
