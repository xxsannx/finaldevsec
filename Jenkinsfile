pipeline{
    agent any
    
    tools{
        jdk 'jdk17'
        nodejs 'node18' 
    }
    
    environment {
        SCANNER_HOME = tool 'sonar-scanner'
        DOCKER_IMAGE = "xxsamx/finaldevsec:latest"
        
        // Sesuai docker-compose.yml
        DOCKER_NETWORK = 'finaldevsec_pineus_network'
        SONARQUBE_HOST = 'http://sonarqube:9000'
        APP_TARGET_URL = 'http://nginx:80'
    }
    
    stages {

        stage('Cleanup Workspace'){
            steps{
                cleanWs()
            }
        }
        
        stage('Checkout from Git'){
            steps{
                git branch: 'main', url: 'https://github.com/xxsannx/finaldevsec.git'
            }
        }

        stage('Wait for Services') {
            steps {
                sleep 15
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
                mkdir -p ${WORKSPACE}/trivy_reports
                docker run --rm \
                -v ${WORKSPACE}/trivy_reports:/reports \
                aquasec/trivy image \
                --format template \
                --template '@contrib/html.tpl' \
                --severity HIGH,CRITICAL \
                --output /reports/trivy_report.html \
                ${DOCKER_IMAGE}
                """
                archiveArtifacts artifacts: 'trivy_reports/trivy_report.html', allowEmptyArchive: false
            }
        }

        stage('OWASP ZAP SCAN (Baseline)') {
            steps {
                script {
                    // Optional: wait + verify target is reachable
                    sh """
                    echo "Waiting for nginx to be ready..."
                    docker run --rm --network ${DOCKER_NETWORK} curlimages/curl:latest \
                    -f --retry 10 --retry-delay 5 --retry-connrefused \
                    http://nginx:80 || exit 1
                    """

                    sh """
                    mkdir -p ${WORKSPACE}/zap_reports
                    docker run --rm \
                    --network ${DOCKER_NETWORK} \
                    -v ${WORKSPACE}/zap_reports:/zap/wrk:Z \
                    zaproxy/zap-stable zap-baseline.py \
                    -t ${APP_TARGET_URL} \
                    -r zap_report.html
                    """
                    sh "ls -la ${WORKSPACE}/zap_reports/"
                    archiveArtifacts artifacts: 'zap_reports/zap_report.html', allowEmptyArchive: false
                }
            }
        }
    }  
}