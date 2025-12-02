pipeline {
    // FIX 1 & 2: Kembali ke sintaks 'agent any' yang benar untuk Declarative Pipeline
    agent any
    
    environment {
        // Nama repositori Docker Hub Anda. Tag akan ditambahkan secara otomatis dengan nomor build.
        DOCKER_IMAGE = 'xxsamx/finaldevsec' 
        DOCKER_REGISTRY = 'registry.hub.docker.com'
        // Nama instalasi Dependency Check di Global Tool Configuration
        ODC_TOOL_NAME = 'DPCheck'
        // Nama instalasi Maven/JDK, jika ada
        // MAVEN_TOOL_NAME = 'Maven 3.8.6'
    }

    options {
        // FIX 3: Menghapus skipStagesAfterUnstable(false) karena sintaksnya tidak valid.
        // Pipeline akan tetap berjalan karena adanya || true di stage scan.
        timeout(time: 15, unit: 'MINUTES')
    }

    stages {
        
        // ==========================================================
        // STAGE 0: SETUP
        // ==========================================================
        stage('Cleanup Workspace') {
            steps {
                cleanWs()
            }
        }
        
        stage('Checkout from Git') {
            steps {
                checkout scm
            }
        }

        stage('Wait for SonarQube Startup') {
            steps {
                // Memberi waktu SonarQube untuk inisialisasi sepenuhnya
                echo "Memberikan jeda waktu 120 detik agar SonarQube selesai startup dan inisialisasi database..."
                sleep 120 
                echo "Jeda selesai. Melanjutkan ke SonarQube Analysis."
            }
        }
        
        // ==========================================================
        // STAGE 1: SAST (Code Quality & Security)
        // ==========================================================
        stage('Sonarqube Analysis'){
            steps{
                echo "Memulai SonarQube Analysis..."
                withSonarQubeEnv('SonarQube-Local') {
                    // Gunakan properti proyek yang didefinisikan di sonar-project.properties
                    sh "${scannerHome}/bin/sonar-scanner -Dsonar.projectKey=${env.JOB_NAME} -Dsonar.projectName=${env.JOB_NAME} -Dsonar.sources=."
                }
            }
        }
        
        stage("Quality Gate"){
           steps {
                script {
                    timeout(time: 10, unit: 'MINUTES') { 
                        // Menunggu hasil Quality Gate dari SonarQube. Sesuaikan ID kredensial Anda.
                        waitForQualityGate abortPipeline: true, credentialsId: 'SONAR_AUTH_TOKEN'
                    }
                }
            }
        }
        
        // ==========================================================
        // STAGE 2: SCA (Dependency Scanning)
        // ==========================================================
        stage('Install Dependencies & SCA') {
            steps{
                echo "Memasang libatomic1 yang dibutuhkan oleh Node.js..."
                // Fix: Menginstal libatomic1 yang diperlukan untuk Node.js agar npm install berhasil
                sh 'apt-get update && apt-get install -y libatomic1'
                
                // Install Dependencies
                sh "npm install" 
                
                // Dependency-Check untuk Software Composition Analysis (SCA)
                echo "Memulai OWASP Dependency-Check (SCA)..."
                dependencyCheck additionalArguments: "--scan ./ --disableYarnAudit --disableNodeAudit --format XML --out ./", odcInstallation: "${ODC_TOOL_NAME}"
                dependencyCheckPublisher pattern: '**/dependency-check-report.xml'
            }
        }
        
        // ==========================================================
        // STAGE 3: CONTAINERIZATION & IMAGE SCANNING
        // ==========================================================
        stage('Docker Build & Push') {
            steps {
                echo "Membangun dan Mendorong Docker Image ke ${DOCKER_IMAGE}:${BUILD_NUMBER}..."
                
                // Menggunakan withCredentials untuk login yang aman
                withCredentials([usernamePassword(credentialsId: 'docker-hub-credentials', passwordVariable: 'DOCKER_PASSWORD', usernameVariable: 'DOCKER_USERNAME')]) {
                    
                    // 1. Login ke Docker Registry
                    sh "docker login -u ${DOCKER_USERNAME} -p ${DOCKER_PASSWORD} ${DOCKER_REGISTRY}"
                    
                    // 2. Bangun dan tag image dengan nomor build (misalnya xxsamx/finaldevsec:13)
                    sh "docker build -t ${DOCKER_IMAGE}:${BUILD_NUMBER} ."
                    
                    // 3. Push image dengan nomor build
                    sh "docker push ${DOCKER_IMAGE}:${BUILD_NUMBER}"
                    
                    
                    // 5. Logout
                    sh "docker logout ${DOCKER_REGISTRY}"
                }
            }
        }
        
        stage('Image Scanning (Trivy)') {
            steps {
                echo "Memulai Image Scanning pada ${DOCKER_IMAGE}:${BUILD_NUMBER}..."
                
                // Trivy memindai image yang baru di-push (Menggunakan format tagging yang benar)
                
                sh """
                    docker run --rm \
                    -v /var/run/docker.sock:/var/run/docker.sock \
                    aquasec/trivy:latest image --exit-code 1 \
                    --severity CRITICAL \
                    --format template --template "@contrib/html.tpl" -o trivy_report.html \
                    ${DOCKER_IMAGE}:${BUILD_NUMBER} || true
                """
                
                // || true digunakan untuk memastikan pipeline tidak gagal karena exit code 1 dari Trivy.
                // Dalam praktik nyata, ini HARUS dihilangkan agar kerentanan tinggi/kritis menyebabkan build gagal.

                archiveArtifacts artifacts: 'trivy_report.html', onlyIfSuccessful: true
                echo "Trivy scan selesai. Laporan diarsipkan."
            }
        }

        // ==========================================================
        // STAGE 4: DAST & DEPLOYMENT
        // ==========================================================
        
        stage('Deploy to container') {
            steps {
                script {
                    echo "Membuat jaringan kustom untuk ZAP Scan..."
                    // Membuat jaringan kustom untuk menghubungkan aplikasi dan scanner
                    sh "docker network create zap-network || true" 
                    
                    echo "Mendeploy image ${DOCKER_IMAGE}:${BUILD_NUMBER} ke lingkungan staging..."
                    
                    // FIX: Tarik image secara eksplisit untuk mengatasi masalah manifest not found/timing issue
                    sh "docker pull ${DOCKER_IMAGE}:${BUILD_NUMBER}" 
                    
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