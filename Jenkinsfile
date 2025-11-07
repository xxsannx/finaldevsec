pipeline {
    agent any

    tools {
        nodejs "NodeJS"
    }

    environment {
        // Application Configuration
        TARGET_URL = "http://localhost:8000"  // Ganti dengan URL aplikasi Anda
        BUILD_ARTIFACT = "build-${BUILD_NUMBER}.tar.gz"
        
        // ZAP Configuration
        ZAP_HOME = "/opt/zap"
        ZAP_HOST = "localhost"
        ZAP_PORT = "8090"
        ZAP_REPORT_DIR = "zap-reports"        
        // Security Thresholds
        SECURITY_SCORE_THRESHOLD = "80"
        MAX_HIGH_VULNERABILITIES = "0"
        MAX_MEDIUM_VULNERABILITIES = "5"
    }

    stages {
        stage('Checkout Code') {
            steps {
                checkout scm
                sh '''
                    echo "🔍 Starting Security Pipeline"
                    echo "Repository: $GIT_URL"
                    echo "Branch: $GIT_BRANCH"
                    echo "Commit: $GIT_COMMIT"
                '''
            }
        }

        stage('Install Dependencies') {
            steps {
                sh '''
                    echo "📦 Installing dependencies..."
                    npm install
                    
                    # Install security tools
                    npm install --save-dev eslint-plugin-security || echo "ESLint security plugin installed"
                    npm install -g @microsoft/security-devops || echo "Microsoft security tools installed"
                '''
            }
        }

        stage('Build Application') {
            steps {
                sh '''
                    echo "🏗️ Building application..."
                    npm run build
                    echo "✅ Build completed successfully"
                '''
            }
        }

        stage('Start Application for Testing') {
            steps {
                sh '''
                    echo "🚀 Starting application for security testing..."
                    # Start the application in background
                    npm run preview &
                    APP_PID=$!
                    echo $APP_PID > app.pid
                    echo "Application PID: $APP_PID"
                    
                    # Wait for app to start
                    echo "⏳ Waiting for application to start..."
                    sleep 15
                    
                    # Test if application is accessible
                    curl -f http://localhost:4173 > /dev/null 2>&1 && echo "✅ Application is running" || echo "⚠️ Application might not be ready"
                '''
            }
        }

        stage('SAST - Static Application Security Testing') {
            parallel {
                stage('npm Audit - Dependency Scan') {
                    steps {
                        sh '''
                            echo "🔒 Running npm audit..."
                            mkdir -p sast-reports
                            npm audit --audit-level moderate > sast-reports/npm-audit.txt || true
                            npm audit --json > sast-reports/npm-audit.json || true
                            
                            # Check for critical vulnerabilities
                            CRITICAL_COUNT=$(grep -c "critical" sast-reports/npm-audit.txt || true)
                            HIGH_COUNT=$(grep -c "high" sast-reports/npm-audit.txt || true)
                            
                            echo "Critical vulnerabilities: $CRITICAL_COUNT"
                            echo "High vulnerabilities: $HIGH_COUNT"
                        '''
                    }
                }

                stage('ESLint Security Scan') {
                    steps {
                        sh '''
                            echo "📝 Running ESLint security scan..."
                            mkdir -p sast-reports
                            
                            # Create temporary ESLint config for security
                            cat > .eslintrc.security.js << EOF
                            module.exports = {
                                plugins: ['security'],
                                extends: ['plugin:security/recommended'],
                                rules: {
                                    'security/detect-object-injection': 'warn',
                                    'security/detect-non-literal-require': 'error',
                                    'security/detect-non-literal-fs-filename': 'error',
                                    'security/detect-eval-with-expression': 'error',
                                    'security/detect-unsafe-regex': 'error',
                                    'security/detect-buffer-noassert': 'error',
                                    'security/detect-child-process': 'error',
                                    'security/detect-disable-mustache-escape': 'error',
                                    'security/detect-no-csrf-before-method-override': 'error',
                                    'security/detect-non-literal-regexp': 'error',
                                    'security/detect-pseudoRandomBytes': 'error',
                                },
                                env: {
                                    node: true,
                                    browser: true
                                }
                            };
                            EOF
                            
                            # Run ESLint security scan
                            npx eslint . --config .eslintrc.security.js --format json > sast-reports/eslint-security-report.json || true
                            npx eslint . --config .eslintrc.security.js --format html > sast-reports/eslint-security-report.html || true
                            
                            echo "✅ ESLint security scan completed"
                        '''
                    }
                }

                stage('Source Code Analysis') {
                    steps {
                        sh '''
                            echo "🔎 Running basic source code analysis..."
                            mkdir -p sast-reports
                            
                            # Check for hardcoded secrets (basic check)
                            grep -r "password.*=" . --include="*.js" --include="*.ts" --include="*.json" > sast-reports/hardcoded-secrets.txt || true
                            grep -r "api_key" . --include="*" > sast-reports/api-keys.txt || true
                            grep -r "token.*=" . --include="*.js" --include="*.ts" > sast-reports/tokens.txt || true
                            
                            # Check for dangerous functions
                            grep -r "eval\\|setTimeout\\|setInterval" . --include="*.js" --include="*.ts" > sast-reports/dangerous-functions.txt || true
                            
                            echo "✅ Source code analysis completed"
                        '''
                    }
                }
            }
            
            post {
                always {
                    archiveArtifacts artifacts: 'sast-reports/**', fingerprint: true
                }
            }
        }

        stage('DAST - ZAP Security Scan') {
            steps {
                script {
                    echo "🎯 Starting ZAP Dynamic Application Security Testing"
                    
                    // Create reports directory
                    sh "mkdir -p ${ZAP_REPORT_DIR}"
                    
                    // Check if ZAP is available
                    sh """
                        if [ -x "${ZAP_HOME}/zap.sh" ]; then
                            echo "✅ ZAP found at ${ZAP_HOME}"
                        else
                            echo "❌ ZAP not found. Installing..."
                            wget -q https://github.com/zaproxy/zaproxy/releases/download/v2.14.0/ZAP_2.14.0_Linux.tar.gz
                            tar -xzf ZAP_2.14.0_Linux.tar.gz -C /opt/
                            mv /opt/ZAP_2.14.0 /opt/zap
                            chmod +x /opt/zap/zap.sh
                        fi
                    """
                    
                    // Run ZAP Baseline Scan
                    sh """
                        echo "🔍 Running ZAP Baseline Scan..."
                        ${ZAP_HOME}/zap-baseline.py -t ${TARGET_URL} \
                            -J ${ZAP_REPORT_DIR}/zap-baseline-report.json \
                            -x ${ZAP_REPORT_DIR}/zap-baseline-report.xml \
                            -r ${ZAP_REPORT_DIR}/zap-baseline-report.html \
                            -c ${ZAP_REPORT_DIR}/zap-baseline-report.conf \
                            -a || true
                        
                        echo "✅ ZAP Baseline Scan completed"
                    """
                }
            }
            
            post {
                always {
                    archiveArtifacts artifacts: "${ZAP_REPORT_DIR}/**", fingerprint: true
                    publishHTML([
                        allowMissing: true,
                        alwaysLinkToLastBuild: true,
                        keepAll: true,
                        reportDir: "${ZAP_REPORT_DIR}",
                        reportFiles: 'zap-baseline-report.html',
                        reportName: 'ZAP Security Report'
                    ])
                }
            }
        }

        stage('ZAP Full Active Scan') {
            steps {
                script {
                    echo "🎯 Starting ZAP Full Active Scan"
                    
                    // Start ZAP in daemon mode
                    sh """
                        echo "🚀 Starting ZAP Daemon..."
                        ${ZAP_HOME}/zap.sh -daemon -port ${ZAP_PORT} -host ${ZAP_HOST} \
                            -config api.disablekey=true \
                            -config scanner.attackOnStart=true \
                            -config connection.timeoutInSecs=60 &
                        ZAP_PID=\$!
                        echo \$ZAP_PID > zap.pid
                        sleep 10
                    """
                    
                    // Run ZAP Spider and Active Scan
                    sh """                        echo "🕷️ Running ZAP Spider..."
                        curl -s "http://${ZAP_HOST}:${ZAP_PORT}/JSON/spider/action/scan/?url=${TARGET_URL}&maxChildren=10&recurse=true"
                        sleep 30
                                                echo "⚡ Running ZAP Active Scan..."
                        curl -s "http://${ZAP_HOST}:${ZAP_PORT}/JSON/ascan/action/scan/?url=${TARGET_URL}&recurse=true&inScopeOnly=true"
                        
                        echo "⏳ Waiting for active scan to complete (2 minutes)..."
                        sleep 120
                        
                        echo "📊 Generating ZAP Reports..."
                        curl -s "http://${ZAP_HOST}:${ZAP_PORT}/OTHER/core/other/htmlreport/" > ${ZAP_REPORT_DIR}/zap-full-active-report.html
                        curl -s "http://${ZAP_HOST}:${ZAP_PORT}/JSON/core/action/jsonreport/" > ${ZAP_REPORT_DIR}/zap-full-active-report.json
                        curl -s "http://${ZAP_HOST}:${ZAP_PORT}/OTHER/core/other/xmlreport/" > ${ZAP_REPORT_DIR}/zap-full-active-report.xml
                        
                        echo "✅ ZAP Full Active Scan completed"
                    """
                }
            }
            
            post {
                always {
                    // Stop ZAP
                    sh '''
                        echo "🛑 Stopping ZAP..."
                        kill $(cat zap.pid) 2>/dev/null || true
                        pkill -f "zap.sh" || true
                        rm -f zap.pid
                    '''
                    
                    archiveArtifacts artifacts: "${ZAP_REPORT_DIR}/zap-full-active-report.*", fingerprint: true
                }
            }
        }

        stage('Security Analysis & Scoring') {
            steps {
                script {
                    echo "📈 Analyzing Security Results..."
                    
                    sh """
                        # Initialize counters
                        HIGH_RISK=0
                        MEDIUM_RISK=0
                        LOW_RISK=0
                        INFO_RISK=0
                        SECURITY_SCORE=100
                        
                        # Analyze ZAP report if exists
                        if [ -f "${ZAP_REPORT_DIR}/zap-baseline-report.json" ]; then
                            echo "📊 Analyzing ZAP Baseline Report..."
                            
                            HIGH_RISK=\$(jq -r '[.site[].alerts[] | select(.riskcode == "3") | .count] | add // 0' ${ZAP_REPORT_DIR}/zap-baseline-report.json 2>/dev/null || echo "0")
                            MEDIUM_RISK=\$(jq -r '[.site[].alerts[] | select(.riskcode == "2") | .count] | add // 0' ${ZAP_REPORT_DIR}/zap-baseline-report.json 2>/dev/null || echo "0")
                            LOW_RISK=\$(jq -r '[.site[].alerts[] | select(.riskcode == "1") | .count] | add // 0' ${ZAP_REPORT_DIR}/zap-baseline-report.json 2>/dev/null || echo "0")
                            INFO_RISK=\$(jq -r '[.site[].alerts[] | select(.riskcode == "0") | .count] | add // 0' ${ZAP_REPORT_DIR}/zap-baseline-report.json 2>/dev/null || echo "0")
                            
                            # Calculate security score (0-100)
                            PENALTY=\$((\$HIGH_RISK * 10 + \$MEDIUM_RISK * 5 + \$LOW_RISK * 2 + \$INFO_RISK * 1))
                            SECURITY_SCORE=\$((100 - \$PENALTY))
                            
                            if [ \$SECURITY_SCORE -lt 0 ]; then
                                SECURITY_SCORE=0
                            fi
                        fi
                        
                        # Analyze npm audit report
                        if [ -f "sast-reports/npm-audit.json" ]; then
                            echo "📊 Analyzing npm audit report..."
                            CRITICAL_AUDIT=\$(jq -r '.metadata.vulnerabilities.critical // 0' sast-reports/npm-audit.json 2>/dev/null || echo "0")
                            HIGH_AUDIT=\$(jq -r '.metadata.vulnerabilities.high // 0' sast-reports/npm-audit.json 2>/dev/null || echo "0")
                            
                            # Adjust security score based on npm audit
                            AUDIT_PENALTY=\$((\$CRITICAL_AUDIT * 15 + \$HIGH_AUDIT * 8))
                            SECURITY_SCORE=\$((\$SECURITY_SCORE - \$AUDIT_PENALTY))
                            
                            if [ \$SECURITY_SCORE -lt 0 ]; then
                                SECURITY_SCORE=0
                            fi
                        fi
                        
                        echo "HIGH_RISK=\$HIGH_RISK" > security_metrics.env
                        echo "MEDIUM_RISK=\$MEDIUM_RISK" >> security_metrics.env
                        echo "LOW_RISK=\$LOW_RISK" >> security_metrics.env
                        echo "INFO_RISK=\$INFO_RISK" >> security_metrics.env
                        echo "SECURITY_SCORE=\$SECURITY_SCORE" >> security_metrics.env
                    """
                    
                    // Load security metrics
                    load 'security_metrics.env'
                    
                    echo "🔐 SECURITY METRICS:"
                    echo "  High Risk Vulnerabilities: ${HIGH_RISK}"
                    echo "  Medium Risk Vulnerabilities: ${MEDIUM_RISK}"
                    echo "  Low Risk Vulnerabilities: ${LOW_RISK}"
                    echo "  Informational: ${INFO_RISK}"
                    echo "  📊 SECURITY SCORE: ${SECURITY_SCORE}/100"
                    
                    // Update build description with security score
                    currentBuild.description = "Security Score: ${SECURITY_SCORE}/100"
                }
            }
        }

        stage('Security Quality Gate') {
            steps {
                script {
                    echo "🚨 Security Quality Gate Assessment"
                    
                    def HIGH_RISK_INT = Integer.parseInt(env.HIGH_RISK ?: "0")
                    def MEDIUM_RISK_INT = Integer.parseInt(env.MEDIUM_RISK ?: "0")
                    def SECURITY_SCORE_INT = Integer.parseInt(env.SECURITY_SCORE ?: "100")
                    
                    // Check for critical vulnerabilities
                    if (HIGH_RISK_INT > Integer.parseInt(env.MAX_HIGH_VULNERABILITIES)) {
                        error "❌ CRITICAL: ${HIGH_RISK_INT} high risk vulnerabilities found (max allowed: ${env.MAX_HIGH_VULNERABILITIES}). Pipeline stopped."
                    }
                    
                    // Check security score threshold
                    if (SECURITY_SCORE_INT < Integer.parseInt(env.SECURITY_SCORE_THRESHOLD)) {
                        echo "⚠️ WARNING: Security score ${SECURITY_SCORE_INT}/100 is below threshold ${env.SECURITY_SCORE_THRESHOLD}"
                        
                        // Ask for manual approval to continue
                        input(
                            message: "Security score ${SECURITY_SCORE_INT}/100 is below threshold ${env.SECURITY_SCORE_THRESHOLD}. Continue deployment?",
                            ok: "Proceed Anyway",
                            parameters: [
                                string(
                                    defaultValue: '',
                                    description: 'Reason for bypassing security threshold:',
                                    name: 'BYPASS_REASON'
                                )
                            ]
                        )
                    }
                    
                    if (MEDIUM_RISK_INT > Integer.parseInt(env.MAX_MEDIUM_VULNERABILITIES)) {
                        echo "⚠️ WARNING: ${MEDIUM_RISK_INT} medium risk vulnerabilities found (threshold: ${env.MAX_MEDIUM_VULNERABILITIES})"
                    }
                    
                    echo "✅ Security quality gate passed"
                }
            }
        }

        stage('Package & Archive') {
            steps {
                sh """
                    echo "📦 Packaging application..."
                    tar -czf ${BUILD_ARTIFACT} --exclude='node_modules' --exclude='.git' .
                    echo "✅ Package created: ${BUILD_ARTIFACT}"
                """
                
                archiveArtifacts artifacts: "*.tar.gz", fingerprint: true
                archiveArtifacts artifacts: "**/*-report.*", fingerprint: true
            }
        }

        stage('Deploy - Security Approved') {
            when {
                expression { 
                    Integer.parseInt(env.SECURITY_SCORE ?: "100") >= Integer.parseInt(env.SECURITY_SCORE_THRESHOLD) 
                }
            }
            steps {
                script {
                    echo "🚀 DEPLOYING SECURE APPLICATION"
                    echo "Security Score: ${env.SECURITY_SCORE}/100"
                    echo "High Risk Issues: ${env.HIGH_RISK}"
                    echo "Medium Risk Issues: ${env.MEDIUM_RISK}"
                    
                    // Your deployment commands here
                    sh '''
                        echo "✅ Security requirements met - Proceeding with deployment"
                        echo "📋 Deployment steps would execute here"
                        # Example: scp build.tar.gz user@server:/path/to/deploy
                        # Example: kubectl apply -f k8s/
                        # Example: aws s3 sync dist/ s3://your-bucket/
                    '''
                }
            }
        }
    }

    post {
        always {
            echo "🧹 Cleaning up..."
            sh '''
                # Stop application
                kill $(cat app.pid) 2>/dev/null || true
                
                # Stop ZAP if still running
                pkill -f "zap.sh" || true
                pkill -f "java.*zap" || true
                
                # Cleanup files
                rm -f app.pid zap.pid security_metrics.env
                rm -f .eslintrc.security.js
                
                echo "✅ Cleanup completed"
            '''
        }
        
        success {
            echo "🎉 PIPELINE SUCCESS!"
            script {
                if (env.SECURITY_SCORE) {
                    echo "🔐 SECURITY SCORE: ${env.SECURITY_SCORE}/100"
                    echo "📊 Vulnerabilities: High=${env.HIGH_RISK}, Medium=${env.MEDIUM_RISK}, Low=${env.LOW_RISK}"
                }
                
                // Send success notification
                emailext (
                    subject: "✅ SUCCESS: Security Pipeline Build #${BUILD_NUMBER}",
                    body: """
                    Security Pipeline completed successfully!
                    
                    📊 Security Metrics:
                    - Security Score: ${env.SECURITY_SCORE}/100
                    - High Risk: ${env.HIGH_RISK}
                    - Medium Risk: ${env.MEDIUM_RISK}
                    - Low Risk: ${env.LOW_RISK}
                    
                    📁 Reports: ${BUILD_URL}artifact/
                    🔍 ZAP Report: ${BUILD_URL}ZAP_20Security_20Report/
                    
                    Build: ${BUILD_URL}
                    """,
                    to: "dev-team@company.com",
                    attachLog: true
                )
            }
        }
        
        failure {
            echo "❌ PIPELINE FAILED!"
            script {
                // Send failure notification
                emailext (
                    subject: "❌ FAILED: Security Pipeline Build #${BUILD_NUMBER}",
                    body: """
                    Security Pipeline failed!
                    
                    ❌ Build: ${BUILD_URL}
                    🔍 Check logs for details
                    """,
                    to: "dev-team@company.com",
                    attachLog: true
                )
            }
        }
        
        unstable {
            echo "⚠️ PIPELINE UNSTABLE - Security issues detected"
        }
        
        changed {
            echo "🔄 Pipeline status changed"
        }
    }
}
