const log = {
	add:function(msg) {
		msg = msg.replaceAll(">","&gt;")
		msg = msg.replaceAll("<","&lt;")
		msg = msg.replaceAll("\n","</br>")
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
/*creat an Array with the 1st object of a json
{"a":1,"b":2} => ["a","b"]
*/
function findex(list) {
	let result = [];
	for (let[key] of Object.entries(list)) {
		result.push(key);
	}
	return result;
}

/*php $_COOKIE
change document.cookie in json
*/
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

/*

*/
function comble(txt,lenght){
	let result = [];
	for (let i=0; i < lenght; i++){
    txt[i] ? result.push(txt[i]) : result.push(" ")
  }
	return result.join("")
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
			xhr.open("POST",conf.server+"./?m=terminal",true)
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

let conf = {
	server:window.location.href,
	ls:"/",
	user:""
}

let localcommands = {
	"cls":"clear log",
	"reset":"clear all cookies and settings",
	"server":"connnect to a server: <server with path !don't set parameter or hash>",
	"help":"show the command"
}
let servercommands = {}

async function exec(commands){
	let command = commands.split('\n')
	for (let i = 0; i < command.length; i++) {
		let cmd = command[i].split(' ')
		if (localcommands[cmd[0]]) {
			switch (cmd[0]) {
				case "cls":
					log.clear()
					break;
				case "reset":
					for (let a in $_COOKIE()) {
						document.cookie = 'user='+a+'; expires=Thu, 01 Jan 1970 00:00:00 UTC'
					}
					break;
				case "server":
					if(cmd[1]){
						let rep = JSON.parse(await new Promise(r => {
							let xhr = new XMLHttpRequest()
							xhr.open("GET",cmd[1]+"/?m=info",true)
							console.log(cmd[1])
							xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
							xhr.onreadystatechange = function(){
								if(xhr.readyState == 4){
									if (xhr.status != 400) {
										if(xhr.responseText){
											r(xhr.responseText);
										}else{
											r(false)
										}
									} else {
										r(false)
									}
								}
							}
							xhr.send()
						}))
						if(rep){
							if (rep.server) {
								conf.server = cmd[1]
								log.add("server set")
							} else {
								log.add("server invalid")
							}
							if (rep.allowsCommands) {
								servercommands = rep.allowsCommands
								log.add("server commands are configure")
							}
						} else {
							log.add("server not find or invalid")
						}
					} else {
						log.add("server not set")
					}
					break;
				case "help":
					let lc = []
					for (const key in localcommands) {
						lc.push(comble(key,7).toUpperCase()+"  "+localcommands[key]);
					}
					let sc = []
					for (const key in servercommands) {
						sc.push(comble(key,7).toUpperCase()+"  "+servercommands[key]);
					}
					let rp = "local commands: \n"+lc.join("\n")+"\n\nserver commands: \n"+sc.join("\n")
					log.add(rp)
					break;
				default:
					log.add("warn: command not found")
					break;
			}
		} else if (servercommands[cmd[0]]) {
			let rep = await send(command[i])
			try {
				rep = JSON.parse(rep)
			} catch {
				rep = false
			}
			if (rep) {
				rep.result.forEach(element => {
					log.add(element)
				});
				if (rep.set){
					if (rep.set.user) {
						conf.user = rep.set.user
					}
				}
			} else {
				log.add("error: response can't be parse")
			}
		} else {
			log.add("warn: command not found")
		}

		let sv = conf.server.match(/:\/\/([^\/]*)\//g)
		sv = sv[0].substr(3)
		sv = sv.slice(0, -1)

		if($_COOKIE()["user"]){
			conf.user = $_COOKIE()["user"]
		}

		if (conf.user != "") {
			directory = conf.user+"@"+sv+":"+conf.ls
		} else {
			directory = sv+":"+conf.ls
		}
		document.getElementById("directory").innerHTML = directory+">"
		document.querySelector("title").innerHTML = "dsb-"+directory
	}
}

window.onload = function(){
	exec("server "+conf.server)
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
	document.body.innerHTML = '<title>dsb</title><div id="log"></div><div id="cmd"><span id="directory"></span></div>'
	document.getElementById("cmd").appendChild(a)

	let sv = conf.server.match(/:\/\/([^\/]*)\//g)
	sv = sv[0].substr(3)
	sv = sv.slice(0, -1)

	if($_COOKIE()["user"]){
		conf.user = $_COOKIE()["user"]
	}

	if (conf.user != "") {
		directory = conf.user+"@"+sv+":"+conf.ls
	} else {
		directory = sv+":"+conf.ls
	}
	document.getElementById("directory").innerHTML = directory+">"
	document.querySelector("title").innerHTML = "dsb-"+directory

	document.body.onkeydown = function(e){
		if(e.key.length == 1){
			a.focus()
		}
	}
	if (localStorage["log"]) {
		historic.commands = JSON.parse(localStorage["log"])
	}
	document.body.style.fontFamily = "Consolas"
	log.add("dsb [v A.0]")
}
