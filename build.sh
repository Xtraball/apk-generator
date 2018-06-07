#!/usr/bin/env bash

export JAVA_HOME=$(java -XshowSettings:properties -version 2>&1 > /dev/null | grep 'java.home' |cut -d '=' -f 2 |xargs)
export ANDROID_HOME=/home/builds/android-sdk

cd ./sources
./gradlew cdvBuildRelease