```
local commands: 
CLS      clear log
RESET    clear all cookies and settings
SERVER   connnect to a server: <server with path !don't set parameter or hash>
HELP     show the command

server commands: 
CO       connect to an user: <id> <password>
DECO     deconnect to the user
CUSER    !super_user, creat an user: <id> <password>
DUSER    !super_user, delet an user: <id>
USERS    show all the user
```

files:
- create
- rename
- open
    - text
    - image
    - sond
    - video
    - executable
- copy | past
- mouve
- edit
- remove

folder:
- create
- rename
- open
- remove


#function (linux):
```
cd <directory:string>                                       go to
pwd                                                         where am I
ls                                                          liste of the files in a directory

cat <file:string> <content:string>                          create | modify file
gedit <file:string>                                         open
rm <file:string>                                            remove
cp <file:string> <new:string>                               copy past
mv <original:string> <new:string>                           move | rename

mkdir <directory:string>                                    create directory
rmdir <directory:string>                                    remove directory
```

#function (user):
```
users                                                       users list
co <name:string> <passworld:string>                         connect
deco                                                        deconnect
cuser <name:string> <password:string> <super user:boolean>  creat user
```
