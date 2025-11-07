pipeline {
    agent any
    
    tools {
        nodejs "NodeJS"
    }
    
    environment {
        DEPLOY_SERVER = 'your-server.com'
        DEPLOY_PATH = '/var/www/web2'
        DEPLOY_USER = 'deploy-user'
    }
    
    stages {
        stage('Checkout') {
            steps {
                echo '📦 Checking out source code...'
                checkout scm
            }
        }
        
        stage('Verify Environment') {
            steps {
                echo '🔍 Verifying environment...'
                sh 'node --version'
                sh 'npm --version'
                sh 'php --version || echo "PHP not installed"'
                sh 'composer --version || echo "Composer not installed"'
            }
        }
        
        stage('Install Dependencies') {
            steps {
                echo '📥 Installing dependencies...'
                sh 'npm install --silent'
                sh 'composer install --no-dev --optimize-autoloader || echo "Composer not needed"'
            }
        }
        
        stage('Build Assets') {
            steps {
                echo '🏗️ Building frontend assets...'
                sh 'npm run build'
            }
        }
        
        stage('Run Tests') {
            steps {
                echo '🧪 Running tests...'
                script {
                    sh '''
                        # Check and run PHPUnit tests if available
                        if [ -f "vendor/bin/phpunit" ]; then
                            echo "Running PHPUnit tests..."
                            ./vendor/bin/phpunit
                        elif command -v composer > /dev/null && composer show phpunit/phpunit > /dev/null 2>&1; then
                            echo "Running PHPUnit tests..."
                            ./vendor/bin/phpunit
                        else
                            echo "No PHPUnit tests found, skipping..."
                        fi
                        
                        # Check and run npm tests if available
                        if npm run | grep -q " test"; then
                            echo "Running npm tests..."
                            npm test
                        else
                            echo "No npm tests found, skipping..."
                        fi
                    '''
                }
            }
        }
        
        stage('Security Scan') {
            steps {
                echo '🔒 Running security checks...'
                script {
                    sh '''
                        # npm audit if available
                        if command -v npm > /dev/null; then
                            echo "Running npm audit..."
                            npm audit --audit-level moderate || true
                        else
                            echo "npm not available for audit"
                        fi
                        
                        # composer security check if available
                        if command -v composer > /dev/null; then
                            echo "Running composer audit..."
                            composer audit || true
                        else
                            echo "composer not available for audit"
                        fi
                    '''
                }
            }
        }
        
        stage('Deploy - Staging') {
            when {
                branch 'develop'
            }
            steps {
                echo '🚀 Deploying to Staging...'
                script {
                    sh """
                        echo "Deploying to staging server..."
                        echo "Staging deployment would happen here"
                    """
                }
            }
        }
        
        stage('Deploy - Production') {
            when {
                branch 'main'
            }
            steps {
                echo '🎯 Deploying to Production...'
                script {
                    sh """
                        echo "Production deployment would happen here"
                        echo "Add your deployment commands here"
                    """
                }
            }
        }
    }
    
    post {
        always {
            echo '🧹 Cleaning workspace...'
            cleanWs()
            // Archive artifacts tanpa script block
            archiveArtifacts artifacts: 'dist/**/*', fingerprint: true
        }
        success {
            echo '✅ Pipeline completed successfully!'
        }
        failure {
            echo '❌ Pipeline failed!'
        }
        unstable {
            echo '⚠️ Pipeline unstable!'
        }
    }
}
