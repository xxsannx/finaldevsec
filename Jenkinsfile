pipeline{
    agent any
    
    tools{
        jdk 'jdk17'
        nodejs 'node18' 
    }
    
    environment {
        SCANNER_HOME = tool 'sonar-scanner'
        DOCKER_IMAGE = "xxsamx/finaldevsec:latest"

        // FIX: Host NGINX di dalam network Docker
        APP_INTERNAL_HOST = 'http://pineus_tilu_nginx:80'

        // Docker network dari docker compose
        DOCKER_NETWORK = 'finaldevsec_pineus_network'

        DEPLOY_CONTAINER_NAME = 'finaldevsec_deployed_app'
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
        
        stage('Deploy to container'){
            steps{
                script{
                    sh "docker rm -f ${DEPLOY_CONTAINER_NAME} || true"
                    sleep 5

                    // FIX: tidak perlu expose host port
                    sh "docker run -d --name ${DEPLOY_CONTAINER_NAME} --network=${DOCKER_NETWORK} ${DOCKER_IMAGE}"
                }
            }
        }

        // ======================================================
        // 🔥 FIX ZAP DAST SCAN (target = NGINX docker service)
        // ======================================================
        stage('OWASP ZAP SCAN (Baseline)') {
            steps {
                script {

                    sh "mkdir -p ${WORKSPACE}/zap_reports"
                    sh "mkdir -p ${WORKSPACE}/zap_work"
                    sh "chmod -R 777 ${WORKSPACE}/zap_reports ${WORKSPACE}/zap_work"

                    echo "Menunggu container up..."
                    sh """
                    docker run --rm \
                        --network ${DOCKER_NETWORK} \
                        -v ${WORKSPACE}/zap_reports:/zap/reports \
                        -v ${WORKSPACE}/zap_work:/zap/wrk \
                        zaproxy/zap-stable \
                            zap-baseline.py \
                            -t ${APP_INTERNAL_HOST} \
                            -r zap_report.html || true
                    """

                }

                archiveArtifacts artifacts: 'zap_reports/zap_report.html', allowEmptyArchive: false
            }
        }

        stage('Post-Deployment Cleanup'){
            steps{
                sh "docker rm -f ${DEPLOY_CONTAINER_NAME} || true"
            }
        }

    }
}