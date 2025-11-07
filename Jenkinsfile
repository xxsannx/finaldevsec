pipeline {
    agent any

    stages {
        stage('Checkout & Build') {
            steps {
                checkout scm
                sh 'npm install'
                sh 'npm run build'
            }
        }

        stage('Verify') {
            steps {
                sh '''
                    echo "Build completed!"
                    ls -la dist/
                '''
            }
        }
    }
}