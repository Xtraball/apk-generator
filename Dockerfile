FROM debian:9

MAINTAINER dev@xtraball.com

RUN rm -f /etc/apt/sources.list
COPY ./assets/sources.list /etc/apt/sources.list

RUN apt-get update
RUN apt-get install -y curl wget
RUN apt-get install -y zip unzip vim
RUN apt-get install -y openjdk-11-jdk openjdk-11-jre
RUN apt-get install -y php-cli php-curl

WORKDIR /home/builds

COPY ./assets/wrapper.sh /usr/bin/wrapper.sh
RUN chmod +x /usr/bin/wrapper.sh

CMD /usr/bin/wrapper.sh