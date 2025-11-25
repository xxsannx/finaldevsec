// Jenkinsfile (Scripted Pipeline)
// Menggunakan pendekatan Plugin Jenkins untuk OWASP Dependency-Check agar lebih stabil
// dan melewati masalah NVD API yang persisten pada mode Docker.
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
        DC_VERSION = '9.0.8' // Versi Docker tidak lagi digunakan, tetapi variabel dipertahankan
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
        
        // 2. DEPENDENCY SCAN (OWASP DC - MENGGUNAKAN PLUGIN JENKINS)
        stage('Dependency Vulnerability (OWASP DC)') {
            steps {
                echo "Menjalankan scan kerentanan dependensi menggunakan Plugin Jenkins..."
                
                // Langkah 1: Salin file lock dari container ke workspace agar Plugin dapat mengaksesnya
                script {
                    sh "mkdir -p dependency-check-report" // Pastikan direktori report ada
                    sh "docker run --name temp_scanner -d ${DOCKER_IMAGE} sleep 30"
                    sh "docker cp temp_scanner:/var/www/html/composer.lock ."
                    sh "docker cp temp_scanner:/var/www/html/package-lock.json ."
                    sh "docker stop temp_scanner"
                    sh "docker rm temp_scanner"
                }

                // Langkah 2: Jalankan Plugin Dependency-Check
                // Argumen: --scan . : Scan direktori saat ini (yang berisi file lock)
                //          --noupdate : Wajib ditambahkan di sini untuk melewati kegagalan NVD
                //          --format XML : Format yang dibutuhkan oleh DependencyCheckPublisher
                dependencyCheck additionalArguments: '''
                    --scan .
                    --format XML
                    --project "Laravel DevSecOps"
                    --noupdate
                    --out dependency-check-report
                ''', odcInstallation: 'Dependency-Check' // PASTIKAN NAMA INI SAMA DENGAN KONFIGURASI DI JENKINS
                
                // Langkah 3: Publikasikan hasilnya ke UI Jenkins
                dependencyCheckPublisher pattern: 'dependency-check-report/dependency-check-report.xml'

                // Pembersihan file lock
                sh "rm composer.lock package-lock.json" 
                
                echo "OWASP Dependency-Check selesai. Laporan diintegrasikan ke Jenkins UI."
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