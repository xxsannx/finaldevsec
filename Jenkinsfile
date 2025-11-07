pipeline {
    agent any
    
    tools {
        nodejs "NodeJS"
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
                sh 'echo "Build completed successfully"'
            }
        }
        
        stage('Archive for Deployment') {
            steps {
                // Archive build directory
                archiveArtifacts artifacts: 'dist/**/*', fingerprint: true
                
                // Create and archive tar.gz
                sh 'tar -czf build-artifacts.tar.gz dist/'
                archiveArtifacts artifacts: 'build-artifacts.tar.gz', fingerprint: true
            }
        }
        
        stage('Deploy Ready') {
            steps {
                sh '''
                    echo "========================================"
                    echo "📦 Artifacts Archived!"
                    echo "Download from Jenkins Artifacts section"
                    echo "Build: build-artifacts.tar.gz"
                    echo "Or use direct dist/ files"
                    echo "========================================"
                '''
            }
        }
    }
    
    post {
        always {
            echo 'Pipeline execution completed'
        }
        success {
            echo '✅ Pipeline succeeded! Artifacts ready for deployment.'
        }
        failure {
            echo '❌ Pipeline failed!'
        }
    }
}