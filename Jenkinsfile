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
        
        // Host aplikasi yang akan di-scan oleh ZAP (setelah deployment)
        // Menggunakan port 3001 di host karena 3000 digunakan Grafana
        APP_HOST = 'http://localhost:3001' 

        // VARIABEL ZAP_CMD DIHAPUS.
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
            steps{
                sh "npm install"
            }
        }
         stage('OWASP FS SCAN') {
            steps {
                // Dependency-Check untuk Software Composition Analysis (SCA)
                dependencyCheck additionalArguments: '--scan ./ --disableYarnAudit --disableNodeAudit', odcInstallation: 'DPCheck'
                dependencyCheckPublisher pattern: '**/dependency-check-report.xml'
            }
        }
        
        // STAGE TRIVY FS SCAN DIHAPUS
        
        stage("Docker Build & Push"){
            steps{
                script{
                   // Kredensial Docker: Menggunakan nama 'docker-hub-credentials'
                   withDockerRegistry(credentialsId: 'docker-hub-credentials', toolName: 'docker'){
                       sh "docker build -t finaldevsec ." // DIUBAH ke finaldevsec
                       sh "docker tag finaldevsec xxsamx/finaldevsec:latest " // DIUBAH ke xxsamx
                       sh "docker push xxsamx/finaldevsec:latest " // DIUBAH ke xxsamx
                    }
                }
            }
        }
        
        // STAGE TRIVY (IMAGE SCAN) DIHAPUS
        
        stage('Deploy to container'){
            steps{
                script{
                   // Kredensial Docker: Menggunakan nama 'docker-hub-credentials'
                   withDockerRegistry(credentialsId: 'docker-hub-credentials', toolName: 'docker'){
                       
                       // *** PERBAIKAN: Gunakan 'docker rm -f' untuk menghapus secara paksa ***
                       sh 'docker rm -f finaldevsec || true'
                       
                       echo "Menunggu 10 detik untuk memastikan port dibebaskan..."
                       sleep 10
                       // *******************************************************************
                       
                       // Nama Container & Image: Menggunakan 'finaldevsec'
                       // Pemetaan port baru: 3001 (host) -> 3000 (container)
                       sh 'docker run -d --name finaldevsec -p 3001:3000 xxsamx/finaldevsec:latest' // DIUBAH ke xxsamx
                    }
                }
            }
        }
        
        // STAGE OWASP ZAP SCAN (DAST) - MENGGUNAKAN SHELL SCRIPT DENGAN PATH ABSOLUT
        stage('OWASP ZAP SCAN (Baseline)') {
            steps {
                echo "Menunggu aplikasi siap di ${APP_HOST}..."
                // Beri waktu 10 detik agar container Docker benar-benar berjalan
                sleep 10
                
                // MENGGUNAKAN PATH ABSOLUT KE ZAP.SH
                // Anda tidak memerlukan plugin Jenkins untuk langkah ini.
                sh """
                    /home/ihsan/zap/ZAP_2.16.1/zap.sh -cmd -quickurl ${APP_HOST} -quickprogress -quickout zap_report.html
                """
                echo "OWASP ZAP Baseline Scan selesai. Laporan ada di zap_report.html"
            }
        }

    }
}