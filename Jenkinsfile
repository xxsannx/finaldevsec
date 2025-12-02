// Jenkinsfile (Declarative Pipeline)
pipeline{
    // Menggunakan agen "any" (berjalan di master atau agent manapun)
    agent any
    
    tools{
        // Tools yang harus dikonfigurasi di Jenkins -> Global Tool Configuration
        jdk 'jdk17'
        nodejs 'node18' 
    }
    
    environment {
        // Konfigurasi Tool Sonar Scanner (Harus diinstal di Global Tool Configuration)
        SCANNER_HOME=tool 'sonar-scanner'
        
        // Nama image yang akan di-push ke Docker Hub
        DOCKER_IMAGE = "xxsamx/finaldevsec"


        // Nama layanan Nginx di JARINGAN DOCKER COMPOSE Anda.
        // Digunakan untuk ZAP. ZAP akan menargetkan port host 8001
        APP_INTERNAL_HOST = 'http://host.docker.internal:8001' 
        
        // Nama jaringan Docker Compose default Anda.
        DOCKER_NETWORK = 'finaldevsec_default:latest'

        // Nama container deployment yang akan dibuat dan dihapus
        DEPLOY_CONTAINER_NAME = 'finaldevsec_deployed_app'
    }
    
    stages {
        
        // ==========================================================
        // STAGE 0: SETUP & CHECKOUT
        // ==========================================================
        stage('Cleanup Workspace & Container'){
            steps{
                // Membersihkan direktori kerja Jenkins
                cleanWs()
                
                // Menghapus container deployment yang mungkin masih berjalan dari build sebelumnya
                sh "docker rm -f ${DEPLOY_CONTAINER_NAME} || true"
            }
        }
        
        stage('Checkout from Git'){
            steps{
                echo "Cloning repository: https://github.com/xxsannx/finaldevsec.git"
                git branch: 'main', url: 'https://github.com/xxsannx/finaldevsec.git'
            }
        }
        
        // ==========================================================
        // STAGE 1: SAST & SCA
        // ==========================================================
        stage('Wait for SonarQube Startup') {
            steps {
                echo "Memberikan jeda waktu 60 detik agar SonarQube selesai startup dan inisialisasi database..."
                sleep 60
                echo "Jeda selesai. Melanjutkan ke SonarQube Analysis."
            }
        }
        
        stage("Sonarqube Analysis "){
            steps{
                // Sonar Server: Menggunakan nama 'SonarQube-Local' (Harus dikonfigurasi di Jenkins)
                withSonarQubeEnv('SonarQube-Local') {
                    sh ''' $SCANNER_HOME/bin/sonar-scanner \
                    -Dsonar.projectKey=finaldevsec \
                    -Dsonar.projectName=finaldevsec \
                    -Dsonar.sources=. '''
                }
            }
        }
        
        stage("Quality Gate"){
           steps {
                script {
                    // Tunggu hasil dari SonarQube selama 5 menit
                    timeout(time: 5, unit: 'MINUTES') {
                        waitForQualityGate abortPipeline: false, credentialsId: 'SONAR_AUTH_TOKEN'
                    }
                }
            }
        }
        
        stage('Install Dependencies & SCA') {
            steps{
                echo "Memasang libatomic1 yang dibutuhkan oleh Node.js..."
                // Menginstal libatomic1 yang dibutuhkan oleh Node.js (digunakan untuk fix error 127)
                sh 'apt-get update && apt-get install -y libatomic1'
                
                // Install Dependencies
                sh "npm install" 
                
                // Dependency-Check untuk Software Composition Analysis (SCA)
                echo "Memulai OWASP Dependency-Check (SCA)..."
                dependencyCheck additionalArguments: '--scan ./ --disableYarnAudit --disableNodeAudit', odcInstallation: 'DPCheck'
                dependencyCheckPublisher pattern: '**/dependency-check-report.xml'
            }
        }
        
        // ==========================================================
        // STAGE 2: CONTAINER & DEPLOYMENT
        // ==========================================================
        stage("Docker Build & Push"){
            steps{
                script{
                   // Kredensial Docker: 'docker-hub-credentials'
                   withDockerRegistry(credentialsId: 'docker-hub-credentials', toolName: 'docker'){
                       sh "docker build -t finaldevsec ."
                       sh "docker tag finaldevsec ${DOCKER_IMAGE}" 
                       sh "docker push ${DOCKER_IMAGE}" 
                       echo "Image ${DOCKER_IMAGE} berhasil di-push ke Docker Hub."
                    }
                }
            }
        }
        

        
        stage('Deploy to container') {
            steps {
                script {
                    echo "Membuat jaringan kustom untuk ZAP Scan..."
                    // Membuat jaringan kustom untuk menghubungkan aplikasi dan scanner
                    sh "docker network create zap-network || true" 
                    
                    echo "Mendeploy image ${DOCKER_IMAGE}:${BUILD_NUMBER} ke lingkungan staging..."
                    // Menjalankan aplikasi sebagai container terpisah (final-app) pada jaringan kustom
                    // Menggunakan port 9000 sesuai Dockerfile
                    sh """
                        docker run -d --rm \
                        --network zap-network \
                        --name final-app \
                        ${DOCKER_IMAGE}:${BUILD_NUMBER}
                    """
                    // Memberi waktu aplikasi untuk booting
                    sleep 10
                }
            }
        }
        
        stage('OWASP ZAP SCAN (Baseline)') {
            steps {
                echo "Memulai OWASP ZAP Baseline Scan..."
                // Menjalankan ZAP container pada jaringan yang sama (zap-network) dan menargetkan nama container aplikasi (final-app)
                // Target URL: http://final-app:9000 (sesuai EXPOSE di Dockerfile)
                sh """
                    docker run --rm \
                    --network zap-network \
                    -v ${PWD}:/zap/reports \
                    owasp/zap2docker-weekly zap-baseline.py \
                    -t http://final-app:9000 \
                    -r zap-baseline-report.xml || true
                """
                
                archiveArtifacts artifacts: 'zap-baseline-report.xml', onlyIfSuccessful: true
                echo "OWASP ZAP Baseline Scan selesai. Laporan diarsipkan."
            }
        }

        // ==========================================================
        // STAGE 5: CLEANUP
        // ==========================================================
        stage('Post-Deployment Cleanup') {
            steps {
                echo "Menghapus image lokal..."
                sh "docker rmi ${DOCKER_IMAGE}:${BUILD_NUMBER} || true"
                
                script {
                    echo "Menghentikan, menghapus kontainer aplikasi, dan membersihkan jaringan kustom..."
                    // Menghentikan dan menghapus kontainer aplikasi
                    sh "docker stop final-app || true"
                    sh "docker rm final-app || true"
                    
                    // Menghapus jaringan kustom ZAP
                    sh "docker network rm zap-network || true"
                }
            }
        }
    }
}