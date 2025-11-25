// Jenkinsfile (Scripted Pipeline)
// Menggunakan pendekatan Docker untuk Dependency-Check agar stabil.
// Tahap DB Migration/Seeding dihilangkan sesuai permintaan.
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
        
        // Direktori cache untuk Dependency-Check di dalam workspace Jenkins
        DC_CACHE_DIR = "${WORKSPACE}/dependency-check-data"

        // Variabel Lingkungan Database (Dipertahankan untuk tahap yang lain jika diperlukan)
        DB_HOST_STAGING = 'mysql_service_name' 
        DB_DATABASE = 'laravel'
        DB_USERNAME = 'user'
        DB_PASSWORD = 'password'
    }

    stages {
        // 1. BUILD & INSTALL DEPENDENCIES
        stage('Build & Install Dependencies') {
            steps {
                echo "Melakukan checkout kode dan membangun image Docker multi-stage..."
                git branch: 'main', url: 'https://github.com/xxsannx/finaldevsec.git'
                
                script {
                    docker.withRegistry('https://registry.hub.docker.com', DOCKER_CREDENTIALS) {
                        docker.build(DOCKER_IMAGE, "-f Dockerfile .")
                        echo "Image berhasil dibangun: ${DOCKER_IMAGE}"
                    }
                }
            }
        }
        
        // 2. DEPENDENCY SCAN (OWASP DC - Menggunakan Docker dan Bypass NVD)
        stage('Dependency Vulnerability (OWASP DC)') {
            steps {
                echo "Menjalankan scan kerentanan dependensi menggunakan Docker (Bypass NVD Update)..."
                
                sh "mkdir -p dependency-check-report"
                
                script {
                    // Menyalin file lock dari container image ke workspace
                    sh "docker run --name temp_scanner -d ${DOCKER_IMAGE} sleep 30"
                    sh "docker cp temp_scanner:/var/www/html/composer.lock ."
                    sh "docker cp temp_scanner:/var/www/html/package-lock.json ."
                    sh "docker stop temp_scanner"
                    sh "docker rm temp_scanner"
                }

                // Membuat direktori cache lokal untuk Dependency-Check
                sh "mkdir -p ${DC_CACHE_DIR}"

                // Menjalankan Dependency-Check di dalam container Docker
                // --data /data: Menggunakan direktori mount /data sebagai lokasi cache
                // -n: Memaksa lewati update NVD
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
                
                // Pembersihan file lock
                sh "rm composer.lock package-lock.json" 
                
                echo "OWASP Dependency-Check selesai. Laporan di dependency-check-report/report.html"
            }
        }

        // 3. STATIC CODE ANALYSIS (SONARQUBE)
        stage('Static Code Analysis (SonarQube)') {
            steps {
                echo "Menjalankan analisis kode statis SonarQube..."
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
        
        // 4. DOCKER IMAGE PUSH
        stage('Docker Image Push') {
            steps {
                echo "Mendorong image Docker ke registry..."
                script {
                    docker.withRegistry('https://registry.hub.docker.com', DOCKER_CREDENTIALS) {
                        docker.image(DOCKER_IMAGE).push() 
                        echo "Image Docker berhasil di dorong: ${DOCKER_IMAGE}"
                    }
                }
            }
        }
        
        // 5. TRAFFIC GENERATION (LOCUST)
        stage('Traffic Generation (Locust)') {
            steps {
                echo "Memulai Load Test menggunakan Locust (60 detik, 50 pengguna)..."
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
                echo "Load Test selesai. Hasil di locust_results.csv."
            }
        }
        
        // 6. DAST SCAN (OWASP ZAP)
        stage('DAST Scan (OWASP ZAP)') {
            steps {
                echo "Menjalankan DAST Scan (OWASP ZAP) terhadap ${STAGING_URL}..."
                sh "docker run --rm zaproxy/zap-stable zap-cli quick-scan --self-contained ${STAGING_URL}"
            }
        }
        
        // 7. FINAL DEPLOYMENT
        stage('Final Deploy to Production') {
            when {
                expression {
                    currentBuild.result == 'SUCCESS'
                }
            }
            steps {
                echo "Semua scan dan tes telah lulus. Siap untuk deployment."
            }
        }
    }
}