const log = {
	add:function(msg) {
		let a = document.createElement("div")
		a.innerHTML = msg
		document.getElementById("log").appendChild(a)
	},
	clear:function(){
		let a = document.getElementById("log").childNodes
		let max = a.length
		for (let i = 0; i < max; i++) {
			a[0].remove()
		}
	}
}

function $_COOKIE(){
	let result = {}
	let c = document.cookie
	c = c.split('; ')
	c.forEach(element =>{
		let a = element.split('=')
		let key = a[0];
		let obj = {};
		obj[key] = a[1];
		result[key] = obj[key]
	})
	return result
}

var historic = {
	cursor:0,
	up:function() {
		if(this.cursor+1 < this.commands.length){
			this.cursor++
		}
		return this.commands[this.cursor]
	},
	down:function() {
		if(this.cursor != 0){
			this.cursor--
		}
		return this.commands[this.cursor]
	},
	commands:[]
}

function send(msg){
	if(msg){
		return new Promise(r => {
			let xhr = new XMLHttpRequest()
			xhr.open("POST","./?m=terminal",true)
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
			xhr.onreadystatechange = function(){
				if(xhr.readyState == 4){
					if(xhr.responseText){
						r(xhr.responseText);
					}else{
						r(false)
					}
				}
			}
			xhr.send("t="+msg)
		})
	}else{
		return false
	}
}

async function exec(commands){
	let command = commands.split('\n')
	for (let i = 0; i < command.length; i++) {
		let cmd = command[i].split(' ')
		let localcommands = [
			"cls",
			"reset",
		]
		let servercommands = [
			"co",
			"deco",
			"cuser",
			"duser"
		]
		if (localcommands.includes(cmd[0])) {
			switch (cmd[0]) {
				case "cls":
					log.clear()
					break;
				case "reset":
					for (let a in $_COOKIE()) {
						document.cookie = 'user='+a+'; expires=Thu, 01 Jan 1970 00:00:00 UTC'
					}
					break;
				default:
					log.add("warn: command not found")
					break;
			}
		} else if(servercommands.includes(cmd[0])){
			let rep = await send(command[i])
			JSON.parse(rep).result.forEach(element => {
				log.add(element)
			});
		} else {
			log.add("warn: command not found")
		}
	}
}

window.onload = function(){
	let a = document.createElement("input")
	a.onkeydown = function(e){
		switch (e.key) {
			case "Enter":
				if(a.value != ''){
					exec(a.value)
					if(a.value != historic.commands[1]){
						historic.commands.splice(1,0,a.value)
					}
					historic.commands[0] = ""
					historic.cursor = 0
					if(historic.commands.length > 10){
						historic.commands.splice(10,historic.commands.length - 10)
					}
					a.value = ""
					localStorage["log"] = JSON.stringify(historic.commands)
				}
				break;
			case "ArrowUp":
				let b = historic.up()
				if(typeof b != "undefined"){
					a.value = b
				}
				break;
			case "ArrowDown":
				let c = historic.down()
				if(typeof c != "undefined"){
					a.value = c
				}
				break;
			case "Escape":
				e.target.blur()
				break;
			default:
				historic.commands[0] = a.value
				break;
			}
	}
	document.body.innerHTML = '<div id="log"></div><div id="cmd"><span id="directory">A:\\></span></div>'
	document.getElementById("cmd").appendChild(a)
	document.body.onkeydown = function(){
		a.focus()
	}
	window.onclick = function(){
		a.focus()
	}
	if (localStorage["log"]) {
		historic.commands = JSON.parse(localStorage["log"])
	}
	log.add("dsb Bios [v A.0]")
}