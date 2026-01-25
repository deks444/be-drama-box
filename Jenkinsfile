pipeline {
    agent any

    triggers {
        githubPush()
    }

    environment {
        APP_PORT = '9004'
        PHP_VERSION = '8.2' // Sesuaikan dengan versi PHP Anda
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Setup Environment') {
            steps {
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'ENV_FILE')]) {
                    script {
                        sh "cp ${ENV_FILE} .env"
                        sh "sed -i 's/APP_PORT=.*/APP_PORT=${APP_PORT}/' .env"
                    }
                }
            }
        }

        stage('Install Dependencies') {
            steps {
                sh 'composer install --no-interaction --prefer-dist --optimize-autoloader'
                sh 'npm install && npm run build'
            }
        }

        stage('Database Migration') {
            steps {
                sh 'php artisan migrate --force'
            }
        }

        stage('Deploy to Background (Port 9004)') {
            steps {
                script {
                    // Menggunakan JENKINS_NODE_COOKIE=dontKillMe agar proses tidak mati setelah job selesai
                    sh """
                        export JENKINS_NODE_COOKIE=dontKillMe
                        
                        # Menghentikan proses lama yang berjalan di port 9004 (jika ada)
                        fuser -k ${APP_PORT}/tcp || true
                        
                        # Menjalankan server Laravel di background
                        nohup php artisan serve --host=0.0.0.0 --port=${APP_PORT} > laravel_logs.log 2>&1 &
                    """
                }
            }
        }
    }

    post {
        success {
            echo "Aplikasi berhasil berjalan di port ${APP_PORT} dalam mode background."
        }
        failure {
            echo "Pipeline gagal. Silakan periksa log Jenkins."
        }
    }
}
