// Jenkinsfile (Scripted Pipeline)
pipeline {
    agent any 

    environment {
        // --- Wajib Dikonfigurasi di Jenkins Credentials ---
        SONAR_TOKEN = credentials('SONAR_AUTH_TOKEN') 
        DOCKER_CREDENTIALS = 'docker-hub-credentials' 
        
        // --- Konfigurasi Image & URL ---
        // Image ini sekarang dibuat di Stage 1 dan memiliki vendor/ dan node_modules/
        DOCKER_IMAGE = "xxsamx/laravel-devsecops:${env.BUILD_ID}" 
        STAGING_URL = "http://nginx:80" 
        DOCKER_NETWORK = "finaldevsec_pineus_network" 
        DC_VERSION = '9.0.8' 
    }

    stages {
        // 1. BUILD & INSTALL DEPENDENCIES (MENGATASI MASALAH VOLUME)
        stage('Build & Install Dependencies') {
            steps {
                echo "Checking out code from GitHub: https://github.com/xxsannx/finaldevsec.git"
                // 1. Checkout Code
                git branch: 'main', url: 'https://github.com/xxsannx/finaldevsec.git'
                
                echo "Running Multi-Stage Docker Build to install dependencies inside the image..."
                
                script {
                    // Gunakan docker.build() untuk membangun image. Image ini akan menjadi $DOCKER_IMAGE
                    docker.withRegistry('https://registry.hub.docker.com', DOCKER_CREDENTIALS) {
                        // Perintah build ini akan menjalankan semua instalasi dependensi
                        docker.build(DOCKER_IMAGE, "-f Dockerfile .")
                        echo "Image built successfully: ${DOCKER_IMAGE}"
                    }
                }
            }
        }
        
        // 2. DEPENDENCY SCAN (OWASP DC - Memindai file lock)
        stage('Dependency Vulnerability (OWASP DC)') {
            steps {
                echo "Running OWASP Dependency-Check scan on lock files..."
                
                sh "mkdir -p dependency-check-report"
                
                sh """
                    docker run --rm \
                        -v "${WORKSPACE}":/scan \
                        -v "${WORKSPACE}/dependency-check-report":/report \
                        owasp/dependency-check:v${DC_VERSION} \
                        --scan /scan/composer.lock /scan/package-lock.json \
                        --format HTML \
                        --out /report \
                        --project "Laravel DevSecOps"
                """
                echo "OWASP Dependency-Check selesai. Laporan disimpan di dependency-check-report/report.html"
            }
        }

        // 3. CODE QUALITY & SAST (SonarQube) - Masih bisa dilakukan pada source code yang sudah dicheckout.
        stage('Static Code Analysis (SonarQube)') {
            steps {
                echo "Running SonarQube static analysis..."
                withSonarQubeEnv('SonarQube-Local') { 
                    sh """
                        sonar-scanner \
                          -Dsonar.projectKey=laravel-devsecops \
                          -Dsonar.sources=app,config,database \
                          -Dsonar.exclusions=vendor/**,node_modules/**,public/**,storage/**,tests/**,dependency-check-report/** \
                          -Dsonar.login=${SONAR_TOKEN}
                    """
                }
            }
        }
        
        // 4. PUSH ARTIFACT (Docker Push)
        // Cukup Push karena sudah di-build di Stage 1
        stage('Docker Image Push') {
            steps {
                echo "Pushing Docker image..."
                script {
                    docker.withRegistry('https://registry.hub.docker.com', DOCKER_CREDENTIALS) {
                        docker.image(DOCKER_IMAGE).push() 
                        echo "Docker Image Pushed: ${DOCKER_IMAGE}"
                    }
                }
            }
        }
        
        // 5. TRAFFIC GENERATION (Locust)
        stage('Traffic Generation (Locust)') {
            steps {
                echo "Starting Load Test using Locust for 60 seconds (50 users)..."
                
                sh """
                    docker run --rm \
                        -v "${WORKSPACE}/docker/locust/locustfile.py":/home/locust/locustfile.py \
                        --network=\${DOCKER_NETWORK} \
                        locustio/locust \
                        -f /home/locust/locustfile.py \
                        --host=${STAGING_URL} \
                        --headless \
                        -u 50 \
                        -r 10 \
                        --run-time 60s \
                        --csv=locust_results
                """
                echo "Load Test finished. Results saved to locust_results.csv in workspace."
            }
        }
        
        // 6. DAST Scan (OWASP ZAP)
        stage('DAST Scan (OWASP ZAP)') {
            steps {
                echo "Running OWASP ZAP DAST scan against ${STAGING_URL}..."
                sh "docker run --rm zaproxy/zap-stable zap-cli quick-scan --self-contained ${STAGING_URL}"
            }
        }
        
        // 7. RELEASE/DEPLOY FINAL
        stage('Final Deploy to Production') {
            when {
                expression {
                    currentBuild.result == 'SUCCESS'
                }
            }
            steps {
                echo "All scans and tests passed. Ready for deployment."
            }
        }
    }
}