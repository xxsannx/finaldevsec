pipeline {
    agent any

    environment {
        APP_ENV = 'production'
        COMPOSER_ALLOW_SUPERUSER = 1
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Install PHP Dependencies') {
            steps {
                sh 'composer install --no-dev --optimize-autoloader --prefer-dist'
            }
        }

        stage('Install Node Dependencies') {
            when {
                expression { fileExists('package.json') }
            }
            steps {
                sh 'npm ci --silent'
                sh 'npm run production'
            }
        }

        stage('Run Tests') {
            parallel {
                stage('PHPUnit Tests') {
                    steps {
                        sh 'php artisan test --stop-on-failure'
                    }
                }
                stage('PHP Code Sniffer') {
                    when {
                        expression { fileExists('phpcs.xml') }
                    }
                    steps {
                        sh './vendor/bin/phpcs'
                    }
                }
            }
            post {
                always {
                    junit 'tests/report.xml'
                }
            }
        }

        stage('Security Check') {
            steps {
                sh 'composer audit'
                // Tambahkan security scanning tools lain
            }
        }

        stage('Deploy to Staging') {
            when {
                branch 'develop'
            }
            steps {
                // Deployment steps untuk staging
                echo 'Deploying to staging server...'
            }
        }

        stage('Deploy to Production') {
            when {
                branch 'main'
            }
            steps {
                input message: 'Deploy to production?', ok: 'Deploy'
                echo 'Deploying to production server...'
                // Production deployment steps
            }
        }
    }

    post {
        always {
            emailext (
                subject: "Pipeline ${currentBuild.result}: Job ${env.JOB_NAME}",
                body: "Check console output at: ${env.BUILD_URL}",
                to: "team@example.com"
            )
        }
    }
}