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
                // Menggunakan withCredentials untuk menghindari 'Insecure Interpolation'
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'SECRET_ENV')]) {
                    script {
                        // 1. Hapus jika ada folder/file .env lama untuk menghindari konflik 'Not a directory'
                        sh 'rm -rf .env'
                        
                        // 2. Salin file rahasia ke file .env (Gunakan single quote untuk keamanan)
                        sh 'cp ${SECRET_ENV} .env'
                    }
                }
            }
        }

        stage('Install Dependencies') {
            steps {
                // Install PHP dependencies via Composer
                sh 'composer install --no-interaction --optimize-autoloader --no-dev'
            }
        }

        stage('Laravel Optimization') {
            steps {
                script {
                    sh 'php artisan key:generate --force'
                    sh 'php artisan config:cache'
                    sh 'php artisan route:cache'
                    // Jalankan migrasi jika database sudah siap
                    // sh 'php artisan migrate --force'
                }
            }
        }

        stage('Permissions') {
            steps {
                // Memberikan izin akses ke folder storage Laravel
                sh 'chmod -R 775 storage bootstrap/cache'
            }
        }
    }
}
