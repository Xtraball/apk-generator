#!/bin/bash

cd /home/builds
php -f ./sdkmanager.php
rm -f ./agent.jar
wget $JENKINS_URL/jnlpJars/agent.jar -O agent.jar
java -jar ./agent.jar -jnlpUrl $JENKINS_URL/computer/$JENKINS_SLAVE/slave-agent.jnlp -secret $JENKINS_SECRET -workDir "/home/builds"