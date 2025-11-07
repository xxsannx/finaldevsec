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
        
        stage('Debug Info') {
            steps {
                sh '''
                    echo "=== DEBUG INFORMATION ==="
                    echo "Node version: $(node --version)"
                    echo "NPM version: $(npm --version)"
                    echo "Current directory: $(pwd)"
                    echo "Directory contents:"
                    ls -la
                    echo "Package.json content:"
                    cat package.json || echo "No package.json found"
                    echo "=== END DEBUG ==="
                '''
            }
        }
        
        stage('Install Dependencies') {
            steps {
                sh 'npm install'
                sh 'npm list --depth=0'  // Show installed dependencies
            }
        }
        
        stage('Build') {
            steps {
                sh '''
                    echo "Starting build process..."
                    npm run build
                    echo "Build command completed"
                '''
            }
        }
        
        stage('Verify Build Output') {
            steps {
                sh '''
                    echo "=== BUILD OUTPUT VERIFICATION ==="
                    echo "Current directory after build:"
                    pwd
                    echo ""
                    echo "Full directory structure:"
                    ls -la
                    echo ""
                    echo "Checking for common build directories:"
                    echo "- dist/: $(ls -la dist/ 2>/dev/null && echo "EXISTS" || echo "NOT FOUND")"
                    echo "- build/: $(ls -la build/ 2>/dev/null && echo "EXISTS" || echo "NOT FOUND")"
                    echo "- public/: $(ls -la public/ 2>/dev/null && echo "EXISTS" || echo "NOT FOUND")"
                    echo "- out/: $(ls -la out/ 2>/dev/null && echo "EXISTS" || echo "NOT FOUND")"
                    echo "- .next/: $(ls -la .next/ 2>/dev/null && echo "EXISTS" || echo "NOT FOUND")"
                    echo ""
                    echo "Files created/modified recently:"
                    find . -type f -mmin -5 -not -path "./node_modules/*" 2>/dev/null | head -20 || echo "No recent files found"
                    echo "=== END VERIFICATION ==="
                '''
            }
        }
        
        stage('Archive Artifacts') {
            when {
                expression { 
                    // Hanya archive jika ada build directory
                    return fileExists('dist') || fileExists('build') || fileExists('out') || fileExists('.next')
                }
            }
            steps {
                script {
                    // Cari direktori build yang tersedia
                    def buildDirs = ['dist', 'build', 'out', '.next', 'public']
                    def foundDir = null
                    
                    for (dir in buildDirs) {
                        if (fileExists(dir)) {
                            foundDir = dir
                            break
                        }
                    }
                    
                    if (foundDir) {
                        echo "Found build directory: ${foundDir}"
                        
                        // Create compressed archive
                        sh """
                            tar -czf build-${BUILD_NUMBER}.tar.gz ${foundDir}/
                            echo "Archive created: build-${BUILD_NUMBER}.tar.gz"
                            ls -la build-*.tar.gz
                        """
                        
                        // Archive the build artifacts
                        archiveArtifacts artifacts: "build-${BUILD_NUMBER}.tar.gz", fingerprint: true
                        archiveArtifacts artifacts: "${foundDir}/**/*", fingerprint: true
                    } else {
                        echo "WARNING: No standard build directory found. Archiving all non-node_modules files..."
                        
                        // Archive semua file kecuali node_modules
                        sh """
                            tar -czf build-${BUILD_NUMBER}.tar.gz --exclude='node_modules' --exclude='.git' .
                            echo "Full project archive created: build-${BUILD_NUMBER}.tar.gz"
                        """
                        archiveArtifacts artifacts: "build-${BUILD_NUMBER}.tar.gz", fingerprint: true
                    }
                }
            }
        }
        
        stage('Deploy - Manual Step') {
            when {
                expression { 
                    fileExists('dist') || fileExists('build') || fileExists('out') || fileExists('.next')
                }
            }
            steps {
                sh '''
                    echo "=========================================="
                    echo "🚀 BUILD READY FOR DEPLOYMENT!"
                    echo "=========================================="
                    echo "Build Number: ${BUILD_NUMBER}"
                    echo "Archive: build-${BUILD_NUMBER}.tar.gz"
                    echo ""
                    echo "📦 Download artifacts from Jenkins Artifacts"
                    echo "🔧 Ready for manual deployment"
                    echo "=========================================="
                '''
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
            echo "✅ Build #${BUILD_NUMBER} succeeded!"
        }
        failure {
            echo "❌ Build #${BUILD_NUMBER} failed!"
            sh '''
                echo "=== TROUBLESHOOTING TIPS ==="
                echo "1. Check if npm run build produces any output"
                echo "2. Verify build script in package.json"
                echo "3. Check if dependencies are installed correctly"
                echo "4. Look for build errors in the logs above"
                echo "============================"
            '''
        }
    }
}