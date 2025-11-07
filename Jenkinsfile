pipeline {
    agent any

    environment {
        COMPOSER_ALLOW_SUPERUSER = 1
        PATH = "/usr/local/bin:/usr/bin:/bin:/usr/local/php/bin:$PATH"
    }

    stages {
        stage('Check Prerequisites') {
            steps {
                script {
                    // Check if required tools are available
                    sh '''
                        echo "Checking required tools..."
                        php --version || echo "PHP not found"
                        composer --version || echo "Composer not found"
                        node --version || echo "Node not found (optional)"
                        npm --version || echo "NPM not found (optional)"
                    '''
                }
            }
        }

        stage('Checkout Code') {
            steps {
                checkout scm
            }
        }

        stage('Install PHP Dependencies') {
            steps {
                echo 'Installing PHP dependencies...'
                sh 'composer install --no-dev --optimize-autoloader --no-progress'
            }
        }

        stage('Build Assets') {
            steps {
                echo 'Building frontend assets...'
                script {
                    if (fileExists('package.json')) {
                        sh 'npm install --silent'
                        sh 'npm run production'
                    } else {
                        echo 'No package.json found, skipping npm build'
                    }
                }
            }
        }

        stage('Setup Environment') {
            steps {
                echo 'Setting up environment...'
                sh '''
                    if [ -f .env.example ]; then
                        cp .env.example .env
                    fi
                    php artisan key:generate --no-interaction --force
                '''
            }
        }

        stage('Run Tests') {
            steps {
                echo 'Running Laravel tests...'
                sh 'php artisan test --no-interaction'
            }
            post {
                always {
                    // Create dummy test report if none exists
                    sh 'mkdir -p storage/logs || true'
                }
            }
        }

        stage('Deploy') {
            steps {
                echo 'Deploying Laravel application...'
                sh '''
                    DEPLOY_DIR="/tmp/laravel-app-${BUILD_NUMBER}"
                    mkdir -p ${DEPLOY_DIR}
                    cp -r . ${DEPLOY_DIR}/
                    echo "Application deployed to: ${DEPLOY_DIR}"
                    ls -la ${DEPLOY_DIR}
                '''
            }
        }
    }

    post {
        always {
            echo "Build ${currentBuild.result} - Build Number: ${env.BUILD_NUMBER}"
            echo "Workspace: ${env.WORKSPACE}"
        }
        success {
            echo '✅ Pipeline completed successfully!'
            // Temporary disable email until configured
            // emailext subject: "SUCCESS: ${env.JOB_NAME}", body: "Build successful!", to: "team@example.com"
        }
        failure {
            echo '❌ Pipeline failed!'
            // Temporary disable email until configured
            // emailext subject: "FAILED: ${env.JOB_NAME}", body: "Build failed!", to: "team@example.com"
        }
    }
}