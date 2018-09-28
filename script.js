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
prestatus.addEventListener('mousemove', function(event) {
	if (isClick) {
		//event.target.checked = !isShift;
		// if(event.target.closest('span')){
		// 	event.target.classList.toggle('select');
		// }
		if(event.target.closest('span')){
			if(typeClick == true){
				event.target.classList.add('select');
			}else{
				event.target.classList.remove('select');
			}
		}
		if(prestatus.querySelector('.select')){
			statusbtn.classList.remove('h');
		}else{
			statusbtn.classList.add('h');
		}
	}
});
prestatus.insertAdjacentHTML('afterend','<ul class="statusbtn h">'
	+'<li data-command="add">add</li> '
//		+'<li data-command="add --patch">patch</li> '
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
		})
		async function POST() {
			const post = await fetch('index.php', {
				method: 'POST',
				credentials: 'include',
				headers: {
					'Accept': 'application/json',
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({command: e.target.dataset.command, files: files})
			})
			return await post.text();
		}
		POST().then(function(answer){
			prestatus.innerHTML = answer;
		}).catch(err => console.log(err));;
	}
})
prestatus.addEventListener('click',function(e){
	if(e.target.closest('span')){
		e.target.classList.toggle('select');
		if(e.target.classList.contains('select')){
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
})