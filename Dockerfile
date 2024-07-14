FROM netreflect/android-builder:34.0.0

USER root

RUN mkdir -p /var/run/sshd
RUN echo 'root:jenkins-slave' |chpasswd
RUN echo "PermitRootLogin yes" >> /etc/ssh/sshd_config

WORKDIR /home/builds

EXPOSE 22
CMD    /usr/sbin/sshd -D