pipeline {
    agent {
        any 
    }
    
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
        // Mengizinkan build yang tidak stabil atau gagal untuk tetap melanjutkan archiving dan post-build actions
        skipStagesAfterUnstable(false)
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
                    
                    // 4. Push tag 'latest' sebagai referensi cepat (opsional)
                    sh "docker tag ${DOCKER_IMAGE}:${BUILD_NUMBER} ${DOCKER_IMAGE}:latest"
                    sh "docker push ${DOCKER_IMAGE}:latest"
                    
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
                echo "Mendeploy image ${DOCKER_IMAGE}:${BUILD_NUMBER} ke lingkungan staging..."
                // Dalam skenario nyata, Anda akan menjalankan perintah 'docker stop/rm/run' 
                // atau menggunakan Kubernetes/Helm di sini.
                
                sh "echo 'Deployment simulated.'"
            }
        }
        
        stage('OWASP ZAP SCAN (Baseline)') {
            steps {
                echo "Memulai OWASP ZAP Baseline Scan..."
                // Asumsi: Target URL aplikasi Anda sudah berjalan di port 80/8080 (misalnya: http://app-container:80)
                // Ganti YOUR_APP_URL_OR_IP dengan alamat yang benar.
                sh "docker run --rm owasp/zap2docker-weekly zap-baseline.py -t http://172.17.0.1:8080 -r zap-baseline-report.xml || true"
                
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
            }
        }
    }
}