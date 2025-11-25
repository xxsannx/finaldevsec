// Jenkinsfile (Declarative Pipeline)
pipeline{
    agent any
    tools{
        // Konfigurasi Tools yang Digunakan:
        jdk 'jdk17'
        nodejs 'node18' // Menggunakan node18 sesuai standar DevSecOps Laravel
    }
    environment {
        // Konfigurasi Tool Sonar Scanner:
        SCANNER_HOME=tool 'sonar-scanner'
    }
    stages {
        stage('clean workspace'){
            steps{
                cleanWs()
            }
        }
        stage('Checkout from Git'){
            steps{
                // Menggunakan tautan GitHub finaldevsec.git
                git branch: 'main', url: 'https://github.com/xxsannx/finaldevsec.git'
            }
        }
        stage("Sonarqube Analysis "){
            steps{
                // Sonar Server: Menggunakan nama 'SonarQube-Local'
                withSonarQubeEnv('SonarQube-Local') {
                    sh ''' $SCANNER_HOME/bin/sonar-scanner -Dsonar.projectName=finaldevsec \
                    -Dsonar.projectKey=finaldevsec ''' // DIUBAH ke finaldevsec
                }
            }
        }
        stage("quality gate"){
           steps {
                script {
                    // Kredensial Token: Menggunakan nama 'SONAR_AUTH_TOKEN'
                    waitForQualityGate abortPipeline: false, credentialsId: 'SONAR_AUTH_TOKEN'
                }
            }
        }
        stage('Install Dependencies') {
            steps {
                sh "npm install"
            }
        }
        stage('OWASP FS SCAN') {
            steps {
                // OWASP DC Tool: Menggunakan nama 'DP-Check'
                dependencyCheck additionalArguments: '--scan ./ --disableYarnAudit --disableNodeAudit', odcInstallation: 'DP-Check'
                dependencyCheckPublisher pattern: '**/dependency-check-report.xml'
            }
        }
        stage('TRIVY FS SCAN') {
            steps {
                sh "trivy fs . > trivyfs.txt"
            }
        }
        stage("Docker Build & Push"){
            steps{
                script{
                   // Kredensial Docker: Menggunakan nama 'docker-hub-credentials'
                   withDockerRegistry(credentialsId: 'docker-hub-credentials', toolName: 'docker'){
                       sh "docker build -t finaldevsec ." // DIUBAH ke finaldevsec
                       sh "docker tag finaldevsec jay75chauhan/finaldevsec:latest " // DIUBAH ke finaldevsec
                       sh "docker push jay75chauhan/finaldevsec:latest " // DIUBAH ke finaldevsec
                    }
                }
            }
        }
        stage("TRIVY"){
            steps{
                // Image Trivy: Menggunakan nama 'finaldevsec'
                sh "trivy image jay75chauhan/finaldevsec:latest > trivy.txt"
            }
        }
        stage('Deploy to container'){
            steps{
                script{
                   // Kredensial Docker: Menggunakan nama 'docker-hub-credentials'
                   withDockerRegistry(credentialsId: 'docker-hub-credentials', toolName: 'docker'){
                       // Nama Container & Image: Menggunakan 'finaldevsec'
                       sh 'docker run -d --name finaldevsec -p 3000:3000 jay75chauhan/finaldevsec:latest'
                    }
                }
            }
        }

    }
}