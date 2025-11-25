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
        
        // --- Dependency Check Cache Directory ---
        DC_CACHE_DIR = "${WORKSPACE}/dependency-check-data"
    }

    stages {
        // ... (Stage 1: Build & Install Dependencies)
        stage('Build & Install Dependencies') {
            steps {
                echo "Checking out code from GitHub: https://github.com/xxsannx/finaldevsec.git"
                // 1. Checkout Code
                git branch: 'main', url: 'https://github.com/xxsannx/finaldevsec.git'
                
                echo "Running Multi-Stage Docker Build to install dependencies inside the image..."
                
                script {
                    docker.withRegistry('https://registry.hub.docker.com', DOCKER_CREDENTIALS) {
                        docker.build(DOCKER_IMAGE, "-f Dockerfile .")
                        echo "Image built successfully: ${DOCKER_IMAGE}"
                    }
                }
            }
        }
        
        // 2. DEPENDENCY SCAN (OWASP DC - Menggunakan Cache di Workspace)
        stage('Dependency Vulnerability (OWASP DC)') {
            steps {
                echo "Running OWASP Dependency-Check scan on lock files..."
                
                sh "mkdir -p dependency-check-report"
                
                script {
                    // 1. Jalankan container sementara dari image yang sudah di-build
                    sh "docker run --name temp_scanner -d ${DOCKER_IMAGE} sleep 30"
                    
                    // 2. Salin file lock dari container ke WORKSPACE Jenkins
                    sh "docker cp temp_scanner:/var/www/html/composer.lock ."
                    sh "docker cp temp_scanner:/var/www/html/package-lock.json ."
                    
                    // 3. Hentikan dan hapus container sementara
                    sh "docker stop temp_scanner"
                    sh "docker rm temp_scanner"
                }

                // Buat direktori cache (walaupun docker run akan membuatnya, ini lebih aman)
                sh "mkdir -p ${DC_CACHE_DIR}"

                // 4. Jalankan scan. Menggunakan --data untuk menunjuk ke direktori cache lokal
                // dan -n untuk memaksa skip update NVD yang gagal.
                sh """
                    docker run --rm \
                        -v "${WORKSPACE}/composer.lock":/scan/composer.lock \
                        -v "${WORKSPACE}/package-lock.json":/scan/package-lock.json \
                        -v "${WORKSPACE}/dependency-check-report":/report \
                        -v "${DC_CACHE_DIR}":/data \
                        owasp/dependency-check:${DC_VERSION} \
                        --scan /scan/composer.lock /scan/package-lock.json \
                        --format HTML \
                        --out /report \
                        --project "Laravel DevSecOps" \
                        --data /data \
                        -n
                """
                
                // Hapus file lock yang dicopy agar tidak mengganggu checkout berikutnya
                sh "rm composer.lock package-lock.json" 
                
                echo "OWASP Dependency-Check selesai. Laporan disimpan di dependency-check-report/report.html"
            }
        }

        // ... (Tahap 3 dan seterusnya tetap sama)
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