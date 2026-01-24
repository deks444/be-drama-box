pipeline {
    agent any
    
    triggers {
        githubPush()
    }
    
    // Menggunakan tool Docker yang sudah diinstal di Jenkins
    tools {
        dockerTool 'docker-latest' 
    }

    environment {
        APP_NAME = 'drama-box-auth'
    }

    stages {
        stage('Checkout SCM') {
            steps {
                // Mengambil kode dari GitHub
                checkout scm
            }
        }

        stage('Prepare Environment') {
            steps {
                echo 'Injecting .env file...'
                script {
                    // Masukkan ID kredensial langsung di sini untuk menghindari error "MissingProperty"
                    // Ganti 'dramabox-auth-env' jika ID di Jenkins Anda berbeda
                    withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'SECRET_PATH')]) {
                        sh 'cp "$SECRET_PATH" .env'
                    }
                }
            }
        }

        stage('Cleanup & Build') {
            steps {
                script {
                    echo 'Stopping old containers and building new ones...'
                    // Gunakan docker compose (V2)
                    sh 'docker compose down --remove-orphans || true'
                    sh 'docker compose up --build -d'
                }
            }
        }

        stage('Git Push') {
            steps {
                script {
                    // Update file status build agar ada perubahan untuk di-push
                    sh 'git config user.email "jenkins@majujayamakmur.com"'
                    sh 'git config user.name "Jenkins CI"'
                    sh "echo 'Last successful deploy: \$(date)' > deploy_report.txt"
                    sh 'git add deploy_report.txt'
                    
                    // [skip ci] mencegah build berulang (infinite loop)
                    sh 'git commit -m "chore: update deploy report [skip ci]" || echo "No changes to commit"'
                    
                    // Push ke branch main
                    sh 'git push origin HEAD:main'
                }
            }
        }
    }

    post {
        always {
            // Hapus .env dari workspace demi keamanan
            sh 'rm -f .env'
            cleanWs()
        }
        success {
            echo "Aplikasi berjalan di background pada port 9004"
        }
    }
}
