// Jenkinsfile (Scripted Pipeline)
pipeline {
    agent any 

    environment {
        // --- Wajib Dikonfigurasi di Jenkins Credentials ---
        SONAR_TOKEN = credentials('SONAR_AUTH_TOKEN') 
        DOCKER_CREDENTIALS = 'docker-hub-credentials' 
        
        // --- Konfigurasi Image & URL ---
        DOCKER_IMAGE = "xxsamx/laravel-devsecops:${env.BUILD_ID}" 
        STAGING_URL = "http://nginx:80" 
        DOCKER_NETWORK = "finaldevsec_pineus_network" 
        DC_VERSION = '9.0.8' 
    }

    stages {
        // 1. DEVELOPMENT & BUILD SETUP - DIAGNOSTIK VOLUME
        stage('Code Checkout & Install Dependencies') {
            steps {
                echo "Checking out code from GitHub: https://github.com/xxsannx/finaldevsec.git"
                // 1. Checkout Code
                git branch: 'main', url: 'https://github.com/xxsannx/finaldevsec.git'
                
                // --- DIAGNOSTIK VOLUME: Cek apakah file terlihat di dalam container ---
                echo "Running diagnostic check inside the container..."
                // Jalankan ls -la /app menggunakan container composer
                sh "docker run --rm -v \"${WORKSPACE}\":/app -w /app composer ls -la /app"
                
                // 2. Install PHP Dependencies (Composer)
                echo "Installing PHP dependencies (Composer)..."
                // Perintah composer yang gagal sebelumnya
                sh "docker run --rm -v \"${WORKSPACE}\":/app -w /app composer install --ignore-platform-reqs"
                
                // 3. Install Node.js Dependencies (NPM)
                echo "Installing Node.js dependencies (NPM) using node:lts-alpine container..."
                sh "docker run --rm -v \"${WORKSPACE}\":/app -w /app node:lts-alpine npm install"
                
                // 4. Compile Assets (Laravel Mix/Vite)
                echo "Compiling front-end assets (npm run dev)..."
                sh "docker run --rm -v \"${WORKSPACE}\":/app -w /app node:lts-alpine npm run dev"
            }
        }
        
        // 2. DEPENDENCY SCAN (Tool: OWASP Dependency-Check)
        // ... (Tahap 2 dan seterusnya tetap sama)
        stage('Dependency Vulnerability (OWASP DC)') {
            steps {
                echo "Running OWASP Dependency-Check scan..."
                
                sh "mkdir -p dependency-check-report"
                
                sh """
                    docker run --rm \
                        -v "${WORKSPACE}":/scan \
                        -v "${WORKSPACE}/dependency-check-report":/report \
                        owasp/dependency-check:v${DC_VERSION} \
                        --scan /scan \
                        --format HTML \
                        --out /report \
                        --project "Laravel DevSecOps"
                """
                
                echo "OWASP Dependency-Check selesai. Laporan disimpan di dependency-check-report/report.html"
            }
        }

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
        
        stage('Docker Image Build and Push') {
            steps {
                echo "Building and pushing Docker image..."
                script {
                    docker.withRegistry('https://registry.hub.docker.com', DOCKER_CREDENTIALS) {
                        docker.build(DOCKER_IMAGE).push() 
                        echo "Docker Image Built and Pushed: ${DOCKER_IMAGE}"
                    }
                }
            }
        }
        
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
        
        stage('DAST Scan (OWASP ZAP)') {
            steps {
                echo "Running OWASP ZAP DAST scan against ${STAGING_URL}..."
                sh "docker run --rm zaproxy/zap-stable zap-cli quick-scan --self-contained ${STAGING_URL}"
            }
        }
        
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