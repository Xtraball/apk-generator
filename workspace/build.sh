#!/usr/bin/env bash

folder=${1}
buildNumber=${2}
buildType=${3:-cdvBuildRelease}

export JAVA_HOME=$(java -XshowSettings:properties -version 2>&1 > /dev/null | grep 'java.home' |cut -d '=' -f 2 |xargs)
export ANDROID_HOME=/home/builds/android-sdk
export GRADLE_OPTS=-Dorg.gradle.daemon=false

cd "./$folder"

counter=0
while [ -f "/home/builds/java.lock" ]
do
  echo "Waiting next slot to execute java ..."
  sleep 15

  # Implements a counter in case a build forget to unlock java.lock
  # after 5 minutes (15s * 20)  locked we force the build!
  let "counter++"
  if [ $counter = '20' ];
  then
    rm -f "/home/builds/java.lock"
  fi;
done

touch "/home/builds/java.lock"
./gradlew $buildType