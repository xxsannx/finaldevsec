pipeline{
    agent any
    tools{
        // Konfigurasi Tools yang Digunakan:
        jdk 'jdk17'
        nodejs 'node18' 
    }
    environment {
        // Konfigurasi Tool Sonar Scanner:
        SCANNER_HOME=tool 'sonar-scanner'
        
        
        // Menggunakan port 3001 di host karena 3000 digunakan Grafana
        APP_HOST = 'http://host.docker.internal:3001'
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
                    -Dsonar.projectKey=finaldevsec '''
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

        stage("Docker Build & Push"){
            steps{
                script{
                   // Kredensial Docker: Menggunakan nama 'docker-hub-credentials'
                   // 'toolName: 'docker'' diperlukan untuk akses docker CLI
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
                   // 'toolName: 'docker'' diperlukan untuk akses docker CLI
                   withDockerRegistry(credentialsId: 'docker-hub-credentials', toolName: 'docker'){
                       
                       // *** PEMBERSIHAN DOCKER AGAR PORT 3001 BEBAS ***
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
        
        // STAGE OWASP ZAP SCAN (DAST) - MENGGUNAKAN DOCKER CONTAINER STABLE (DI DALAM BLOK OTENTIKASI)
        stage('OWASP ZAP SCAN (Baseline)') {
            steps {
                // Membungkus langkah DAST di dalam withDockerRegistry agar docker pull terotentikasi
                // Solusi: Menambahkan 'url' eksplisit
                withDockerRegistry(credentialsId: 'docker-hub-credentials', url: 'https://registry.hub.docker.com') { 
                    echo "Menunggu aplikasi siap di ${APP_HOST}..."
                    sleep 10
                    
                    // MENGGUNAKAN ZAP DOCKER CONTAINER STABLE
                    sh """
                        docker run --rm \
                        --user root \
                        --network finaldevsec_pineus_network \
                        -v \$(pwd):/zap/wrk/:rw \
                        zaproxy/zap-stable zap-baseline.py \
                        -t http://finaldevsec:3000 \
                        -r /tmp/zap_report.html
                    """
                    echo "OWASP ZAP Baseline Scan selesai menggunakan Docker. Laporan ada di zap_report.html"
                }
            }
        }

    }
}