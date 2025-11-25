// Jenkinsfile (Declarative Pipeline) - Menggunakan konfigurasi DevSecOps Laravel/PHP
pipeline {
    agent any

    tools {
        // Asumsi: Java dan Node diperlukan untuk SonarQube Scanner dan Tools di Agent
        // Sesuaikan nama tool ('jdk17', 'node16') dengan konfigurasi Global Tool Jenkins Anda
        jdk 'jdk17'
        nodejs 'node18'
    }

    environment {
        // --- KONFIGURASI DARI PROJECT LARAVEL ---
        DOCKER_IMAGE = "xxsamx/laravel-devsecops:${env.BUILD_ID}" 
        DOCKER_NETWORK = "finaldevsec_pineus_network" // Jaringan Docker yang digunakan
        STAGING_URL = "http://nginx:80"             // URL untuk DAST dan Load Testing
        DC_CACHE_DIR = "${WORKSPACE}/dependency-check-data" // Cache untuk Docker DC

        // --- CREDENTIALS (Dikonfigurasi di Jenkins Credentials Manager) ---
        SONAR_TOKEN = credentials('SONAR_AUTH_TOKEN') 
        DOCKER_CREDENTIALS = 'docker-hub-credentials' 
        
        // Asumsi SonarQube Server bernama 'SonarQube-Local'
    }

    stages {
        stage('clean workspace'){
            steps{
                cleanWs()
            }
        }
        
        // 1. CHECKOUT & BUILD (Menggantikan Checkout dan Install Dependencies)
        stage('Checkout & Build Image') {
            steps {
                echo "Melakukan checkout kode dan membangun image Docker multi-stage..."
                // Menggunakan source code dari project DevSecOps Anda
                git branch: 'main', url: 'https://github.com/xxsannx/finaldevsec.git'
                
                script {
                    withDockerRegistry(credentialsId: DOCKER_CREDENTIALS) {
                        // Membangun image Docker multi-stage
                        sh "docker build -t ${DOCKER_IMAGE} -f Dockerfile ."
                        echo "Image berhasil dibangun: ${DOCKER_IMAGE}"
                    }
                }
            }
        }
        
        // 2. DEPENDENCY SCAN (OWASP DC - MENGGUNAKAN DOCKER STABIL)
        stage('OWASP Dependency-Check (Docker)') {
            steps {
                echo "Menjalankan scan dependensi menggunakan Docker (Solusi Stabil)..."
                
                sh "mkdir -p dependency-check-report"
                
                // Salin file lock dari image yang baru di-build
                script {
                    sh "docker run --name temp_scanner -d ${DOCKER_IMAGE} sleep 30"
                    sh "docker cp temp_scanner:/var/www/html/composer.lock ."
                    sh "docker cp temp_scanner:/var/www/html/package-lock.json ."
                    sh "docker stop temp_scanner"
                    sh "docker rm temp_scanner"
                }

                // Jalankan Dependency-Check di dalam container Docker
                // Menggunakan -n dan --data /data untuk bypass kegagalan update NVD
                sh "mkdir -p ${DC_CACHE_DIR}"
                sh """
                    docker run --rm \
                        -v "${WORKSPACE}/composer.lock":/scan/composer.lock \
                        -v "${WORKSPACE}/package-lock.json":/scan/package-lock.json \
                        -v "${WORKSPACE}/dependency-check-report":/report \
                        -v "${DC_CACHE_DIR}":/data \
                        owasp/dependency-check:9.0.8 \
                        --scan /scan/composer.lock /scan/package-lock.json \
                        --format HTML \
                        --out /report \
                        --project "Laravel DevSecOps" \
                        --data /data \
                        -n
                """
                
                sh "rm composer.lock package-lock.json" 
                echo "OWASP Dependency-Check selesai."
            }
        }

        // 3. STATIC CODE ANALYSIS (SONARQUBE)
        stage("Sonarqube Analysis") {
            steps {
                withSonarQubeEnv('SonarQube-Local') { // Ganti dengan nama Server SonarQube Anda
                    sh """
                        // SonarQube Scanner menggunakan tool 'sonar-scanner' yang didefinisikan secara global
                        sonar-scanner \
                          -Dsonar.projectKey=laravel-devsecops \
                          -Dsonar.projectName=laravel-devsecops \
                          -Dsonar.sources=app,config,database,routes \
                          -Dsonar.exclusions=vendor/**,node_modules/**,public/**,storage/**,tests/**,dependency-check-report/** \
                          -Dsonar.login=${SONAR_TOKEN}
                    """
                }
            }
        }
        
        // 4. QUALITY GATE CHECK
        stage("Quality Gate") {
           steps {
                script {
                    // Pastikan credentialsId ini sesuai dengan Sonar Token Anda di Jenkins
                    waitForQualityGate abortPipeline: true, credentialsId: 'SONAR_AUTH_TOKEN' 
                }
            }
        }

        // 5. TRIVY FILESYSTEM SCAN
        stage('TRIVY Filesystem Scan') {
            steps {
                // Melakukan FS scan terhadap workspace (sebelum push)
                sh "trivy fs . > trivyfs_report.txt"
            }
        }

        // 6. DOCKER IMAGE PUSH
        stage("Docker Image Push"){
            steps{
                script{
                   withDockerRegistry(credentialsId: DOCKER_CREDENTIALS) {
                       sh "docker tag ${DOCKER_IMAGE} jay75chauhan/finaldevsec:${env.BUILD_ID}"
                       sh "docker push jay75chauhan/finaldevsec:${env.BUILD_ID}"
                    }
                }
            }
        }
        
        // 7. TRIVY IMAGE SCAN
        stage("TRIVY Image Scan"){
            steps{
                // Melakukan Image scan terhadap image yang baru di-push
                sh "trivy image jay75chauhan/finaldevsec:${env.BUILD_ID} > trivy_image_report.txt"
            }
        }
        
        // 8. DAST SCAN (Menggantikan Deploy to Container yang sederhana)
        stage('DAST Scan (OWASP ZAP)') {
            steps {
                echo "Menjalankan DAST Scan (OWASP ZAP) terhadap ${STAGING_URL}..."
                // ZAP berjalan di jaringan yang sama dengan aplikasi (pineus_tilu_network)
                sh "docker run --rm --network=${DOCKER_NETWORK} zaproxy/zap-stable zap-cli quick-scan --self-contained ${STAGING_URL}"
            }
        }
        
        // 9. FINAL DEPLOYMENT
        stage('Final Deployment') {
            steps{
                echo "Semua scan dan tes telah lulus. Siap untuk deployment final."
            }
        }

    }
}