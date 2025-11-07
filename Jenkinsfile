pipeline {
    agent any
    
    tools {
        nodejs "nodejs" // Pastikan sudah dikonfigurasi di Jenkins
    }
    
    stages {
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
        
        stage('Deploy') {
            steps {
                echo 'Deploying application...'
                // Tambahkan deploy steps di sini
            }
        }
    }
    
    post {
        always {
            echo 'Pipeline completed!'
        }
        failure {
            echo '❌ Pipeline failed!'
        }
        success {
            echo '✅ Pipeline succeeded!'
        }
    }
}
