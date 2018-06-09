#!/usr/bin/env bash

folder=${1}
buildNumber=${2}
buildType=${3:-cdvBuildRelease}

export JAVA_HOME=$(java -XshowSettings:properties -version 2>&1 > /dev/null | grep 'java.home' |cut -d '=' -f 2 |xargs)
export ANDROID_HOME=/home/builds/android-sdk
export GRADLE_OPTS=-Dorg.gradle.daemon=false

cd "./$folder"

while [ -f "/home/builds/java.lock" ]
do
  echo "Waiting next slot to execute java ..."
  sleep 15
done

./gradlew $buildType