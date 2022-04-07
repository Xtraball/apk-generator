FROM debian:9

MAINTAINER dev@xtraball.com

RUN rm -f /etc/apt/sources.list
COPY ./assets/sources.list /etc/apt/sources.list

RUN apt-get update
RUN apt-get install -y curl wget
RUN apt-get install -y zip unzip vim
RUN apt-get install -y openjdk-11-jdk openjdk-11-jre
RUN apt-get install -y php-cli php-curl
RUN apt-get install -y openssh-server
RUN mkdir -p /var/run/sshd
RUN echo 'root:jenkins-slave' |chpasswd
RUN echo "PermitRootLogin yes" >> /etc/ssh/sshd_config

WORKDIR /home/builds

EXPOSE 22
CMD    /usr/sbin/sshd -D