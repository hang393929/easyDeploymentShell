# 本示例核心为docker文件，外层代码只作为安装参考


# 一键安装环境及使用
## windows系统：

### 1)安装docker并切换镜像源(docker engine)：
```
{
    "registry-mirrors" : [
        "https://mirror.ccs.tencentyun.com",
        "http://registry.docker-cn.com",
        "http://docker.mirrors.ustc.edu.cn",
        "http://hub-mirror.c.163.com"
    ],
    "insecure-registries" : [
        "registry.docker-cn.com",
        "docker.mirrors.ustc.edu.cn"
    ],
    "debug" : true,
    "experimental" : true
}
```
### 2) (第二步也可不做)
```
在docker中setting选Resouces->File Sharing 选：c:\  
*尽可能设置预将代码存放目录的上级目录跟"~"目录必须包含 dokcer的File Sharing列表中的目录中(Windows环境的"~"目录为"C:/Users/Administrator"
```

### 3) 配置env并启动初始化
```
把配置好的.env文件复制到docker文件下
sh ./docker/install.sh
```

### 4) 编辑器查看
```
./docker/mysql/init.sql密码值是否存在换行问题,如有请处理一下,不然后面数据库连接不上
```

### 5) 启动项目：
```
winpty docker-compose run --rm php composer config -g repo.packagist composer https://mirrors.cloud.tencent.com/composer #设置镜像源
winpty docker-compose run --rm php composer global require laravel/envoy -vvv #该命令出错了请切换镜像源
winpty docker-compose run --rm php composer global dump-autoload
winpty docker-compose run --rm php chmod u+x docker/php/run.sh #启动命令添加执行权限
winpty docker-compose run --rm php envoy run init --branch=master #项目初始化
winpty docker-compose up -d #启动服务

----注意----
操作 或者使用标准的代码编辑器(phpstorm之类的)将第一行空白内容删除掉保存

winpty docker-compose down
winpty docker-compose run --rm php bash
mv docker/php/run.sh docker/php/run.sh.back
echo '' > docker/php/run.sh
cat docker/php/run.sh.back >> docker/php/run.sh
chmod u+x docker/php/run.sh
chmod 777 docker/php/run.sh
exit
winpty docker-compose up -d
winpty docker-compose ps
-------------
```
###  6) 加载vendor包
```
进入容器：winpty docker-compose exec php sh
执行: composer install  
如提示update:执行: composer update
如提示mongodb:执行：composer update --ignore-platform-req=ext-mongodb
```

###  7) 修改hosts
```
127.0.0.1   local.xzq-laravel.com
```

## linux或mac:

### 1) 初始化
```
把配置好的.env文件复制到docker文件下
直接执行：sh ./docker/install.sh

***注意：当遇到buildx/current权限提示，只需增加权限即可，如：sudo chmod -R 766 /Users/macuser/.docker/buildx/current
```

### 2）配置参数
```
docker中Preferences->Resources->File Sharing:
把: /User  /Volumes  /private  /tmp  /var/folders 分别加入到里面
```
###  3) 启动

```
docker-compose run --rm php composer config -g repo.packagist composer https://mirrors.cloud.tencent.com/composer #设置镜像源
docker-compose run --rm php composer global require laravel/envoy -vvv #该命令出错了请切换镜像源
docker-compose run --rm php composer global dump-autoload
docker-compose run --rm php envoy run init --branch=master #项目初始化
docker-compose up -d #启动服务
```

###  4) 加载vendor包
```
进入容器：docker-compose exec php sh
执行: composer install  
如提示update:执行: composer update
如提示mongodb:执行：composer update --ignore-platform-req=ext-mongodb
```

###  5) 修改hosts
```
127.0.0.1   local.xzq-laravel.com
```


## 默认域名:
127.0.0.1 local.xzq-laravel.com

## 服务器配置文件:
docker/nginx/vhost/local-xzq-laravel.conf

## 进入容器内部:
php : docker-compose exec php sh
nginx : docker-compose exec nginx sh
mysql : docker-compose exec mysql sh
redis : docker-compose exec redis sh

## 查看容器执行日志:
php : docker logs docker-php-1
nginx : docker logs docker-nginx-1
mysql : docker logs docker-mysql-1
redis : docker logs docker-redis-1

## 后台启动:
docker compose up -d


# 服务器 :

##  supervisord 常规配置
```
1）服务器设置定时任务： * * * * * php /项目绝对路径/artisan schedule:run >> /dev/null 2>&1
2）清理定时任务 php artisan schedule:clear

3) 配置supervisor
supervisor.d下创建:laravel-worker.ini文件，内容如下：
[program:laravel-worker-sendByTask]
process_name=%(program_name)s_%(process_num)02d
command=php /项目路径/artisan queue:work redis --queue=sendByTask --sleep=3 --tries=3 --daemon
autostart=true
autorestart=true
user=root
numprocs=1
redirect_stderr=true
stdout_logfile=/home/wwwroot/shucang-api/laravel-worker.log

4）重新加载配置：sudo supervisorctl reread 
5）启动守护进程：sudo supervisorctl start laravel-worker:*

每次更新完job需要：
php artisan cache:clear
php artisan queue:restart
```
