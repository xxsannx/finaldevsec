pipeline {
    agent any
    
    tools {
        nodejs "NodeJS"  // Nama harus sama persis dengan yang di Jenkins
    }
    
    stages {
        stage('Checkout & Build') {
            steps {
                checkout scm
                sh 'node --version'
                sh 'npm --version'
                sh 'npm install'
                sh 'npm run build'
            }
        }
        
        stage('Verify') {
            steps {
                // Tambahkan step verifikasi jika diperlukan
                sh 'echo "Build completed successfully"'
            }
        }
    }
    
    post {
        always {
            echo 'Pipeline execution completed'
        }
        success {
            echo 'Pipeline succeeded!'
        }
        failure {
            echo 'Pipeline failed!'
        }
    }
}