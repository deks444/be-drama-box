pipeline {
    agent any

    environment {
        // Mengambil kredensial 'Secret File' dari Jenkins
        DOT_ENV_FILE = credentials('dramabox-auth-env')
    }

    stages {
        stage('Cleanup & Checkout') {
            steps {
                // Menghapus folder .env jika ada (penyebab error sebelumnya)
                sh "rm -rf .env"
                checkout scm
            }
        }

        stage('Setup Environment') {
            steps {
                script {
                    // Menyalin file rahasia ke file .env di root project
                    // Pastikan menggunakan flag -f (force)
                    sh "cp -f ${DOT_ENV_FILE} .env"
                    
                    // Verifikasi tipe file (untuk memastikan bukan direktori)
                    sh "ls -ld .env"
                }
            }
        }

        stage('Install Dependencies') {
            steps {
                sh "composer install --no-interaction --prefer-dist --optimize-autoloader"
                // Generate key jika .env baru tidak memilikinya
                sh "php artisan key:generate --force"
            }
        }

        stage('Quality & Security Scan') {
            steps {
                echo 'Checking vulnerabilities...'
                sh "composer audit"
                echo 'Running Pint (Linting)...'
                sh "./vendor/bin/pint --test"
            }
        }

        stage('Test') {
            steps {
                echo 'Running Drama Box Auth Tests...'
                // Pastikan DB testing sudah sesuai di dramabox-auth-env
                sh "php artisan test"
            }
        }

        stage('Build Assets') {
            steps {
                sh "npm install && npm run build"
            }
        }
    }

    post {
        success {
            echo "✅ Pipeline drama-box-auth Berhasil!"
        }
        failure {
            echo "❌ Pipeline Gagal! Cek kembali isi dramabox-auth-env atau log testing."
        }
    }
}
