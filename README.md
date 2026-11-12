## Docker for APK-Generator via Jenkins

First make a copy of **docker-compose-template.yml** to **docker-compose.yml** then adjust to your Jenkins setup

- JENKINS_URL=https://my-jenkins-server.com/
- JENKINS_SLAVE=slave-node-name
- JENKINS_SECRET=my-node-secret-key

Then run `docker-compose up -d` to start your instance.

## Adding a newer Android API level

`sdkmanager.php` installs every build-tools/platforms version it lists, so the base image (`Dockerfile`,
`netreflect/android-builder:34.0.0`) stays the one to build when a new level only needs new SDK packages.

A level that needs more than that (a newer toolchain in the base image) is layered instead of rebuilt from
scratch: `image/Dockerfile` builds `FROM apk-generator:<previous>` and only adds what changed, the way
`apk-generator:36` is `FROM apk-generator:35`. Build it after the previous level's image exists:

```sh
docker build -f image/Dockerfile -t apk-generator:36 .
```