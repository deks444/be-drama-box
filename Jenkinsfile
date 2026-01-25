pipeline {
    agent {
        docker {
            // Menggunakan image yang sudah lengkap (PHP + Composer)
            image 'serversideup/php:8.2-cli'
            // Menjalankan sebagai root untuk menghindari masalah permission pada workspace
            args '-u root'
        }
    }

    stages {
        stage('Preparation') {
            steps {
                // Bersihkan workspace dari sisa build gagal
                cleanWs()
                checkout scm
            }
        }

        stage('Inject Environment') {
            steps {
                // Pastikan ID 'dramabox-auth-env' sudah ada di Jenkins Credentials (Secret File)
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'SECRET_ENV')]) {
                    script {
                        // Salin env secara aman ke workspace
                        sh 'cp -f "$SECRET_ENV" .env'
                        sh 'chmod 644 .env'
                    }
                }
            }
        }

        stage('Install Dependencies') {
            steps {
                sh '''
                    # Karena menggunakan image serversideup, composer sudah tersedia
                    composer install --no-interaction --prefer-dist --optimize-autoloader
                    
                    # Generate key jika belum ada
                    php artisan key:generate --force
                '''
            }
        }

        stage('Security Scan') {
            steps {
                // Melakukan audit keamanan pada package composer
                sh 'composer audit'
            }
        }

        stage('Testing') {
            steps {
                echo 'Running Drama Box Auth Tests...'
                // Menjalankan unit test Laravel di dalam container
                sh 'php artisan test'
            }
        }
    }

    post {
        always {
            // Hapus file sensitif sebelum container dimatikan
            sh 'rm -f .env'
        }
        success {
            echo "✅ Drama Box Auth: Build & Test Sukses!"
        }
        failure {
            echo "❌ Drama Box Auth: Build Gagal. Cek log di atas."
        }
    }
}
