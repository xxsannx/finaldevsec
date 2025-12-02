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
        DOCKER_IMAGE = "xxsamx/finaldevsec:latest"

        // Nama layanan Nginx di JARINGAN DOCKER COMPOSE Anda.
        // Digunakan untuk ZAP. ZAP akan menargetkan port host 8001
        APP_INTERNAL_HOST = 'http://host.docker.internal:8001' 
        
        // Nama jaringan Docker Compose default Anda.
        DOCKER_NETWORK = 'finaldevsec_default' 

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
                echo "Memberikan jeda waktu 90 detik agar SonarQube selesai startup dan inisialisasi database..."
                sleep 90
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
        
        stage('Image Scanning (Trivy)') {
            steps {
                echo "Memulai Image Scanning menggunakan Trivy untuk image ${DOCKER_IMAGE}..."
                // Trivy akan gagal jika menemukan kerentanan HIGH atau CRITICAL
                sh """
                    docker run --rm aquasec/trivy:latest image \
                    --exit-code 1 --severity HIGH,CRITICAL ${DOCKER_IMAGE}
                """
                echo "Trivy scan selesai. Tidak ada kerentanan HIGH/CRITICAL ditemukan."
            }
        }
        
        stage('Deploy to container'){
            steps{
                script{
                   // Menghapus container deployment yang mungkin masih berjalan
                   sh 'docker rm -f finaldevsec_deployed_app || true' 
                   
                   echo "Menunggu 5 detik untuk memastikan port dibebaskan..."
                   sleep 5
                       
                   // Deploy image ke port 8001 (host) -> 80 (container)
                   sh "docker run -d --name ${DEPLOY_CONTAINER_NAME} -p 8001:80 ${DOCKER_IMAGE}"
                   echo "Aplikasi dideploy ke http://localhost:8001 (Host Port) untuk DAST."
                }
            }
        }
        
        // ==========================================================
        // STAGE 3: DAST (Dynamic Analysis)
        // ==========================================================
        stage('OWASP ZAP SCAN (Baseline)') {
            steps {
                echo "Menunggu aplikasi siap di ${DEPLOY_CONTAINER_NAME}..."
                sleep 20 // Beri waktu ekstra untuk aplikasi PHP dan Nginx siap
                
                // ZAP menargetkan host.docker.internal:8001 (port host)
                sh """
                    docker run --rm -v \$(pwd)/zap_reports:/zap/reports \
                    --network ${DOCKER_NETWORK} \
                    zaproxy/zap-stable zap-baseline.py \
                    -t ${APP_INTERNAL_HOST} \
                    -r zap_report.html
                """
                
                echo "OWASP ZAP Baseline Scan selesai. Laporan ada di zap_report.html di workspace."
                archiveArtifacts artifacts: 'zap_reports/zap_report.html', onlyIfSuccessful: false
            }
        }
        
        // ==========================================================
        // STAGE 4: CLEANUP
        // ==========================================================
        stage('Post-Deployment Cleanup'){
            steps{
                echo "Menghapus container deployment: ${DEPLOY_CONTAINER_NAME}"
                sh 'docker rm -f ${DEPLOY_CONTAINER_NAME} || true'
            }
        }

    }
}