## Docker for APK-Generator via Jenkins

First make a copy of **docker-compose-template.yml** to **docker-compose.yml** then adjust to your Jenkins setup

- JENKINS_URL=https://my-jenkins-server.com/
- JENKINS_SLAVE=slave-node-name
- JENKINS_SECRET=my-node-secret-key

Then run `docker-compose up -d` to start your instance.