pipeline {
    agent any
    
    tools {
        nodejs "NodeJS"
    }
    
    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }
        
        stage('Setup') {
            steps {
                sh 'node --version'
                sh 'npm --version'
                sh 'echo "Current directory structure:"'
                sh 'ls -la'
            }
        }
        
        stage('Install Dependencies') {
            steps {
                sh 'npm install'
            }
        }
        
        stage('Build') {
            steps {
                sh 'npm run build'
            }
        }
        
        stage('Verify Build') {
            steps {
                sh '''
                    echo "=== Build Artifacts Verification ==="
                    echo "Checking dist directory:"
                    ls -la dist/ || echo "dist/ directory not found"
                    echo ""
                    echo "Checking build directory:"
                    ls -la build/ || echo "build/ directory not found"
                    echo ""
                    echo "Current directory contents:"
                    ls -la
                    echo "=== Verification Complete ==="
                '''
            }
        }
        
        stage('Archive Artifacts') {
            steps {
                script {
                    // Cek direktori build yang ada
                    def buildDir = ""
                    if (fileExists('dist')) {
                        buildDir = 'dist'
                    } else if (fileExists('build')) {
                        buildDir = 'build'
                    } else {
                        error "No build directory found! Check build process."
                    }
                    
                    echo "Using build directory: ${buildDir}"
                    
                    // Create compressed archive
                    sh """
                        tar -czf build-${BUILD_NUMBER}.tar.gz ${buildDir}/
                        echo "Archive created: build-${BUILD_NUMBER}.tar.gz"
                        ls -la build-*.tar.gz
                    """
                    
                    // Archive the build artifacts
                    archiveArtifacts artifacts: "build-${BUILD_NUMBER}.tar.gz", fingerprint: true
                    
                    // Also archive the build directory directly untuk akses mudah
                    archiveArtifacts artifacts: "${buildDir}/**/*", fingerprint: true
                }
            }
        }
        
        stage('Deploy - Manual Step') {
            steps {
                sh '''
                    echo "=========================================="
                    echo "🚀 BUILD READY FOR DEPLOYMENT!"
                    echo "=========================================="
                    echo "Build Number: ${BUILD_NUMBER}"
                    echo "Archive: build-${BUILD_NUMBER}.tar.gz"
                    echo ""
                    echo "📦 Download artifacts from:"
                    echo "   Jenkins → Build → Artifacts"
                    echo ""
                    echo "🔧 Manual deployment steps:"
                    echo "   1. Download build-${BUILD_NUMBER}.tar.gz"
                    echo "   2. Extract: tar -xzf build-${BUILD_NUMBER}.tar.gz"
                    echo "   3. Deploy files to your server"
                    echo "=========================================="
                '''
                
                // Optional: Tambahkan input manual untuk deploy
                input message: 'Deploy to production?', ok: 'Deploy'
            }
        }
    }
    
    post {
        always {
            echo "Pipeline execution completed for build #${BUILD_NUMBER}"
            
            // Cleanup temporary files
            sh '''
                echo "Cleaning up temporary files..."
                rm -f build-*.tar.gz
            '''
        }
        success {
            echo "✅ Build #${BUILD_NUMBER} succeeded! Artifacts are archived and ready for deployment."
            
            // Optional: Add notification
            emailext (
                subject: "SUCCESS: Build #${BUILD_NUMBER} - Ready for Deployment",
                body: """
                Build #${BUILD_NUMBER} completed successfully!
                
                Artifacts available at:
                ${BUILD_URL}artifact/
                
                Download: build-${BUILD_NUMBER}.tar.gz
                
                Ready for manual deployment.
                """,
                to: "your-email@company.com"
            )
        }
        failure {
            echo "❌ Build #${BUILD_NUMBER} failed! Check logs for details."
            
            // Optional: Add failure notification
            emailext (
                subject: "FAILED: Build #${BUILD_NUMBER}",
                body: """
                Build #${BUILD_NUMBER} failed!
                
                Please check: ${BUILD_URL}
                """,
                to: "your-email@company.com"
            )
        }
    }
}