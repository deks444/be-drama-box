pipeline {
    agent any

    triggers {
        githubPush()
    }

    environment {
        APP_NAME = 'drama-box-auth'
        // Mengambil file .env dari sistem kredensial Jenkins
        DOT_ENV_FILE = credentials('dramabox-auth-env')
    }

    stages {
        stage('Checkout') {
            steps {
                // Mengambil source code dari GitHub
                checkout scm
            }
        }

        stage('Prepare Environment') {
            steps {
                echo 'Injecting credentials...'
                // Menyalin file secret .env ke workspace
                sh "cp ${DOT_ENV_FILE} .env"
            }
        }

        stage('Cleanup Old Process') {
            steps {
                echo 'Stopping old containers and cleaning up disk...'
                script {
                    // Menghentikan kontainer lama agar port 9004 terlepas
                    sh 'docker-compose down --remove-orphans'
                    // Menghapus image sisa (dangling) agar storage tidak penuh
                    sh 'docker image prune -f'
                }
            }
        }

        stage('Build & Run') {
            steps {
                echo 'Building and starting application in background...'
                // -d (detached) menjalankan di background
                // --build memastikan perubahan kode terbaru ikut di-build
                sh 'docker-compose up --build -d'
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
