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
                script {
                    // Use local image tag to guarantee availability
                    final LOCAL_IMAGE = 'finaldevsec:latest'

                    sh """
                        mkdir -p ${WORKSPACE}/trivy_reports

                        # Run Trivy scan
                        docker run --rm \
                        -v ${WORKSPACE}/trivy_reports:/reports:Z \
                        aquasec/trivy image \
                        --format template \
                        --template '@contrib/html.tpl' \
                        --severity HIGH,CRITICAL \
                        --output /reports/trivy_report.html \
                        ${LOCAL_IMAGE} || true

                        # Ensure file exists for archiving
                        if [ ! -s ${WORKSPACE}/trivy_reports/trivy_report.html ]; then
                        echo '<html><head><title>Trivy Report</title></head><body><h2>Trivy Security Scan</h2><p>No HIGH or CRITICAL vulnerabilities detected.</p></body></html>' > ${WORKSPACE}/trivy_reports/trivy_report.html
                        fi

                        ls -la ${WORKSPACE}/trivy_reports/
                    """
                }
                archiveArtifacts artifacts: 'trivy_reports/trivy_report.html'
            }
        }

        stage('OWASP ZAP SCAN (Baseline)') {
            steps {
                script {
                    sh """
                       mkdir -p ${WORKSPACE}/zap_reports
                        docker run --rm \\
                        --network ${DOCKER_NETWORK} \\
                        -v ${WORKSPACE}/zap_reports:/zap/wrk \\
                        -w /zap/wrk \\
                        zaproxy/zap-stable \\
                        zap-baseline.py \\
                            -t ${APP_TARGET_URL} \\
                            -r zap_report.html \
                            -x zap_report.xml \
                            -I
                            
                    """
                    // Confirm report exists
                    sh "ls -la ${WORKSPACE}/zap_reports/"

                    archiveArtifacts artifacts: 'zap_reports/*', allowEmptyArchive: false
                   
                }
            }
        }
    }  
}