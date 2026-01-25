pipeline {
    agent any

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Setup Environment') {
            steps {
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'SECRET_ENV')]) {
                    script {
                        // Menghapus entitas .env lama (baik file maupun folder)
                        sh 'rm -rf .env'
                        // Membuat file .env baru dari Secret File
                        sh 'cat ${SECRET_ENV} > .env'
                        sh 'chmod 644 .env'
                    }
                }
            }
        }

        stage('Install Dependencies') {
            steps {
                // Tambahkan path composer jika tidak terbaca
                sh 'composer install --no-interaction --optimize-autoloader --no-dev'
            }
        }

        stage('Laravel Prep') {
            steps {
                sh 'php artisan key:generate --force'
                sh 'php artisan storage:link'
                // Pastikan folder storage bisa ditulisi
                sh 'chmod -R 775 storage bootstrap/cache'
            }
        }
    }
}
