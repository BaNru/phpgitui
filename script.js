/*
 * async POST
 * Ассинхронный POST запрос git комманд
 *
 * @param {string} command = git command
 * - add
 * - patch
 * - rm
 * - checkout
 * - diff
 * - reset HEAD
 * - gitignore
 * @param {string} text - list of files with a space or text
 * @return {string} - git status
 */
async function POST(command,text) {
	const post = await fetch('index.php', {
		method: 'POST',
		credentials: 'include',
		headers: {
			'Accept': 'application/json',
			'Content-Type': 'application/json'
		},
		body: JSON.stringify({command: command, data: text})
	})
	return await post.text();
}

/*
 * multiple mouse selection
 * множественное выделение мышью
 */
var isClick = false,
	typeClick = false;
// var isShift = false;
document.addEventListener('mousedown', function(event) {
	isClick = true;
	if(event.target.closest('span') && !event.target.classList.contains('select')){
		typeClick = true;
	}
});
document.addEventListener('mouseup', function() {
	isClick = false;
	typeClick = false;
});
// document.addEventListener('keydown', function(event) {
// 	isShift = event.shiftKey;
// });
// document.addEventListener('keyup', function(event) {
// 	isShift = event.shiftKey;
// });
var prestatus = document.querySelector('pre.status');
prestatus.addEventListener('mousemove', function(e) {
	var t = e.target; // this
	if (isClick && t.closest('pre.status')) {
		//e.target.checked = !isShift;
		// if(e.target.closest('span')){
		// 	e.target.classList.toggle('select');
		// }
		if(e.target.closest('span')){
			if(typeClick == true){
				e.target.classList.add('select');
			}else{
				e.target.classList.remove('select');
			}
		}
		if(prestatus.querySelector('.select')){
			statusbtn.classList.remove('h');
		}else{
			statusbtn.classList.add('h');
		}
	}
});

/*
 * Command bar for ajax
 * Командная панель с ajax кнопками
 */
prestatus.insertAdjacentHTML('afterend','<ul class="statusbtn h">'
	+'<li data-command="add">add</li> '
	+'<li data-command="patch">patch</li> '
	+'<li data-command="rm">remove</li> '
	+'<li data-command="checkout">checkout</li> '
	+'<li data-command="diff">diff</li> '
	+'<li data-command="reset HEAD">reset</li> '
	+'<li data-command="gitignore">gitignore</li> '
	+'</ul>');
var statusbtn = document.querySelector('ul.statusbtn');
statusbtn.addEventListener('click',function(e){
	if(e.target.closest('li')){
		var files = '';
		prestatus.querySelectorAll('.select').forEach(function(item){
			 files += item.textContent+' '
		});
		POST(e.target.dataset.command,files).then(function(answer){
			if(e.target.dataset.command == 'patch'){
				prestatus.classList.remove('status');
				statusbtn.classList.add('h');
				prestatus.classList.add('patch');
				var btn = document.createElement('span');
				btn.className = 'btn';
				btn.textContent = 'Добавить патч';
				btn.addEventListener('click',function(){
					var text = prestatus.textContent.match(/[^\r\n]+/g);
					for (var i = 0; i < text.length; i++) {
					    if (text[i].match(/^#/)) {
					        text.splice(i--, 1);
					    }
					}
					console.log(text.join('\n'))
					POST('patchadd',text.join('\n')).then(function(answer){
						prestatus.innerHTML = answer;
						btn.remove();
					})
				})
				prestatus.parentNode.insertBefore(btn, prestatus.nextSibling);
			}
			prestatus.innerHTML = answer;
		}).catch(err => console.log(err));
	}
})
/*
 * diff/patch editing
 * редактирование diff/patch
*/
prestatus.addEventListener('click',function(e){
	var t = e.target; // this
	if(t.closest('span') && t.closest('pre.status')){
		t.classList.toggle('select');
		if(t.classList.contains('select')){
			statusbtn.classList.remove('h');
			// Вариант ДВА
			// statusbtn.style.top = e.target.offsetTop + 24 + 'px';
		}else{
			if(!document.querySelector('pre .select')){
				statusbtn.classList.add('h');
			// Вариант ДВА
			// }else{
			//	let allselect = document.querySelectorAll('pre .select');
			//	statusbtn.style.top = allselect[allselect.length- 1].offsetTop + 24 + 'px';
			}
		}
		// Вариант ОДИН
		// let allselect = document.querySelectorAll('pre .select');
		// if(allselect.length > 0){
		//	statusbtn.style.top = allselect[allselect.length- 1].offsetTop + 24 + 'px';
		// }
	}
	if(t.closest('span') && !t.closest('span.diff-header') && !t.closest('span.diff-sub-header') && t.closest('pre.patch')){
		t.closest('span').textContent = t.closest('span').textContent.replace(/^(.)/, function(){
			t.closest('span').classList.remove('diff-added','diff-deleted','diff-comment');
			if(arguments[0] == ' '){
				t.closest('span').classList.add('diff-deleted');
				return '-';
			}else if(arguments[0] == '-'){
				t.closest('span').classList.add('diff-added');
				return '+';
			}else if(arguments[0] == '+'){
				t.closest('span').classList.add('diff-comment');
				return '#';
			}else if(arguments[0] == '#'){
				return ' ';
			}
			return arguments[0];
		})
	}
})
