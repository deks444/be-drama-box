pipeline {
    agent any

    triggers {
        githubPush()
    }

    tools {
        dockerTool 'docker-latest' 
    }

    environment {
        APP_NAME = 'drama-box-auth'
    }

    stages {
        stage('Checkout') {
            steps {
                // Mengambil source code dari GitHub
                checkout scm
            }
        }

         stage('Setup Environment') {
            steps {
                // Mengambil file .env dari Jenkins Credentials (ID: dramabox-auth-env)
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'SECRET_FILE')]) {
                    script {
                        echo 'Konfigurasi file .env...'
                        // Hapus file lama jika ada untuk menghindari 'Permission Denied'
                        sh 'rm -f .env || true'
                        sh 'cp "${SECRET_FILE}" .env'
                        sh 'chmod 644 .env'
                    }
                }
            }
        }

        stage('Cleanup') {
            steps {
                echo 'Cleaning up with Docker Compose V2...'
                // Kita coba 'docker compose' (V2), jika gagal baru lari ke 'docker-compose' (V1)
                sh 'docker compose down --remove-orphans || docker-compose down --remove-orphans'
                sh 'docker image prune -f'
            }
        }

        stage('Build & Run') {
            steps {
                echo 'Deploying to port 9004...'
                sh 'docker compose up --build -d || docker-compose up --build -d'
            }
        }

        stage('Health Check') {
            steps {
                echo 'Verifying application status on port 9004...'
                sleep 5
                sh 'docker ps | grep drama-box-auth'
                // Opsional: Cek apakah port merespon
                sh 'curl -f http://localhost:9004 || echo "App is starting up..."'
            }
        }
    }

    post {
        always {
            echo 'Cleaning up workspace...'
            sh 'rm -f .env' // Hapus file sensitif dari workspace Jenkins
        }
        success {
            echo "DEPLOYMENT SUCCESS: Aplikasi berjalan di http://localhost:9004"
        }
        failure {
            echo "DEPLOYMENT FAILED: Periksa docker logs drama-box-auth"
        }
    }
}
