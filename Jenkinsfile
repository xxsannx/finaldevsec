pipeline{
    agent any
    
    tools{
        jdk 'jdk17'
        nodejs 'node18' 
    }
    
    environment {
        SCANNER_HOME=tool 'sonar-scanner'
        DOCKER_IMAGE = "xxsamx/finaldevsec:latest"
        APP_INTERNAL_HOST = 'http://host.docker.internal:8001'
        DOCKER_NETWORK = 'finaldevsec_default'
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
                echo "Cloning repository..."
                git branch: 'main', url: 'https://github.com/xxsannx/finaldevsec.git'
            }
        }
        
        stage('Wait for SonarQube Startup') {
            steps {
                echo "Memberikan jeda 30 detik untuk SonarQube..."
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
                -v \$(pwd)/trivy_reports:/reports \
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
                   sh 'docker rm -f finaldevsec_deployed_app || true'
                   sleep 5
                   sh "docker run -d --name ${DEPLOY_CONTAINER_NAME} -p 8001:80 ${DOCKER_IMAGE}"
                   echo "Aplikasi berjalan di http://localhost:8001"
                }
            }
        }
        
        stage('OWASP ZAP SCAN (Baseline)') {
            steps {
                echo "Menunggu aplikasi siap..."
                sleep 20

                sh "mkdir -p zap_reports"

                sh """
                docker run --rm \
                    --add-host=host.docker.internal:host-gateway \
                    -v \$(pwd)/zap_reports:/zap/reports \
                    zaproxy/zap-stable zap-baseline.py \
                    -t ${APP_INTERNAL_HOST} \
                    -r zap_report.html || true
                """

                archiveArtifacts artifacts: 'zap_reports/zap_report.html', onlyIfSuccessful: false
            }
        }
        
        stage('Post-Deployment Cleanup'){
            steps{
                sh 'docker rm -f ${DEPLOY_CONTAINER_NAME} || true'
            }
        }

    }
}