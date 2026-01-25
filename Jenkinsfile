pipeline {
    agent any

    stages {
        stage('Checkout SCM') {
            steps {
                // Mengambil kode dari GitHub
                checkout scm
            }
        }

        stage('Setup Environment') {
            steps {
                // Mengambil file .env dari Jenkins Credentials (tipe: Secret File)
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'SECRET_ENV')]) {
                    script {
                        // Salin file rahasia ke file .env di root project
                        sh 'cp ${SECRET_ENV} .env'
                    }
                }
            }
        }

        stage('Install Dependencies') {
            steps {
                // Menjalankan composer install
                // Gunakan --no-dev untuk produksi agar lebih ringan
                sh 'composer install --no-interaction --prefer-dist --optimize-autoloader'
            }
        }

        stage('Generate App Key') {
            steps {
                script {
                    // Hanya jalankan jika APP_KEY di .env masih kosong
                    sh 'php artisan key:generate --force'
                }
            }
        }

        stage('Database Migration') {
            steps {
                // Menjalankan migrasi database
                sh 'php artisan migrate --force'
            }
        }

        stage('Optimization') {
            steps {
                // Optimasi Laravel untuk performa lebih cepat
                sh 'php artisan config:cache'
                sh 'php artisan route:cache'
                sh 'php artisan view:cache'
            }
        }

        stage('Set Permissions') {
            steps {
                // Penting agar Laravel bisa menulis log dan cache
                sh 'chmod -R 775 storage bootstrap/cache'
                sh 'chown -R www-data:www-data storage bootstrap/cache'
            }
        }
    }

    post {
        success {
            echo "Deployment drama-box-auth (Laravel) berhasil!"
        }
        failure {
            echo "Build gagal. Periksa log Composer atau koneksi database."
        }
    }
}
