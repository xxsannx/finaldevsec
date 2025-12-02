pipeline {
    agent any

    environment {
        DOCKER_IMAGE = 'xxsamx/finaldevsec'
        DOCKER_REGISTRY = 'registry.hub.docker.com'
        ODC_TOOL_NAME = 'DPCheck'
    }

    options {
        timeout(time: 15, unit: 'MINUTES')
    }

    stages {

        stage('Cleanup Workspace') {
            steps {
                cleanWs()
            }
        }

        stage('Checkout from Git') {
            steps {
                checkout scm
            }
        }

        stage('Wait for SonarQube Startup') {
            steps {
                echo "Waiting 120 seconds for SonarQube to fully start..."
                sleep 120
            }
        }

        stage('SonarQube Analysis') {
            steps {
                withSonarQubeEnv('SonarQube-Local') {
                    sh """
                        sonar-scanner \
                        -Dsonar.projectKey=${env.JOB_NAME} \
                        -Dsonar.projectName=${env.JOB_NAME} \
                        -Dsonar.sources=.
                    """
                }
            }
        }

        stage('Quality Gate') {
            steps {
                script {
                    timeout(time: 10, unit: 'MINUTES') {
                        waitForQualityGate abortPipeline: true, credentialsId: 'SONAR_AUTH_TOKEN'
                    }
                }
            }
        }

        stage('Install Dependencies & SCA') {
            steps {
                sh 'apt-get update && apt-get install -y libatomic1'
                sh 'npm install'

                dependencyCheck additionalArguments: "--scan ./ --disableYarnAudit --disableNodeAudit --format XML --out ./", odcInstallation: "${ODC_TOOL_NAME}"
                dependencyCheckPublisher pattern: '**/dependency-check-report.xml'
            }
        }

        stage('Docker Build & Push') {
            steps {
                withCredentials([usernamePassword(
                    credentialsId: 'docker-hub-credentials', 
                    usernameVariable: 'DOCKER_USERNAME', 
                    passwordVariable: 'DOCKER_PASSWORD'
                )]) {
                    sh "docker login -u ${DOCKER_USERNAME} -p ${DOCKER_PASSWORD} ${DOCKER_REGISTRY}"
                    sh "docker build -t ${DOCKER_IMAGE}:${BUILD_NUMBER} ."
                    sh "docker push ${DOCKER_IMAGE}:${BUILD_NUMBER}"
                    sh "docker tag ${DOCKER_IMAGE}:${BUILD_NUMBER} ${DOCKER_IMAGE}:latest"
                    sh "docker push ${DOCKER_IMAGE}:latest"
                    sh "docker logout ${DOCKER_REGISTRY}"
                }
            }
        }

        stage('Image Scanning (Trivy)') {
            steps {
                sh """
                    docker run --rm \
                    -v /var/run/docker.sock:/var/run/docker.sock \
                    aquasec/trivy:latest image --exit-code 1 \
                    --severity CRITICAL \
                    --format template --template "@contrib/html.tpl" -o trivy_report.html \
                    ${DOCKER_IMAGE}:${BUILD_NUMBER} || true
                """

                archiveArtifacts artifacts: 'trivy_report.html', onlyIfSuccessful: false
            }
        }

        stage('Deploy to container') {
            steps {
                script {
                    sh "docker network create zap-network || true"
                    sh "docker pull ${DOCKER_IMAGE}:${BUILD_NUMBER}"

                    sh """
                        docker run -d --rm \
                        --network zap-network \
                        --name final-app \
                        ${DOCKER_IMAGE}:${BUILD_NUMBER}
                    """

                    sleep 10
                }
            }
        }

        stage('OWASP ZAP SCAN (Baseline)') {
            steps {
                sh """
                    docker run --rm \
                    --network zap-network \
                    -v ${PWD}:/zap/reports \
                    owasp/zap2docker-weekly zap-baseline.py \
                    -t http://final-app:9000 \
                    -r zap-baseline-report.xml || true
                """

                archiveArtifacts artifacts: 'zap-baseline-report.xml', onlyIfSuccessful: false
            }
        }

        stage('Post-Deployment Cleanup') {
            steps {
                sh "docker rmi ${DOCKER_IMAGE}:${BUILD_NUMBER} || true"
                sh "docker stop final-app || true"
                sh "docker rm final-app || true"
                sh "docker network rm zap-network || true"
            }
        }
    }
}