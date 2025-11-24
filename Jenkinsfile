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
        // Stage 1: Build & Install Dependencies
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
        
        // Stage 2: DEPENDENCY SCAN (OWASP DC) - FIXED VERSION
        stage('Dependency Vulnerability (OWASP DC)') {
            steps {
                echo "Running OWASP Dependency-Check scan on lock files..."
                
                sh "mkdir -p dependency-check-report"
                
                script {
                    // 1. Jalankan container sementara dari image yang sudah di-build
                    sh "docker run --name temp_scanner -d ${DOCKER_IMAGE} sleep 30"
                    
                    // 2. Salin file lock dari container ke WORKSPACE Jenkins
                    sh "docker cp temp_scanner:/var/www/html/composer.lock . || true"
                    sh "docker cp temp_scanner:/var/www/html/package-lock.json . || true"
                    
                    // 3. Hentikan dan hapus container sementara
                    sh "docker stop temp_scanner || true"
                    sh "docker rm temp_scanner || true"
                }

                // 4. Jalankan scan TANPA flag -n untuk build pertama
                // Database akan di-download dan di-cache
                sh """
                    docker run --rm \
                        -e user=\$(id -u) \
                        -v "${WORKSPACE}/composer.lock":/scan/composer.lock:ro \
                        -v "${WORKSPACE}/package-lock.json":/scan/package-lock.json:ro \
                        -v "${WORKSPACE}/dependency-check-report":/report \
                        -v "dcheck-data-cache:/usr/share/dependency-check/data" \
                        owasp/dependency-check:${DC_VERSION} \
                        --scan /scan \
                        --format HTML \
                        --format JSON \
                        --out /report \
                        --project "Laravel DevSecOps" \
                        --failOnCVSS 7 \
                        --enableExperimental
                """
                
                // Publish HTML report
                publishHTML([
                    allowMissing: false,
                    alwaysLinkToLastBuild: true,
                    keepAll: true,
                    reportDir: 'dependency-check-report',
                    reportFiles: 'dependency-check-report.html',
                    reportName: 'OWASP Dependency Check Report'
                ])
                
                // Hapus file lock yang dicopy agar tidak mengganggu checkout berikutnya
                sh "rm -f composer.lock package-lock.json" 
                
                echo "OWASP Dependency-Check selesai. Laporan disimpan di dependency-check-report/"
            }
        }

        // Stage 3: Static Code Analysis
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
        
        // Stage 4: Quality Gate Check
        stage('Quality Gate') {
            steps {
                timeout(time: 5, unit: 'MINUTES') {
                    waitForQualityGate abortPipeline: false
                }
            }
        }
        
        // Stage 5: Docker Image Push
        stage('Docker Image Push') {
            steps {
                echo "Pushing Docker image..."
                script {
                    docker.withRegistry('https://registry.hub.docker.com', DOCKER_CREDENTIALS) {
                        docker.image(DOCKER_IMAGE).push() 
                        docker.image(DOCKER_IMAGE).push('latest')
                        echo "Docker Image Pushed: ${DOCKER_IMAGE}"
                    }
                }
            }
        }
        
        // Stage 6: Deploy to Staging
        stage('Deploy to Staging') {
            steps {
                echo "Deploying to staging environment..."
                script {
                    // Stop dan remove container lama jika ada
                    sh """
                        docker stop laravel-staging || true
                        docker rm laravel-staging || true
                    """
                    
                    // Deploy container baru
                    sh """
                        docker run -d \
                            --name laravel-staging \
                            --network=${DOCKER_NETWORK} \
                            -p 8080:80 \
                            ${DOCKER_IMAGE}
                    """
                    
                    // Wait for container to be ready
                    sleep(time: 10, unit: 'SECONDS')
                    
                    echo "Staging deployment completed at http://localhost:8080"
                }
            }
        }
        
        // Stage 7: Traffic Generation (Locust)
        stage('Traffic Generation (Locust)') {
            steps {
                echo "Starting Load Test using Locust for 60 seconds (50 users)..."
                
                sh """
                    mkdir -p locust-results
                    
                    docker run --rm \
                        -v "${WORKSPACE}/docker/locust/locustfile.py":/home/locust/locustfile.py \
                        -v "${WORKSPACE}/locust-results":/home/locust/results \
                        --network=${DOCKER_NETWORK} \
                        locustio/locust \
                        -f /home/locust/locustfile.py \
                        --host=${STAGING_URL} \
                        --headless \
                        -u 50 \
                        -r 10 \
                        --run-time 60s \
                        --html=/home/locust/results/locust_report.html \
                        --csv=/home/locust/results/locust_results
                """
                
                // Publish Locust results
                publishHTML([
                    allowMissing: false,
                    alwaysLinkToLastBuild: true,
                    keepAll: true,
                    reportDir: 'locust-results',
                    reportFiles: 'locust_report.html',
                    reportName: 'Locust Load Test Report'
                ])
                
                echo "Load Test finished. Results saved to locust-results/"
            }
        }
        
        // Stage 8: DAST Scan (OWASP ZAP)
        stage('DAST Scan (OWASP ZAP)') {
            steps {
                echo "Running OWASP ZAP DAST scan against ${STAGING_URL}..."
                
                sh """
                    mkdir -p zap-reports
                    
                    docker run --rm \
                        -v "${WORKSPACE}/zap-reports":/zap/wrk \
                        --network=${DOCKER_NETWORK} \
                        zaproxy/zap-stable \
                        zap-baseline.py \
                        -t ${STAGING_URL} \
                        -r zap_report.html \
                        -J zap_report.json \
                        -w zap_report.md
                """
                
                // Publish ZAP report
                publishHTML([
                    allowMissing: false,
                    alwaysLinkToLastBuild: true,
                    keepAll: true,
                    reportDir: 'zap-reports',
                    reportFiles: 'zap_report.html',
                    reportName: 'OWASP ZAP DAST Report'
                ])
                
                echo "DAST scan completed. Report saved to zap-reports/"
            }
        }
        
        // Stage 9: Security Review
        stage('Security Review') {
            steps {
                script {
                    echo "Reviewing security scan results..."
                    
                    // Read dependency check results
                    def depCheckFile = "${WORKSPACE}/dependency-check-report/dependency-check-report.json"
                    if (fileExists(depCheckFile)) {
                        def depCheckJson = readJSON file: depCheckFile
                        def criticalVulns = depCheckJson.dependencies.findAll { 
                            it.vulnerabilities?.any { vuln -> vuln.severity == 'CRITICAL' }
                        }.size()
                        
                        echo "Critical vulnerabilities found: ${criticalVulns}"
                        
                        if (criticalVulns > 0) {
                            unstable(message: "Found ${criticalVulns} critical vulnerabilities")
                        }
                    }
                }
            }
        }
        
        // Stage 10: Final Deploy to Production
        stage('Deploy to Production') {
            when {
                expression {
                    currentBuild.result == null || currentBuild.result == 'SUCCESS'
                }
            }
            steps {
                input message: 'Deploy to production?', ok: 'Deploy'
                
                echo "Deploying to production environment..."
                script {
                    // Stop dan remove container production lama
                    sh """
                        docker stop laravel-production || true
                        docker rm laravel-production || true
                    """
                    
                    // Deploy container production baru
                    sh """
                        docker run -d \
                            --name laravel-production \
                            --network=${DOCKER_NETWORK} \
                            -p 80:80 \
                            --restart unless-stopped \
                            ${DOCKER_IMAGE}
                    """
                    
                    echo "Production deployment completed!"
                }
            }
        }
    }
    
    post {
        always {
            echo "Pipeline execution completed."
            
            // Archive artifacts
            archiveArtifacts artifacts: '**/dependency-check-report/**, **/locust-results/**, **/zap-reports/**', 
                           allowEmptyArchive: true
            
            // Clean workspace
            cleanWs(
                deleteDirs: true,
                patterns: [
                    [pattern: 'dependency-check-report/**', type: 'INCLUDE'],
                    [pattern: 'locust-results/**', type: 'INCLUDE'],
                    [pattern: 'zap-reports/**', type: 'INCLUDE']
                ]
            )
        }
        
        success {
            echo "✅ Pipeline completed successfully!"
            
            // Send notification (optional)
            // emailext (
            //     subject: "✅ Jenkins Pipeline Success: ${env.JOB_NAME} - ${env.BUILD_NUMBER}",
            //     body: "Build ${env.BUILD_NUMBER} completed successfully.",
            //     to: "your-email@example.com"
            // )
        }
        
        failure {
            echo "❌ Pipeline failed!"
            
            // Send notification (optional)
            // emailext (
            //     subject: "❌ Jenkins Pipeline Failed: ${env.JOB_NAME} - ${env.BUILD_NUMBER}",
            //     body: "Build ${env.BUILD_NUMBER} failed. Check console output for details.",
            //     to: "your-email@example.com"
            // )
        }
        
        unstable {
            echo "⚠️ Pipeline completed with warnings!"
        }
    }
}