FROM redis:5
COPY ./conf/redis.conf /usr/local/etc/redis/redis.conf
#有状态数据目录声明
VOLUME /data
#这里制定的容器使用的端口号，如果需要映射，把这里去掉，在docker-compose.yml中写ports
#EXPOSE 6379

CMD ["redis-server", "/usr/local/etc/redis/redis.conf"]
#CMD ["redis-server"]


