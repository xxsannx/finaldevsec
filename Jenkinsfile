pipeline {
    agent any

    environment {
        COMPOSER_ALLOW_SUPERUSER = 1
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
                sh 'npm install'
                sh 'npm run production'
            }
        }

        stage('Run Tests') {
            steps {
                echo 'Running Laravel tests...'
                sh 'php artisan test'
                sh './vendor/bin/phpunit'
            }
            post {
                always {
                    junit 'storage/logs/junit.xml'
                }
            }
        }

        stage('Setup Environment') {
            steps {
                echo 'Setting up environment...'
                sh 'cp .env.example .env || true'
                sh 'php artisan key:generate'
            }
        }

        stage('Deploy') {
            steps {
                echo 'Deploying Laravel application...'
                // Ganti dengan deployment strategy Anda
                sh '''
                    mkdir -p /var/www/laravel-app
                    cp -r * /var/www/laravel-app/
                    chmod -R 755 /var/www/laravel-app/storage
                    chmod -R 755 /var/www/laravel-app/bootstrap/cache
                    echo "Application deployed successfully!"
                '''
            }
        }
    }

    post {
        success {
            echo 'Pipeline completed successfully!'
            // Tambahkan notifikasi di sini (email, slack, dll)
        }
        failure {
            echo 'Pipeline failed!'
        }
    }
}