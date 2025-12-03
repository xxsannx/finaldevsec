pipeline{
    agent any
    
    tools{
        jdk 'jdk17'
        nodejs 'node18' 
    }
    
    environment {
        SCANNER_HOME = tool 'sonar-scanner'
        DOCKER_IMAGE = "xxsamx/finaldevsec:latest"

        APP_INTERNAL_HOST = 'http://nginx:80' 
        COMPOSE_FILE = 'docker-compose.yml' 
        
        // Asumsi nama network yang dibuat oleh docker compose adalah 'finaldevsec_pineus_network' (nama folder + nama network)
        // Jika hanya 'pineus_network', ganti variabel ini. Saya gunakan nama lengkap untuk keamanan.
        COMPOSE_NETWORK_NAME = 'finaldevsec_pineus_network'
    }
    
    stages {

        stage('Cleanup Workspace & Container'){
            steps{
                cleanWs()
                sh "docker rm -f ${DEPLOY_CONTAINER_NAME} || true"
            }
        }
        
        stage('Checkout from Git'){
            steps{
                git branch: 'main', url: 'https://github.com/xxsannx/finaldevsec.git'
            }
        }

        stage('Wait for SonarQube Startup') {
            steps {
                sleep 30
            }
        }
        
        stage("Sonarqube Analysis"){
            steps{
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
                    timeout(time: 5, unit: 'MINUTES') {
                        waitForQualityGate abortPipeline: false, credentialsId: 'SONAR_AUTH_TOKEN'
                    }
                }
            }
        }
        
        stage('Install Dependencies & SCA') {
            steps{
                sh 'apt-get update && apt-get install -y libatomic1'
                sh "npm install"
                dependencyCheck additionalArguments: '--scan ./ --disableYarnAudit --disableNodeAudit', odcInstallation: 'DPCheck'
                dependencyCheckPublisher pattern: '**/dependency-check-report.xml'
            }
        }
        
        stage("Docker Build & Push"){
            steps{
                script{
                    withDockerRegistry(credentialsId: 'docker-hub-credentials', toolName: 'docker'){
                        sh "docker build -t finaldevsec ."
                        sh "docker tag finaldevsec ${DOCKER_IMAGE}" 
                        sh "docker push ${DOCKER_IMAGE}"
                    }
                }
            }
        }
        
        stage('Image Scanning (Trivy)') {
            steps {
                sh """
                docker run --rm \
                -v ${WORKSPACE}/trivy_reports:/reports \
                aquasec/trivy:latest image \
                --severity HIGH,CRITICAL \
                --format template --template "@/contrib/html.tpl" \
                -o /reports/trivy_report.html \
                ${DOCKER_IMAGE} || true
                """
                archiveArtifacts artifacts: 'trivy_reports/trivy_report.html', allowEmptyArchive: true
            }
        }
        
        stage('Deploy Full Stack & Observability'){
            steps{
                script{
                    echo "Deploying full stack using docker-compose.yml..."
                    sh "docker-compose -f ${COMPOSE_FILE} up -d --build --remove-orphans"

                    echo "Waiting 60 seconds for all services to stabilize..."
                    sleep 60
                }
            }
        }

        stage('OWASP ZAP SCAN (Baseline)') {
            steps {
                sh "mkdir -p zap_reports"
                // 🔥 Tambahkan direktori kerja untuk ZAP
                sh "mkdir -p zap_working_dir" 
                
                script {
                    sh """
                    docker run --rm \
                        --network ${COMPOSE_NETWORK_NAME} \
                        -v ${WORKSPACE}/zap_reports:/zap/reports \
                        -v ${WORKSPACE}/zap_working_dir:/zap/wrk \
                        zaproxy/zap-stable \
                            zap-baseline.py \
                            -t ${APP_INTERNAL_HOST} \
                            -r /zap/reports/zap_report.html || true
                    """
                }
                
                // Hapus direktori kerja setelah ZAP selesai
                sh "rm -rf zap_working_dir"

                archiveArtifacts artifacts: 'zap_reports/zap_report.html', allowEmptyArchive: false
            }
        }
        
        stage('Generate Load Test Traffic (Locust)'){
            steps{
                script{
                    sh 'apt-get update && apt-get install -y curl'
                    
                    echo "Starting Load Test using Locust service (pineus_tilu_locust)..."

                    sh """
                    docker run --rm \
                        --network ${COMPOSE_NETWORK_NAME} \
                        appropriate/curl \
                        bash -c '
                            echo "Starting Locust run..."
                            curl -X POST http://pineus_tilu_locust:8089/run -d "locust_count=5&hatch_rate=1" || true
                        '
                    """
                    
                    echo "Waiting 30 seconds for load generation (adjust as needed)..."
                    sleep 30
                    
                    sh """
                    docker run --rm \
                        --network ${COMPOSE_NETWORK_NAME} \
                        appropriate/curl \
                        bash -c '
                            echo "Stopping Locust run..."
                            curl -X GET http://pineus_tilu_locust:8089/stop' || true
                        '
                    """
                    
                    echo "Load test completed. Metrics/Traces are recorded in Prometheus/Grafana/Jaeger."
                }
            }
        }


        stage('Post-Deployment Cleanup'){
            steps{
                echo "Shutting down full stack..."
                sh "docker-compose -f ${COMPOSE_FILE} down"
            }
        }

    }
}