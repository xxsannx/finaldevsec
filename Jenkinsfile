pipeline {
    agent any
    
    tools {
        nodejs "NodeJS"
    }
    
    environment {
        // Ganti dengan server Anda
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
                        elif composer show phpunit/phpunit > /dev/null 2>&1; then
                            echo "Running PHPUnit tests..."
                            ./vendor/bin/phpunit
                        else
                            echo "No PHPUnit tests found, skipping..."
                        fi
                        
                        # Check and run npm tests if available
                        if npm run | grep -q test; then
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
                            npm audit --audit-level moderate || true
                        fi
                        
                        # composer security check if available
                        if command -v composer > /dev/null; then
                            composer audit || true
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
                    // Contoh deploy ke staging server
                    sh """
                        echo "Deploying to staging server..."
                        # Tambahkan commands deploy staging di sini
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
                    // Pilih salah satu metode deploy di bawah
                }
            }
        }
    }
    
    post {
        always {
            echo '🧹 Cleaning workspace...'
            cleanWs()
            script {
                // Archive artifacts jika diperlukan
                archiveArtifacts artifacts: 'dist/**/*', fingerprint: true
            }
        }
        success {
            echo '✅ Pipeline completed successfully!'
            // Slack/Email notification untuk success
        }
        failure {
            echo '❌ Pipeline failed!'
            // Slack/Email notification untuk failure
        }
        unstable {
            echo '⚠️ Pipeline unstable!'
        }
    }}