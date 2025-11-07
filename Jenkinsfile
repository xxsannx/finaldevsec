pipeline {
    agent any
    
    tools {
        nodejs "NodeJS"  // Gunakan tools yang sudah dikonfigurasi
    }
    
    stages {
        stage('Verify Node.js') {
            steps {
                sh 'node --version'
                sh 'npm --version'
            }
        }
        
        stage('Install Dependencies') {
            steps {
                echo 'Installing PHP dependencies...'
                sh 'composer install --no-dev --optimize-autoloader'
            }
        }
        
        stage('Build Assets') {
            steps {
                echo 'Building frontend assets...'
                sh 'npm install --silent'
                sh 'npm run build'
            }
        }
        
        stage('Setup Environment') {
            steps {
                echo 'Setting up environment...'
                sh 'cp .env.example .env'
                sh 'php artisan key:generate'
            }
        }
        
        stage('Run Tests') {
            steps {
                echo 'Running tests...'
                sh 'php artisan test'
            }
        }
    }
    
    post {
        failure {
            echo '❌ Pipeline failed!'
        }
        success {
            echo '✅ Pipeline succeeded!'
        }
    }
}
